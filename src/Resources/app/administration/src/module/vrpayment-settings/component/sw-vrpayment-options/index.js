/* global Shopware */

import template from './index.html.twig';
import constants from '../../page/vrpayment-settings/configuration-constants'

const {Component, Mixin} = Shopware;

Component.register('sw-vrpayment-options', {
	template: template,

	name: 'VRPaymentOptions',

	mixins: [
		Mixin.getByName('notification')
	],

	props: {
		actualConfigData: {
			type: Object,
			required: true
		},
		allConfigs: {
			type: Object,
			required: true
		},
		selectedSalesChannelId: {
			required: true
		},
		isLoading: {
			type: Boolean,
			required: true
		}
	},

	data() {
		return {
			...constants
		};
	},

	computed: {
		integrationOptions() {
			return [
				{
					id: 'iframe',
					name: this.$tc('vrpayment-settings.settingForm.options.integration.options.iframe')
				},
				{
					id: 'payment_page',
					name: this.$tc('vrpayment-settings.settingForm.options.integration.options.payment_page')
				}
			];
		}
	},

	methods: {
		checkTextFieldInheritance(value) {
			if (typeof value !== 'string') {
				return true;
			}

			return value.length <= 0;
		},

		checkNumberFieldInheritance(value) {
			if (typeof value !== 'number') {
				return true;
			}

			return value.length <= 0;
		},

		checkBoolFieldInheritance(value) {
			return typeof value !== 'boolean';
		}
	}
});
