<?php declare(strict_types=1);

namespace VRPaymentPayment\Core\Api\WebHooks\Service;

use Psr\Log\LoggerInterface;
use Shopware\Core\PlatformRequest;
use Symfony\Component\{
	Routing\Generator\UrlGeneratorInterface,
	Routing\RouterInterface,};
use VRPayment\Sdk\{
	ApiClient,
	Model\CreationEntityState,
	Model\CriteriaOperator,
	Model\EntityQuery,
	Model\EntityQueryFilter,
	Model\EntityQueryFilterType,
	Model\RefundState,
	Model\TransactionInvoiceState,
	Model\TransactionState,
	Model\WebhookListener,
	Model\WebhookListenerCreate,
	Model\WebhookUrl,
	Model\WebhookUrlCreate,};
use VRPaymentPayment\Core\{
	Api\WebHooks\Struct\Entity,
	Settings\Service\SettingsService};

/**
 * Class WebHooksService
 *
 * @package VRPaymentPayment\Core\Api\WebHooks\Service
 */
class WebHooksService {
	
	public const TRANSACTION = 1472041829003;
	public const TRANSACTION_INVOICE = 1472041816898;
	public const REFUND = 1472041839405;
	public const PAYMENT_METHOD_CONFIGURATION = 1472041857405;

	/**
	 * @var \VRPaymentPayment\Core\Settings\Service\SettingsService
	 */
	protected $settingsService;

	/**
	 * @var \Symfony\Component\Routing\RouterInterface
	 */
	protected $router;

	/**
	 * @var \VRPayment\Sdk\ApiClient
	 */
	protected $apiClient;

	/**
	 * Space Id
	 *
	 * @var int
	 */
	protected $spaceId;

	/**
	 * WebHook configs
	 */
	protected $webHookEntitiesConfig = [];

	/**
	 * WebHook configs
	 */
	protected $webHookEntityArrayConfig = [
		/**
		 * Transaction WebHook Entity Id
		 *
		 * @link https://www.vr-payment.de//doc/api/webhook-entity/view/1472041829003
		 */
		[
			'id'                => WebHooksService::TRANSACTION,
			'name'              => 'Shopware6::WebHook::Transaction',
			'states'            => [
				TransactionState::AUTHORIZED,
				TransactionState::COMPLETED,
				TransactionState::CONFIRMED,
				TransactionState::DECLINE,
				TransactionState::FAILED,
				TransactionState::FULFILL,
				TransactionState::PROCESSING,
				TransactionState::VOIDED,
			],
			'notifyEveryChange' => false,
		],
		/**
		 * Transaction Invoice WebHook Entity Id
		 *
		 * @link https://www.vr-payment.de//doc/api/webhook-entity/view/1472041816898
		 */
		[
			'id'                => WebHooksService::TRANSACTION_INVOICE,
			'name'              => 'Shopware6::WebHook::Transaction Invoice',
			'states'            => [
				TransactionInvoiceState::NOT_APPLICABLE,
				TransactionInvoiceState::PAID,
				TransactionInvoiceState::DERECOGNIZED,
			],
			'notifyEveryChange' => false,
		],
		/**
		 * Refund WebHook Entity Id
		 *
		 * @link https://www.vr-payment.de//doc/api/webhook-entity/view/1472041839405
		 */
		[
			'id'                => WebHooksService::REFUND,
			'name'              => 'Shopware6::WebHook::Refund',
			'states'            => [
				RefundState::FAILED,
				RefundState::SUCCESSFUL,
			],
			'notifyEveryChange' => false,
		],
		/**
		 * Payment Method Configuration Id
		 *
		 * @link https://www.vr-payment.de//doc/api/webhook-entity/view/1472041857405
		 */
		[
			'id'                => WebHooksService::PAYMENT_METHOD_CONFIGURATION,
			'name'              => 'Shopware6::WebHook::Payment Method Configuration',
			'states'            => [
				CreationEntityState::ACTIVE,
				CreationEntityState::DELETED,
				CreationEntityState::DELETING,
				CreationEntityState::INACTIVE
			],
			'notifyEveryChange' => true,
		],

	];

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	/**
	 * @var ?string $salesChannelId
	 */
	private $salesChannelId;

	/**
	 * WebHooksService constructor.
	 *
	 * @param \VRPaymentPayment\Core\Settings\Service\SettingsService $settingsService
	 * @param \Symfony\Component\Routing\RouterInterface                          $router
	 */
	public function __construct(SettingsService $settingsService, RouterInterface $router)
	{
		$this->router          = $router;
		$this->settingsService = $settingsService;
		$this->setWebHookEntitiesConfig();
	}

