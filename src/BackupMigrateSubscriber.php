<?php

/**
 * @file
 * Contains \Drupal\backup_migrate\BackupMigrateSubscriber.
 */

namespace Drupal\backup_migrate;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Route;

/**
 * Listens to the dynamic route events.
 */
class BackupMigrateSubscriber implements EventSubscriberInterface {

  /**
   * The types to generate paths for.
   */
  protected $crudTypes;

  /**
   * Constructs a BackupMigrateSubscriber object.
   */
  public function __construct() {
    $this->crudTypes = array('destination', 'profile', 'schedule');
  }

  /**
   * {@inherit}
   */
  public static function getSubscribedEvents() {
    $events[RoutingEvents::DYNAMIC] = 'routes';
    return $events;
  }

  /**
   * Generate dynamic routes for profiles, destinations and schedules.
   *
   * @param \Drupal\Core\Routing\RouteBuildEvent $event
   *   The route building event.
   *
   * @return \Symfony\Component\Routing\RouteCollection
   *   The route collection that contains the new dynamic route.
   */
  public function routes(RouteBuildEvent $event) {
    $collection = $event->getRouteCollection();
    foreach ($this->crudTypes as $type) {
      $route = new Route(
        'admin/config/system/backup_migrate/' . $type,
        array(
          '_controller' => '\Drupal\backup_migrate\Controller\BackupMigrateCrudController::listAll',
        ),
        array(
          '_permission' => 'administer backup and migrate',
        )
      );
      $collection->add('backup_migrate.' . $type, $route);

      $route = new Route(
        'admin/config/system/backup_migrate/' . $type . '/add',
        array(
          '_controller' => '\Drupal\backup_migrate\Controller\BackupMigrateCrudController::add',
        ),
        array(
          '_permission' => 'administer backup and migrate',
        )
      );
      $collection->add('backup_migrate.' . $type . '_add', $route);

      $route = new Route(
        'admin/config/system/backup_migrate/' . $type . '/delete',
        array(
          '_controller' => '\Drupal\backup_migrate\Controller\BackupMigrateCrudController::delete',
        ),
        array(
          '_permission' => 'administer backup and migrate',
        )
      );
      $collection->add('backup_migrate.' . $type . '_delete', $route);

      $route = new Route(
        'admin/config/system/backup_migrate/' . $type . '/edit',
        array(
          '_controller' => '\Drupal\backup_migrate\Controller\BackupMigrateCrudController::edit',
        ),
        array(
          '_permission' => 'administer backup and migrate',
        )
      );
      $collection->add('backup_migrate.' . $type . '_edit', $route);

      $route = new Route(
        'admin/config/system/backup_migrate/' . $type . '/export',
        array(
          '_controller' => '\Drupal\backup_migrate\Controller\BackupMigrateCrudController::export',
        ),
        array(
          '_permission' => 'administer backup and migrate',
        )
      );
      $collection->add('backup_migrate.' . $type . '_export', $route);
    }
  }

}
