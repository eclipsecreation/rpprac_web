<?php

namespace Drupal\webform_sanitize_submissions\Plugin\WebformHandler;

use Drupal\Core\Database\Connection;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @WebformHandler(
 *   id = "sanitize_submission",
 *   label = @Translation("Sanitize submission"),
 *   category = @Translation("Advanced"),
 *   description = @Translation("Sanitize submission data of selected elements."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class SanitizeSubmissionWebformHandler extends WebformHandlerBase {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->database = $container->get('database');

    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#markup' => $this->formatPlural(
        count($this->getElementsToSanitize()),
        'Sanitize submission data of 1 selected element.',
        'Sanitize submission data of @count selected elements.',
      ),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    if ($this->getWebform()->isResultsDisabled()) {
      return;
    }

    foreach ($this->getElementsToSanitize() as $name => $element) {
      $this->clearElementData($webform_submission, $name);
    }
  }

  /**
   * Get elements that need to be sanitized.
   *
   * @return array
   *   An array of elements that need to be sanitized.
   */
  protected function getElementsToSanitize(): array {
    return array_filter(
      $this->getWebform()->getElementsDecodedAndFlattened(),
      function ($element) { return !empty($element['#sanitize']); },
    );
  }

  /**
   * Clear submission data for a specific element.
   *
   * We're deleting the element data straight from the database to avoid triggering any hooks.
   *
   * @param \Drupal\webform\WebformSubmissionInterface $submission
   *   The webform submission.
   * @param string $elementName
   *   The element name.
   */
  protected function clearElementData(WebformSubmissionInterface $submission, string $elementName): void {
    $this->database->delete('webform_submission_data')
      ->condition('sid', $submission->id())
      ->condition('name', $elementName)
      ->execute();

    $submission->setElementData($elementName, NULL);
    $this->submissionStorage->resetCache([$submission->id()]);
  }

}
