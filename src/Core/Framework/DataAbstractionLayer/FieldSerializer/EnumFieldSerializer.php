<?php

declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer;

use BackedEnum;
use Generator;
use Shopware\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException;
use Shopware\Core\Framework\DataAbstractionLayer\Field\EnumField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Field;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Write\DataStack\KeyValuePair;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityExistence;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteParameterBag;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Constraints\Type;
use Throwable;
use UnitEnum;

/**
 * @internal
 */
#[Package('core')]
class EnumFieldSerializer extends AbstractFieldSerializer
{
    public function encode(Field $field, EntityExistence $existence, KeyValuePair $data, WriteParameterBag $parameters): Generator
    {
        if (!$field instanceof EnumField) {
            throw DataAbstractionLayerException::invalidSerializerField(EnumField::class, $field);
        }
        $value = $data->getValue();
        if ($value === null || $value === '') {
            if (!$field->is(Required::class)) {
                yield $field->getStorageName() => null;
                return;
            }
        }
        if (is_string($value)) {
            try {
                $data->setValue($this->decode($field, $value));
            } catch (\Exception $e) {
                $data->setValue(null);
            }
        }
        $this->validateIfNeeded($field, $existence, $data, $parameters);

        $value = $data->getValue();
        if ($value === null) {
            yield $field->getStorageName() => null;
            return;
        }
        if ($field->getEnumClass() === get_class($value)) {
            if ($field->getEnumTypeClass() === BackedEnum::class) {
                yield $field->getStorageName() => $value->value;
                return;
            } elseif ($field->getEnumTypeClass() === UnitEnum::class) {
                yield $field->getStorageName() => $value->name;
                return;
            }
        }
        throw DataAbstractionLayerException::invalidEnumFormat($field->getEnumClass(), $value);
    }

    public function decode(Field $field, mixed $value): ?UnitEnum
    {
        if (!$field instanceof EnumField) {
            throw DataAbstractionLayerException::invalidSerializerField(EnumField::class, $field);
        }
        $enumClass = $field->getEnumClass();
        if ($value === null || $value === '') {
            if ($field->is(Required::class)) {
                throw DataAbstractionLayerException::invalidEnumFormat($enumClass, $value);
            }
            return null;
        }
        if ($field->getEnumTypeClass() === BackedEnum::class) {
            /** @var BackedEnum $enumClass */
            try {
                return $enumClass::from($value);
            } catch (Throwable $throwable) {
                throw DataAbstractionLayerException::invalidEnumFormat($enumClass, $value, $throwable);
            }
        } elseif ($field->getEnumTypeClass() === UnitEnum::class) {
            /** @var UnitEnum $enumClass */
            foreach ($enumClass::cases() as $case) {
                if ($case->name === $value) {
                    return $case;
                }
            }
        }
        throw DataAbstractionLayerException::invalidEnumFormat($enumClass, $value);
    }

    protected function getConstraints(Field $field): array
    {
        /** @var EnumField $field */
        $constraints = [new Type($field->getEnumClass())];

        if ($field->is(Required::class)) {
            $constraints[] = new NotNull();
        }
        return $constraints;
    }
}
