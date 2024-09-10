<?php

namespace Drupal\rest_login_addons\Controller;

use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Flood\FloodInterface;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\user\Controller\UserAuthenticationController;
use Drupal\user\UserAuthInterface;
use Drupal\user\UserStorageInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Serializer;

/**
 * Class RestLoginAddonsController.
 *
 * We are altering the user.login route to add all the cusom fields here.
 */
class RestLoginAddonsController extends UserAuthenticationController {

  /**
   * Logs in a user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   A response which contains the ID and CSRF token.
   */
  public function login(Request $request) {
    $response_data = parent::login($request);
    $content = $response_data->getContent();
    $decoded_data = $this->serializer->decode($content, 'json');
    $uid = $decoded_data['current_user']['uid'];
    $user = $this->userStorage->load($uid);

    $decoded_data = $this->fetchCustomfields($user, $decoded_data);
    $encoded_custom_data = $this->serializer->encode($decoded_data, 'json');
    $response_data->setContent($encoded_custom_data);
    return $response_data;
  }

  /**
   * Fetching custom fields.
   *
   * @param object $user
   *   The user object.
   * @param array $response_data
   *   The array of responses.
   *
   * @return mixed
   *   The response
   */
  protected function fetchCustomfields($user, array $response_data) {
    $response_data['current_user']['full_name'] = $user->get('field_full_name')->value;
    $response_data['current_user']['last_name'] = $user->get('field_last_name')->value;
    $response_data['current_user']['birth_date'] = $user->get('field_date_birth')->value;
    $response_data['current_user']['gender'] = $user->get('field_gender')->value;
    $response_data['current_user']['email'] = $user->get('field_email')->value;

    $picture_field = $user->get('user_picture')->entity;
    if ($picture_field) {
      $file_uri = $picture_field->getFileUri();
      $response_data['current_user']['picture'] = \Drupal::service('file_url_generator')->generateAbsoluteString($file_uri);
    } else {
      $response_data['current_user']['picture'] = NULL;
    }
       
    return $response_data;
  }

}