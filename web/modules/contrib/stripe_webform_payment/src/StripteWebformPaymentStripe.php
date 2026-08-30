<?php

namespace Drupal\stripe_webform_payment;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Stripe\Customer;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;
use Stripe\PaymentIntent;

/**
 * A service wrapper for Stripe PHP library.
 */
class StripteWebformPaymentStripe {

  use StringTranslationTrait;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $logger;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a StripeWebformPaymentService object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger channel factory.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, LoggerChannelFactoryInterface $logger_factory, MessengerInterface $messenger) {
    $this->configFactory = $config_factory;
    $this->logger = $logger_factory->get('stripe_webform_payment');
    $this->messenger = $messenger;
  }

  /**
   * Gets the list of Stripe products.
   *
   * @return array
   *   An associative array of Stripe products as ID - Name.
   */
  public function getProductsList(): array {
    try {
      Stripe::setApiKey($this->getApiSecret());
      $products = Product::all()->data;
      $options = [];
      foreach ($products as $product) {
        $options[$product->id] = $product->name;
      }
      return $options;

    }
    catch (ApiErrorException $e) {
      $this->logger->error($e->getMessage());
      $this->messenger->addError($this->t('Error fetching Stripe products: @code - @description', [
        '@code' => $e->getStripeCode(),
        '@description' => $e->getError()->message,
      ]));
      return [];
    }
  }

  /**
   * Create a payment intent.
   *
   * @param string $amount
   *   The price amount.
   * @param string $currency
   *   The currency.
   * @param array $options
   *   Extra options to pass while creating the Stripe payment intent.
   *
   * @return \Stripe\PaymentIntent|null
   *   Stripe payment intent object.
   */
  public function createPaymentIntent(string $amount, string $currency, array $options = []): PaymentIntent|null {
    try {
      Stripe::setApiKey($this->getApiSecret());

      $createParams = [
        'amount' => $amount,
        'currency' => $currency,
      ];
      $createParams += $options;

      return PaymentIntent::create($createParams);
    }
    catch (ApiErrorException $e) {
      $this->logger->error($e->getMessage());
      $this->messenger->addError($this->t('Error creating Stripe payment intent: @code - @description', [
        '@code' => $e->getStripeCode(),
        '@description' => $e->getError()->message,
      ]));
      return NULL;
    }
  }

  /**
   * Create a new customer.
   *
   * @param array $customerAttributes
   *   The customer attributes..
   *
   * @return \Stripe\Customer|null
   *   Stripe customer object.
   */
  public function createCustomer(array $customerAttributes): ?Customer {
    try {
      Stripe::setApiKey($this->getApiSecret());

      return Customer::create($customerAttributes);
    }
    catch (ApiErrorException $e) {
      $this->logger->error($e->getMessage());
      $this->messenger->addError($this->t('Error creating Stripe payment intent: @code - @description', [
        '@code' => $e->getStripeCode(),
        '@description' => $e->getError()->message,
      ]));
      return NULL;
    }
  }

  /**
   * Get the customer.
   *
   * @param array|null $customerAttributes
   *   The customer attributes.
   *
   * @return array
   *   Stripe customer object if exists.
   *
   * @throws \Stripe\Exception\ApiErrorException
   */
  public function getAllCustomers(array $customerAttributes): ?array {
    try {
      Stripe::setApiKey($this->getApiSecret());
      $allCustomers = [];

      $limit = 100;
      $startingAfter = NULL;

      do {
        $params = array_merge($customerAttributes, [
          'limit' => $limit,
          'starting_after' => $startingAfter,
        ]);

        $customers = Customer::all($params);

        foreach ($customers->data as $customer) {
          $allCustomers[] = $customer;
        }

        if (isset($customers->has_more) && $customers->has_more) {
          $startingAfter = $customers->data[count($customers->data) - 1]->id;
        }
        else {
          break;
        }

      } while (TRUE);

      return $allCustomers;
    }
    catch (ApiErrorException $e) {
      $this->logger->error($e->getMessage());
      $this->messenger->addError($this->t('Error creating Stripe payment intent: @code - @description', [
        '@code' => $e->getStripeCode(),
        '@description' => $e->getError()->message,
      ]));
      return NULL;
    }
  }

  /**
   * Get the Stripe price object of a product.
   *
   * @param string $productId
   *   The Stripe product ID.
   *
   * @return array
   *   Array of the price amount and the currency.
   *
   * @throws \Stripe\Exception\ApiErrorException
   */
  public function getProductPrice(string $productId): array {
    try {
      // Retrieve the product from Stripe.
      Stripe::setApiKey($this->getApiSecret());
      $product = Product::retrieve($productId);
      $price = Price::retrieve($product->default_price);

      return [
        'amount' => $price->unit_amount,
        'currency' => $price->currency,
      ];
    }
    catch (ApiErrorException $e) {
      $this->logger->error($e->getMessage());
      $this->messenger->addError($this->t('Error fetching Stripe product price: @code - @description', [
        '@code' => $e->getStripeCode(),
        '@description' => $e->getError()->message,
      ]));
      return [];
    }
  }

  /**
   * Gets the Stripe API credentials.
   *
   * @return array|null
   *   Array of API publishable key, API secret, and webhook secret.
   */
  private function getCredentials(): ?array {
    $config = $this->configFactory->get('stripe.settings');
    return $config->get('apikey.' . $config->get('environment'));
  }

  /**
   * Get the Stripe API publishable key.
   *
   * @return string
   *   The Stripe API publishable key.
   */
  public function getPublishableKey(): string {
    $credentials = $this->getCredentials();

    return $credentials['public'];
  }

  /**
   * Get the Stripe API secret.
   *
   * @return string
   *   The Stripe API secret.
   */
  private function getApiSecret(): string {
    $credentials = $this->getCredentials();

    return $credentials['secret'];
  }

  /**
   * Get the Stripe webhook secret.
   *
   * @return string
   *   The Stripe webhook secret.
   */
  private function getApiWebhookSecret(): string {
    $credentials = $this->getCredentials();

    return $credentials['webhook'];
  }

  /**
   * Get the Stripe one-time client secret.
   *
   * @param string $amount
   *   The price amount.
   * @param string $currency
   *   The price currency.
   *
   * @return array
   *   The Stripe one-time client secret array..
   *
   * @throws \Stripe\Exception\ApiErrorException
   */
  public function getClientSecret(string $amount, string $currency) {
    $paymentIntent = $this->createPaymentIntent($amount, $currency);

    return [
      'clientSecret' => $paymentIntent?->client_secret,
    ];
  }

  /**
   * Get the payment intent object.
   *
   * @param string $paymentIntentId
   *   The payment intent ID.
   *
   * @return \Stripe\PaymentIntent|null
   *   Payment intent object if any.
   */
  public function getPaymentIntent(string $paymentIntentId) {

    try {
      Stripe::setApiKey($this->getApiSecret());
      return PaymentIntent::retrieve($paymentIntentId);
    }
    catch (ApiErrorException $e) {
      $this->logger->error($e->getMessage());
      $this->messenger->addError($this->t('Error fetching Stripe product price: @code - @description', [
        '@code' => $e->getStripeCode(),
        '@description' => $e->getError()->message,
      ]));
      return NULL;
    }
  }

}
