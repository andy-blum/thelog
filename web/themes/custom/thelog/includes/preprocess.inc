<?php

use Drupal\the_log_utilities\ContractManager;

/**
 * @file
 * Set up variables to be placed within the template (.html.twig) files.
 *
 * The variables set up here apply to both templates (.html.twig) files and
 * functions (theme_HOOK). These are also used for providing
 * @link https://www.drupal.org/node/2354645 Twig Template naming conventions @endlink.
 *
 * @see process.inc
 */


/**
 * Implements hook_preprocess_HOOK().
 */
function thelog_preprocess_page(&$variables) {
}

/**
 * Implements hook_preprocess_HOOK().
 */
function thelog_preprocess_node(&$variables) {
  /**
   * @var \Drupal\node\Entity\Node
   */
  $node = $variables['node'];
  $type = $node->bundle();

  $flag_types = \Drupal::service('flag')->getAllFlags('node', 'contract');
  $flags = [];

  foreach ($flag_types as $flag_type) {
    $flags[$flag_type->id] = $flag_type->isFlagged($node);
  }

  $variables['flags'] = $flags;

  if ($type === 'player') {
    $injury_status = $node->get('field_injury_status')->referencedEntities();
    $variables['injury_status'] = $injury_status[0]->label();

    if (!$node->get('field_is_rookie')->isEmpty()) {
      $isRookie = boolval($node->get('field_is_rookie')->first()->getValue()['value']);
      $variables['is_rookie'] = $isRookie;
    }

    $flaglink = \Drupal::service('flag.link_builder')
      ->build('node', $node->id(), 'watchlist');
    $variables['add_to_watchlist'] = $flaglink;

    /**
     * @var \Drupal\node\Entity\Node
     */
    $has_contract = !$node->get('field_contract')->isEmpty();
    $contract_info = null;
    if ($has_contract) {
      $contract = $node->get('field_contract')->referencedEntities()[0];

      $years = "??";
      if (!$contract->field_years_remaining->isEmpty()) {
        $years = intval($contract->get('field_years_remaining')
          ->first()->getValue()['value']);
      }

      $salary = "??";
      if (!$contract->field_salary->isEmpty()) {
        $salary = strval(
          number_format(
            floatval(
              $contract->get('field_salary')
                ->first()->getValue()['value']
            ),
            2
          )
        );
      }

      $contract_info = "(\${$salary} {$years}yrs)";
    } else {
      $contract_info = "(FA)";
    }
    $variables['contract_info'] = $contract_info;
  }

  if ($type === 'contract') {
    $flaglink = \Drupal::service('flag.link_builder');
    $status = $node->get('field_status')->getString();

    switch ($status) {
      case 'active':
        $flag_types = [
          'tradable',
          'untouchable',
          'injured_reserve',
          'waived',
        ];
        break;

      case 'dts':
        $flag_types = [
          'tradable',
          'untouchable',
          'promote',
          'waived',
        ];
        break;

      default:
        $flag_types = [];
        break;
    }

    $variables['flag_links'] = [];

    foreach ($flag_types as $flag_type) {
      $variables['flag_links'][] = $flaglink
        ->build('node', $node->id(), $flag_type);
    }
  }
}
