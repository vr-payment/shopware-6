/* global Shopware */

import template from './sw-order.html.twig';
import './sw-order.scss';

const {Component, Context} = Shopware;
const Criteria = Shopware.Data.Criteria;

const vrpaymentFormattedHandlerIdentifier = 'handler_vrpaymentpayment_vrpaymentpaymenthandler';

Component.override('sw-order-detail', {
	template,

	data() {
		return {
			isVRPaymentPayment: false
		};
	},

	computed: {
		isEditable() {
			return !this.isVRPaymentPayment || this.$route.name !== 'vrpayment.order.detail';
		},
		showTabs() {
			return true;
		}
	},

	watch: {
		orderId: {
			deep: true,
			handler() {
				if (!this.orderId) {
					this.setIsVRPaymentPayment(null);
					return;
				}

				const orderRepository = this.repositoryFactory.create('order');
				const orderCriteria = new Criteria(1, 1);
				orderCriteria.addAssociation('transactions');

				orderRepository.get(this.orderId, Context.api, orderCriteria).then((order) => {
					if (
						(order.amountTotal <= 0) ||
						(order.transactions.length <= 0) ||
						!order.transactions[0].paymentMethodId
					) {
						this.setIsVRPaymentPayment(null);
						return;
					}

					const paymentMethodId = order.transactions[0].paymentMethodId;
					if (paymentMethodId !== undefined && paymentMethodId !== null) {
						this.setIsVRPaymentPayment(paymentMethodId);
					}
				});
			},
			immediate: true
		}
	},

	methods: {
		setIsVRPaymentPayment(paymentMethodId) {
			if (!paymentMethodId) {
				return;
			}
			const paymentMethodRepository = this.repositoryFactory.create('payment_method');
			paymentMethodRepository.get(paymentMethodId, Context.api).then(
				(paymentMethod) => {
					this.isVRPaymentPayment = (paymentMethod.formattedHandlerIdentifier === vrpaymentFormattedHandlerIdentifier);
				}
			);
		}
	}
});
