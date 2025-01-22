<?php declare(strict_types=1);

namespace VRPaymentPayment\Core\Api\WebHooks\Controller;

use Doctrine\DBAL\{
	Connection,
	TransactionIsolationLevel};
use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Checkout\Cart\CartException,
	Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity,
	Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity,
	Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler,
	Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates,
	Checkout\Order\OrderEntity,
	Checkout\Order\SalesChannel\OrderService,
	Framework\Context,
	Framework\DataAbstractionLayer\Search\Criteria,
	Framework\DataAbstractionLayer\Search\Sorting\FieldSorting,
	Framework\Routing\Annotation\RouteScope,
	System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions,
	System\StateMachine\Exception\IllegalTransitionException};
use Shopware\Core\Checkout\Order\OrderStates;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\{
	HttpFoundation\JsonResponse,
	HttpFoundation\ParameterBag,
	HttpFoundation\Request,
	HttpFoundation\Response,
	Routing\Attribute\Route};
use VRPayment\Sdk\{
	Model\RefundState,
	Model\Transaction,
	Model\TransactionInvoiceState,
	Model\TransactionState,
	Model\TransactionInvoice,};
use VRPaymentPayment\Core\{Api\OrderDeliveryState\Handler\OrderDeliveryStateHandler,
	Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService,
	Api\Refund\Service\RefundService,
	Api\Transaction\Service\OrderMailService,
	Api\Transaction\Service\TransactionService,
	Api\WebHooks\Strategy\WebHookPaymentMethodConfigurationStrategy,
	Api\WebHooks\Strategy\WebHookRefundStrategy,
	Api\WebHooks\Strategy\WebHookStrategyManager,
	Api\WebHooks\Strategy\WebHookTransactionInvoiceStrategy,
	Api\WebHooks\Strategy\WebHookTransactionStrategy,
	Api\WebHooks\Struct\WebHookRequest,
	Settings\Service\SettingsService,
	Util\Payload\TransactionPayload};

/**
 * Class WebHookController
 *
 * @package VRPaymentPayment\Core\Api\WebHooks\Controller
 *
 */
#[Package('sales-channel')]
#[Route(defaults: ['_routeScope' => ['api']])]
class WebHookController extends AbstractController {

	/**
	 * @var \Doctrine\DBAL\Connection
	 */
	protected $connection;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var \VRPaymentPayment\Core\Api\Transaction\Service\OrderMailService
	 */
	protected $orderMailService;

	/**
	 * @var \Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler
	 */
	protected $orderTransactionStateHandler;

	/**
	 * @var \VRPaymentPayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService
	 */
	protected $paymentMethodConfigurationService;

	/**
	 * @var \VRPaymentPayment\Core\Settings\Struct\Settings
	 */
	protected $settings;

	/**
	 * @var \VRPaymentPayment\Core\Settings\Service\SettingsService
	 */
	protected $settingsService;

	/**
	 * @var \VRPaymentPayment\Core\Api\Refund\Service\RefundService
	 */
	protected $refundService;

	/**
	 * @var \VRPaymentPayment\Core\Api\Transaction\Service\TransactionService
	 */
	protected $transactionService;

	/**
	 * Transaction Final States
	 *
	 * @var array
	 */
	protected $transactionFinalStates = [
		OrderTransactionStates::STATE_CANCELLED,
		OrderTransactionStates::STATE_PAID,
		OrderTransactionStates::STATE_REFUNDED,
	];
	/**
	 * Transaction Failed States
	 *
	 * @var array
	 */
	protected $transactionFailedStates = [
		TransactionState::DECLINE,
		TransactionState::FAILED,
		TransactionState::VOIDED,
	];

	protected $vrpaymentTransactionSuccessStates = [
		TransactionState::AUTHORIZED,
		TransactionState::COMPLETED,
		TransactionState::FULFILL,
	];

	/**
	 * @var \Shopware\Core\Checkout\Order\OrderEntity
	 */
	private $orderEntity;

	/**
	 * @var \Shopware\Core\Checkout\Order\SalesChannel\OrderService
	 */
	private $orderService;

	/**
	 * @var \VRPaymentPayment\Core\Api\WebHooks\Strategy\WebHookStrategyManager
	 */
	private $webHookStrategyManager;

	const LINE_ITEM_TYPE_FEE = 'FEE';

