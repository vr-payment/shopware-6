

VR Payment Integration for Shopware 6
=============================

The VR Payment plugin wraps around the VR Payment API. This library facilitates your interaction with various services such as transactions.
Please note that this plugin is for versions 6.5 and 6.6. For the 6.4 plugin please visit [our Shopware 6.4 plugin](https://github.com/vr-payment/shopware-6-4).

## Requirements

- Shopware 6.5.x or Shopware 6.6.x. See table below.
- PHP minimum version supported by the each shop version.

## Supported versions

___________________________________________________________________________________
| Shopware 6 version            | Plugin major version   | Supported until        |
|-------------------------------|------------------------|------------------------|
| Shopware 6.6.x                | 6.x                    | Further notice         |
| Shopware 6.5.x                | 5.x                    | October 2024           |
-----------------------------------------------------------------------------------

## Installation

You can use **Composer** or **install manually**

### Composer

The preferred method is via [composer](https://getcomposer.org). Follow the
[installation instructions](https://getcomposer.org/doc/00-intro.md) if you do not already have
composer installed.

Once composer is installed, execute the following command from the shop root to install the plugin:

```bash
composer require vrpayment/shopware-6
php bin/console plugin:refresh
php bin/console plugin:install --activate --clearCache VRPaymentPayment
```

#### Update via composer
```bash
composer update vrpayment/shopware-6
php bin/console plugin:refresh
php bin/console plugin:install --activate --clearCache VRPaymentPayment
```

### Manual Installation

Alternatively you can download the package in its entirety. The [Releases](../../releases) page lists all stable versions.

Uncompress the zip file you download, and include the autoloader in your project:

```bash
# unzip to ShopwareInstallDir/custom/plugins/VRPaymentPayment
# For versions 6.1.10 and older, the SDK is installed automatically when installing the plugin in the shop, so you don't need to
# run the following command.
composer require vrpayment/sdk 4.8.0
php bin/console plugin:refresh
php bin/console plugin:install --activate --clearCache VRPaymentPayment
```

## Usage
The library needs to be configured with your account's space id, user id, and application key which are available in your VR Payment
account dashboard.

### Logs and debugging
To view the logs please run the command below:
```bash
cd shopware/install/dir
tail -f var/log/vrpayment_payment*.log
```

## Documentation

[Documentation](https://gateway.vr-payment.de/doc/shopware-6/6.1.12/docs/en/documentation.html)

## License

Please see the [license file](https://github.com/vr-payment/shopware-6/blob/master/LICENSE.txt) for more information.
