/* global Shopware */

const ApiService = Shopware.Classes.ApiService;

/**
 * @class VRPaymentPayment\Core\Api\Config\Controller\ConfigurationController
 */
class VRPaymentConfigurationService extends ApiService {

	/**
	 * VRPaymentConfigurationService constructor
	 *
	 * @param httpClient
	 * @param loginService
	 * @param apiEndpoint
	 */
	constructor(httpClient, loginService, apiEndpoint = 'vrpayment') {
		super(httpClient, loginService, apiEndpoint);
	}

	/**
	 * Register web hooks
	 *
	 * @param {String|null} salesChannelId
	 * @return {*}
	 */
	registerWebHooks(salesChannelId = null) {

		const headers = this.getBasicHeaders();
		const apiRoute = `${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/configuration/register-web-hooks`;

		return this.httpClient.post(
			apiRoute,
			{
				salesChannelId: salesChannelId
			},
			{
				headers: headers
			}
		).then((response) => {
			return ApiService.handleResponse(response);
		});
	}

	/**
	 * Test API connection
	 *
	 * @param {int|null} spaceId
	 * @param {int|null} userId
	 * @param {String|null} applicationId
	 * @return {*}
	 */
	checkApiConnection(spaceId = null, userId = null, applicationId = null) {

		const headers = this.getBasicHeaders();
		const apiRoute = `${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/configuration/check-api-connection`;

		return this.httpClient.post(
			apiRoute,
			{
				spaceId: spaceId,
				userId: userId,
				applicationId: applicationId
			},
			{
				headers: headers
			}
		).then((response) => {
			return ApiService.handleResponse(response);
		});
	}

	/**
	 * Set's the default payment method to VRPayment for the given salesChannel id.
	 *
	 * @param {String|null} salesChannelId
	 *
	 * @returns {Promise}
	 */
	setVRPaymentAsSalesChannelPaymentDefault(salesChannelId = null) {

		const headers = this.getBasicHeaders();
		const apiRoute = `${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/configuration/set-vrpayment-as-sales-channel-payment-default`;

		return this.httpClient.post(
			apiRoute,
			{
				salesChannelId: salesChannelId
			},
			{
				headers: headers
			}
		).then((response) => {
			return ApiService.handleResponse(response);
		});
	}

	/**
	 *
	 * @param salesChannelId
	 * @return {Promise}
	 */
	synchronizePaymentMethodConfiguration(salesChannelId = null) {
		const headers = this.getBasicHeaders();
		const apiRoute = `${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/configuration/synchronize-payment-method-configuration`;

		return this.httpClient.post(
			apiRoute,
			{
				salesChannelId: salesChannelId
			},
			{
				headers: headers
			}
		).then((response) => {
			return ApiService.handleResponse(response);
		});
	}

	/**
	 *
	 * @return {*}
	 */
	installOrderDeliveryStates() {
		const headers = this.getBasicHeaders();
		const apiRoute = `${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/configuration/install-order-delivery-states`;

		return this.httpClient.post(
			apiRoute,
			{
			},
			{
				headers: headers
			}
		).then((response) => {
			return ApiService.handleResponse(response);
		});
	}
}

export default VRPaymentConfigurationService;
