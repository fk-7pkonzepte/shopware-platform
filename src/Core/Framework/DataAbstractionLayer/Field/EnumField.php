<?php

declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\Field;

use BackedEnum;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Flag;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\PrimaryKey;
use Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\EnumFieldSerializer;
use Shopware\Core\Framework\Log\Package;
use TypeError;
use UnitEnum;

#[Package('core')]
class EnumField extends Field implements StorageAware
{
    protected readonly string $enumTypeClass;

    public function __construct(
        private readonly string $storageName,
        protected string $propertyName,
        protected readonly string $enumClass,
    ) {
        parent::__construct($propertyName);

        if (!enum_exists($enumClass)) {
            throw new TypeError("Class \"$enumClass\" is not a enum class.");
        }
        $classInterfaces = class_implements($enumClass);
        if (!empty($classInterfaces)) {
            if (in_array(BackedEnum::class, $classInterfaces)) {
                $this->enumTypeClass = BackedEnum::class;
            } elseif (in_array(UnitEnum::class, $classInterfaces)) {
                $this->enumTypeClass = UnitEnum::class;
            }
        }
        if (!isset($this->enumTypeClass)) {
            throw new TypeError("Class \"$enumClass\" is not a enum class.");
        }
    }

    public function addFlags(Flag ...$flags): Field
    {
        parent::addFlags(...$flags);
        if ($this->is(PrimaryKey::class)) {
            // Since enums (currently) are not allowed to implement Stringable or the __toString method, there would be problems with the verificator.
            throw new TypeError('PrimaryKey flag is not allowed on EnumField!');
        }

        return $this;
    }

    public function getEnumClass(): string
    {
        return $this->enumClass;
    }

    public function getEnumTypeClass(): string
    {
        return $this->enumTypeClass;
    }

    public function getStorageName(): string
    {
        return $this->storageName;
    }

    protected function getSerializerClass(): string
    {
        return EnumFieldSerializer::class;
    }
}
