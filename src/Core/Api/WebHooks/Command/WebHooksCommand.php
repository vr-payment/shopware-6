<?php declare(strict_types=1);


namespace VRPaymentPayment\Core\Api\WebHooks\Command;

use Symfony\Component\{
	Console\Command\Command,
    Console\Attribute\AsCommand,
	Console\Input\InputInterface,
	Console\Output\OutputInterface};
use VRPaymentPayment\Core\Api\WebHooks\Service\WebHooksService;

/**
 * Class WebHooksCommand
 *
 * @package VRPaymentPayment\Core\Api\WebHooks\Command
 */
#[AsCommand(name: 'vrpayment:webhooks:install')]
class WebHooksCommand extends Command {

	/**
	 * @var \VRPaymentPayment\Core\Api\WebHooks\Service\WebHooksService
	 */
	protected $webHooksService;

	/**
	 * WebHooksCommand constructor.
	 *
	 * @param \VRPaymentPayment\Core\Api\WebHooks\Service\WebHooksService $webHooksService
	 */
	public function __construct(WebHooksService $webHooksService)
	{
		parent::__construct();
		$this->webHooksService = $webHooksService;
	}

	/**
	 * @param \Symfony\Component\Console\Input\InputInterface   $input
	 * @param \Symfony\Component\Console\Output\OutputInterface $output
	 *
	 * @return int
	 * @throws \VRPayment\Sdk\ApiException
	 * @throws \VRPayment\Sdk\Http\ConnectionException
	 * @throws \VRPayment\Sdk\VersioningException
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$output->writeln('Install VRPaymentPayment webhooks...');
		$this->webHooksService->install();
		return 0;
	}

	/**
	 * Configures the current command.
	 */
	protected function configure()
	{
		$this->setDescription('Install VRPaymentPayment webhooks.')
			 ->setHelp('This command installs VRPaymentPayment webhooks.');
	}

}
