<?php declare(strict_types=1);

namespace VRPaymentPayment\Core\Storefront\Framework\Cookie;

use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

/**
 * Class VRPaymentCookieProvider
 *
 * @package VRPaymentPayment\Core\Storefront\Framework\Cookie
 */
class VRPaymentCookieProvider implements CookieProviderInterface {
	/**
	 * @var CookieProviderInterface
	 */
	private $original;

	public function __construct(CookieProviderInterface $cookieProvider)
	{
		$this->original = $cookieProvider;
	}

	public function getCookieGroups(): array
	{
		$cookies = $this->original->getCookieGroups();

		foreach ($cookies as &$cookie) {
			if (!\is_array($cookie)) {
				continue;
			}

			if (!$this->isRequiredCookieGroup($cookie)) {
				continue;
			}

			if (!\array_key_exists('entries', $cookie)) {
				continue;
			}

			$cookie['entries'][] = [
				'snippet_name' => 'vrpayment.cookie.name',
				'cookie'       => 'vrpayment-cookie-key',
			];
		}

		return $cookies;
	}

	private function isRequiredCookieGroup(array $cookie): bool
	{
		return (\array_key_exists('isRequired', $cookie) && $cookie['isRequired'] === true)
			&& (\array_key_exists('snippet_name', $cookie) && $cookie['snippet_name'] === 'cookie.groupRequired');
	}
}