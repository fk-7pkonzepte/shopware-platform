<?php declare(strict_types=1);

namespace Shopware\Tests\Migration\Core\V6_5;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Migration\V6_5\Migration1671029945CreateFlowTemplateTable;

/**
 * @package business-ops
 *
 * @internal
 * @covers \Shopware\Core\Migration\V6_5\Migration1671029945CreateFlowTemplateTable
 */
class Migration1671029945CreateFlowTemplateTableTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = KernelLifecycleManager::getConnection();

        $this->connection->executeStatement('DROP TABLE IF EXISTS `flow_template`');
    }

    public function testGetCreationTimestamp(): void
    {
        $migration = new Migration1671029945CreateFlowTemplateTable();
        static::assertEquals('1671029945', $migration->getCreationTimestamp());
    }

    public function testTablesArePresent(): void
    {
        $migration = new Migration1671029945CreateFlowTemplateTable();

        // should work as expected if executed multiple times
        $migration->update($this->connection);
        $migration->update($this->connection);

        $flowTemplateColumns = array_column($this->connection->fetchAllAssociative('SHOW COLUMNS FROM flow_template'), 'Field');

        static::assertContains('id', $flowTemplateColumns);
        static::assertContains('name', $flowTemplateColumns);
        static::assertContains('config', $flowTemplateColumns);
        static::assertContains('created_at', $flowTemplateColumns);
        static::assertContains('updated_at', $flowTemplateColumns);
    }
}
