# Friendzy Shared Shopping for Magento

Your customers shop together with a friend, or with someone from your store's team, live on your store. Each person uses their own phone or computer. They see the same pages and can talk in a chat, send a voice message or place a call. The friend does not install an app and does not need an account.

A shopper is asked first, and nothing is shared until they accept. The cart, checkout, the customer account and the order confirmation page are not shared.

This module puts Friendzy on your storefront pages, tells Friendzy which pages are private, and connects each store view to your Friendzy account.

## Requirements

- Magento Open Source or Adobe Commerce 2.4.7, 2.4.8 or 2.4.9
- PHP 8.2, 8.3, 8.4 or 8.5
- A Friendzy account (free). Installing the module does not create one.

Tested on Magento Open Source 2.4.7-p10, 2.4.8-p5 and 2.4.9 with the Luma theme and Varnish. Not tested with Hyvä Checkout. It does not run on a headless or PWA storefront, or on Adobe Commerce as a Cloud Service, because those do not render Magento's layout or install PHP modules.

## Plans

The module is the same for every plan. The plan is set in your Friendzy account.

- Free: up to 51 shared sessions a month, and a 14-day trial with up to 500.
- Friendzy Starter and Friendzy Growth: bought in your Friendzy account, or on Adobe Commerce Marketplace for a year. A Marketplace purchase is connected on the Subscription page of your Friendzy account, with your Marketplace user name and order number.

## Install

From the Magento root, as the user that owns the Magento files:

    composer require friendzy/module-widget
    bin/magento module:enable Friendzy_Widget
    bin/magento setup:upgrade
    bin/magento cache:flush

In production mode, run `bin/magento setup:di:compile` and `bin/magento setup:static-content:deploy` before flushing the cache.

On Adobe Commerce on cloud infrastructure, require the package on your workstation, commit `composer.lock` and deploy. Do not enable or disable the module on the remote environment.

## Connect

The module adds nothing to the page until a store view has a site id.

1. Create a Friendzy account at

https://getfriendzy.com

2. In the Magento admin, open Stores, Settings, Friendzy.
3. Choose Connect and sign in with your Friendzy account (and its authenticator code, if it uses one). The module finds or registers the Friendzy site for the store view's address and saves its site id. It keeps the site id only, never the password.
4. Shoppers see the Friendzy button once Friendzy has checked the store view and you turn on Live in your Friendzy account.

You can also paste a site id yourself under Stores, Configuration, Services, Friendzy. It looks like `site-abcdefghijkl`. Store views on one host share one site; a store view on its own domain is its own site.

## What it adds to the page

On storefront pages of a connected store view, the head gets the Friendzy snippet (loaded async from getfriendzy.com), a meta tag naming the store's private routes, and a cart meta tag with the store view's path and nothing about the shopper. Checkout gets nothing. The head depends only on the store view, so pages stay cacheable in Varnish and the full page cache.

## What data goes where

The module does not call Friendzy on its own, and Friendzy never calls your store. It uses no OAuth or integration tokens.

While a site id is set, the shopper's browser loads Friendzy. Friendzy asks each shopper before a shared session; a shopper who declines does not join, and nothing about a shared session is sent. Shoppers are not asked for a name or an email.

On the order confirmation page only, and only for the shopper who just placed the order, the module passes that order's number, total and currency to Friendzy. It is not shown on the page, and a cancelled order is not passed.

Your store's privacy notice should say that the store uses Friendzy. Friendzy's privacy notice:

https://getfriendzy.com/privacy

## Uninstall

    bin/magento module:uninstall Friendzy_Widget
    composer remove friendzy/module-widget

Uninstall deletes the module's settings (every `friendzy/` path in `core_config_data`), so a later install starts unconnected. It does not close the site in your Friendzy account: use Close this website there to close it and erase its data. A site Friendzy no longer sees is switched off after 30 days and erased 90 days later.

## Licence

Proprietary, free to install and use on a store connected to Friendzy. See `LICENSE` and the Friendzy Terms:

https://getfriendzy.com/terms

## Support

support@getfriendzy.com
