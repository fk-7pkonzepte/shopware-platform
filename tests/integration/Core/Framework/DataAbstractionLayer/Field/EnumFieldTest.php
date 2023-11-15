<?php

/**
 * @noinspection SqlDialectInspection
 * @noinspection SqlNoDataSourceInspection
 */
declare(strict_types=1);

namespace Shopware\Tests\Integration\Core\Framework\DataAbstractionLayer\Field;


use Doctrine\DBAL\Connection;
use Exception;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Read\EntityReaderInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriter;
use Shopware\Core\Framework\DataAbstractionLayer\Write\EntityWriterInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteContext;
use Shopware\Core\Framework\DataAbstractionLayer\Write\WriteException;
use Shopware\Core\Framework\Test\DataAbstractionLayer\Field\DataAbstractionLayerFieldTestBehaviour;
use Shopware\Core\Framework\Test\DataAbstractionLayer\Field\TestDefinition\EnumFieldTestDefinition;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Test\TestCaseHelper\TestStringBackedEnum;
use Shopware\Core\Framework\Test\TestCaseHelper\TestUnitEnum;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\WriteConstraintViolationException;

class EnumFieldTest extends TestCase
{
    use DataAbstractionLayerFieldTestBehaviour;
    use KernelTestBehaviour;

    private const SQL_FIELD = [
        'field1' => 'field_1',
        'field2' => 'field_2',
        'field3' => 'field_3',
        'field4' => 'field_4',
    ];
    private Connection $connection;

    protected function setUp(): void
    {
        /** @noinspection PhpFieldAssignmentTypeMismatchInspection */
        $this->connection = $this->getContainer()->get(Connection::class);

        $tableName = EnumFieldTestDefinition::ENTITY_NAME;
        $sql = <<<EOF
DROP TABLE IF EXISTS $tableName;
CREATE TABLE IF NOT EXISTS `$tableName` (
  `id` binary(16) NOT NULL,
  `field_1` varchar(255) COLLATE 'utf8mb4_unicode_ci' NULL,
  `field_2` varchar(255) COLLATE 'utf8mb4_unicode_ci' NULL,
  `field_3` varchar(255) COLLATE 'utf8mb4_unicode_ci' NOT NULL,
  `field_4` varchar(255) COLLATE 'utf8mb4_unicode_ci' NOT NULL,
  `created_at` DATETIME(3) NOT NULL,
  `updated_at` DATETIME(3) NULL,
  PRIMARY KEY `id` (`id`)
)  DEFAULT CHARSET=utf8mb4;
EOF;
        $this->connection->executeStatement($sql);
        $this->connection->beginTransaction();
    }

    protected function tearDown(): void
    {
        $tableName = EnumFieldTestDefinition::ENTITY_NAME;
        $this->connection->rollBack();
        $this->connection->executeStatement("DROP TABLE `$tableName`;");

        parent::tearDown();
    }

    /**
     * @dataProvider testEnumFieldProvider
     */
    public function testEnumField(array $data, bool $expectException): void
    {
        $tableName = EnumFieldTestDefinition::ENTITY_NAME;
        $writeContext = $this->createWriteContext();
        $definition = $this->registerDefinition(EnumFieldTestDefinition::class);
        $id = Uuid::randomHex();
        $data['id'] = $id;


        $exception = null;
        try {
            $this->getWriter()->insert($definition, [$data], $writeContext);
        } catch (Exception $exception) {
        }
        static::assertSame(!$expectException, $exception === null);
        if ($expectException) {
            static::assertInstanceOf(WriteException::class, $exception);
            $exceptions = $exception->getExceptions();
            static::assertCount(2, $exceptions);

            foreach (['/field3', '/field4'] as $index => $violationPropertyPath) {
                $fieldException = $exceptions[$index];
                static::assertInstanceOf(WriteConstraintViolationException::class, $fieldException);
                static::assertCount(1, $fieldException->getViolations());
                static::assertSame($violationPropertyPath, $fieldException->getViolations()->get(0)->getPropertyPath());
            }
        } else {
            $rows = $this->connection->fetchAllAssociative("SELECT * FROM `$tableName`");
            static::assertCount(1, $rows);
            static::assertSame(Uuid::fromHexToBytes($id), $rows[0]['id']);
            unset($data['id']);
            foreach ($data as $propertyName => $value) {
                if (!is_string($value)) {
                    if ($propertyName === 'field1' || $propertyName === 'field3') {
                        $value = $value?->name;
                    } elseif ($propertyName === 'field2' || $propertyName === 'field4') {
                        $value = $value?->value;
                    }
                }
                static::assertSame($value, $rows[0][self::SQL_FIELD[$propertyName]]);
            }
        }
    }

    protected function testEnumFieldProvider(): iterable
    {
        yield 'missing required fields' => [
            'data' => [
                'field1' => TestUnitEnum::VALUE_1,
                'field2' => TestStringBackedEnum::VALUE_1,
            ],
            'expectException' => true,
        ];

        yield 'wrong value enum type' => [
            'data' => [
                'field1' => TestUnitEnum::VALUE_1,
                'field2' => TestStringBackedEnum::VALUE_1,
                // wrong enum types in field 3 an 4
                'field3' => TestStringBackedEnum::VALUE_1,
                'field4' => TestUnitEnum::VALUE_1,
            ],
            'expectException' => true,
        ];

        yield 'required fields with null values' => [
            'data' => [
                'field1' => TestUnitEnum::VALUE_1,
                'field2' => TestStringBackedEnum::VALUE_1,
            ],
            'expectException' => true,
        ];

        yield 'required fields with enum values' => [
            'data' => [
                'field1' => null,
                'field2' => null,
                'field3' => TestUnitEnum::VALUE_2,
                'field4' => TestStringBackedEnum::VALUE_2,
            ],
            'expectException' => false,
        ];

        yield 'required fields with string values' => [
            'data' => [
                'field1' => null,
                'field2' => null,
                'field3' => TestUnitEnum::VALUE_2->name,
                'field4' => TestStringBackedEnum::VALUE_2->value,
            ],
            'expectException' => false,
        ];

        yield 'non required fields with enum values' => [
            'data' => [
                'field1' => TestUnitEnum::VALUE_1,
                'field2' => TestStringBackedEnum::VALUE_1,
                'field3' => TestUnitEnum::VALUE_2,
                'field4' => TestStringBackedEnum::VALUE_2,
            ],
            'expectException' => false,
        ];

        yield 'non required fields with string values' => [
            'data' => [
                'field1' => TestUnitEnum::VALUE_1->name,
                'field2' => TestStringBackedEnum::VALUE_1->value,
                'field3' => TestUnitEnum::VALUE_2,
                'field4' => TestStringBackedEnum::VALUE_2,
            ],
            'expectException' => false,
        ];
    }

    protected function createWriteContext(): WriteContext
    {
        return WriteContext::createFromContext(Context::createDefaultContext());
    }

    protected function getWriter(): EntityWriterInterface
    {
        return $this->getContainer()->get(EntityWriter::class);
    }

    protected function getReader(): EntityReaderInterface
    {
        return $this->getContainer()->get(EntityReaderInterface::class);
    }
}
