=== MoneyTigo pour WooCommerce ===
Author: moneytigo.com
Author URI: https://www.moneytigo.com/
Contributors: moneytigo
Tags: payment,payments,payment gateway,payment processor,payment processing,checkout,payment pro,merchant account,contrat vad,moyen de paiement,card,credit card,paiement,bezahlsystem,purchase,online payment,ipspayment,ips payment,moneytigo
Requires at least: 4.1
Tested up to: 5.8
Requires PHP: 7.1
Stable tag: 1.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
WC Tested up to: 5.4
WC requires at least: 2.6

MoneyTigo.com is a credit card payment processing solution compatible with Wordpress Woocommerce.

== Description ==

With MoneyTigo you can easily accept credit card payments on your Worpress Woocommerce store, MoneyTigo is a payment processor.

== Installation ==

These steps should be made for module's correct work:

1. Open a moneytigo.com account (Registration is done online and the account is opened immediately).
2. Add your website to moneytigo.com (from your dashboard to get your API key credentials)
2. Install and adjust the module

[Important] - next steps presume you already have WooCommerce module installed on your website:

1. Module's installation. Choose "Plugins -> Add new" in admin's console, press "Upload Plugin" button, choose zip-archive with plugin and then press "Install".
2. Adjustment. Choose "WooCommerce -> Settings" in admin's console and proceed to "Payments" tab. Choose "MoneyTigo" in payment gateways list and proceed to settings.
Fill in "Api Key" and "Secret Key" - these values can be found in https://app.moneytigo.com. You can leave the rest settings as they go.
3. After saving your settings, you will have MoneyTigo payments available on your website.


== Frequently Asked Questions ==

= What does the plugin do? =

Le plugin MoneyTigo ajoute à votre boutique Woocommerce et à Wordpress l'interfacage de votre compte de paiement MoneyTigo directement sur votre boutique sans dévellopement particulier.

= What is MoneyTigo? =

<a href="https://www.moneytigo.com/?utm_source=wordpress-plugin-listing&utm_campaign=wordpress&utm_medium=marketplaces" target="_blank">MoneyTigo</a> is an online payment gateway that allows you to accept credit cards in a matter of moments.

== Upgrade notice ==

Please note that the MoneyTigo plugin requires a minimum PHP version of 7.1

== Screenshots ==

1. A unique payment experience

== Changelog ==

= 1.1.1 =
* Compatibility test with Wordpress 5.8 and validation
* Compatibility test with the latest version of WOCOMMERCE 5.4 and validation

= 1.1.0 =
* Correction of the version management bug
* Modification in case of refused payment, forcing the creation of a new order at each payment attempt
* Fixed bug with duplicate stock increment
* Removal of the moneytigo footer
* Update check native to wordpress abandon manual check
* Switch to automatic update by default for the moneytigo module

= 1.0.9 =
* Solved redirection problem for orders with completed status

= 1.0.8 =
* Fix bug in list payment method

= 1.0.7 =
* Fix bug in list payment method

= 1.0.6 =
* Add spanish translate
* Update french translate
* Fix domain text
* Fix checking moneytigo

= 1.0.5 =
* Added 2 and 4 times payment methods

= 1.0.4 =
* Removal of the integrated mode and change of api version
* Standardization of the module with the latest security rules
* Optimization of processing and response times

= 1.0.3 =
* Choice between integrated mode and redirection mode
* In the integrated reminder mode of the amount to be paid
* Redirection in case of payment in installments

= 1.0.2 =
* Modified termination url

= 1.0.1 =
* Encryption of notifications

= 1.0.0 =
* WooCommerce Payment Gateway.
