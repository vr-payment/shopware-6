/* global Shopware */

import template from './index.html.twig';

const {Component, Mixin, Filter, Utils} = Shopware;

Component.register('vrpayment-order-action-refund-by-amount', {
	template,

	inject: ['VRPaymentRefundService'],

	mixins: [
		Mixin.getByName('notification')
	],

	props: {
		transactionData: {
			type: Object,
			required: true
		},

		orderId: {
			type: String,
			required: true
		}
	},

	data() {
		return {
			isLoading: true,
			currency: this.transactionData.transactions[0].currency,
			refundAmount: 0,
			refundableAmount: 0,
		};
	},

	computed: {
		dateFilter() {
			return Filter.getByName('date');
		}
	},

	created() {
		this.createdComponent();
	},

	methods: {
		createdComponent() {
			this.isLoading = false;
			this.currency = this.transactionData.transactions[0].currency;
			this.refundAmount = Number(this.transactionData.transactions[0].amountIncludingTax);
			this.refundableAmount = Number(this.transactionData.transactions[0].amountIncludingTax);
		},

		refundByAmount() {
			this.isLoading = true;
			this.VRPaymentRefundService.createRefundByAmount(
				this.transactionData.transactions[0].metaData.salesChannelId,
				this.transactionData.transactions[0].id,
				this.refundAmount
			).then(() => {
				this.createNotificationSuccess({
					title: this.$tc('vrpayment-order.refundAction.successTitle'),
					message: this.$tc('vrpayment-order.refundAction.successMessage')
				});
				this.isLoading = false;
				this.$emit('modal-close');
				this.$nextTick(() => {
					this.$router.replace(`${this.$route.path}?hash=${Utils.createId()}`);
				});
			}).catch((errorResponse) => {
				try {
					this.createNotificationError({
						title: errorResponse.response.data.errors[0].title,
						message: errorResponse.response.data.errors[0].detail,
						autoClose: false
					});
				} catch (e) {
					this.createNotificationError({
						title: errorResponse.title,
						message: errorResponse.message,
						autoClose: false
					});
				} finally {
					this.isLoading = false;
					this.$emit('modal-close');
					this.$nextTick(() => {
						this.$router.replace(`${this.$route.path}?hash=${Utils.createId()}`);
					});
				}
			});
		}
	}
});