	/**
	 * WebHookController constructor.
	 *
	 * @param \Doctrine\DBAL\Connection                                                                                   $connection
	 * @param \Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler                       $orderTransactionStateHandler
	 * @param \Shopware\Core\Checkout\Order\SalesChannel\OrderService                                                     $orderService
	 * @param \VRPaymentPayment\Core\Api\PaymentMethodConfiguration\Service\PaymentMethodConfigurationService $paymentMethodConfigurationService
	 * @param \VRPaymentPayment\Core\Api\Refund\Service\RefundService                                         $refundService
	 * @param \VRPaymentPayment\Core\Api\Transaction\Service\OrderMailService                                 $orderMailService
	 * @param \VRPaymentPayment\Core\Api\Transaction\Service\TransactionService                               $transactionService
	 * @param \VRPaymentPayment\Core\Settings\Service\SettingsService                                         $settingsService
	 * @param \VRPaymentPayment\Core\Api\WebHooks\Strategy\WebHookStrategyManager                                         $settingsService
	 */
	public function __construct(
		Connection $connection,
		OrderTransactionStateHandler $orderTransactionStateHandler,
		OrderService $orderService,
		PaymentMethodConfigurationService $paymentMethodConfigurationService,
		RefundService $refundService,
		OrderMailService $orderMailService,
		TransactionService $transactionService,
		SettingsService $settingsService,
		WebHookStrategyManager $webHookStrategyManager
	)
	{
		$this->connection                        = $connection;
		$this->orderTransactionStateHandler      = $orderTransactionStateHandler;
		$this->paymentMethodConfigurationService = $paymentMethodConfigurationService;
		$this->refundService                     = $refundService;
		$this->orderMailService                  = $orderMailService;
		$this->transactionService                = $transactionService;
		$this->settingsService                   = $settingsService;
		$this->orderService                      = $orderService;
		$this->webHookStrategyManager            = $webHookStrategyManager;
	}

	/**
	 * @param \Psr\Log\LoggerInterface $logger
	 *
	 * @internal
	 * @required
	 *
	 */
	public function setLogger(LoggerInterface $logger): void
	{
		$this->logger = $logger;
	}

