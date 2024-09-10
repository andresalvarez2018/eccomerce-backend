<?php

namespace Drupal\rest_login_addons;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class PIcoreRouteSuscriber.
 */
class RestLoginAddonsRouteSuscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    $route_login = $collection->get('user.login.http');
    $route_login->setDefaults([
      '_controller' => '\Drupal\rest_login_addons\Controller\RestLoginAddonsController::login',
    ]);


  }

}