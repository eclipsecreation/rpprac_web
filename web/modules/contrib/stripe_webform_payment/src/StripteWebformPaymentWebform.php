<?php

namespace Drupal\stripe_webform_payment;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Stripe\PaymentIntent;

/**
 * Service description.
 */
class StripteWebformPaymentWebform {

  // Use StringTranslationTrait is needed here to allow $this->t().
  use StringTranslationTrait;

  /**
   * The string translation service.
   *
   * @var \Drupal\Core\StringTranslation\TranslationInterface
   */
  protected $stringTranslation;

  /**
   * The stripe_webform_payment.stripe service.
   *
   * @var StripteWebformPaymentStripe
   */
  protected StripteWebformPaymentStripe $stripeService;

  /**
   * Constructs a StripteWebformPaymentWebform object.
   *
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\stripe_webform_payment\StripteWebformPaymentStripe $stripeService
   *   The Stripe webform payment webform service.
   */
  public function __construct(TranslationInterface $string_translation, StripteWebformPaymentStripe $stripeService) {
    $this->stringTranslation = $string_translation;
    $this->stripeService = $stripeService;
  }

  /**
   * The stripe_payment element configurations.
   *
   * @return array
   *   The stripe_payment element configurations array.
   */
  public function getConfigurations(): array {
    return [
      'stripe_name' => '',
      'stripe_email' => '',
      'stripe_source' => '',
      'stripe_product' => '',
      'stripe_amount' => '',
      'stripe_currency' => '',
      'payment_intent_id' => '',
      'stripe_address' => FALSE,
      'stripe_address_type' => '',
      'stripe_payment_intent_config' => "automatic_payment_methods:\n  enabled: true",
      'stripe_enable_appearance' => FALSE,
      'stripe_appearance' => "theme: 'stripe'",
      'stripe_customer' => FALSE,
      'stripe_customer_attributes' => "name: [current-user:display-name]\nemail: [current-user:mail]\nmetadata:\n  drupal_user_id: [current-user:uid]\n  website: [site:name]",
    ];
  }

  /**
   * The stripe_payment element form.
   *
   * @param array $form
   *   The form element.
   */
  public function getFormElements(array &$form) {
    $form['stripe'] = [
      '#type' => 'details',
      '#title' => $this->t('Stripe payment element'),
      '#open' => TRUE,
    ];

    $form['stripe']['source_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Stripe payment options'),
      '#open' => TRUE,
    ];
    $form['stripe']['source_container']['stripe_source'] = [
      '#type' => 'select',
      '#title' => $this->t('Select the source (Product or custom)'),
      '#description' => $this->t('This option provides the ability to link an existing Stripe product or to use custom price and amount..'),
      '#options' => [
        'product' => $this->t('Stripe product'),
        'custom' => $this->t('Custom payment'),
      ],
      '#attributes' => [
        'id' => 'stripe-source',
      ],
    ];

    $form['stripe']['source_container']['stripe_amount'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Amount'),
      '#description' => $this->t('The amount should be with float value e.g. 100.00 equals 100.'),
      '#states' => [
        'visible' => [
          ':input[id="stripe-source"]' => ['value' => 'custom'],
        ],
        'required' => [
          ':input[id="stripe-source"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['stripe']['source_container']['stripe_currency'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Currency'),
      '#description' => $this->t('Three character currency code (e.g., usd).'),
      '#states' => [
        'visible' => [
          ':input[id="stripe-source"]' => ['value' => 'custom'],
        ],
        'required' => [
          ':input[id="stripe-source"]' => ['value' => 'custom'],
        ],
      ],
    ];

    $form['stripe']['source_container']['stripe_product'] = [
      '#type' => 'select',
      '#title' => $this->t('Stripe Product'),
      '#description' => $this->t('Select the Stripe product to associate with this form.'),
      '#options' => $this->stripeService->getProductsList(),
      '#states' => [
        'visible' => [
          ':input[id="stripe-source"]' => ['value' => 'product'],
        ],
        'required' => [
          ':input[id="stripe-source"]' => ['value' => 'product'],
        ],
      ],
    ];

    $form['stripe']['address_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Stripe address options'),
      '#open' => TRUE,
    ];

    $form['stripe']['address_container']['stripe_address'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Collect Stripe address information when collect payment.'),
      '#description' => $this->t('The Address Element enables you to collect local and international shipping or billing addresses of your customers.'),
      '#attributes' => [
        'id' => 'stripe-address',
      ],
    ];

