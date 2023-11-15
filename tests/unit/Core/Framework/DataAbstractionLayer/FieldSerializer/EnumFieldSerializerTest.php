<?php

declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Framework\DataAbstractionLayer\FieldSerializer;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Shopware\Core\Framework\DataAbstractionLayer\DefinitionInstanceRegistry;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InvalidSerializerFieldException;
use Shopware\Core\Framework\DataAbstractionLayer\Field\EnumField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\EnumFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\Write\Command\WriteCommandQueue;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\EnumFieldTestDefinition;
use Shopware\Core\Framework\Test\TestCaseHelper\TestStringBackedEnum;
use Shopware\Core\Framework\Test\TestCaseHelper\TestUnitEnum;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @covers \Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\EnumFieldSerializer
 *
 * @internal
 */
#[Package('core')]
class EnumFieldSerializerTest extends TestCase
{
    private ValidatorInterface & MockObject $validator;
    private EnumFieldSerializer $serializer;

    protected function setUp(): void
    {
        $definitionInstanceRegistry = $this->createMock(DefinitionInstanceRegistry::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->serializer = new EnumFieldSerializer($this->validator, $definitionInstanceRegistry);
    }

    public function testEncodeWithNonEnumField(): void
    {
        if (Feature::isActive('v6.6.0.0')) {
            static::expectException(DataAbstractionLayerException::class);
        } else {
            static::expectException(InvalidSerializerFieldException::class);
        }
        $existence = new EntityExistence(EnumFieldTestDefinition::ENTITY_NAME, [], false, false, false, []);
        $field = new StringField('test', 'test');
        $keyValuePair = new KeyValuePair($field->getPropertyName(), null, true);
        $params = new WriteParameterBag(new EnumFieldTestDefinition(), WriteContext::createFromContext(Context::createDefaultContext()), '', new WriteCommandQueue());

        $this->serializer->encode($field, $existence, $keyValuePair, $params)->getReturn();
    }

    public function testDecodeWithNonEnumField(): void
    {
        if (Feature::isActive('v6.6.0.0')) {
            static::expectException(DataAbstractionLayerException::class);
        } else {
            static::expectException(InvalidSerializerFieldException::class);
        }
        $field = new StringField('test', 'test');

        $this->serializer->decode($field, null);
    }

    /**
     * @dataProvider testDecodeMethodProvider
     */
    public function testDecodeMethod(mixed $value, array $fieldConfig, bool $expectException, mixed $expectedValue): void
    {
        if ($expectException) {
            static::expectException(DataAbstractionLayerException::class);
        }
        $field = new EnumField('test', 'test', $fieldConfig['class']);
        if ($fieldConfig['required'] ?? false) {
            $field->addFlags(new Required());
        }

        $result = $this->serializer->decode($field, $value);

        static::assertEquals($expectedValue, $result);
    }

    /**
     * @dataProvider testEncodeMethodProvider
     */
    public function testEncodeMethod(
        mixed $value,
        array $fieldConfig,
        array $expectException,
        mixed $expectedValue,
        array $constraints
    ): void {
        $existence = new EntityExistence(EnumFieldTestDefinition::ENTITY_NAME, [], false, false, false, []);
        $field = new EnumField('test', 'test', $fieldConfig['class']);
        if ($fieldConfig['required'] ?? false) {
            $field->addFlags(new Required());
        }
        $keyValuePair = new KeyValuePair($field->getPropertyName(), $value, true);
        $params = new WriteParameterBag(new EnumFieldTestDefinition(), WriteContext::createFromContext(Context::createDefaultContext()), '', new WriteCommandQueue());

        if ($expectException['expect']) {
            static::expectException($expectException['class']);
        }

        $constraintViolations = new ConstraintViolationList();
        $this->validator
            ->expects(static::exactly(\count($constraints)))->method('validate')
            ->willReturn($constraintViolations);

        $result = $this->serializer->encode($field, $existence, $keyValuePair, $params)->current();

        static::assertEquals($expectedValue, $result);
    }

    protected static function testDecodeMethodProvider(): iterable
    {
        //region # UnitEnum, not nullable
        yield 'UnitEnum, not nullable with null value' => [
            'value' => null,
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => true],
            'expectException' => true,
            'expectedValue' => null,
        ];
        yield 'UnitEnum, not nullable with empty value' => [
            'value' => '',
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => true],
            'expectException' => true,
            'expectedValue' => null,
        ];
        yield 'UnitEnum, not nullable with unknown enum value' => [
            'value' => 'UNKNOWN_CASE',
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => true],
            'expectException' => true,
            'expectedValue' => null,
        ];
        yield 'UnitEnum, not nullable with valid value' => [
            'value' => TestUnitEnum::VALUE_1->name,
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => true],
            'expectException' => false,
            'expectedValue' => TestUnitEnum::VALUE_1,
        ];
        //endregion

        //region # UnitEnum, nullable
        yield 'UnitEnum, nullable with null value' => [
            'value' => null,
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => false],
            'expectException' => false,
            'expectedValue' => null,
        ];
        yield 'UnitEnum, nullable with empty value' => [
            'value' => '',
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => false],
            'expectException' => false,
            'expectedValue' => null,
        ];
        yield 'UnitEnum, nullable with unknown enum value' => [
            'value' => 'UNKNOWN_CASE',
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => false],
            'expectException' => true,
            'expectedValue' => null,
        ];
        yield 'UnitEnum, nullable with valid value' => [
            'value' => TestUnitEnum::VALUE_1->name,
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => false],
            'expectException' => false,
            'expectedValue' => TestUnitEnum::VALUE_1,
        ];
        //endregion

        //region # BackendEnum, not nullable
        yield 'BackendEnum, not nullable with null value' => [
            'value' => null,
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => true],
            'expectException' => true,
            'expectedValue' => null,
        ];
        yield 'BackendEnum, not nullable with empty value' => [
            'value' => '',
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => true],
            'expectException' => true,
            'expectedValue' => null,
        ];
        yield 'BackendEnum, not nullable with unknown enum value' => [
            'value' => 'UNKNOWN_CASE',
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => true],
            'expectException' => true,
            'expectedValue' => null,
        ];
        yield 'BackendEnum, not nullable with valid value' => [
            'value' => TestStringBackedEnum::VALUE_1->value,
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => true],
            'expectException' => false,
            'expectedValue' => TestStringBackedEnum::VALUE_1,
        ];
        //endregion

        //region # BackendEnum, nullable
        yield 'BackendEnum, nullable with null value' => [
            'value' => null,
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => false],
            'expectException' => false,
            'expectedValue' => null,
        ];
        yield 'BackendEnum, nullable with empty value' => [
            'value' => '',
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => false],
            'expectException' => false,
            'expectedValue' => null,
        ];
        yield 'BackendEnum, nullable with unknown enum value' => [
            'value' => 'UNKNOWN_CASE',
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => false],
            'expectException' => true,
            'expectedValue' => null,
        ];
        yield 'BackendEnum, nullable with valid value' => [
            'value' => TestStringBackedEnum::VALUE_1->value,
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => false],
            'expectException' => false,
            'expectedValue' => TestStringBackedEnum::VALUE_1,
        ];
        //endregion
    }

    protected static function testEncodeMethodProvider(): iterable
    {
        //region # UnitEnum, not required
        $constraintsUnitEnum = [
            new Type(TestUnitEnum::class),
        ];
        yield 'UnitEnum, not required with null value' => [
            'value' => null,
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => false],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => null,
            'constraints' => [],
        ];
        yield 'UnitEnum, not required with empty string value' => [
            'value' => '',
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => false],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => null,
            'constraints' => [],
        ];
        yield 'UnitEnum, not required with unknown enum string value' => [
            'value' => 'UNKNOWN_CASE',
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => false],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => null,
            'constraints' => [],
        ];
        yield 'UnitEnum, not required with value from other Enum' => [
            'value' => TestStringBackedEnum::VALUE_1,
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => false],
            'expectException' => ['expect' => true, 'class' => DataAbstractionLayerException::class],
            'expectedValue' => null,
            'constraints' => $constraintsUnitEnum,
        ];
        yield 'UnitEnum, not required with valid value' => [
            'value' => TestUnitEnum::VALUE_1,
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => false],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => 'VALUE_1',
            'constraints' => $constraintsUnitEnum,
        ];
        //endregion

        //region # UnitEnum, required
        $constraintsUnitEnumRequired = [
            new Type(TestUnitEnum::class),
            new NotNull(),
        ];
        yield 'UnitEnum, required with null value' => [
            'value' => null,
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => true],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => null,
            'constraints' => $constraintsUnitEnumRequired,
        ];
        yield 'UnitEnum, required with empty string value' => [
            'value' => '',
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => true],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => null,
            'constraints' => $constraintsUnitEnumRequired,
        ];
        yield 'UnitEnum, required with unknown enum string value' => [
            'value' => 'UNKNOWN_CASE',
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => true],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => null,
            'constraints' => $constraintsUnitEnumRequired,
        ];
        yield 'UnitEnum, required with value from other Enum' => [
            'value' => TestStringBackedEnum::VALUE_1,
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => true],
            'expectException' => ['expect' => true, 'class' => DataAbstractionLayerException::class, 'message' => ''],
            'expectedValue' => null,
            'constraints' => $constraintsUnitEnumRequired,
        ];
        yield 'UnitEnum, required with valid value' => [
            'value' => TestUnitEnum::VALUE_1,
            'fieldConfig' => ['class' => TestUnitEnum::class, 'required' => true],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => 'VALUE_1',
            'constraints' => $constraintsUnitEnumRequired,
        ];
        //endregion

        // region # BackendEnum, nullable
        $constraintsBackedEnum = [
            new Type(TestStringBackedEnum::class),
        ];
        yield 'BackendEnum, nullable with null value' => [
            'value' => null,
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => false],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => null,
            'constraints' => [],
        ];
        yield 'BackendEnum, nullable with empty string value' => [
            'value' => '',
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => false],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => null,
            'constraints' => [],
        ];
        yield 'BackendEnum, nullable with unknown enum string value' => [
            'value' => 'UNKNOWN_CASE',
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => false],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => null,
            'constraints' => [],
        ];
        yield 'BackendEnum, nullable with value from other Enum' => [
            'value' => TestUnitEnum::VALUE_1,
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => false],
            'expectException' => ['expect' => true, 'class' => DataAbstractionLayerException::class],
            'expectedValue' => null,
            'constraints' => $constraintsBackedEnum,
        ];
        yield 'BackendEnum, nullable with valid value' => [
            'value' => TestStringBackedEnum::VALUE_1,
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => false],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => TestStringBackedEnum::VALUE_1->value,
            'constraints' => $constraintsBackedEnum,
        ];
        //endregion

        //region # BackendEnum, required
        $constraintsBackedEnumRequired = [
            new Type(TestStringBackedEnum::class),
            new NotNull(),
        ];
        yield 'BackendEnum, required with null value' => [
            'value' => null,
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => true],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => null,
            'constraints' => $constraintsBackedEnumRequired,
        ];
        yield 'BackendEnum, required with empty string value' => [
            'value' => '',
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => true],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => null,
            'constraints' => $constraintsBackedEnumRequired,
        ];
        yield 'BackendEnum, required with unknown enum string value' => [
            'value' => 'UNKNOWN_CASE',
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => true],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => null,
            'constraints' => $constraintsBackedEnumRequired,
        ];
        yield 'BackendEnum, required with value from other Enum' => [
            'value' => TestUnitEnum::VALUE_1,
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => true],
            'expectException' => ['expect' => true, 'class' => DataAbstractionLayerException::class],
            'expectedValue' => null,
            'constraints' => $constraintsBackedEnumRequired,
        ];
        yield 'BackendEnum, required with valid value' => [
            'value' => TestStringBackedEnum::VALUE_1,
            'fieldConfig' => ['class' => TestStringBackedEnum::class, 'required' => true],
            'expectException' => ['expect' => false, 'class' => null],
            'expectedValue' => TestStringBackedEnum::VALUE_1->value,
            'constraints' => $constraintsBackedEnumRequired,
        ];
        //endregion
    }
}
