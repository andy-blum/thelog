<?php

/**
* @file
* Contains \Drupal\the_log_utilities\EventSubscriber\FlagSubscriber.
*/

namespace Drupal\the_log_utilities\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ImportFinishedEvent;
use Drupal\node\Entity\Node;

class FeedsSubscriber implements EventSubscriberInterface {

  public function onImportFinished(ImportFinishedEvent $event) {


    $type = $event->getFeed()->getType()->id();

    if ($type === 'contracts') {
      $query = \Drupal::entityQuery('node')
        ->condition('type', 'contract')
        ->condition('field_status', 'waived');

      $nids = $query->execute();

      foreach ($nids as $nid) {
        $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
        $node->delete();
      }
    }
  }

  public static function getSubscribedEvents() {
    $events[FeedsEvents::IMPORT_FINISHED][] = ['onImportFinished'];
    return $events;
  }

}
