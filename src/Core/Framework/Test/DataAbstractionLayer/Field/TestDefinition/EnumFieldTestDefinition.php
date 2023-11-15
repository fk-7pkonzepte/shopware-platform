<?php

namespace Shopware\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition;

use Shopware\Core\Framework\DataAbstractionLayer\EntityDefinition;
use Shopware\Core\Framework\DataAbstractionLayer\Field\EnumField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\ApiAware;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\IdField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\Test\TestCaseHelper\TestStringBackedEnum;
use Shopware\Core\Framework\Test\TestCaseHelper\TestUnitEnum;

/**
 * @internal
 */
class EnumFieldTestDefinition extends EntityDefinition
{
    final public const ENTITY_NAME = '_test_enum_field';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function since(): ?string
    {
        return '6.5.6.3';
    }

    protected function defineFields(): FieldCollection
    {
        $apiAware = new ApiAware();
        $primaryKey = new PrimaryKey();
        $required = new Required();

        return new FieldCollection([
            (new IdField('id', 'id'))->addFlags($apiAware, $primaryKey),
            (new EnumField('field_1', 'field1', TestUnitEnum::class))->addFlags($apiAware),
            (new EnumField('field_2', 'field2', TestStringBackedEnum::class))->addFlags($apiAware),
            (new EnumField('field_3', 'field3', TestUnitEnum::class))->addFlags($apiAware, $required),
            (new EnumField('field_4', 'field4', TestStringBackedEnum::class))->addFlags($apiAware, $required),
        ]);
    }
}
