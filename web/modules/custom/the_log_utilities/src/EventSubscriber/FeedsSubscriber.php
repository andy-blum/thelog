<?php

/**
 * @file
 * Contains \Drupal\the_log_utilities\EventSubscriber\FlagSubscriber.
 */

namespace Drupal\the_log_utilities\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ImportFinishedEvent;
use Drupal\feeds\Event\InitEvent;
use Drupal\feeds\Event\ParseEvent;
use Drupal\the_log_utilities\ContractManager;

class FeedsSubscriber implements EventSubscriberInterface {

  /**
   * Runs before the feed starts.
   *
   * - Deletes waived contracts.
   *
   * @param [type] $event
   * @return void
   */
  public function beforeImportStart(InitEvent $event) {
    $type = $event->getFeed()->getType()->id();
    $stage = $event->getStage();

    if ($type === 'contracts' && $stage === 'fetch') {
      // Delete all waived contracts
      $nids = \Drupal::entityQuery('node')
        ->condition('type', 'contract')
        ->condition('field_status', 'waived')
        ->execute();

      foreach ($nids as $nid) {
        \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($nid)->delete();
      }
    }
  }

  public function afterParse(ParseEvent $event) {

    $type = $event->getFeed()->getType()->id();
    $result = $event->getParserResult();

    if ($type === 'contracts' && $result) {
      for ($i = 0; $i < $result->count(); $i++) {
        if (!$result->offsetExists($i)) {
          break;
        }

        /** @var \Drupal\feeds\Feeds\item\ItemInterface $item */
        $item = $result->offsetGet($i);
        $data = $item->toArray();

        $nid = $data['nid'];
        $salary = intval($data['salary']);
        $years = intval($data['years']);
        $status = $data['status'];

        // All contracts decrement by 1 year
        $years --;

        // 0-year contracts go to RFA
        if (!$years && $status !== 'dts') {
          $status = 'rfa';
        }

        $logger = \Drupal::logger('Contract Updates');

        switch ($status) {
          case 'active':
            $salary = round($salary * 1.1);
            break;

          case 'dts':
            // TODO: flag top-level DTS contractsx
            break;

          case 'ir':
            $status = 'active';
            $salary = round($salary * 1.1);
            break;

          case 'waived':
            $result->offsetUnset($i);
            $i--;
            break;

          case 'rfa':
            break;

          default:
            $logger->error('Contract @nid has an impossible contract status: @status', [
              '@nid' => $nid,
              '@status' => $status
            ]);
            break;
        }

        // Convert cents to dollars
        $salary = $salary / 100;

        $item->set('years', $years);
        $item->set('salary', $salary);
        $item->set('status', $status);
      }
    }

  }

  /**
   * Runs after the feed completes.
   *
   * - Updates all cap values
   *
   * @param ImportFinishedEvent $event
   * @return void
   */
  public function onImportFinished(ImportFinishedEvent $event) {
    $uids = \Drupal::entityQuery('user');

    foreach ($uids as $uid) {
      $user = \Drupal::entityTypeManager()->getStorage('user')->load($uid);
      if (is_a($user, 'Drupal\user\Entity\User')) {
        ContractManager::updateCapHit($user);
      }
    }
  }

  public static function getSubscribedEvents() {
    $events = [
      FeedsEvents::INIT_IMPORT => 'beforeImportStart',
      FeedsEvents::IMPORT_FINISHED => 'onImportFinished',
    ];

    $events[FeedsEvents::PARSE][] = ['afterParse', FeedsEvents::AFTER];

    return $events;
  }
}
