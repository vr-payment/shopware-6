<?php declare(strict_types=1);

namespace VRPaymentPayment\Core\Api\Transaction\Controller;

use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\Log\Package;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\{
	HttpFoundation\JsonResponse,
	HttpFoundation\Request,
	HttpFoundation\Response,
	Routing\Attribute\Route};
use VRPayment\Sdk\{
	Model\TransactionState};
use VRPaymentPayment\Core\Settings\Service\SettingsService;

/**
 * Class TransactionVoidController
 *
 * @package VRPaymentPayment\Core\Api\Transaction\Controller
 *
 */
#[Package('sales-channel')]
#[Route(defaults: ['_routeScope' => ['api']])]
class TransactionVoidController extends AbstractController {

	/**
	 * @var \VRPaymentPayment\Core\Settings\Service\SettingsService
	 */
	protected $settingsService;

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * TransactionVoidController constructor.
	 *
	 * @param \VRPaymentPayment\Core\Settings\Service\SettingsService $settingsService
	 */
	public function __construct(SettingsService $settingsService)
	{
		$this->settingsService = $settingsService;
	}

	/**
	 * @param \Psr\Log\LoggerInterface $logger
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
	 * @return \Symfony\Component\HttpFoundation\JsonResponse
	 * @throws \VRPayment\Sdk\ApiException
	 * @throws \VRPayment\Sdk\Http\ConnectionException
	 * @throws \VRPayment\Sdk\VersioningException
	 *
	 */
    #[Route("/api/_action/vrpayment/transaction-void/create-transaction-void/",
    	name: "api.action.vrpayment.transaction-void.create-transaction-void",
        methods: ['POST'])]
	public function createTransactionVoid(Request $request): JsonResponse
	{
		$salesChannelId = $request->request->get('salesChannelId');
		$transactionId  = $request->request->get('transactionId');

		$settings  = $this->settingsService->getSettings($salesChannelId);
		$apiClient = $settings->getApiClient();

		$transaction = $apiClient->getTransactionService()->read($settings->getSpaceId(), $transactionId);
		if ($transaction->getState() == TransactionState::AUTHORIZED) {
			$transactionVoid = $apiClient->getTransactionVoidService()->voidOnline($settings->getSpaceId(), $transaction->getId());
			return new JsonResponse(strval($transactionVoid), Response::HTTP_OK, [], true);
		}

		return new JsonResponse(
			[
				'message' => strtr('Transaction is in state {state}, it can not be completed at this time.', ['{state}' => $transaction->getState()]),
			],
			Response::HTTP_NOT_ACCEPTABLE
		);
	}
}
