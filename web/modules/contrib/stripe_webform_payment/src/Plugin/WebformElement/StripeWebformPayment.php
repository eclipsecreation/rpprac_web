<?php

namespace Drupal\stripe_webform_payment\Plugin\WebformElement;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\stripe_webform_payment\StripteWebformPaymentStripe;
use Drupal\webform\Plugin\WebformElementBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\stripe_webform_payment\StripteWebformPaymentWebform;

/**
 * Provides a 'stripe_payment' element.
 *
 * @WebformElement(
 *   id = "stripe_payment",
 *   label = @Translation("Stripe Payment"),
 *   description = @Translation("Provides a Stripe payment element."),
 *   category = @Translation("Advanced elements"),
 * )
 */
class StripeWebformPayment extends WebformElementBase {

  /**
   * The StripteWebformPaymentStripe Service.
   *
   * @var \Drupal\stripe_webform_payment\StripteWebformPaymentStripe
   */
  protected StripteWebformPaymentStripe $stripeService;

  /**
   * The StripteWebformPaymentWebform.
   *
   * @var \Drupal\stripe_webform_payment\StripteWebformPaymentWebform
   */
  protected StripteWebformPaymentWebform $webformService;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->stripeService = $container->get('stripe_webform_payment.stripe');
    $instance->webformService = $container->get('stripte_webform_payment.webform');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultProperties(): array {
    $elementProperties = $this->webformService
      ->getConfigurations();
    return $elementProperties + parent::getDefaultProperties();
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    // Inject the Stripe Payment Form elements.
    $this->webformService->getFormElements($form);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function prepare(array &$element, WebformSubmissionInterface $webform_submission = NULL) {
    parent::prepare($element, $webform_submission);

    $element['payment_intent_id'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Payment ID'),
      '#default_value' => '',
      '#required' => TRUE,
    ];

    // Get the webform object from the webform submission.
    $webform = $webform_submission->getWebform();

    // Get the AJAX setting from the webform object.
    $ajaxEnabled = $webform->getSetting('ajax');

    if ($ajaxEnabled) {
      $library = ['stripe_webform_payment/stripe_elements_ajax'];
    }
    else {
      $library = ['stripe_webform_payment/stripe_elements'];
    }

    $markup = '
        <div class="stripe-webform-payment-container">
            <div id="payment-message" class="hidden">
              <div class="messages__content"></div>
            </div>  <div id="payment-element"></div>
          </div>';

    if (isset($element['#stripe_address']) && !empty($element['#stripe_address'])) {
      $markup .= '<div id="address-element"></div>';
    }

    $element['stripe_placeholder'] = [
      '#type' => 'markup',
      '#markup' => $markup,
      '#attached' => [
        'library' => $library,
        'drupalSettings' => [
          'stripeWebformPayment' => [
            'publishableKey' => $this->stripeService->getPublishableKey(),
            'clientName' => $element['#stripe_name'] ?? '',
            'clientEmail' => $element['#stripe_email'] ?? '',
            'collectAddress' => $element['#stripe_address'] ?? NULL,
            'addressType' => $element['#stripe_address_type'] ?? NULL,
            'appearanceEnabled' => $element['#stripe_enable_appearance'] ?? NULL,
            'paymentAppearance' => $element['#stripe_appearance'] ? self::sanitizeArray(Yaml::decode($element['#stripe_appearance'])) : [],
            'clientSecret' => '',
            'webformId' => $element['#webform_id'],
            'returnUrl' => '/',
          ],
        ],
      ],
    ];

  }

  /**
   * {@inheritdoc}
   *
   * @throws \Stripe\Exception\ApiErrorException
   */
  public function alterForm(array &$element, array &$form, FormStateInterface $form_state) {
    parent::alterForm($element, $form, $form_state);

    $formStateStorage = $form_state->getStorage();
    $currentPage = $formStateStorage['current_page'];

    // Set webform attributes to be recognized by JS code.
    $form['#attributes']['data-stripe-form-id'] = $element['#webform_id'];

    // Handle the case where webform uses pages.
    if (!empty($currentPage)) {
      // If the webform uses multi-pages but the element is not in any page.
      if (array_key_exists($element['#webform_key'], $form['elements'])) {
        $this->handlePaymentForm($element, $form, $form_state);
      }
      // Load the updates on the pages contains the stripe payment element.
      elseif (in_array($currentPage, $element['#webform_parents'])) {
        $this->handlePaymentForm($element, $form, $form_state, $currentPage);
      }
    }
    // Handle the cause of webform is one page.
    else {
      $this->handlePaymentForm($element, $form, $form_state);
    }
  }

  /**
   * Handles the Stripe Payment form.
   *
   * @param array $element
   *   The Stripe Payment element.
   * @param array $form
   *   The Webform form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The webform form states.
   * @param string|null $currentPage
   *   The current page.
   *
   * @throws \Stripe\Exception\ApiErrorException
   */
  private function handlePaymentForm(array &$element, array &$form, FormStateInterface $formState, string $currentPage = NULL) {

    // Get the payment intent ID from user input.
    $userInput = $formState->getUserInput() ?? NULL;
    $paymentIntentId = $userInput['payment_intent_id'] ?? NULL;

    // Sanitize the user input.
    $paymentIntentId = $paymentIntentId ? strip_tags((string) $paymentIntentId) : NULL;

    if ($paymentIntentId) {
      // If the payment Intent ID is available, get the payment intent.
      $paymentIntent = $this->stripeService
        ->getPaymentIntent($paymentIntentId) ?? NULL;

      // If payment intent status is succeeded, load the payment information.
      if ($paymentIntent?->status &&'succeeded' === $paymentIntent->status) {
        $this->webformService
          ->formElementSucceeded($form, $paymentIntent);
      }
    }
    // If the payment intent ID is not available, generate it and update the
    // form elements.
    else {
      // If the webform is using multiple pages.
      if ($currentPage) {
        $this->updatePaymentForm($element, $form['elements'][$currentPage], $formState);
      }
      else {
        $this->updatePaymentForm($element, $form['elements'], $formState);
      }
      // Update the webform attributes.
      $this->updateSubmitAttributes($form, $formState);
    }
  }

  /**
   * Update Webform elements with values from Stripe paymentIntent.
   *
   * @param array $element
   *   The Webform Stripe element.
   * @param array $form
   *   The Webform submission form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Webform submission form status.
   *
   * @throws \Stripe\Exception\ApiErrorException
   */
  private function updatePaymentForm(array &$element, array &$form, FormStateInterface $form_state) {

    $webformKey = $this->getKey($element);

    // Create a new Stripe payment intent.
    $paymentIntent = $this->createPaymentIntent($element);
    $paymentIntentId = $paymentIntent->id;
    $paymentIntentClientSecret = $paymentIntent->client_secret;

    // Update the stripe_element default value and the client secret.
    $this->updateNestedValues($form, $webformKey, $paymentIntentId, $paymentIntentClientSecret, $form_state);

    $form_state->setValue($element['#webform_key'], $paymentIntentId);
  }

  /**
   * {@inheritdoc}
   */
  protected function defineDefaultProperties(): array {
    $default = parent::defineDefaultProperties();
    unset($default['required']);
    unset($default['required_error']);
    unset($default['prepopulate']);
    unset($default['disabled']);
    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function initialize(array &$element) {
    parent::initialize($element);
    // Set a validation class for the stripe_payment element type.
    $element['#element_validate'][] = [
      get_called_class(),
      'validatePaymentIntent',
    ];
  }

  /**
   * Checks if webform is enabled and if so, gets the ajax setting.
   */
  protected function isWebformAjaxEnabled(FormStateInterface $formState) {
    // Get the webform object.
    $webform = $formState->getFormObject()?->getWebform() ?? NULL;
    if (!$webform) {
      return FALSE;
    }
    // Get the ajax setting from the webform object.
    return $webform?->getSetting('ajax');
  }

  /**
   * Validates the Stripe payment on form submission.
   *
   * @param array $element
   *   The element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function validatePaymentIntent(array &$element, FormStateInterface $form_state) {
    // Retrieve the payment intent from the submitted values.
    $formStateStorage = $form_state->getStorage();
    $currentPage = $formStateStorage['current_page'];

    // Handle the case of webform uses pages.
    if (!empty($currentPage)) {
      // Load the updates on the pages contains the stripe payment element.
      if (in_array($currentPage, $element['#webform_parents'])) {
        self::validateIntentId($element, $form_state);
      }
    }

    // Handle the case where webform uses a single form.
    else {
      self::validateIntentId($element, $form_state);
    }

  }

  /**
   * Validate Stripe paymentIntent ID.
   *
   * @param array $element
   *   The Stripe payment element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The Webform submission form state.
   */
  public static function validateIntentId(array &$element, FormStateInterface $form_state) {
    $payment_intent_id = $form_state->getValue('payment_intent_id');
    // Check if the payment intent ID exists.
    if (empty($payment_intent_id)) {
      $form_state->setError($element, t('The payment intent ID is missing. Please try again.'));
      return;
    }

    // Retrieve the payment intent from Stripe.
    $stripeService = \Drupal::service('stripe_webform_payment.stripe');
    $paymentIntent = $stripeService->getPaymentIntent($payment_intent_id);

    // Check if the payment intent status is 'succeeded'.
    if ('succeeded' !== $paymentIntent->status) {
      $form_state->setError($element, $paymentIntent->status);
      return;
    }
    $form_state->setValueForElement($element, $payment_intent_id);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItem(array $element, WebformSubmissionInterface $webform_submission, array $options = []) {
    return $this->getValue($element, $webform_submission, $options);
  }

  /**
   * Creates Stripe payment intent.
   *
   * @param array $element
   *   The Stripe Payment element.
   *
   * @return \Stripe\PaymentIntent|void|null
   *   Stripe payment intent if available.
   *
   * @throws \Stripe\Exception\ApiErrorException
   */
  protected function createPaymentIntent(array $element) {
    $source = $element['#stripe_source'];

    // Get the amount and currency from element settings.
    if ('custom' === $source) {
      $amount = floatval($element['#stripe_amount']) * 100;
      $currency = $element['#stripe_currency'];
    }
    elseif ('product') {
      $price = $this->stripeService
        ->getProductPrice($element['#stripe_product']);
      $amount = $price['amount'];
      $currency = $price['currency'];
    }
    if (!$amount || !$currency) {
      $this->messenger()->addError($this->t("The price amount or currency is not defined, please correct this and try again."));
      return;
    }

    $options = Yaml::decode($element['#stripe_payment_intent_config']);
    // Sanitize the options as they are inserted by the user.
    $options = $options ? self::sanitizeArray($options) : [];

    $linkCustomer = $element['#stripe_customer'] ?? NULL;

    if ($linkCustomer) {
      $stripeCustomer = $this->getCustomer($element);
      if (empty($stripeCustomer)) {
        $stripeCustomer = $this->createCustomer($element);
      }

      $stripeCustomerId = $stripeCustomer?->id;

      $stripeCustomerId ?
        $options['customer'] = $stripeCustomerId : NULL;
    }

    // Make payment intent.
    return $this->stripeService
      ->createPaymentIntent($amount, $currency, $options);
  }

  /**
   * Get Stripe customer.
   *
   * @param array $element
   *   The stripe_payment element.
   *
   * @return \Stripe\Collection
   *   The customer object if exists.
   *
   * @throws \Stripe\Exception\ApiErrorException
   */
  protected function getCustomer(array $element) {
    $customerAttributes = Yaml::decode($element['#stripe_customer_attributes']);
    $customerAttributes = $customerAttributes ? self::sanitizeArray($customerAttributes) : NULL;

    if (!empty($customerAttributes)) {
      $emailFilter = [];

      if (array_key_exists('email', $customerAttributes)) {
        $emailFilter['email'] = $customerAttributes['email'];

        // Remove the email to user $customerAttributes in future validation.
        unset($customerAttributes['email']);
      }
      $emailFilter = !empty($emailFilter) ? $emailFilter : NULL;
      $customers = $this->stripeService->getAllCustomers($emailFilter);

      $filteredCustomers = array_filter($customers, function ($customer) use ($customerAttributes) {
        $customerMetadata = $customer->metadata->toArray();

        // Check if customer metadata contains all the attributes.
        foreach ($customerAttributes['metadata'] as $key => $value) {

          if (!isset($customerMetadata[$key]) && $customerMetadata[$key] !== $value) {
            return FALSE;
          }
        }

        return TRUE;
      });

      if (count($filteredCustomers) > 0) {
        // Get the first customer with the matching metadata.
        return reset($filteredCustomers);
      }
    }

    return NULL;
  }

  /**
   * Create Stripe customer.
   *
   * @param array $element
   *   The stripe_payment element.
   *
   * @return \Stripe\Customer|null
   *   The customer object if exists.
   */
  protected function createCustomer(array $element) {

    $customerAttributes = Yaml::decode($element['#stripe_customer_attributes']);
    $customerAttributes = $customerAttributes ?
      self::sanitizeArray($customerAttributes) : NULL;

    if (!empty($customerAttributes)) {
      return $this->stripeService
        ->createCustomer($customerAttributes);
    }

    return NULL;
  }

  /**
   * Handle the case of the Stripe Payment Element is nested in parent elements.
   *
   * @param array $elements
   *   The Stripe payment element.
   * @param string $webformKey
   *   The Webform key.
   * @param string $paymentIntentId
   *   The Stripe paymentIntent ID.
   * @param string $clientSecret
   *   The client secret from the Stripe payment element.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The webform state.
   */
  private function updateNestedValues(array &$elements, string $webformKey, string $paymentIntentId, string $clientSecret, FormStateInterface $formState = NULL) {
    // Handle the form with single page.
    if (isset($elements[$webformKey]['payment_intent_id'])) {
      $this->updateValue($elements, $webformKey, $paymentIntentId, $clientSecret, $formState);
    }
    else {
      foreach ($elements as $key => &$element) {
        if (!is_array($element)) {
          continue;
        }
        if (isset($element[$webformKey]['payment_intent_id'])) {
          $this->updateValue($element, $webformKey, $paymentIntentId, $clientSecret, $formState);
          break;
        }
        // If the current element is an array, call the function recursively.
        if (is_array($element)) {
          $this->updateNestedValues($element, $webformKey, $paymentIntentId, $clientSecret, $formState);
        }
      }
    }
  }

  /**
   * Update the element with paymentIntent ID and client secret.
   *
   * @param array $element
   *   The Webform payment element.
   * @param string $webformKey
   *   The Webform key.
   * @param string $paymentIntentId
   *   The Stripe paymentIntent ID.
   * @param string $clientSecret
   *   The client secret from the Stripe payment element.
   * @param \Drupal\Core\Form\FormStateInterface|null $formState
   *   The webform state.
   */
  private function updateValue(array &$element, string $webformKey, string $paymentIntentId, string $clientSecret, FormStateInterface $formState = NULL) {
    // Update the #default_value.
    $element[$webformKey]['payment_intent_id']['#default_value'] = $paymentIntentId;

    // Update the clientSecret.
    $stripeFormElement = $element[$webformKey]['stripe_placeholder']['#attached']['drupalSettings']['stripeWebformPayment'] ?? NULL;
    if ($stripeFormElement) {
      $element[$webformKey]['stripe_placeholder']['#attached']['drupalSettings']['stripeWebformPayment']['clientSecret'] = $clientSecret;
    }

  }

  /**
   * Update the webform submission element with necessary attributes.
   *
   * @param array $form
   *   The Webform submission form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The Webform submission form state.
   */
  private function updateSubmitAttributes(array &$form, FormStateInterface $formState) {

    // Set an array of possible submitters of the stripe_payment elements.
    $submitKeys = ['submit', 'preview_next', 'wizard_next'];

    foreach ($submitKeys as $submitKey) {
      if (isset($form['actions'][$submitKey])) {

        // Set a class identifier for the form submit element.
        if (!isset($form['actions'][$submitKey]['#attributes'])) {
          $form['actions'][$submitKey]['#attributes'] = [];
        }
        if (!isset($form['actions'][$submitKey]['#attributes']['class'])) {
          $form['actions'][$submitKey]['#attributes']['class'] = [];
        }

        $form['actions'][$submitKey]['#attributes']['class'][] = 'stripe-submit-button';

        // Set a second data identifier for the form submit element.
        $form['actions'][$submitKey]['#attributes']['data-stripe-submit'] = 'stripe-webform-submit-button';
        $input = $formState->getUserInput();

        // If webform is ajax-enabled.
        if ($this->isWebformAjaxEnabled($formState)) {
          // Set a class identifier for the form ajax submit.
          $form['actions'][$submitKey]['#attributes']['class'][] = 'stripe-webform-submit-button-ajax';
        }
      }
    }
  }

  /**
   * Sanitize a string value, let mixed as the parent array may send mixed data.
   *
   * @param mixed $value
   *   The value to sanitize.
   *
   * @return mixed|string
   *   Sanitized value.
   */
  private static function sanitize(mixed $value): mixed {
    if (is_string($value)) {
      return strip_tags($value);
    }
    return $value;
  }

  /**
   * Sanitize array values.
   *
   * @param array $array
   *   The array to sanitize.
   *
   * @return array
   *   The sanatized array.
   */
  private static function sanitizeArray(array $array) {
    return array_map(function ($value) {
      if (is_array($value)) {
        return self::sanitizeArray($value);
      }
      return self::sanitize($value);
    }, $array);
  }

}
