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
function thelog_preprocess_node(&$variables) {
  $node = $variables['node'];

  $flag_types = \Drupal::service('flag')->getAllFlags('node', 'contract');
  $flags = [];

  foreach ($flag_types as $flag_type) {
    $flags[$flag_type->id] = $flag_type->isFlagged($node);
  }

  $variables['flags'] = $flags;
}



