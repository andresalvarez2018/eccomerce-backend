<?php

namespace Drupal\rest_login_addons\Controller;

use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\File\FileSystemInterface;

/**
 * Class RestRegisterController.
 *
 * Handles user registration via REST.
 */
class RestRegisterController extends ControllerBase {

  /**
   * Registers a new user.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   *   A response indicating success or failure.
   */
  public function register(Request $request) {

    $data = json_decode($request->getContent(), TRUE);

    if (empty($data['email']) || empty($data['password']) || empty($data['confirmPassword'])) {
      return new JsonResponse(['error' => 'Missing required fields.'], 400);
    }

    if ($data['password'] !== $data['confirmPassword']) {
      return new JsonResponse(['error' => 'Passwords do not match.'], 400);
    }

    $existing_users = \Drupal::entityTypeManager()->getStorage('user')->loadByProperties(['mail' => $data['email']]);
    if ($existing_users) {
      return new JsonResponse(['error' => 'User with this email already exists.'], 400);
    }

    try {
      // Create a new user account.
      $user = User::create();
      $user->setPassword($data['password']);
      $user->enforceIsNew();
      $user->setEmail($data['email']);
      $user->setUsername($data['email']);

      // Setting custom fields.
      $user->set("field_full_name", $data['firstName']);
      $user->set("field_last_name", $data['lastName']);
      $user->set("field_gender", $data['gender']);
      $user->set("field_date_birth", $data['birthDate']);
      
      // Procesar la imagen en base64 (opcional).
      if (!empty($data['photo'])) {
        $this->saveUserProfilePicture($user, $data['photo']);
      }

      // Activate the account immediately.
      $user->activate();
      $user->save();

      return new JsonResponse([
        'message' => 'User registered successfully',
        'uid' => $user->id(),
      ], 200);
    }
    catch (\Exception $e) {
      // In case of an exception.
      return new JsonResponse(['error' => $e->getMessage()], 500);
    }
  }

  /**
   * Save a user's profile picture from base64 data.
   *
   * @param \Drupal\user\Entity\User $user
   *   The user entity.
   * @param string $photo_base64
   *   The base64 image data.
   */
  protected function saveUserProfilePicture(User $user, $photo_base64) {
    list($type, $data) = explode(';', $photo_base64);
    list(, $data) = explode(',', $data);
    $data = base64_decode($data);

    $directory = 'public://profile_pictures/';
    \Drupal::service('file_system')->prepareDirectory($directory, FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    $file_name = $directory . 'profile_' . $user->id() . '.png';
    $file = \Drupal::service('file.repository')->writeData($data, $file_name);

    if ($file) {
      $user->set('user_picture', $file);
    }
  }
}
