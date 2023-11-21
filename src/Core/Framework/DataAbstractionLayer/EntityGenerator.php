<?php

declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer;

use DateTimeInterface;
use RuntimeException;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\CartPrice;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\EntityDefinitionQueryHelper;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BlobField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\BoolField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CalculatedPriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CartPriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ChildrenAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CreatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\CustomFields;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\DateTimeField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FkField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\FloatField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IntField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\LongTextField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ManyToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToManyAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\OneToOneAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ParentAssociationField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\PasswordField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\PriceDefinitionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\PriceField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\ReferenceVersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\RemoteAddressField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\TranslatedField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\UpdatedAtField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\VersionField;
use Shopware\Core\Framework\DataAbstractionLayer\Pricing\Price;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Struct\ArrayEntity;

/**
 * @final
 */
#[Package('core')]
class EntityGenerator
{
    private string $classTemplate = <<<EOF
<?php declare(strict_types=1);

namespace #namespace#;

#uses#

class #classNamePrefix#Entity extends Entity
{
    use EntityIdTrait;#traits#

    #properties#

#functions#
}
EOF;

    private string $propertyTemplate = <<<EOF
    protected #nullable##type# $#property##value#;
EOF;

    private string $propertyFunctions = <<<EOF
    public function get#propertyUc#(): #nullable##type#
    {
        return \$this->#propertyLc#;
    }

    public function set#propertyUc#(#nullable##type# $#propertyLc#): void
    {
        \$this->#propertyLc# = $#propertyLc#;
    }
EOF;

    private string $collectionTemplate = <<<EOF
<?php declare(strict_types=1);

namespace #namespace#;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @package core
 * @extends EntityCollection<#entityClass#>
 */
class #classNamePrefix#Collection extends EntityCollection
{
    protected function getExpectedClass(): string
    {
        return #entityClass#::class;
    }
}
EOF;

    public function generate(EntityDefinition $definition): ?array
    {
        if ($definition instanceof MappingEntityDefinition) {
            return null;
        }

        $classNamePrefix = $definition->getClass();
        if (str_ends_with($classNamePrefix, 'Definition')) {
            $classNamePrefix = substr($classNamePrefix, 0, -10);
        }

        $namespace = explode('\\', $definition->getClass());
        $namespace = \array_slice($namespace, 0, \count($namespace) - 1);
        $namespace = implode('\\', $namespace);

        $result = [];

        $entityContent = $this->generateEntity($definition, $namespace, $classNamePrefix);
        if (!empty($entityContent)) {
            $result[$classNamePrefix . 'Entity.php'] = $entityContent;
        }

        $collectionContent = $this->generateCollection($definition, $namespace, $classNamePrefix);
        if (!empty($collectionContent)) {
            $result[$classNamePrefix . 'Collection.php'] = $collectionContent;
        }

        return $result;
    }

    private function generateEntity(EntityDefinition $definition, string $namespace, string $classNamePrefix): ?string
    {
        if ($definition->getEntityClass() === ArrayEntity::class) {
            return null;
        }

        $properties = [];

        $uses = [
            $this->getUsage(Entity::class),
            $this->getUsage(EntityIdTrait::class),
        ];
        $traits = [];

        foreach ($definition->getFields() as $field) {
            $property = $this->generateProperty($definition, $field);
            if (!$property) {
                continue;
            }
            foreach ($property['uses'] as $use) {
                $uses[] = $use;
            }
            foreach ($property['traits'] as $traitName) {
                $traits[] = '    use ' . $traitName;
            }
            $properties[] = $property;
        }

        $functions = array_filter(array_column($properties, 'functions'));
        $properties = array_filter(array_column($properties, 'property'));

        sort($uses);
        sort($traits);
        $uses = array_unique($uses);
        $traits = array_unique($traits);

        $parameters = [
            '#namespace#' => $namespace,
            '#uses#' => implode(";\n", $uses) . ';',
            '#traits#' => "\n" . implode(";\n", $traits) . ';',
            '#classNamePrefix#' => $classNamePrefix,
            '#properties#' => implode("\n    ", $properties),
            '#functions#' => implode("\n\n", $functions),
        ];

        return str_replace(
            array_keys($parameters),
            array_values($parameters),
            $this->classTemplate
        );
    }

