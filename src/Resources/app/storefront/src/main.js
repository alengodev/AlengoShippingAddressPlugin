// Import all necessary Storefront plugins
import ShippingAddressPlugin from './shippingaddress-plugin/shipping-address-plugin.plugin';

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;

PluginManager.register('ShippingAddressPlugin', ShippingAddressPlugin, '[data-save-alt-shipping-address="true"]');
