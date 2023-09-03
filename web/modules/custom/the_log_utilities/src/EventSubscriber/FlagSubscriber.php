<?php

/**
 * @file
 * Contains \Drupal\the_log_utilities\EventSubscriber\FlagSubscriber.
 */

namespace Drupal\the_log_utilities\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Drupal\flag\Event\FlagEvents;
use Drupal\flag\Event\FlaggingEvent;
use Drupal\flag\Event\UnflaggingEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\node\Entity\Node;

class FlagSubscriber implements EventSubscriberInterface {

  public function onFlag(FlaggingEvent $event) {
    $flagging = $event->getFlagging();
    $entity = $flagging->getFlaggable();
    $flag = $flagging->getFlag();
    $flag_type = $flag->id;
    $flag_service = \Drupal::service('flag');

    // Ends RFA contract & redirects to new contract form.
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

      $entity->delete();

      // Redirect to new contract form.
      $response = new RedirectResponse($url->toString());
      $response->send();
    }

    // Creates new contract node with values from bid node
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

    // Moves contracts to waivers.
    if ($flag_type == 'waived') {
      $current_status = $entity->field_status->getString();

      // DTS contracts get deleted after flaggingEvent finishes propagating.
      if ($current_status != 'dts') {
        $entity->field_status->set(0, 'waived');
        $entity->save();
        $flag_service->unflag($flag, $entity);
      }
    }

    if ($flag_type == 'injured_reserve') {
      $injury_status = $entity
        ->field_player
        ->referencedEntities()[0]
        ->field_injury_status
        ->referencedEntities()[0]
        ->label();

      if ($injury_status !== 'ACTIVE') {
        $entity->field_status->set(0, 'ir');
        $entity->save();
        $flag_service->unflag($flag, $entity);
      } else {
        $flag_service->unflag($flag, $entity);
        \Drupal::messenger()->addWarning('Contract ' . $entity->label() . ' is not eligible for IR.');
      }
    }

    if ($flag_type == 'promote') {
      $entity->field_status->set(0, 'active');
      $entity->save();
      $flag_service->unflag($flag, $entity);
    }
  }

  public function onUnflag(UnflaggingEvent $event) {
    // $flagging = $event->getFlaggings();
    // $flagging = reset($flagging);
    // $entity = $flagging->getFlaggable();
    // $flag = $flagging->getFlag();
    // $flag_type = $flag->id;
  }

  public function afterFlagChange(ResponseEvent $event) {
    $parameters = $event->getRequest()->attributes;

    // Is this response about a flag?
    if ($parameters->has('flag')) {
      $flag = $parameters->get('flag');

      if ($flag->id == 'waived') {

        $flag_service = \Drupal::service('flag');

        $contract = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($parameters->get('entity_id'));

        $flaggings = $flag_service
          ->getEntityFlaggings($flag, $contract);

        $status = $contract
          ->field_status
          ->getString();

        if (
          !empty($flaggings) &&
          $status === 'dts'
        ) {
          $contract->delete();
        }
      }
    }
  }

  public static function getSubscribedEvents() {
    $events = [];
    $events[FlagEvents::ENTITY_FLAGGED][] = ['onFlag'];
    $events[FlagEvents::ENTITY_UNFLAGGED][] = ['onUnflag'];
    $events[KernelEvents::RESPONSE] = 'afterFlagChange';
    return $events;
  }
}