    private function generateProperty(EntityDefinition $definition, Field $field): ?array
    {
        $uses = [];
        $traits = [];

        $value = 'null';

        switch (true) {
            case $field instanceof ReferenceVersionField:
            case $field instanceof VersionField:
            case $field instanceof CreatedAtField:
            case $field instanceof UpdatedAtField:
                return null;
            case $field instanceof CustomFields:
                return [
                    'uses' => [$this->getUsage(EntityCustomFieldsTrait::class)],
                    'traits' => ['EntityCustomFieldsTrait'],
                ];
            case $field instanceof TranslatedField:
                return $this->generateProperty(
                    $definition,
                    EntityDefinitionQueryHelper::getTranslatedField($definition, $field)
                );
            case $field instanceof ParentAssociationField:
                $uses[] = $this->getUsage($definition->getEntityClass());
                $type = $this->getClassTypeHint($definition->getEntityClass());

                break;
            case $field instanceof ChildrenAssociationField:
                $uses[] = $this->getUsage($definition->getCollectionClass());
                $type = $this->getClassTypeHint($definition->getCollectionClass());

                break;
            case $field instanceof OneToOneAssociationField:
            case $field instanceof ManyToOneAssociationField:
                $uses[] = $this->getUsage($field->getReferenceDefinition()->getEntityClass());
                $type = $this->getClassTypeHint($field->getReferenceDefinition()->getEntityClass());

                break;
            case $field instanceof OneToManyAssociationField:
                $uses[] = $this->getUsage($field->getReferenceDefinition()->getCollectionClass());
                $type = $this->getClassTypeHint($field->getReferenceDefinition()->getCollectionClass());

                break;
            case $field instanceof ManyToManyAssociationField:
                $uses[] = $this->getUsage($field->getToManyReferenceDefinition()->getCollectionClass());
                $type = $this->getClassTypeHint($field->getToManyReferenceDefinition()->getCollectionClass());

                break;
            case $field instanceof CartPriceField:
                $type = 'CartPrice';
                $uses[] = $this->getUsage(CartPrice::class);

                break;
            case $field instanceof CalculatedPriceField:
                $type = 'CalculatedPrice';
                $uses[] = $this->getUsage(CalculatedPrice::class);

                break;
            case $field instanceof PriceDefinitionField:
                $type = 'QuantityPriceDefinition';
                $uses[] = $this->getUsage(QuantityPriceDefinition::class);

                break;
            case $field instanceof PriceField:
                $type = 'Price';
                $uses[] = $this->getUsage(Price::class);

                break;
            case $field instanceof FloatField:
                $type = 'float';

                break;
            case $field instanceof IntField:
                $type = 'int';

                break;
            case $field instanceof JsonField:
                if ($field->getPropertyName() === 'translated') {
                    return null;
                }
                $type = 'array';

                break;
            case $field instanceof IdField:
                if ($field->getPropertyName() === 'id') {
                    return null;
                }
                $type = 'string';

                break;
            case $field instanceof LongTextField:
            case $field instanceof PasswordField:
            case $field instanceof FkField:
            case $field instanceof StringField:
            case $field instanceof RemoteAddressField:
                $type = 'string';

                break;
            case $field instanceof BoolField:
                $type = 'bool';

                break;
            case $field instanceof DateTimeField:
            case $field instanceof DateField:
                $type = "DateTimeInterface";
                $uses[] = $this->getUsage(DateTimeInterface::class);

                break;
            case $field instanceof BlobField:
                $type = 'object';

                break;
            default:
                throw new RuntimeException(sprintf('Unknown field %s', $field::class));
        }

        $nullable = '?';
        if ($field->is(Required::class)) {
            $nullable = '';
            $value = '';
        }
        if ($value !== '') {
            $value = ' = ' . $value;
        }

        $template = str_replace(
            ['#property#', '#type#', '#nullable#', '#value#'],
            [$field->getPropertyName(), $type, $nullable, $value],
            $this->propertyTemplate
        );

        $functions = str_replace(
            ['#propertyUc#', '#propertyLc#', '#nullable#', '#type#'],
            [ucfirst($field->getPropertyName()), lcfirst($field->getPropertyName()), $nullable, $type],
            $this->propertyFunctions
        );

        return [
            'property' => trim($template),
            'uses' => $uses,
            'traits' => $traits,
            'functions' => $functions,
        ];
    }

    private function generateCollection(EntityDefinition $definition, string $namespace, string $classNamePrefix): ?string
    {
        if ($definition->getCollectionClass() === EntityCollection::class) {
            return null;
        }

        $entityClass = $definition->getEntityClass();
        $entityClass = explode('\\', $entityClass);
        $entityClass = array_pop($entityClass);

        $parameters = [
            '#namespace#' => $namespace,
            '#entityClass#' => $entityClass,
            '#classNamePrefix#' => $classNamePrefix,
        ];

        return str_replace(
            array_keys($parameters),
            array_values($parameters),
            $this->collectionTemplate
        );
    }

    private function getUsage(string $class): string
    {
        return 'use ' . $class;
    }

    private function getClassTypeHint(string $class): string
    {
        $parts = explode('\\', $class);

        return array_pop($parts);
    }
}
