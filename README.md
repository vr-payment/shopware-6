

VR Payment Integration for Shopware 6
=============================

The VR Payment plugin wraps around the VR Payment API. This library facilitates your interaction with various services such as transactions.
Please note that this plugin is for versions 6.5, 6.6 or 6.7. For the 6.4 plugin please visit [our Shopware 6.4 plugin](https://github.com/vr-payment/shopware-6-4).

## Requirements

- Shopware 6.7.x, 6.6.x or 6.5.x. See table below.
- PHP minimum version supported by the each shop version.

## Documentation

- For English documentation click [here](https://docs.plugin-documentation.vr-payment.de/vr-payment/shopware-6/7.1.6/docs/en/documentation.html)
- Für die deutsche Dokumentation klicken Sie [hier](https://docs.plugin-documentation.vr-payment.de/vr-payment/shopware-6/7.1.6/docs/de/documentation.html)
- Pour la documentation Française, cliquez [ici](https://docs.plugin-documentation.vr-payment.de/vr-payment/shopware-6/7.1.6/docs/fr/documentation.html)
- Per la documentazione in tedesco, clicca [qui](https://docs.plugin-documentation.vr-payment.de/vr-payment/shopware-6/7.1.6/docs/it/documentation.html)

## Installation

### **Via Composer (Recommended)**  
1. Navigate to your Shopware root directory.
2. Run:

```bash
Copy
composer require vrpayment/shopware-6
php bin/console plugin:refresh
php bin/console plugin:install --activate --clearCache VRPayment
```

### Manual Installation

1. Download the latest [Release](../../releases)
2. Extract the ZIP to custom/plugins/VRPayment.

```bash
Copy
bin/console plugin:refresh  
bin/console plugin:install --activate --clearCache VRPayment
```

## Configuration
### API Credentials

1. Navigate to Shopware Admin > Settings > VRPayment.
2. Enter your Space ID, User ID, and API Key (obtained from the [VR Payment Portal](https://gateway.vr-payment.de/)).

### VRPayment does not appear in Settings

1. You should run the following commmand

```bash
Copy
bin/build-administration.sh
```

### Payment Methods

Configure supported methods (e.g., credit cards, Apple Pay) via the [VR Payment Portal](https://gateway.vr-payment.de/).

### Key Features
**iFrame Integration**: Embed payment forms directly into your checkout.

**Refunds & Captures**: Trigger full/partial refunds and captures from Shopware or the [VR Payment Portal](https://gateway.vr-payment.de/).

**Multi-Store Support**: Manage configurations across multiple stores.

**Automatic Updates**: Payment methods sync dynamically via the VRPayment API.

**Subscriptions**: Make recurring payments with Subscriptions (Shopware Commercial) and VRPayment.

## Compatibiliity

___________________________________________________________________________________
| Shopware 6 version            | Plugin major version   | Supported until        |
|-------------------------------|------------------------|------------------------|
| Shopware 6.7.x                | 7.x                    | Further notice         |
| Shopware 6.6.x                | 6.x                    | December 2025          |
| Shopware 6.5.x                | 5.x                    | October 2024           |
-----------------------------------------------------------------------------------

### Troubleshooting
**Logs**: Check payment logs with:

```bash
Copy
tail -f var/log/vrpayment*.log
```
### Common Issues:

Ensure composer update vrpayment/shopware-6 is run after updates.

Verify API credentials match your VRPayment account.

## FAQs
**Q: Does this plugin support one-click payments?**
A: Yes, via tokenization in the VRPayment Portal.

**Q: How do I handle PCI compliance?**
A: The plugin uses iFrame integration, reducing PCI requirements to SAQ-A.

### Changelog
For version-specific updates, see the [GitHub Releases](https://github.com/vr-payment/shopware-6/releases).

### Contributing
Report issues via GitHub Issues.

Follow the Shopware Plugin Base Guide for development.

This template combines technical clarity with user-friendly guidance. For advanced customization (e.g., overriding templates or payment handlers), refer to the Shopware Documentation.

## License

Please see the [license file](https://github.com/vr-payment/shopware-6/blob/master/LICENSE.txt) for more information.
