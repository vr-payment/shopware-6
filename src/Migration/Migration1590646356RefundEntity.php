<?php declare(strict_types=1);

namespace VRPaymentPayment\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

/**
 * Class Migration1590646356RefundEntity
 *
 * @package VRPaymentPayment\Migration
 */
class Migration1590646356RefundEntity extends MigrationStep {
	/**
	 * get creation timestamp
	 *
	 * @return int
	 */
	public function getCreationTimestamp(): int
	{
		return 1590646356;
	}

	/**
	 * update non-destructive changes
	 *
	 * @param \Doctrine\DBAL\Connection $connection
	 * @throws \Doctrine\DBAL\DBALException
	 */
	public function update(Connection $connection): void
	{
		$connection->executeStatement('
            CREATE TABLE IF NOT EXISTS `vrpayment_refund` (
              `id` BINARY(16) NOT NULL,
              `data` JSON NOT NULL,
              `refund_id` INT UNSIGNED NOT NULL,
              `space_id` INT UNSIGNED NOT NULL,
              `state` VARCHAR(255) NOT NULL,
              `transaction_id` INT UNSIGNED NOT NULL,
              `created_at` DATETIME(3) NOT NULL,
              `updated_at` DATETIME(3) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `refund_id_UNIQUE` (`refund_id`),
              KEY `fk.vrp_refund.transaction_id` (`transaction_id`),
              CONSTRAINT `fk.vrp_refund.transaction_id` FOREIGN KEY (`transaction_id`) REFERENCES `vrpayment_transaction` (`transaction_id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
	}

	/**
	 * update destructive changes
	 *
	 * @param \Doctrine\DBAL\Connection $connection
	 */
	public function updateDestructive(Connection $connection): void
	{
		// implement update destructive
	}
}
