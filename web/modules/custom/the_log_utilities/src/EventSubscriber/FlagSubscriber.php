<?php

/**
* @file
* Contains \Drupal\the_log_utilities\EventSubscriber\FlagSubscriber.
*/

namespace Drupal\the_log_utilities\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\node\Entity\Node;

class FlagSubscriber implements EventSubscriberInterface {

  public function onFlag(FlaggingEvent $event) {
    $flagging = $event->getFlagging();
    $entity = $flagging->getFlaggable();
    $flag_type = $flagging->getFlag()->id;

    if ($flag_type == 'terminate_contract') {
      $player_id = $entity->field_player->getString();

      // Add contract form URL.
      $url = \Drupal\Core\Url::fromRoute('node.add', [
        'node_type' => 'contract',
      ]);

      // Set up queries.
      $url->setOptions(['query' => [
        'field_player' => $player_id,
      ]]);

      // Redirect to new contract form.
      $response = new RedirectResponse($url->toString());
      $response->send();
    }

    if ($flag_type == 'bid_approved') {

      $player = $entity->field_player->first()->entity;
      $salary = $entity->field_bid_salary->getString();
      $years = $entity->field_bid_years->getString();
      $isDTS = boolval($entity->field_bid_dts->getString());

      $node_args = [
        'type' => 'contract',
        'langcode' => 'en',
        'created' => time(),
        'changed' => time(),
        'uid' => $entity->getOwner(),
        'field_player' => $player,
        'field_salary' => $salary,
        'field_years_remaining' => $years,
        'field_status' => $isDTS ? 'dts' : 'active',
      ];

      $node = Node::create($node_args);
      $node->save();
    }

    if ($flag_type == 'waived') {
      $entity->field_status->set(0, 'waived');
      $entity->save();
    }
  }

  public function onUnflag(UnflaggingEvent $event) {
    $flagging = $event->getFlaggings();
    $flagging = reset($flagging);
    $entity_nid = $flagging->getFlaggable()->id();
    // WRITE SOME CUSTOM LOGIC
  }

  public static function getSubscribedEvents() {
    $events = [];
    $events[FlagEvents::ENTITY_FLAGGED][] = ['onFlag'];
    $events[FlagEvents::ENTITY_UNFLAGGED][] = ['onUnflag'];
    return $events;
  }

}
