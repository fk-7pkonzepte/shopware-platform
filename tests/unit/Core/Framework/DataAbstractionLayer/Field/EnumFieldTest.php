<?php

declare(strict_types=1);

namespace Shopware\Tests\Unit\Core\Framework\DataAbstractionLayer\Field;

use BackedEnum;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\DataAbstractionLayer\Field\EnumField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\Test\TestCaseHelper\TestStringBackedEnum;
use Shopware\Core\Framework\Test\TestCaseHelper\TestUnitEnum;
use TypeError;
use UnitEnum;

class EnumFieldTest extends TestCase
{
    public function testConstructor(): void
    {
        $field = new EnumField('enum_field', 'enumField', TestUnitEnum::class);

        static::assertSame('enum_field', $field->getStorageName());
        static::assertSame('enumField', $field->getPropertyName());
        static::assertSame(TestUnitEnum::class, $field->getEnumClass());
        static::assertSame(UnitEnum::class, $field->getEnumTypeClass());

        $field = new EnumField('enum_field', 'enumField', TestStringBackedEnum::class);
        static::assertSame(TestStringBackedEnum::class, $field->getEnumClass());
        static::assertSame(BackedEnum::class, $field->getEnumTypeClass());
    }

    public function testConstructorWithNonEnumClass(): void
    {
        static::expectException(TypeError::class);
        new EnumField('enum_field', 'enumField', self::class);
    }

    public function testFlagPrimaryKeyRestriction(): void
    {
        static::expectException(TypeError::class);
        (new EnumField('enum_field', 'enumField', TestUnitEnum::class))->addFlags(new PrimaryKey());
    }
}
