<?php

namespace Drupal\the_log_utilities\Plugin\WebformHandler;

use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\Component\Utility\Html;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\user\Entity\User;
use Drupal\the_log_utilities\ContractManager;

/**
 * Webform validate handler.
 *
 * @WebformHandler(
 *   id = "my_module_custom_validator",
 *   label = @Translation("Alter form to validate it"),
 *   category = @Translation("Settings"),
 *   description = @Translation("Form alter to validate it."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 * )
 */
class ValidateBids extends WebformHandlerBase {

  public function validateForm(
    array &$form,
    FormStateInterface $form_state,
    WebformSubmissionInterface $webform_submission
  ) {

    $this->formState = $form_state;

    $bids = $webform_submission->getData()['bids'];
    $team = $webform_submission->getOwner();

    // Make sure submitter is a team owner
    $this->checkRole($team, $form_state);

    $contract_totals = ContractManager::getAllContractValues($team->id());

    foreach ($bids as $bid) {

      // Check each bid against the caps.
      $this->checkCapSpace($bid, $contract_totals, $form_state);

      // Make sure DTS bids are on rookies
      if ($bid['dts']) {
        $this->checkDTSEligibility($bid, $form_state);
      }

      // Make sure bids with more than 1 contract year are >= $5
      if (intval($bid['years']) > 1) {
      }
    }
  }

  private function checkRole(User $user, FormStateInterface $formState) {
    if (!$user->hasRole('team_owner')) {
      $formState->setErrorByName('bids', 'You must be a team owner to complete this form.');
    }
  }

  private function checkCapSpace(
    $bid,
    $contract_totals,
    FormStateInterface $formState
  ) {

    // Check salary cap.
    if (($contract_totals['salary'] + $bid['salary']) > ContractManager::SALARY_CAP) {
      $player = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($bid['player']);

      $message = 'Bid for ' . $player->label() . ' would put you over the salary cap!';

      $formState->setErrorByName('bids', $message);
    }

    // Check contract year cap.
    if (($contract_totals['years'] + $bid['years']) > ContractManager::YEAR_CAP) {
      $player = \Drupal::entityTypeManager()
        ->getStorage('node')
        ->load($bid['player']);

      $message = 'Bid for ' . $player->label() . ' would put you over the contract year cap!';

      $formState->setErrorByName('bids', $message);
    }

    // Check the contract count cap.
    if ($contract_totals['contracts'] == ContractManager::CONTRACT_CAP) {
      $message = 'You are currently at the maximum number of active players.';
      $formState->setErrorByName('bids', $message);
    }

    // Check the DTS cap.
    if (
      $bid['dts'] &&
      $contract_totals['contracts'] == ContractManager::DTS_CAP
    ) {
      $message = 'You are currently at the maximum number of DTS players.';
      $formState->setErrorByName('bids', $message);
    }

  }

  private function checkDTSEligibility(
    $bid,
    FormStateInterface $formState
  ) {
    $player = \Drupal::entityTypeManager()
      ->getStorage('node')
      ->load($bid['player']);

    $isRookie = boolval($player->field_is_rookie->getString());

    if (!$isRookie) {
      $message = $player->label() . ' is not eligible for DTS!';
      $formState->setErrorByName('bids', $message);
    }
  }
}