    $form['stripe']['address_container']['stripe_address_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Stripe Address type'),
      '#description' => $this->t('Select the type of the address; shipping or billing address.'),
      '#options' => [
        'shipping' => $this->t('Collect shipping address'),
        'billing' => $this->t('Collect billing address'),
      ],
      '#states' => [
        'visible' => [
          ':input[id="stripe-address"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[id="stripe-address"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['stripe']['customer_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Stripe customer options'),
      '#open' => TRUE,
    ];

    $form['stripe']['customer_container']['stripe_customer'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow linking Stripe customers to Drupal users/visitors.'),
      '#description' => $this->t("This option allows organizing the payments done by certain users/visitors by mapping them to Stripe customers.<br /> A example of a usecase is when you want to group all payments of a user under one Stripe customer and activate Stripe customer portal on your website so the user can manage their subscriptions and payment.<br />This field accepts tokens."),
      '#attributes' => [
        'id' => 'stripe-enable-customer',
      ],
    ];

    $form['stripe']['customer_container']['stripe_email'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Stripe customer E-mail'),
      '#description' => $this->t("The customer email address to be used to send an invoice that Stripe generates, you may use tokens.<br /> Learn more about <a href='https://stripe.com/docs/invoicing/send-email'>Stripe Invoice</a>"),
      '#states' => [
        'visible' => [
          ':input[id="stripe-enable-customer"]' => ['checked' => FALSE],
        ],
      ],
    ];

    $form['stripe']['customer_container']['stripe_customer_attributes'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Customer attributes (YAML)'),
      '#description' => $this->t("Add the Stripe customer attributes, usually includes the metadata of the user ID.<br />
        Please note that the attributes that you will insert will be used to check if the customer exists, and if not it will create a new one.<br />
        <strong>The keys that will be used to retrieve the customer are:</strong>
        <ul>
          <li>The 'email' key</li>
          <li>The elements inside the 'metadata' array</li>
        </ul>
        See <a href='https://stripe.com/docs/api/customers/create'>Stripe Customer API</a>"),
      '#states' => [
        'visible' => [
          ':input[id="stripe-enable-customer"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[id="stripe-enable-customer"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['stripe']['appearance_container'] = [
      '#type' => 'details',
      '#title' => $this->t('Stripe appearance options'),
      '#open' => TRUE,
    ];

    $form['stripe']['appearance_container']['stripe_enable_appearance'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable custom stripe appearance'),
      '#description' => $this->t('Allow customizing Stripe appearance'),
      '#attributes' => [
        'id' => 'stripe-enable-appearance',
      ],
    ];

    $form['stripe']['appearance_container']['stripe_appearance'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Custom Stripe appearance (YAML)'),
      '#description' => $this->t("You can customize your Stripe Payment elements appearance. Please refer to <a href='https://stripe.com/docs/elements/appearance-api'>Stripe appearance API</a> for more details."),
      '#states' => [
        'visible' => [
          ':input[id="stripe-enable-appearance"]' => ['checked' => TRUE],
        ],
        'required' => [
          ':input[id="stripe-enable-appearance"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['stripe']['stripe_payment_intent_config'] = [
      '#type' => 'webform_codemirror',
      '#mode' => 'yaml',
      '#title' => $this->t('Stripe Payment Intent configurations (YAML)'),
      '#description' => $this->t('Handles custom configurations of <a href="https://stripe.com/docs/api/payment_intents/object">payment intent</a> for advanced use cases.') . '<br /><br />',
    ];
  }

  /**
   * The stripe_payment element where payment is succeeded.
   *
   * @param array $form
   *   The parent form elements.
   * @param \Stripe\PaymentIntent $paymentIntent
   *   The Stripe payment intent object.
   */
  public function formElementSucceeded(array &$form, PaymentIntent $paymentIntent) {
    $form['stripe_payment_succeeded'] = [
      '#type' => 'markup',
      '#markup' => '
      <div class="payment-info">
        <h2>Payment Processed</h2>
        <ul>
                  <li><strong>Status:</strong> ' . $paymentIntent->status . '</li>
                  <li><strong>Amount:</strong> ' . ($paymentIntent->amount / 100) . ' ' . strtoupper($paymentIntent->currency) . '</li>
                  <li><strong>Receipt Email:</strong> ' . $paymentIntent->receipt_email . '</li>
                  <li><strong>Created:</strong> ' . date('Y-m-d H:i:s', $paymentIntent->created) . '</li>
        </ul>
      </div>
            ',
    ];
  }

}
