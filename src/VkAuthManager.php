<?php

namespace Drupal\social_auth_vk;

use Drupal\social_auth\AuthManager\OAuth2Manager;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Url;
use VK\VK;

/**
 * Manages the authentication requests.
 *
 * This class is specific to VK authentication, although it is extended
 * from OAuth2Manager class.
 *
 * @see \Drupal\social_auth\AuthManager\OAuth2Manager
 */
class VkAuthManager extends OAuth2Manager {

  /**
   * The request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The VK service client.
   *
   * @var \VK\VK
   */
  protected $client;

  /**
   * Code returned by VK for authentication.
   *
   * @var string
   */
  protected $code;

  /**
   * VkLoginManager constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to get the parameter code returned by VK.
   */
  public function __construct(RequestStack $request) {
    $this->request = $request->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate() {
    $this->client->setAccessToken($this->getAccessToken());
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessToken() {
    if (!$this->accessToken) {
      $code = $this->getCode();
      $redirect_uri = Url::fromRoute('social_auth_vk.callback', [], ['absolute' => TRUE]);
      $redirect_uri = $redirect_uri->toString();
      $this->accessToken = $this->client->getAccessToken($code, $redirect_uri);
    }

    return $this->accessToken;
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInfo() {
    $token = $this->getAccessToken();
    $parameters = array(
      'user_ids' => $token['user_id'],
      'fields' => 'first_name,last_name,photo_200,mail',
      'access_token' => $token['access_token'],
    );
    $user_info = $this->client->api('users.get', $parameters);
    if (!empty($user_info) && !empty($user_info['response'][0])) {
      $user_info = $user_info['response'][0];
      $user_info['email'] = $token['email'];
    }
    else {
      $user_info = FALSE;
    }
    return $user_info;
  }

  /**
   * Gets the code returned by VK to authenticate.
   *
   * @return string
   *   The code string returned by VK.
   */
  protected function getCode() {
    if (!$this->code) {
      $this->code = $this->request->query->get('code');
    }

    return $this->code;
  }

}
