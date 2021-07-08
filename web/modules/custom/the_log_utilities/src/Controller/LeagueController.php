<?php

namespace Drupal\the_log_utilities\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\Query\QueryInterface;

class LeagueController extends ControllerBase {

  /**
   * A queryInterface instance for nodes.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface
   */
  protected $nodeQuery;

  public function __construct(
    QueryInterface $nodeQuery
  ) {
  }
}
