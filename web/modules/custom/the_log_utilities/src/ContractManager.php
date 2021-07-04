<?php

namespace Drupal\the_log_utilities;

use Drupal\Core\Entity\EntityInterface;
use Drupal\user\Entity\User;

class ContractManager {

  const YEAR_CAP = 100;
  const SALARY_CAP = 1000;
  const CONTRACT_CAP = 40;
  const DTS_CAP = 13;

  protected static function db() {
    return \Drupal::database();
  }

  public static function getContract($id) {
    $database = self::db();
    $query = $database->select('node_field_data', 'node');
    $query->condition('node.type', 'contract', '=');
    $query->condition('node.nid', $id, '=');
    $query->join('node__field_salary', 'salary', 'node.nid = salary.entity_id');
    $query->join('node__field_years_remaining', 'years', 'node.nid = years.entity_id');
    $query->join('node__field_status', 'status', 'node.nid = status.entity_id');
    $query->join('node__field_player', 'player', 'node.nid = player.entity_id');
    $query->fields('node', ['nid', 'status', 'title']);
    $query->fields('salary', ['field_salary_value']);
    $query->fields('years', ['field_years_remaining_value']);
    $query->fields('player', ['field_player_target_id']);
    $query->fields('status', ['field_status_value']);
    $query->range(0, 1);
    $results = $query->execute()->fetchAll();

    return $results;
  }

  public static function getAllContractsForUser($uid) {
    $database = self::db();
    $query = $database->select('node_field_data', 'node');
    $query->condition('node.type', 'contract', '=');
    $query->condition('node.uid', $uid, '=');
    $query->join('node__field_salary', 'salary', 'node.nid = salary.entity_id');
    $query->join('node__field_years_remaining', 'years', 'node.nid = years.entity_id');
    $query->join('node__field_status', 'status', 'node.nid = status.entity_id');
    $query->join('node__field_player', 'player', 'node.nid = player.entity_id');
    $query->fields('node', ['nid', 'status', 'title']);
    $query->fields('salary', ['field_salary_value']);
    $query->fields('years', ['field_years_remaining_value']);
    $query->fields('player', ['field_player_target_id']);
    $query->fields('status', ['field_status_value']);
    $results = $query->execute()->fetchAll();

    return $results;
  }

  public static function getAllContractValues($uid) {

    $contracts = self::getAllContractsForUser($uid);

    $total_salary = 0;
    $total_years = 0;
    $active_contracts = 0;
    $dts_contracts = 0;

    foreach ($contracts as $contract) {

      if ($contract->field_years_remaining_value > 0) {
        switch ($contract->field_status_value) {
          case 'active':
            $total_salary += $contract->field_salary_value;
            $total_years += $contract->field_years_remaining_value;
            $active_contracts++;
            break;

          case 'dts':
            $total_salary += ($contract->field_salary_value / 10);
            $dts_contracts++;
            break;

          case 'ir':
            $total_salary += ($contract->field_salary_value / 2);
            $total_years += ($contract->field_years_remaining_value - 1);
            break;

          case 'waived':
            $total_salary += ($contract->field_salary_value / 2);
            $total_years++;
            break;

          default:
            break;
        }
      }
    }

    return [
      'salary' => $total_salary,
      'years' => $total_years,
      'contracts' => $active_contracts,
      'dts' => $dts_contracts,
    ];
  }

  public static function updateCapHit(User $user) {
    $values = self::getAllContractValues($user->id());

    $user->field_total_contracts->set(0, strval($values['contracts']));
    $user->field_total_contract_years->set(0, strval($values['years']));
    $user->field_total_salary->set(0, strval($values['salary']));
    $user->field_dts_slots->set(0, strval($values['dts']));
    $user->save();
  }
}
