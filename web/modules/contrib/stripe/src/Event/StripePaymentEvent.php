<?php

namespace Drupal\stripe\Event;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Wraps a stripe event for webhook.
 */
class StripePaymentEvent extends Event {

  /**
   * The form.
   *
   * @var array
   */
  private $form;

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  private $formState;

  /**
   * Stripe element name.
   *
   * @var array
   */
  private $element;

  /**
   * The description.
   *
   * @var string
   */
  private $description = '';

  /**
   * Total label and amounts.
   *
   * @var array
   */
  private $total = [];

  /**
   * Total label and amounts.
   *
   * @var array
   */
  private $billing = [];

  /**
   * Metadata key-value array.
   *
   * @var array
   */
  private $metadata = [];

  /**
   * Additional settings data.
   *
   * @var array
   */
  private $settings = [];

  /**
   * Constructor.
   *
   * @param array $form
   *   Nested array of form elements that comprise the form.
   * @param \Drupal\Core\Form\FormStateInterface $formState
   *   The current state of the form. The arguments that
   *   \Drupal::formBuilder()->getForm() was originally called with are
   *   available in the array $form_state->getBuildInfo()['args'].
   */
  public function __construct(array &$form, FormStateInterface $formState, $element) {
    $this->form = &$form;
    $this->formState = $formState;
    $this->element = $element;
  }

  /**
   * Get the form.
   *
   * @return array
   *   The form.
   */
  public function &getForm(): array {
    return $this->form;
  }

  /**
   * Get the form state.
   *
   * @return \Drupal\Core\Form\FormStateInterface
   *   The form state.
   */
  public function getFormState(): FormStateInterface {
    return $this->formState;
  }

  /**
   * Get form element.
   */
  public function getFormElement(): string {
    return $this->element;
  }

  /**
   * Get the form id.
   *
   * @return string
   *   The form id.
   */
  public function getFormId(): string {
    return $this->formId;
  }

  /**
   * Set the total.
   */
  public function setTotal(int $amount, string $label) {
    $this->total['amount'] = $amount;
    $this->total['label'] = $label;
  }

  /**
   * Set the billing city.
   */
  public function setBillingCity(string $city) {
    $this->billing['address']['city'] = $city;
  }

  /**
   * Set the billing country.
   */
  public function setBillingCountry(string $country) {
    $this->billing['address']['country'] = $country;
  }

  /**
   * Set the billing address1.
   */
  public function setBillingAddress1(string $address1) {
    $this->billing['address']['line1'] = $address1;
  }

  /**
   * Set the billing address2.
   */
  public function setBillingAddress2(string $address2) {
    $this->billing['address']['line2'] = $address2;
  }

  /**
   * Set the billing postal code.
   */
  public function setBillingPostalCode(string $postal_code) {
    $this->billing['address']['postal_code'] = $postal_code;
  }

  /**
   * Set the billing state.
   */
  public function setBillingState(string $state) {
    $this->billing['address']['state'] = $state;
  }

  /**
   * Set the billing email.
   */
  public function setBillingEmail(string $email) {
    $this->billing['email'] = $email;
  }

  /**
   * Set the billing name.
   */
  public function setBillingName(string $name) {
    $this->billing['name'] = $name;
  }

  /**
   * Set the billing phone.
   */
  public function setBillingPhone(string $phone) {
    $this->billing['phone'] = $phone;
  }

  /**
   * Set description.
   */
  public function setDescription(string $description) {
    $this->description = $description;
  }

  /**
   * Set stripe metadata.
   */
  public function setMetadata(string $key, $value) {
    $this->metadata[$key] = $value;
  }

  /**
   * Set stripe settings.
   */
  public function setSetting(string $key, $value) {
    $this->settings[$key] = $value;
  }

  /**
   * Get stripe metadata.
   */
  public function getMetadata(): array {
    return $this->metadata;
  }

  /**
   * Get billing details.
   */
  public function getBillingDetails(): array {
    return $this->billing;
  }

  /**
   * Get description.
   */
  public function getDescription(): string {
    return $this->description;
  }

  /**
   * Get total.
   */
  public function getTotal(): array {
    return $this->total;
  }

  /**
   * Get settings.
   */
  public function getSettings(): array {
    return $this->settings;
  }

}
