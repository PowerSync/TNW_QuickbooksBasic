# Magento 2 and QuickBooks Online Integration (Starter Pack)
Close the gap to ensure complete and accurate accounting when using QuickBooks Online and Magento 2 platform.

#### Build Status
[![CircleCI](https://circleci.com/gh/PowerSync/TNW_QuickbooksBasic/tree/master.svg?style=svg&circle-token=5685ad176382f7a924d39e99ae5d292024b5bf24)](https://circleci.com/gh/PowerSync/TNW_QuickbooksBasic/tree/master)

## Requirements
* PHP >= 7.0
* Magento >= 2.2

## How to install
#### via Magento Marketplace
You can get this extension from Magento Marketplace by visiting [PowerSync QuickBooks (Basic Plan)](https://marketplace.magento.com/tnw-quickbooks.html) page. Then follow [Installation instructions](https://technweb.atlassian.net/wiki/spaces/IWQ/pages/590807169/Starter+Pack) to install the extension.

#### via Composer (skip Magento Marketplace)
1. Install our extension
```
composer require tnw/quickbooksbasic
```
2. Update Magento
```
bin/magento setup:upgrade
bin/magento setup:di:compile
bin/magento setup:static-content:deploy
```
3. Re-index
```
bin/magento indexer:reindex
```

## How to articles
* [How to connect to QuickBooks](https://technweb.atlassian.net/wiki/spaces/IWQ/pages/45350947/Configuration+Connecting+to+QuickBooks)
* [Customer Synchronization](https://technweb.atlassian.net/wiki/spaces/IWQ/pages/45350965/Configuration+Customer+Synchronization)
* [Switch from Sandbox to Production](https://technweb.atlassian.net/wiki/spaces/IWQ/pages/339804165/Switch+from+Sandbox+to+Production)
* [Additional troubleshooting articles](https://technweb.atlassian.net/wiki/spaces/IWQ/pages/339836929/Troubleshooting) are available as well.

## Contribute to this module
Feel free to Fork and contrinute to this module and create a pull request so we will merge your changes to `develop` branch.

## Features
* Customer Synchronization
* Customer Address Synchronization
* Data Normalization
* Extension tests are included
* Books Balanced Quicker!

#### Paid Version
More information about the paid version is available on [PowerSync.biz - QuickBooks + Magento 2 Integration](https://powersync.biz/integrations-magento2-quickbooks/) website. Paid version includes the following features:
* Product & Inventory
* Invoice & Payment
* Sales Receipt
* Tax Rates
* Payment Methods
* Refund Receipt
* Bundled Products
* Configurable products
* Tax Multi-Account support
* Bulk & Scheduled sync

You can [view the demo of our paid verion](https://www.youtube.com/watch?v=F-6PMuZ1aLs) on YouTube.

## License
[GNU General Public License v3.0](https://choosealicense.com/licenses/gpl-3.0/)
