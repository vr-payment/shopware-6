/* global Shopware */

import VRPaymentConfigurationService from '../core/service/api/vrpayment-configuration.service';
import VRPaymentRefundService from '../core/service/api/vrpayment-refund.service';
import VRPaymentTransactionService from '../core/service/api/vrpayment-transaction.service';
import VRPaymentTransactionCompletionService
	from '../core/service/api/vrpayment-transaction-completion.service';
import VRPaymentTransactionVoidService
	from '../core/service/api/vrpayment-transaction-void.service';


const {Application} = Shopware;

// noinspection JSUnresolvedFunction
Application.addServiceProvider('VRPaymentConfigurationService', (container) => {
	const initContainer = Application.getContainer('init');
	return new VRPaymentConfigurationService(initContainer.httpClient, container.loginService);
});

// noinspection JSUnresolvedFunction
Application.addServiceProvider('VRPaymentRefundService', (container) => {
	const initContainer = Application.getContainer('init');
	return new VRPaymentRefundService(initContainer.httpClient, container.loginService);
});

// noinspection JSUnresolvedFunction
Application.addServiceProvider('VRPaymentTransactionService', (container) => {
	const initContainer = Application.getContainer('init');
	return new VRPaymentTransactionService(initContainer.httpClient, container.loginService);
});

// noinspection JSUnresolvedFunction
Application.addServiceProvider('VRPaymentTransactionCompletionService', (container) => {
	const initContainer = Application.getContainer('init');
	return new VRPaymentTransactionCompletionService(initContainer.httpClient, container.loginService);
});

// noinspection JSUnresolvedFunction
Application.addServiceProvider('VRPaymentTransactionVoidService', (container) => {
	const initContainer = Application.getContainer('init');
	return new VRPaymentTransactionVoidService(initContainer.httpClient, container.loginService);
});