<?php declare(strict_types=1);

namespace VRPaymentPayment\Core\Api\Transaction\Entity;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * Class TransactionEntityCollection
 *
 * @package VRPaymentPayment\Core\Api\Transaction\Entity
 *
 * @method void              add(TransactionEntity $entity)
 * @method void              set(string $key, TransactionEntity $entity)
 * @method TransactionEntity[]    getIterator()
 * @method TransactionEntity[]    getElements()
 * @method TransactionEntity|null get(string $key)
 * @method TransactionEntity|null first()
 * @method TransactionEntity|null last()
 */
class TransactionEntityCollection extends EntityCollection {

	/**
	 * Get by transaction id
	 *
	 * @param int $transactionId
	 * @return \VRPaymentPayment\Core\Api\Transaction\Entity\TransactionEntity|null
	 */
	public function getByTransactionId(int $transactionId): ?TransactionEntity
	{
		foreach ($this->getIterator() as $element) {
			if ($element->getTransactionId() === $transactionId) {
				return $element;
			}
		}

		return null;
	}

	/**
	 * @return string
	 */
	protected function getExpectedClass(): string
	{
		return TransactionEntity::class;
	}
}