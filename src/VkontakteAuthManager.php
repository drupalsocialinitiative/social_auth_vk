<?php

namespace Drupal\social_auth_vk;

use Drupal\Core\Url;
use Drupal\Core\Config\ConfigFactory;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\AuthManager\OAuth2Manager;
use Symfony\Component\HttpFoundation\RequestStack;
use VK\OAuth\VKOAuth;
use VK\OAuth\VKOAuthDisplay;
use VK\OAuth\Scopes\VKOAuthUserScope;
use VK\OAuth\VKOAuthResponseType;
use VK\Client\VKApiClient;

/**
 * Contains all the logic for Vkontakte login integration.
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
   * The OAuth client.
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
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $configFactory
   *   Used for accessing configuration object factory.
   */
  public function __construct(ConfigFactory $configFactory, RequestStack $request_stack) {
    parent::__construct($configFactory->get('social_auth_vk.settings'));

    $this->client = new VKApiClient();
    $this->request = $request_stack->getCurrentRequest();
  }

  /**
   * Set OAuth to authorization and authentication.
   *
   * @param \VK\OAuth\VKOAuth $oauth
   *   VKOAuth object.
   *
   * @return \Drupal\social_auth_vk\VkontakteAuthManager
   *   The current object.
   */
  public function setOAuth(VKOAuth $oauth) {
    $this->oauth = $oauth;

    return $this;
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
   * {@inheritdoc}
   */
  public function authenticate() {
    $client_id = $this->getClientId();
    $client_secret = $this->getClientSecret();
    $redirect_uri = $this->getRedirectUrl();
    $code = $this->request->query->get('code');

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

  /**
   * {@inheritdoc}
   */
  public function getUserInfo() {
    $user_ids = [$this->getUserId()];
    $fields = ['id', 'first_name', 'last_name', 'photo_max_orig'];

    $profiles = $this->loadProfilesData($user_ids, $fields);
    $profile = reset($profiles);
    $profile['email'] = $this->getEmail();

    return $profile;
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
    if ($extra_scopes = $this->getScopes()){
      $scopes = array_merge($scopes, explode(',', $extra_scopes));
    }

    return $this->oauth->getAuthorizeUrl(VKOAuthResponseType::CODE, $client_id, $redirect_uri, $display, $scopes, $state);
  }

  /**
   * Load profiles data.
   *
   * @param array $user_ids
   *   User ids.
   * @param array $fields
   *   Fields list to load.
   *
   * @return array
   *   Profiles list.
   *
   * @throws \Drupal\social_api\SocialApiException
   *   If profiles list is empty.
   * @throws \VK\Exceptions\VKApiException
   *   Wrong request data.
   * @throws \VK\Exceptions\VKClientException
   *   Request errors.
   */
  protected function loadProfilesData($user_ids, $fields) {
    $profiles = $this->client->users()->get($this->getAccessToken(), [
      'fields' => $fields,
      'user_ids' => $user_ids,
    ]);

    if (!$profiles) {
      throw new SocialApiException('Vkontakte login failed, could not load Vkontakte profile.');
    }

    return $profiles;
  }

  /**
   * {@inheritdoc}
   */
  public function getExtraDetails() {
    $user_ids = [$this->getUserId()];
    $fields = [
      'verified', 'sex', 'bdate', 'city', 'country', 'home_town', 'education',
      'universities', 'schools',
    ];

    $profiles = $this->loadProfilesData($user_ids, $fields);

    return reset($profiles);
  }

  /**
   * {@inheritdoc}
   */
  public function requestEndPoint($path) {}

  /**
   * {@inheritdoc}
   */
  public function getState() {
    return 'secret_state_code';
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
   *   Redirect url.
   */
  public function getRedirectUrl() {
    $callback_uri = Url::fromRoute('social_auth_vk.callback');
    $callback_uri->setAbsolute();

    return $callback_uri->toString(TRUE)->getGeneratedUrl();
  }

}
