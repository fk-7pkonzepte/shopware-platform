<?php

declare(strict_types=1);

namespace Shopware\Core\Migration\V6_6;

use Doctrine\DBAL\Connection;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Migration\MigrationStep;
use Shopware\Core\Framework\Uuid\Uuid;

/**
 * @internal
 */
#[Package('core')]
class Migration1727863533ChangeProductManufacturerLink extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1727863533;
    }

    public function update(Connection $connection): void
    {
        if (!$this->columnExists($connection, 'product_manufacturer_translation', 'link')) {
            $connection->executeStatement(
                <<<SQL
            ALTER TABLE `product_manufacturer_translation`
            ADD COLUMN `link` LONGTEXT COLLATE utf8mb4_unicode_ci NULL AFTER `description`;
        SQL
            );
        }

        $result = $connection->executeQuery(
            'SELECT `id`, `version_id`, `link` FROM `product_manufacturer` WHERE `link` IS NOT NULL;'
        );

        $languageId = Uuid::fromHexToBytes(Defaults::LANGUAGE_SYSTEM);

        foreach ($result->iterateAssociative() as $row) {
            $connection->update(
                'product_manufacturer_translation',
                ['link' => $row['link']],
                [
                    'product_manufacturer_id' => $row['id'],
                    'product_manufacturer_version_id' => $row['version_id'],
                    'language_id' => $languageId,
                ]
            );
        }

        $this->dropColumnIfExists($connection, 'product_manufacturer', 'link');
    }
}
