<?php

/**
 * @file
 * thelog theme system, which controls the output of thelog.
 *
 * The theme system allows for nearly all output of the Drupal system to be
 * customized by UIkit themes.
 */

/**
 * Implements hook_theme_suggestions_HOOK_alter().
 */
function thelog_theme_suggestions_user_alter(array &$suggestions, array $variables) {
  $user = $variables['elements']['#user'];
  foreach ($user->getRoles() as $role) {
    $suggestions[] = implode('__', ['user', $role]);
  }
}