	/**
	 * Set webhook configs
	 */
	protected function setWebHookEntitiesConfig(): void
	{
		foreach ($this->webHookEntityArrayConfig as $item) {
			$this->webHookEntitiesConfig[] = (new Entity())
				->setId((int) $item['id'])
				->setName($item['name'])
				->setStates($item['states'])
				->setNotifyEveryChange($item['notifyEveryChange']);
		}
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
	 * @return \VRPayment\Sdk\ApiClient
	 */
	public function getApiClient(): ApiClient
	{
		return $this->apiClient;
	}

	/**
	 * @param \VRPayment\Sdk\ApiClient $apiClient
	 *
	 * @return \VRPaymentPayment\Core\Api\WebHooks\Service\WebHooksService
	 */
	public function setApiClient(ApiClient $apiClient): WebHooksService
	{
		$this->apiClient = $apiClient;
		return $this;
	}

	/**
	 * @return int
	 */
	public function getSpaceId(): int
	{
		return $this->spaceId;
	}

	/**
	 * @param int $spaceId
	 *
	 * @return \VRPaymentPayment\Core\Api\WebHooks\Service\WebHooksService
	 */
	public function setSpaceId(int $spaceId): WebHooksService
	{
		$this->spaceId = $spaceId;
		return $this;
	}

	/**
	 * Install WebHooks
	 *
	 * @return array
	 * @throws \VRPayment\Sdk\ApiException
	 * @throws \VRPayment\Sdk\Http\ConnectionException
	 * @throws \VRPayment\Sdk\VersioningException
	 */
	public function install(): array
	{
		// Configuration
		$settings = $this->settingsService->getSettings($this->getSalesChannelId());
		$this->setSpaceId($settings->getSpaceId())->setApiClient($settings->getApiClient());

		return $this->installListeners();
	}

	/**
	 * Get sales channel id
	 *
	 * @return string|null
	 */
	public function getSalesChannelId(): ?string
	{
		return $this->salesChannelId;
	}

	/**
	 * Set sales channel id
	 *
	 * @param string|null $salesChannelId
	 *
	 * @return \VRPaymentPayment\Core\Api\WebHooks\Service\WebHooksService
	 */
	public function setSalesChannelId(?string $salesChannelId = null): WebHooksService
	{
		$this->salesChannelId = $salesChannelId;
		return $this;
	}

	/**
	 * Install Listeners
	 *
	 * @return array
	 */
	protected function installListeners(): array
	{
		$this->logger->info('Installing webhooks.');
		$returnValue = [];
		try {
			$webHookUrlId      = $this->getOrCreateWebHookUrl()->getId();
			$installedWebHooks = $this->getInstalledWebHookListeners($webHookUrlId);
			$webHookEntityIds  = array_map(function (WebhookListener $webHook) {
				return $webHook->getEntity();
			}, $installedWebHooks);


			/**
			 * @var \VRPaymentPayment\Core\Api\WebHooks\Struct\Entity $data
			 */
			foreach ($this->webHookEntitiesConfig as $data) {

				if (in_array($data->getId(), $webHookEntityIds)) {
					continue;
				}

				$entity = (new WebhookListenerCreate())
					->setName($data->getName())
					->setEntity($data->getId())
					->setNotifyEveryChange($data->isNotifyEveryChange())
					->setState(CreationEntityState::CREATE)
					->setEntityStates($data->getStates())
					->setEnablePayloadSignatureAndState( true )
					->setUrl($webHookUrlId);

				$returnValue[] = $this->apiClient->getWebhookListenerService()->create($this->spaceId, $entity);
			}
		} catch (\Exception $exception) {
			$this->logger->critical($exception->getTraceAsString());
			return $exception->getTrace();
		}

		return $returnValue;
	}

	/**
	 * Create WebHook URL
	 *
	 * @return WebhookUrl
	 * @throws \VRPayment\Sdk\ApiException
	 * @throws \VRPayment\Sdk\Http\ConnectionException
	 * @throws \VRPayment\Sdk\VersioningException
	 */
	protected function getOrCreateWebHookUrl(): WebhookUrl
	{
		$url = $this->getWebHookCallBackUrl();
		/** @noinspection PhpParamsInspection */
		$entityQueryFilter = (new EntityQueryFilter())
			->setType(EntityQueryFilterType::_AND)
			->setChildren([
				$this->getEntityFilter('state', CreationEntityState::ACTIVE),
				$this->getEntityFilter('url', $url),
			]);

		$query = (new EntityQuery())->setFilter($entityQueryFilter)->setNumberOfEntities(1);

		$webHookUrls = $this->apiClient->getWebhookUrlService()->search($this->spaceId, $query);

		if (!empty($webHookUrls[0])) {
			return $webHookUrls[0];
		}

		/** @noinspection PhpParamsInspection */
		$entity = (new WebhookUrlCreate())
			->setName('Shopware6::WebHookURL')
			->setUrl($url)
			->setState(CreationEntityState::ACTIVE);

		return $this->apiClient->getWebhookUrlService()->create($this->spaceId, $entity);
	}

	/**
	 * Creates and returns a new entity filter.
	 *
	 * @param string $fieldName
	 * @param        $value
	 * @param string $operator
	 *
	 * @return \VRPayment\Sdk\Model\EntityQueryFilter
	 */
	protected function getEntityFilter(string $fieldName, $value, string $operator = CriteriaOperator::EQUALS): EntityQueryFilter
	{
		/** @noinspection PhpParamsInspection */
		return (new EntityQueryFilter())
			->setType(EntityQueryFilterType::LEAF)
			->setOperator($operator)
			->setFieldName($fieldName)
			->setValue($value);
	}

	/**
	 * Get web hook callback url
	 *
	 * @return string
	 */
	protected function getWebHookCallBackUrl(): string
	{
		return $this->router->generate(
			'api.action.vrpayment.webhook.update',
			['salesChannelId' => $this->getSalesChannelId() ?? 'null',],
			UrlGeneratorInterface::ABSOLUTE_URL
		);
	}

	/**
	 * @param int $webHookUrlId
	 *
	 * @return array
	 * @throws \VRPayment\Sdk\ApiException
	 * @throws \VRPayment\Sdk\Http\ConnectionException
	 * @throws \VRPayment\Sdk\VersioningException
	 */
	protected function getInstalledWebHookListeners(int $webHookUrlId): array
	{
		/** @noinspection PhpParamsInspection */
		$entityQueryFilter = (new EntityQueryFilter())
			->setType(EntityQueryFilterType::_AND)
			->setChildren([
				$this->getEntityFilter('state', CreationEntityState::ACTIVE),
				$this->getEntityFilter('url.id', $webHookUrlId),
			]);

		$query = (new EntityQuery())->setFilter($entityQueryFilter);

		return $this->apiClient->getWebhookListenerService()->search($this->spaceId, $query);
	}

}