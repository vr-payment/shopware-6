/* global Shopware */

import './acl';
import './page/vrpayment-settings';
import './component/sw-vrpayment-credentials';
import './component/sw-vrpayment-options';
import './component/sw-vrpayment-settings-icon';
import './component/sw-vrpayment-storefront-options';
import './component/sw-vrpayment-advanced-options';

import deDE from './snippet/de-DE.json';
import enGB from './snippet/en-GB.json';
import frFR from './snippet/fr-FR.json';
import itIT from './snippet/it-IT.json';

const {Module} = Shopware;

Module.register('vrpayment-settings', {
	type: 'plugin',
	name: 'VRPayment',
	title: 'vrpayment-settings.general.descriptionTextModule',
	description: 'vrpayment-settings.general.descriptionTextModule',
	color: '#28d8ff',
	icon: 'default-action-settings',
	version: '1.0.1',
	targetVersion: '1.0.1',

    snippets: {
        'de-DE': deDE,
        'en-GB': enGB,
        'fr-FR': frFR,
        'it-IT': itIT,
    },

	routes: {
		index: {
			component: 'vrpayment-settings',
			path: 'index',
			meta: {
				parentPath: 'sw.settings.index',
				privilege: 'vrpayment.viewer'
			},
			props: {
                default: (route) => {
                    return {
                        hash: route.params.hash,
                    };
                },
            },
		}
	},

	settingsItem: {
		group: 'plugins',
		to: 'vrpayment.settings.index',
		iconComponent: 'sw-vrpayment-settings-icon',
		backgroundEnabled: true,
		privilege: 'vrpayment.viewer'
	}

});
