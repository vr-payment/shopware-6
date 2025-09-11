/* global Shopware */

const ApiService = Shopware.Classes.ApiService;

/**
 * @class VRPaymentPayment\Core\Api\WebHooks\Controller\WebHookController
 */
class VRPaymentWebHookRegisterService extends ApiService {

	/**
	 * VRPaymentWebHookRegisterService
	 *
	 * @param httpClient
	 * @param loginService
	 * @param apiEndpoint
	 */
	constructor(httpClient, loginService, apiEndpoint = 'vrpayment') {
		super(httpClient, loginService, apiEndpoint);
	}

	/**
	 * Register a webhook
	 *
	 * @param {String|null} salesChannelId
	 * @return {*}
	 */
	registerWebHook(salesChannelId) {

		const headers = this.getBasicHeaders();
		const apiRoute = `${Shopware.Context.api.apiPath}/_action/${this.getApiBasePath()}/webHook/register/${salesChannelId}`;

		return this.httpClient.post(
			apiRoute,
			{},
			{
				headers: headers
			}
		).then((response) => {
			return ApiService.handleResponse(response);
		});
	}
}

export default VRPaymentWebHookRegisterService;
