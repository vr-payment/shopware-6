// Import all necessary Storefront plugins and scss files
import VRPaymentCheckoutPlugin
    from './vrpayment-checkout-plugin/vrpayment-checkout-plugin.plugin';

// Register them via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register(
    'VRPaymentCheckoutPlugin',
    VRPaymentCheckoutPlugin,
    '[data-vrpayment-checkout-plugin]'
);

if (module.hot) {
    // noinspection JSValidateTypes
    module.hot.accept();
}