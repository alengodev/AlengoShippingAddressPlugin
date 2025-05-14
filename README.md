# AlengoShippingAddress Plugin for Shopware 6

## Description

The `AlengoShippingAddress` plugin for Shopware 6 allows you to override the shipping address during the checkout process. Due to the calculation of shipping costs or other applicable rules, the country cannot be changed. However, the street, postal code, and city can be overridden.
In the plugin settings, addresses can be stored as CSV data that can be selected in the checkout.

## Installation

1. Upload the plugin to the `custom/plugins/AlengoShippingAddress` directory.
2. In the Shopware backend, navigate to `Settings > System > Plugins`.
3. Search for `AlengoShippingAddress` and install the plugin.
4. Activate the plugin after installation.

## Configuration

After installing and activating the plugin, you can configure it in the Shopware backend under `Settings > System > Plugins > AlengoShippingAddress`.
The settings can be made per sales channel.

* **Enable change of delivery address via an additional form**: Allows the user to change the delivery address during checkout.
* **Enable change of recipient name as well**: Allows the user to change the recipient's name during checkout.
* **Disable change** of default shipping and billing address.
* **Add a list** of default addresses as CSV

## Requirements

- Shopware Version: ^6.5.0
- PHP Version: ^7.4 || ^8.0

## License

This plugin is released under the MIT license. For more information, see the `LICENSE` file.

## Authors

- **alengo OG** - [https://www.alengo.at](https://www.alengo.at)
