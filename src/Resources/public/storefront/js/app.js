/* global window */
// noinspection ThisExpressionReferencesGlobalObjectJS
(function (window) {
    /**
     * VRPaymentCheckout
     * @type {
     *      {
     *          payment_method_handler_name: string,
     *          payment_method_iframe_class: string,
     *          init: init,
     *          validationCallBack: validationCallBack,
     *          payment_method_handler_status: string,
     *          submitPayment: (function(*): boolean),
     *          payment_method_iframe_prefix: string,
     *          payment_form_id: string,
     *          payment_method_handler_prefix: string,
     *          payment_method_tabs: string,
     *          getIframe: (function(): boolean
     *      }
     * }
     */
    const VRPaymentCheckout = {
        /**
         * Variables
         */
        payment_panel_id: 'vrpayment-payment-panel',
        payment_method_iframe_id: 'vrpayment-payment-iframe',
        payment_method_handler_name: 'vrpayment_payment_handler',
        payment_method_handler_status: 'input[name="vrpayment_payment_handler_validation_status"]',
        payment_form_id: 'confirmOrderForm',
        button_cancel_id: 'vrpaymentOrderCancel',
        // button_home_override: 'vrpaymentHomeLink',
        loader_id: 'vrpaymentLoader',
        checkout_url: null,
        checkout_url_id: 'checkoutUrl',
        cart_recreate_url: null,
        cart_recreate_url_id: 'cartRecreateUrl',
        handler: null,

        /**
         * Initialize plugin
         */
        init: function () {
            VRPaymentCheckout.activateLoader(true);
            this.checkout_url = document.getElementById(this.checkout_url_id).value;
            this.cart_recreate_url = document.getElementById(this.cart_recreate_url_id).value;

            document.getElementById(this.button_cancel_id).addEventListener('click', this.recreateCart, false);
            // document.getElementById(this.button_home_override).addEventListener('click', this.recreateCart, false);
            document.getElementById(this.payment_form_id).addEventListener('submit', this.submitPayment, false);

            VRPaymentCheckout.getIframe();
        },

        activateLoader: function (activate) {
            const buttons = document.querySelectorAll('button');
            if (activate) {
                for (let i = 0; i < buttons.length; i++) {
                    buttons[i].disabled = true;
                }
            } else {
                for (let i = 0; i < buttons.length; i++) {
                    buttons[i].disabled = false;
                }
            }
        },

        hideLoader: function () {
            const loader = document.getElementById(VRPaymentCheckout.loader_id);
            if (loader !== null && loader.parentNode !== null) {
                loader.parentNode.removeChild(loader);
            }
            VRPaymentCheckout.activateLoader(false);
        },

        recreateCart: function (e) {
            window.location.href = VRPaymentCheckout.cart_recreate_url;
            e.preventDefault();
        },

        /**
         * Submit form
         *
         * @param event
         * @return {boolean}
         */
        submitPayment: function (event) {
            VRPaymentCheckout.activateLoader(true);
            VRPaymentCheckout.handler.validate();
            event.preventDefault();
            return false;
        },

        /**
         * Get iframe
         */
        getIframe: function () {
            const paymentPanel = document.getElementById(VRPaymentCheckout.payment_panel_id);
            const paymentMethodConfigurationId = paymentPanel.dataset.id;
            const iframeContainer = document.getElementById(VRPaymentCheckout.payment_method_iframe_id);

            if (!VRPaymentCheckout.handler) { // iframe has not been loaded yet
                // noinspection JSUnresolvedFunction
                VRPaymentCheckout.handler = window.IframeCheckoutHandler(paymentMethodConfigurationId);
                // noinspection JSUnresolvedFunction
                VRPaymentCheckout.handler.setValidationCallback(function(validationResult){
                    VRPaymentCheckout.hideErrors();
                    VRPaymentCheckout.validationCallBack(validationResult);
                });
                VRPaymentCheckout.handler.setInitializeCallback(VRPaymentCheckout.hideLoader());
                VRPaymentCheckout.handler.setHeightChangeCallback(function(height){
                    if(height < 1){ // iframe has no fields
                        VRPaymentCheckout.handler.submit();
                    }
                });
                VRPaymentCheckout.handler.create(iframeContainer);
                setTimeout(VRPaymentCheckout.hideLoader(), 10000);

            }
        },

        /**
         * validation callback
         * @param validationResult
         */
        validationCallBack: function (validationResult) {
            if (validationResult.success) {
                document.querySelector(this.payment_method_handler_status).value = true;
                VRPaymentCheckout.handler.submit();
            } else {
                document.body.scrollTop = 0;
                document.documentElement.scrollTop = 0;

                if (validationResult.errors) {
                    VRPaymentCheckout.showErrors(validationResult.errors);
                }
                document.querySelector(this.payment_method_handler_status).value = false;
                VRPaymentCheckout.activateLoader(false);
            }
        },

        showErrors: function(errors) {
            let alert = document.createElement('div');
            alert.setAttribute('class', 'alert alert-danger');
            alert.setAttribute('role', 'alert');
            alert.setAttribute('id', 'vrpayment-errors');
            document.getElementsByClassName('flashbags')[0].appendChild(alert);

            let alertContentContainer = document.createElement('div');
            alertContentContainer.setAttribute('class', 'alert-content-container');
            alert.appendChild(alertContentContainer);

            let alertContent = document.createElement('div');
            alertContent.setAttribute('class', 'alert-content');
            alertContentContainer.appendChild(alertContent);

            if (errors.length > 1) {
                let alertList = document.createElement('ul');
                alertList.setAttribute('class', 'alert-list');
                alertContent.appendChild(alertList);
                for (let index = 0; index < errors.length; index++) {
                    let alertListItem = document.createElement('li');
                    alertListItem.innerHTML = errors[index];
                    alertList.appendChild(alertListItem);
                }
            } else {
                alertContent.innerHTML = errors[0];
            }
        },

        hideErrors: function() {
            let errorElement = document.getElementById('vrpayment-errors');
            if (errorElement) {
                errorElement.parentNode.removeChild(errorElement);
            }
        }
    };

    window.VRPaymentCheckout = VRPaymentCheckout;

}(typeof window !== "undefined" ? window : this));

/**
 * Vanilla JS over JQuery
 */
window.addEventListener('load', function (e) {
    VRPaymentCheckout.init();
    window.history.pushState({}, document.title, VRPaymentCheckout.cart_recreate_url);
    window.history.pushState({}, document.title, VRPaymentCheckout.checkout_url);
}, false);

/**
 * This only works if the user has interacted with the page
 * @link https://stackoverflow.com/questions/57339098/chrome-popstate-not-firing-on-back-button-if-no-user-interaction
 */
window.addEventListener('popstate', function (e) {
    if (window.history.state == null) { // This means it's page load
        return;
    }
    window.location.href = VRPaymentCheckout.cart_recreate_url;
}, false);
