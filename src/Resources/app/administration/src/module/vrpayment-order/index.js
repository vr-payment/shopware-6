/* global Shopware */

import './extension/sw-order';
import './page/vrpayment-order-detail';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';
import frFR from './snippet/fr-FR.json';
import itIT from './snippet/it-IT.json';

const {Module} = Shopware;

Module.register('vrpayment-order', {
	type: 'plugin',
	name: 'VRPayment',
	title: 'vrpayment-order.general.title',
	description: 'vrpayment-order.general.descriptionTextModule',
	version: '1.0.1',
	targetVersion: '1.0.1',
	color: '#2b52ff',

	snippets: {
		'de-DE': deDE,
		'en-GB': enGB,
		'fr-FR': frFR,
		'it-IT': itIT
	},

	routeMiddleware(next, currentRoute) {
		if (currentRoute.name === 'sw.order.detail') {
			currentRoute.children.push({
				component: 'vrpayment-order-detail',
				name: 'vrpayment.order.detail',
				isChildren: true,
				path: '/sw/order/vrpayment/detail/:id'
			});
		}
		next(currentRoute);
	}
});
