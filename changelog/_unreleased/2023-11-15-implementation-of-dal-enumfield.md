---
title: Implementation of DAL EnumField
issue: NEXT-0000
author: Florian Kasper
author_email: flkasper@web.de
author_github: flkasper
---

# Core

* Added new DAL EnumField for using enumerations instead of (string) constants.
  * Added DAL field `Shopware\Core\Framework\DataAbstractionLayer\Field\EnumField`.
  * Added DAL field serializer `Shopware\Core\Framework\DataAbstractionLayer\FieldSerializer\EnumFieldSerializer`.
  * Added EnumField test definition `Shopware\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\EnumFieldTestDefinition`.
  * Added (string) BackendEnum based test enum `Shopware\Core\Framework\Test\TestCaseHelper\TestStringBackedEnum`.
  * Added UnitEnum based test enum `Shopware\Core\Framework\Test\TestCaseHelper\TestUnitEnum`.
  * Added integration test `Shopware\Tests\Integration\Core\Framework\DataAbstractionLayer\Field\EnumFieldTest`.
  * Added unit test `Shopware\Tests\Unit\Core\Framework\DataAbstractionLayer\Field\EnumFieldTest`.
  * Added unit test `Shopware\Tests\Unit\Core\Framework\DataAbstractionLayer\FieldSerializer\EnumFieldSerializerTest`.
  * Changed `Shopware\Core\Framework\Api\ApiDefinition\Generator\EntitySchemaGenerator` to handle EnumField fields.
  * Changed `Shopware\Core\Framework\DataAbstractionLayer\DataAbstractionLayerException` to create EnumField based Exception.
  * Changed `Shopware\Core\Framework\DataAbstractionLayer\EntityGenerator` to handle EnumField fields.
  * Changed `Shopware\Core\Framework\DataAbstractionLayer\SchemaGenerator` to handle EnumField type.
  * Changed `Core/Framework/DependencyInjection/data-abstraction-layer.xml` to register EnumFieldSerializer.
  * Changed `Core/Framework/DependencyInjection/services_test.xml` to register EnumFieldTestDefinition.
