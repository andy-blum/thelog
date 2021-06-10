<?php

namespace Drupal\the_log_utilities\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\media\Entity\Media;
use Drupal\file\Entity\File;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Create a new node entity from a webform submission.
 *
 * @WebformHandler(
 *   id = "create_a_node",
 *   label = @Translation("Create a node"),
 *   category = @Translation("Entity Creation"),
 *   description = @Translation("Creates a new node from Webform Submissions."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */

class CreateNodeFromSubmission extends WebformHandlerBase {

  /**
   * {@inheritdoc}
   */

  // Function to be fired after submitting the Webform.
  public function postSave(WebformSubmissionInterface $webform_submission, $update = TRUE) {
    // Get an array of the values from the submission.
    $values = $webform_submission->getData();
    $uid = $webform_submission->getOwnerId();

    foreach ($values['bids'] as $bid) {
      $node_args = [
        'type' => 'bid',
        'langcode' => 'en',
        'created' => time(),
        'changed' => time(),
        'uid' => $uid,
        'title' => implode(' ', ['Bid', $bid['player'], $bid['salary'], $bid['years'], $bid['dts']]),
        'field_player' => $bid['player'],
        'field_bid_salary' => $bid['salary'],
        'field_bid_years' => $bid['years'],
        'field_bid_dts' => $bid['dts'],
      ];

      $node = Node::create($node_args);
      $node->save();
    }
  }
}