	/**
	 * This is the method VRPayment calls
	 *
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Shopware\Core\Framework\Context          $context
	 * @param string                                    $salesChannelId
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
	 */
	#[Route(
		path: "/api/_action/vrpayment/webHook/callback/{salesChannelId}",
		name: "api.action.vrpayment.webhook.update",
		options: ["seo" => false],
		defaults: [
			"csrf_protected" => false,
			"XmlHttpRequest" => true,
			"auth_required" => false,
		],
		methods: ["POST"],
	)]
	public function callback(Request $request, Context $context, string $salesChannelId): Response
	{
		$status       = Response::HTTP_UNPROCESSABLE_ENTITY;
		$callBackData = new WebHookRequest();
		try {
			// Configuration
			$salesChannelId = $salesChannelId == 'null' ? null : $salesChannelId;
			$this->settings = $this->settingsService->getSettings($salesChannelId);
			$signature      = $request->server->get('HTTP_X_SIGNATURE');
			$requestJson    = json_decode($request->getContent(), true);
			$apiClient      = $this->settings->getApiClient();
			$callBackData->assign($requestJson);

			// Handling of payloads without a signature (legacy method).
			// Deprecated since 3.0.12
			if (empty($signature)) {
				switch ($callBackData->getListenerEntityTechnicalName()) {
					case WebHookRequest::PAYMENT_METHOD_CONFIGURATION:
						return $this->updatePaymentMethodConfiguration($context, $salesChannelId);
					case WebHookRequest::REFUND:
						return $this->updateRefund($callBackData, $context);
					case WebHookRequest::TRANSACTION:
						return $this->updateTransaction($callBackData, $context);
					case WebHookRequest::TRANSACTION_INVOICE:
						return $this->updateTransactionInvoice($callBackData, $context);
					default:
						$this->logger->warning(__CLASS__ . ' : ' . __FUNCTION__ . ' : Listener not implemented : ', $callBackData->jsonSerialize());
				}
			}

			// Handling of payloads with a valid signature.
			// This payload signed has the transaction state
			if (!empty($signature) && $apiClient->getWebhookEncryptionService()->isContentValid($signature, $request->getContent())) {
				return $this->webHookStrategyManager->process($callBackData, $context, $salesChannelId);
			}

			$status = Response::HTTP_OK;
		} catch (\Exception $exception) {
			$this->logger->critical(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage(), $callBackData->jsonSerialize());
		}
		return new JsonResponse(['data' => $callBackData], $status);
	}

	/**
	 * Handle VRPayment Payment Method Configuration callback
	 *
	 * @param \Shopware\Core\Framework\Context $context
	 * @param string                           $salesChannelId
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \VRPayment\Sdk\ApiException
	 * @throws \VRPayment\Sdk\Http\ConnectionException
	 * @throws \VRPayment\Sdk\VersioningException
	 * @deprecated 6.1.8 No longer used by internal code and not recommended.
	 * @see WebHookPaymentMethodConfigurationStrategy
	 */
	private function updatePaymentMethodConfiguration(Context $context, string $salesChannelId = null): Response
	{
		$result = $this->paymentMethodConfigurationService->setSalesChannelId($salesChannelId)->synchronize($context);

		return new JsonResponse(['result' => $result]);
	}

	/**
	 * Handle VRPayment Refund callback
	 *
	 * @param \VRPaymentPayment\Core\Api\WebHooks\Struct\WebHookRequest $callBackData
	 * @param \Shopware\Core\Framework\Context                                      $context
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @deprecated 6.1.8 No longer used by internal code and not recommended.
	 * @see WebHookRefundStrategy
	 */
	public function updateRefund(WebHookRequest $callBackData, Context $context): Response
	{
		$status = Response::HTTP_UNPROCESSABLE_ENTITY;

		try {
			/**
			 * @var \VRPayment\Sdk\Model\Transaction $transaction
			 */
			$refund  = $this->settings->getApiClient()->getRefundService()
									  ->read($callBackData->getSpaceId(), $callBackData->getEntityId());
			$orderId = $refund->getTransaction()->getMetaData()[TransactionPayload::VRPAYMENT_METADATA_ORDER_ID];

			if(!empty($orderId)) {

				$this->executeLocked($orderId, $context, function () use ($orderId, $refund, $context) {

					$this->refundService->upsert($refund, $context);

					$orderTransactionId = $refund->getTransaction()->getMetaData()[TransactionPayload::VRPAYMENT_METADATA_ORDER_TRANSACTION_ID];
					$orderTransaction   = $this->getOrderTransaction($orderId, $context);
					if (
						in_array(
							$orderTransaction->getStateMachineState()?->getTechnicalName(),
							[
								OrderTransactionStates::STATE_PAID,
								OrderTransactionStates::STATE_PARTIALLY_PAID,
							]
						) &&
						($refund->getState() == RefundState::SUCCESSFUL)
					) {
						if ($refund->getAmount() == $orderTransaction->getAmount()->getTotalPrice()) {
							$this->orderTransactionStateHandler->refund($orderTransactionId, $context);
						} else {
							if ($refund->getAmount() < $orderTransaction->getAmount()->getTotalPrice()) {
								$this->orderTransactionStateHandler->refundPartially($orderTransactionId, $context);
							}
						}
					} elseif ($orderTransaction->getStateMachineState()?->getTechnicalName()
						=== OrderTransactionStates::STATE_PARTIALLY_REFUNDED &&
						($refund->getState() == RefundState::SUCCESSFUL)
					) {
						$transactionByOrderTransactionId = $this->transactionService->getByOrderTransactionId($orderTransactionId, $context);
						$totalRefundedAmount  = $this->getTotalRefundedAmount($transactionByOrderTransactionId->getTransactionId(), $context);
						if (floatval($orderTransaction->getAmount()->getTotalPrice()) - $totalRefundedAmount <= 0) {
							$this->orderTransactionStateHandler->refund($orderTransactionId, $context);
						}
					}

				});
			}

			$status = Response::HTTP_OK;
		} catch (CartException $exception) {
			$status = Response::HTTP_OK;
			$this->logger->info(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage(), $callBackData->jsonSerialize());
		} catch (IllegalTransitionException $exception) {
			$status = Response::HTTP_OK;
			$this->logger->info(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage(), $callBackData->jsonSerialize());
		} catch (\Exception $exception) {
			$this->logger->critical(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage(), $callBackData->jsonSerialize());
		}

		return new JsonResponse(['data' => $callBackData->jsonSerialize()], $status);
	}

	/**
	 * @param int $transactionId
	 * @param Context $context
	 * @return float
	 */
	private function getTotalRefundedAmount(int $transactionId, Context $context): float
	{
		$amount = 0;
		$refunds = $this->transactionService->getRefundEntityCollectionByTransactionId($transactionId, $context);
		foreach ($refunds as $refund) {
			$amount += floatval($refund->getData()['amount'] ?? 0);
		}

		return (float) (string) $amount;
	}

	/**
	 * @param string   $orderId
	 * @param Context  $context
	 * @param callable $operation
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	private function executeLocked(string $orderId, Context $context, callable $operation)
	{
		//$this->connection->setTransactionIsolation(TransactionIsolationLevel::READ_COMMITTED);
		//$this->connection->beginTransaction();
		try {

			$data = [
				'id'                         => $orderId,
				'vrpayment_lock' => date('Y-m-d H:i:s'),
			];

			$order = $this->container->get('order.repository')->search(new Criteria([$orderId]), $context)->first();

			if(empty($order)){
				throw CartException::orderNotFound($orderId);
			}

			$this->container->get('order.repository')->upsert([$data], $context);

			$result = $operation();

			//$this->connection->commit();
			return $result;
		} catch (\Exception $exception) {
			//$this->connection->rollBack();
			throw $exception;
		}
	}

	/**
	 * @param String                           $orderId
	 * @param \Shopware\Core\Framework\Context $context
	 *
	 * @return OrderTransactionEntity
	 * @deprecated 6.1.8 No longer used by internal code and not recommended.
	 * @see WebHookTransactionStrategy
	 */
	private function getOrderTransaction(String $orderId, Context $context): OrderTransactionEntity
	{
		return $this->getOrderEntity($orderId, $context)->getTransactions()->last();
	}

	/**
	 * Get order
	 *
	 * @param String                           $orderId
	 * @param \Shopware\Core\Framework\Context $context
	 *
	 * @return \Shopware\Core\Checkout\Order\OrderEntity
	 * @deprecated 6.1.8 No longer used by internal code and not recommended.
	 * @see WebHookTransactionStrategy
	 */
	private function getOrderEntity(string $orderId, Context $context): OrderEntity
	{
		if (is_null($this->orderEntity)) {
			$criteria = (new Criteria([$orderId]))
				->addAssociations(['deliveries', 'transactions']);
			$criteria->getAssociation('transactions')
				->addSorting(new FieldSorting('createdAt', FieldSorting::ASCENDING));

			try {
				$this->orderEntity = $this->container->get('order.repository')->search(
					$criteria,
					$context
				)->first();
				if (is_null($this->orderEntity)) {
					throw CartException::orderNotFound($orderId);
				}
			} catch (\Exception $e) {
				throw CartException::orderNotFound($orderId);
			}
		}

		return $this->orderEntity;
	}

	/**
	 * Handle VRPayment Transaction callback
	 *
	 * @param \VRPaymentPayment\Core\Api\WebHooks\Struct\WebHookRequest $callBackData
	 * @param \Shopware\Core\Framework\Context                                      $context
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @deprecated 6.1.8 No longer used by internal code and not recommended.
	 * @see WebHookTransactionStrategy
	 */
	private function updateTransaction(WebHookRequest $callBackData, Context $context): Response
	{
		$status = Response::HTTP_UNPROCESSABLE_ENTITY;

		try {
			/**
			 * @var \VRPayment\Sdk\Model\Transaction $transaction
			 * @var \Shopware\Core\Checkout\Order\OrderEntity    $order
			 */
			$transaction = $this->settings->getApiClient()
										  ->getTransactionService()
										  ->read($callBackData->getSpaceId(), $callBackData->getEntityId());
			$orderId     = $transaction->getMetaData()[TransactionPayload::VRPAYMENT_METADATA_ORDER_ID];
			if(!empty($orderId) && !$transaction->getParent()) {
				$this->executeLocked($orderId, $context, function () use ($orderId, $transaction, $context, $callBackData) {
					$this->transactionService->upsert($transaction, $context);
					$orderTransactionId = $transaction->getMetaData()[TransactionPayload::VRPAYMENT_METADATA_ORDER_TRANSACTION_ID];
					$orderTransaction   = $this->getOrderTransaction($orderId, $context);
					$this->logger->info("OrderId: {$orderId} Current state: {$orderTransaction->getStateMachineState()?->getTechnicalName()}");

					if (!in_array(
						$orderTransaction->getStateMachineState()?->getTechnicalName(),
						$this->transactionFinalStates
					)) {
						switch ($transaction->getState()) {
							case TransactionState::FAILED:
								$this->orderTransactionStateHandler->fail($orderTransactionId, $context);
								$this->unholdAndCancelDelivery($orderId, $context);
								break;
							case TransactionState::DECLINE:
							case TransactionState::VOIDED:
								$this->orderTransactionStateHandler->cancel($orderTransactionId, $context);
								$this->unholdAndCancelDelivery($orderId, $context);
								break;
							case TransactionState::FULFILL:
								$this->unholdDelivery($orderId, $context);
								break;
							case TransactionState::AUTHORIZED:
								$this->orderTransactionStateHandler->process($orderTransactionId, $context);
								$this->sendEmail($transaction, $context);
								break;
							default:
								break;
						}
					}

				});
			}
			$status = Response::HTTP_OK;
		} catch (CartException $exception) {
			$status = Response::HTTP_OK;
			$this->logger->info(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage(), $callBackData->jsonSerialize());
		} catch (IllegalTransitionException $exception) {
			$status = Response::HTTP_OK;
			$this->logger->info(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage(), $callBackData->jsonSerialize());
		} catch (\Exception $exception) {
			$this->logger->critical(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage(), $callBackData->jsonSerialize());
		}

		return new JsonResponse(['data' => $callBackData->jsonSerialize()], $status);
	}

	/**
	 * @param \VRPayment\Sdk\Model\Transaction $transaction
	 * @param \Shopware\Core\Framework\Context             $context
	 * @deprecated 6.1.8 No longer used by internal code and not recommended.
	 */
	protected function sendEmail(Transaction $transaction, Context $context): void
	{
		$orderId = $transaction->getMetaData()[TransactionPayload::VRPAYMENT_METADATA_ORDER_ID];
		if ($this->settings->isEmailEnabled() && in_array($transaction->getState(), $this->vrpaymentTransactionSuccessStates)) {
			$this->orderMailService->send($orderId, $context);
		}
	}

	/**
	 * Handle VRPayment TransactionInvoice callback
	 *
	 * @param \VRPaymentPayment\Core\Api\WebHooks\Struct\WebHookRequest $callBackData
	 * @param \Shopware\Core\Framework\Context                                      $context
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @deprecated 6.1.8 No longer used by internal code and not recommended.
	 * @see WebHookTransactionInvoiceStrategy
	 */
	public function updateTransactionInvoice(WebHookRequest $callBackData, Context $context): Response
	{
		$status = Response::HTTP_UNPROCESSABLE_ENTITY;

		try {
			/**
			 * @var \VRPayment\Sdk\Model\Transaction        $transaction
			 * @var TransactionInvoice $transactionInvoice
			 */
			$transactionInvoice = $this->settings->getApiClient()->getTransactionInvoiceService()
												 ->read($callBackData->getSpaceId(), $callBackData->getEntityId());
			$orderId            = $transactionInvoice->getCompletion()
													 ->getLineItemVersion()
													 ->getTransaction()
													 ->getMetaData()[TransactionPayload::VRPAYMENT_METADATA_ORDER_ID];
			if(!empty($orderId)) {
				$this->executeLocked($orderId, $context, function () use ($orderId, $transactionInvoice, $context) {

					$orderTransactionId = $transactionInvoice->getCompletion()
															 ->getLineItemVersion()
															 ->getTransaction()
															 ->getMetaData()[TransactionPayload::VRPAYMENT_METADATA_ORDER_TRANSACTION_ID];
					$orderTransaction   = $this->getOrderTransaction($orderId, $context);
					$this->updatePriceIfAdditionalItemsExist($transactionInvoice, $orderTransaction, $context);

					if (!in_array(
						$orderTransaction->getStateMachineState()?->getTechnicalName(),
						$this->transactionFinalStates
					)) {
						switch ($transactionInvoice->getState()) {
							case TransactionInvoiceState::DERECOGNIZED:
								$this->orderTransactionStateHandler->cancel($orderTransactionId, $context);
								break;
							case TransactionInvoiceState::NOT_APPLICABLE:
							case TransactionInvoiceState::PAID:
								$this->orderTransactionStateHandler->paid($orderTransactionId, $context);
								$this->unholdDelivery($orderTransactionId, $context);
								break;
							default:
								break;
						}
					}
				});
			}
			$status = Response::HTTP_OK;
		} catch (CartException $exception) {
			$status = Response::HTTP_OK;
			$this->logger->info(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage(), $callBackData->jsonSerialize());
		} catch (IllegalTransitionException $exception) {
			$status = Response::HTTP_OK;
			$this->logger->info(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage(), $callBackData->jsonSerialize());
		} catch (\Exception $exception) {
			$this->logger->critical(__CLASS__ . ' : ' . __FUNCTION__ . ' : ' . $exception->getMessage(), $callBackData->jsonSerialize());
		}

		return new JsonResponse(['data' => $callBackData->jsonSerialize()], $status);
	}

	/**
	 * Updates order table field price only if there are additional items added from portal side
	 *
	 * @param TransactionInvoice $transactionInvoice
	 * @param OrderTransactionEntity $orderTransaction
	 * @param Context $context
	 * @return void
	 */
	private function updatePriceIfAdditionalItemsExist(TransactionInvoice $transactionInvoice, OrderTransactionEntity $orderTransaction, Context $context): void
	{
		$completionLineItems = $transactionInvoice->getCompletion()->getLineItems();
		$lineItems = $transactionInvoice->getLineItems();

		if (count($completionLineItems) !== count($lineItems)) {
			$this->transactionService->updateOrderTotalPriceByInvoiceTotal(
				$orderTransaction->getOrderId(),
				$transactionInvoice->getOutstandingAmount(),
				$context
			);
		}
	}

	/**
	 * Hold delivery
	 *
	 * @param string                           $orderId
	 * @param \Shopware\Core\Framework\Context $context
	 */
	private function unholdDelivery(string $orderId, Context $context): void
	{
		try {
			/**
			 * @var OrderDeliveryStateHandler $orderDeliveryStateHandler
			 */
			$order = $this->getOrderEntity($orderId, $context);
			/**
			 * @var OrderDeliveryEntity $orderDelivery
			 */
			$orderDelivery = $order->getDeliveries()?->last();
			
			if (null === $orderDelivery) {
				return;
			}
			if ($orderDelivery->getStateMachineState()?->getTechnicalName() !== OrderDeliveryStateHandler::STATE_HOLD){
				return;
			}
			$orderDeliveryStateHandler = $this->container->get(OrderDeliveryStateHandler::class);
			$orderDeliveryStateHandler->unhold($orderDelivery->getId(), $context);
		} catch (\Exception $exception) {
			$this->logger->info($exception->getMessage(), $exception->getTrace());
		}
	}

	/**
	 * Unhold and cancel delivery
	 *
	 * @param string                           $orderId
	 * @param \Shopware\Core\Framework\Context $context
	 */
	private function unholdAndCancelDelivery(string $orderId, Context $context): void
	{
		$order = $this->getOrderEntity($orderId, $context);
		try {
			$this->orderService->orderStateTransition(
				$order->getId(),
				StateMachineTransitionActions::ACTION_CANCEL,
				new ParameterBag(),
				$context
			);
		} catch (\Exception $exception) {
			$this->logger->info($exception->getMessage(), $exception->getTrace());
		}

		try {
			/**
			 * @var OrderDeliveryStateHandler $orderDeliveryStateHandler
			 */
			$orderDeliveryStateHandler = $this->container->get(OrderDeliveryStateHandler::class);
			/**
			 * @var OrderDeliveryEntity $orderDelivery
			 */
			$orderDelivery = $order->getDeliveries()?->last();
			
			if (null === $orderDelivery) {
				return;
			}
			if ($orderDelivery->getStateMachineState()?->getTechnicalName() !== OrderDeliveryStateHandler::STATE_HOLD){
				return;
			}
			$orderDeliveryId = $orderDelivery->getId();
			$orderDeliveryStateHandler->unhold($orderDeliveryId, $context);
			$orderDeliveryStateHandler->cancel($orderDeliveryId, $context);
		} catch (\Exception $exception) {
			$this->logger->info($exception->getMessage(), $exception->getTrace());
		}
	}
}
