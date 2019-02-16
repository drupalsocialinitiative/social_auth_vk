<?php

namespace Drupal\social_auth_vk;

use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Url;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\AuthManager\OAuth2Manager;
use Symfony\Component\HttpFoundation\RequestStack;
use VK\OAuth\VKOAuth;
use VK\OAuth\VKOAuthDisplay;
use VK\OAuth\Scopes\VKOAuthUserScope;
use VK\OAuth\VKOAuthResponseType;

/**
 * Contains all the logic for Vkontakte OAuth2 authentication.
 */
class VkontakteAuthManager extends OAuth2Manager {

  /**
   * Authenticated user email.
   *
   * @var string
   */
  protected $email;

  /**
   * Authenticated user id.
   *
   * @var string
   */
  protected $userId;

  /**
   * The VKApi client.
   *
   * @var \VK\Client\VKApiClient
   */
  protected $client;

  /**
   * The VkOAuth object used for authorization.
   *
   * @var \VK\OAuth\VKOAuth
   */
  protected $oauth;

  /**
   * The currently active request object.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The secret state for the authentication.
   *
   * @var string
   */
  protected $state;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Used for accessing configuration object factory.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger factory.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Used to get current request information.
   */
  public function __construct(ConfigFactory $configFactory,
                              LoggerChannelFactoryInterface $logger_factory,
                              RequestStack $request_stack) {
    parent::__construct($configFactory->get('social_auth_vk.settings'), $logger_factory);

    $this->oauth = new VKOAuth();
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * {@inheritdoc}
   */
  public function authenticate() {
    $client_id = $this->getClientId();
    $client_secret = $this->getClientSecret();
    $redirect_uri = $this->getRedirectUrl();
    $code = $this->request->query->get('code');

    try {
      $response = $this->oauth->getAccessToken($client_id, $client_secret, $redirect_uri, $code);

      if ($response) {
        if (isset($response['access_token'])) {
          $this->setAccessToken($response['access_token']);
        }

        if (isset($response['email'])) {
          $this->setEmail($response['email']);
        }

        if (isset($response['user_id'])) {
          $this->setUserId($response['user_id']);
        }
      }
    }
    catch (\Exception $e) {
      $this->loggerFactory->get('social_auth_vk')
        ->error('There was an error during authentication. Exception: ' . $e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getUserInfo() {

    if (!$this->user) {
      $user_ids = [$this->getUserId()];
      $fields = ['id', 'first_name', 'last_name', 'photo_max_orig'];

      $profile = $this->getProfileData($user_ids, $fields);
      $profile['email'] = $this->getEmail();

      $this->user = $profile;
    }

    return $this->user;
  }

  /**
   * {@inheritdoc}
   */
  public function getAuthorizationUrl() {
    $client_id = $this->getClientId();
    $redirect_uri = $this->getRedirectUrl();
    $state = $this->getState();
    $display = VKOAuthDisplay::PAGE;

    $scopes = [VKOAuthUserScope::EMAIL, VKOAuthUserScope::OFFLINE];

    $extra_scopes = $this->getScopes();
    if ($extra_scopes) {
      $scopes = array_merge($scopes, explode(',', $extra_scopes));
    }

    return $this->oauth->getAuthorizeUrl(VKOAuthResponseType::CODE, $client_id, $redirect_uri, $display, $scopes, $state);
  }

  /**
   * {@inheritdoc}
   */
  public function requestEndPoint($method, $path, $domain = NULL, array $options = []) {
    // TODO: Implement this method.
  }

  /**
   * Set email for authenticated user.
   *
   * @param string $email
   *   User email.
   *
   * @return \Drupal\social_auth_vk\VkontakteAuthManager
   *   The current object.
   */
  public function setEmail($email) {
    $this->email = $email;

    return $this;
  }

  /**
   * Get authenticated user email.
   *
   * @return string
   *   User email.
   */
  public function getEmail() {
    return $this->email;
  }

  /**
   * Set authenticated user id.
   *
   * @param string $uid
   *   User id.
   *
   * @return \Drupal\social_auth_vk\VkontakteAuthManager
   *   The current object.
   */
  public function setUserId($uid) {
    $this->userId = $uid;

    return $this;
  }

  /**
   * Get authenticated user id.
   *
   * @return string
   *   User id.
   */
  public function getUserId() {
    return $this->userId;
  }

  /**
   * Get profile's data.
   *
   * @param array $user_ids
   *   The user ids.
   * @param array $fields
   *   The fields to load.
   *
   * @return array
   *   The user profile.
   *
   * @throws \Drupal\social_api\SocialApiException
   *   If profile list is empty.
   * @throws \VK\Exceptions\VKApiException
   *   Wrong request data.
   * @throws \VK\Exceptions\VKClientException
   *   Request errors.
   */
  protected function getProfileData(array $user_ids, array $fields) {
    $profiles = $this->client->users()->get($this->getAccessToken(), [
      'fields' => $fields,
      'user_ids' => $user_ids,
    ]);

    if (!$profiles) {
      throw new SocialApiException('Vkontakte login failed, could not load Vkontakte profile.');
    }

    return $profiles[0];
  }

  /**
   * {@inheritdoc}
   */
  public function getState() {
    if (!$this->state) {
      $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $length = 32;

      // Generates a random string of 32 characters.
      $this->state = substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 1, $length);
    }

    return $this->state;
  }

  /**
   * Get client id.
   *
   * @return string
   *   Client client id.
   */
  public function getClientId() {
    return $this->settings->get('client_id');
  }

  /**
   * Get client secret key.
   *
   * @return string
   *   Client secret key.
   */
  public function getClientSecret() {
    return $this->settings->get('client_secret');
  }

  /**
   * Get redirect url.
   *
   * @return string
   *   The redirect url.
   */
  public function getRedirectUrl() {
    return Url::fromRoute('social_auth_vk.callback')->setAbsolute()->toString();
  }

}
