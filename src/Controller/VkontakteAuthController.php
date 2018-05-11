<?php

namespace Drupal\social_auth_vk\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_api\SocialApiException;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\SocialAuthUserManager;
use Drupal\social_auth_vk\VkontakteAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Returns responses for Simple Vkontakte Connect module routes.
 */
class VkontakteAuthController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The user manager.
   *
   * @var \Drupal\social_auth\SocialAuthUserManager
   */
  private $userManager;

  /**
   * The vkontakte authentication manager.
   *
   * @var \Drupal\social_auth_vk\VkontakteAuthManager
   */
  private $vkontakteManager;

  /**
   * Used to access GET parameters.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  private $request;

  /**
   * The Social Auth Data Handler.
   *
   * @var \Drupal\social_auth\SocialAuthDataHandler
   */
  private $dataHandler;


  /**
   * The logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * VkontakteAuthController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_vk network plugin.
   * @param \Drupal\social_auth\SocialAuthUserManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_auth_vk\VkontakteAuthManager $vkontakte_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_auth\SocialAuthDataHandler $social_auth_data_handler
   *   SocialAuthDataHandler object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   */
  public function __construct(NetworkManager $network_manager, SocialAuthUserManager $user_manager, VkontakteAuthManager $vkontakte_manager, RequestStack $request, SocialAuthDataHandler $social_auth_data_handler, LoggerChannelFactoryInterface $logger_factory) {
    $this->networkManager = $network_manager;
    $this->userManager = $user_manager;
    $this->vkontakteManager = $vkontakte_manager;
    $this->request = $request;
    $this->dataHandler = $social_auth_data_handler;
    $this->loggerFactory = $logger_factory;

    // Sets the plugin id.
    $this->userManager->setPluginId('social_auth_vk');

    // Sets the session keys to nullify if user could not logged in.
    $this->userManager->setSessionKeysToNullify(['access_token', 'oauth2state']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_manager'),
      $container->get('social_auth_vk.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler'),
      $container->get('logger.factory')
    );
  }

  /**
   * Gets the underlying SDK library.
   *
   * @return \VK\OAuth\VKOAuth
   *   The VKOAuth client.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown when no more class is applicable.
   */
  protected function getSdk() {
    /** @var \Drupal\social_auth_vk\Plugin\Network\VkontakteAuth $plugin */
    $plugin = $this->networkManager->createInstance('social_auth_vk');

    return $plugin->getSdk();
  }

  /**
   * Response for path 'user/login/vkontakte'.
   *
   * Redirects the user to Vkontakte for authentication.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  public function redirectToVkontakte() {
    try {
      $vkontakte = $this->getSdk();

      // Vkontakte service was returned, inject it to $vkontakteManager.
      $this->vkontakteManager->setOAuth($vkontakte);

      // Generates the URL where the user will be redirected for Vkontakte login.
      $vkontakte_login_url = $this->vkontakteManager->getAuthorizationUrl();

      $state = $this->vkontakteManager->getState();

      $this->dataHandler->set('oauth2state', $state);

      $response = new TrustedRedirectResponse($vkontakte_login_url);
    }
    catch (\Exception $exception) {
      drupal_set_message($this->t($exception->getMessage()), 'error');
      $response = $this->redirect('user.login');
    }

    return $response;
  }

  /**
   * Response for path 'user/login/vkontakte/callback'.
   *
   * Vkontakte returns the user here after user has authenticated in Vkontakte.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   Redirect response.
   */
  public function callback() {
    try {
      // Checks if user cancel login via Vkontakte.
      $error = $this->request->getCurrentRequest()->get('error');
      if ($error === 'access_denied') {
        throw new SocialApiException('You could not be authenticated.');
      }

      // Retreives $_GET['state'].
      $state = $this->dataHandler->get('oauth2state');
      $retrievedState = $this->request->getCurrentRequest()->query->get('state');
      if (empty($retrievedState) || ($retrievedState !== $state)) {
        $this->userManager->nullifySessionKeys();
        throw new SocialApiException('Vkontakte login failed. Unvalid OAuth2 State.');
      }

      $vkontakte = $this->getSdk();
      $this->vkontakteManager->setOAuth($vkontakte)->authenticate();

      // Saves access token to session.
      $this->dataHandler->set('access_token', $this->vkontakteManager->getAccessToken());

      // Gets user's info from Vkontakte API.
      $profile = $this->vkontakteManager->getUserInfo();
      $full_name = $profile['first_name'] . ' ' . $profile['last_name'];

      // Gets (or not) extra initial data.
      $data = !$this->userManager->checkIfUserExists($profile['id'])
        ? $this->vkontakteManager->getExtraDetails()
        : [];

      // If user information could be retrieved.
      $response = $this->userManager->authenticateUser($full_name, $profile['email'], $profile['id'], $this->vkontakteManager->getAccessToken(), $profile['photo_max_orig'], $data);
    }
    catch (\Exception $exception) {
      drupal_set_message($this->t($exception->getMessage()), 'error');
      $response = $this->redirect('user.login');
    }

    return $response;
  }

}
