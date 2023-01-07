<?php declare(strict_types=1);

namespace Shopware\Tests\Migration\Core\V6_5;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Test\TestCaseBase\KernelLifecycleManager;
use Shopware\Core\Migration\V6_5\Migration1671029945CreateFlowTemplateTable;
use Shopware\Core\Migration\V6_5\Migration1671030271GenerateFlowTemplateDataFromEventAction;

/**
 * @package business-ops
 *
 * @internal
 * @covers \Shopware\Core\Migration\V6_5\Migration1671030271GenerateFlowTemplateDataFromEventAction
 */
class Migration1671030271GenerateFlowTemplateDataFromEventActionTest extends TestCase
{
    private Connection $connection;

    public function setUp(): void
    {
        $this->connection = KernelLifecycleManager::getConnection();
    }

    public function testGetCreationTimestamp(): void
    {
        $migration = new Migration1671030271GenerateFlowTemplateDataFromEventAction();
        static::assertEquals('1671030271', $migration->getCreationTimestamp());
    }

    public function testGenerateDefaultFlowTemplates(): void
    {
        $migration = new Migration1671029945CreateFlowTemplateTable();
        $migration->update($this->connection);

        $this->connection->executeStatement('DELETE FROM `flow_template`');

        $migration = new Migration1671030271GenerateFlowTemplateDataFromEventAction();

        // should work as expected if executed multiple times
        $migration->update($this->connection);
        $migration->update($this->connection);

        $countFlowSequences = $this->connection->fetchOne('SELECT COUNT(*) FROM `flow_template`');
        static::assertEquals(26, $countFlowSequences);
    }
}
