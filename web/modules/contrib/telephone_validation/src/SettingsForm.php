<?php

namespace Drupal\telephone_validation;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use libphonenumber\PhoneNumberFormat;

/**
 * Form for default validation settings.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The validator.
   *
   * @var \Drupal\telephone_validation\Validator
   */
  protected Validator $validator;

  /**
   * Element info manager.
   *
   * @var \Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected ElementInfoManagerInterface $elementInfoManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): static {
    $object = parent::create($container);
    $object->validator = $container->get('telephone_validation.validator');
    $object->elementInfoManager = $container->get('plugin.manager.element_info');
    return $object;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'telephone_validation_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'telephone_validation.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Retrieve configuration object.
    $config = $this->config('telephone_validation.settings');

    // Define valid telephone format.
    $form['format'] = [
      '#type' => 'select',
      '#title' => $this->t('Format'),
      '#default_value' => $config->get('format') ?: PhoneNumberFormat::E164,
      '#options' => [
        PhoneNumberFormat::E164 => $this->t('E164'),
        PhoneNumberFormat::NATIONAL => $this->t('National'),
      ],
      '#ajax' => [
        'callback' => '::getCountry',
        'wrapper' => 'telephone-validation-country',
        'method' => 'replace',
      ],
    ];

    // Define available countries (or country if format = NATIONAL).
    $val = $form_state->getValue('format') ?: $form['format']['#default_value'];
    $form['country'] = [
      '#type' => 'select',
      '#title' => $this->t('Valid countries'),
      '#description' => t('If no country selected all countries are valid.'),
      '#default_value' => $config->get('country') ?: [],
      '#multiple' => $val != PhoneNumberFormat::NATIONAL,
      '#options' => $this->validator->getCountryList(),
      '#prefix' => '<div id="telephone-validation-country">',
      '#suffix' => '</div>',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Ajax callback.
   */
  public function getCountry(array &$form, FormStateInterface $form_state) {
    return $form['country'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $country = $form_state->getValue('country');
    // Save new config.
    $this->config('telephone_validation.settings')
      ->set('format', $form_state->getValue('format'))
      ->set('country', is_array($country) ? $country : [$country])
      ->save();
    // Clear element info cache.
    $this->elementInfoManager->clearCachedDefinitions();

    parent::submitForm($form, $form_state);
  }

}
