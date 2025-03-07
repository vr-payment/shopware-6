<?php declare(strict_types=1);

namespace VRPaymentPayment\Core\Api\Transaction\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\{
	Framework\Context,
	Framework\Log\Package};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\{
	HttpFoundation\HeaderUtils,
	HttpFoundation\JsonResponse,
	HttpFoundation\Request,
	HttpFoundation\Response,
	Routing\Attribute\Route};
use VRPaymentPayment\{
	Core\Api\Transaction\Service\TransactionService,
	Core\Settings\Service\SettingsService};

/**
 * Class TransactionController
 *
 * @package VRPaymentPayment\Core\Api\Transaction\Controller
 *
 */
#[Package('sales-channel')]
#[Route(defaults: ['_routeScope' => ['api']])]
class TransactionController extends AbstractController {

	/**
	 * @var \VRPaymentPayment\Core\Settings\Service\SettingsService
	 */
	protected $settingsService;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var \VRPaymentPayment\Core\Api\Transaction\Service\TransactionService
	 */
	protected $transactionService;

	/**
	 * TransactionController constructor.
	 *
	 * @param \VRPaymentPayment\Core\Settings\Service\SettingsService           $settingsService
	 * @param \VRPaymentPayment\Core\Api\Transaction\Service\TransactionService $transactionService
	 */
	public function __construct(SettingsService $settingsService, TransactionService $transactionService)
	{
		$this->settingsService    = $settingsService;
		$this->transactionService = $transactionService;
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
	 * @param \Symfony\Component\HttpFoundation\Request $request
	 * @param \Shopware\Core\Framework\Context          $context
	 *
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 */
    #[Route("/api/_action/vrpayment/transaction/get-transaction-data/",
    	name: "api.action.vrpayment.transaction.get-transaction-data",
        methods: ['POST'])]
	public function getTransactionData(Request $request, Context $context): JsonResponse
	{
		$transactionId = $request->request->get('transactionId');

		$transaction      = $this->transactionService->getByTransactionId(intval($transactionId), $context);
		$refundCollection = $this->transactionService->getRefundEntityCollectionByTransactionId(intval($transactionId), $context);

		$refunds = [];
		foreach ($refundCollection as $refundEntity) {
			$refunds[] = $refundEntity ? $refundEntity->getData() : [];
		}

		return new JsonResponse([
			'refunds'      => $refunds,
			'transactions' => [$transaction ? $transaction->getData() : []],
		]);
	}

	/**
	 * @param string $salesChannelId
	 * @param int    $transactionId
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \VRPayment\Sdk\ApiException
	 * @throws \VRPayment\Sdk\Http\ConnectionException
	 * @throws \VRPayment\Sdk\VersioningException
	 *
	 */
    #[Route("/api/_action/vrpayment/transaction/get-invoice-document/{salesChannelId}/{transactionId}",
    	name: "api.action.vrpayment.transaction.get-invoice-document",
        methods: ['GET'],
        defaults: ["csrf_protected" => false, "auth_required" => false])]
	public function getInvoiceDocument(string $salesChannelId, int $transactionId): Response
	{
		$settings  = $this->settingsService->getSettings($salesChannelId);
		$apiClient = $settings->getApiClient();

		$invoiceDocument = $apiClient->getTransactionService()->getInvoiceDocument($settings->getSpaceId(), $transactionId);
		$forceDownload   = true;
		$filename        = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '_', $invoiceDocument->getTitle()) . '.pdf';
		$disposition     = HeaderUtils::makeDisposition(
			$forceDownload ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
			$filename,
			$filename
		);
		$response        = new Response(base64_decode($invoiceDocument->getData()));
		$response->headers->set('Content-Type', $invoiceDocument->getMimeType());
		$response->headers->set('Content-Disposition', $disposition);

		return $response;
	}

	/**
	 * @param string $salesChannelId
	 * @param int    $transactionId
	 *
	 * @return \Symfony\Component\HttpFoundation\Response
	 * @throws \VRPayment\Sdk\ApiException
	 * @throws \VRPayment\Sdk\Http\ConnectionException
	 * @throws \VRPayment\Sdk\VersioningException
	 *
	 */
    #[Route("/api/_action/vrpayment/transaction/get-packing-slip/{salesChannelId}/{transactionId}",
    	name: "api.action.vrpayment.transaction.get-packing-slip",
        methods: ['GET'],
        defaults: ["csrf_protected" => false, "auth_required" => false])]
	public function getPackingSlip(string $salesChannelId, int $transactionId): Response
	{
		$settings  = $this->settingsService->getSettings($salesChannelId);
		$apiClient = $settings->getApiClient();

		$invoiceDocument = $apiClient->getTransactionService()->getPackingSlip($settings->getSpaceId(), $transactionId);
		$forceDownload   = true;
		$filename        = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '_', $invoiceDocument->getTitle()) . '.pdf';
		$disposition     = HeaderUtils::makeDisposition(
			$forceDownload ? HeaderUtils::DISPOSITION_ATTACHMENT : HeaderUtils::DISPOSITION_INLINE,
			$filename,
			$filename
		// only printable ascii

		);
		$response        = new Response(base64_decode($invoiceDocument->getData()));
		$response->headers->set('Content-Type', $invoiceDocument->getMimeType());
		$response->headers->set('Content-Disposition', $disposition);

		return $response;
	}
}
