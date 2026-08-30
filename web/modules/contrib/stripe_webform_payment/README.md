# Stripe Webform Payment

The Stripe Webform Payment module is a powerful and flexible module that seamlessly integrates Webform and Stripe Payment Elements. This module elevates the capabilities of a website by providing an array of advanced features and options for processing online payments with ease.

## Key Features

* Add a Stripe Payment Element to Webforms: Effortlessly include a Stripe payment element in any webform, enabling smooth and secure transactions.
* Payment Intent Handler: Efficiently retrieve succeeded payment intent values and pass them to other handlers for further processing.
* AJAX Compatibility: The module is fully compatible with Webform AJAX, ensuring optimal performance and user experience.
* Dynamic Stripe Product Linking: Automatically fetch and link Stripe products to your payment elements, simplifying the payment process.
* Custom Pricing and Currency: Flexibly set custom prices and currency without the need to link payments to a Stripe product.
* Drupal-Stripe Customer Mapping: Map Drupal users to Stripe customers, allowing for easier customer management, tracking, and communication.
* Collect Shipping and Billing Addresses: Optionally enables the collection of Stripe shipping and billing addresses for a streamlined checkout process.
* Customizable Stripe Payment Element: Style the Stripe payment element to match your website's design and branding.
* Link Stripe Customers to Drupal Users: Automatically associate Stripe customers with Drupal users or visitors for improved user management and customer insights.
* Payment Intent UI Management: Effortlessly manage payment intent elements from the user interface.

## Similar Modules

* Stripe's webform integration: This is a webform integration using the Stripe module allowing the use of a credit card element and adding a submission handler to collect charges/subscriptions.

The main difference is that this module uses Stripe Card Element for the integration, not Stripe Payment elements. Also, our module provides more flexibility to manage Stripe Products, Customers, and payment Intents.

## Requirements

* Stripe module 2.0+
* Webform module 6.1+

## Installation and Usage

The best way to install the module is by using Composer to download all dependencies.

To use the module:

1. Enable the module as usual
2. Go to Stripe module configurations and add your stripe API credentials. Please note that the credentials are saved as configurations and might be exported as well. Otherwise, you can save them in the settings.php
3. Go to your webform build page
4. Add new "Stripe payment" element
5. The module comes with default configurations that allow it to work; check each field help for advanced usage.

## Options & Advanced Setup
Please check the documentation at https://bit.ly/46vniV7.
Also you can find a comprehensive comparision of similar modules at https://bit.ly/44quVKr
