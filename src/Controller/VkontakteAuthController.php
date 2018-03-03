<?php

namespace Drupal\social_auth_vkontakte\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\SocialAuthUserManager;
use Drupal\social_auth_vkontakte\VkontakteAuthManager;
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
   * @var \Drupal\social_auth_vkontakte\VkontakteAuthManager
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
   *   Used to get an instance of social_auth_vkontakte network plugin.
   * @param \Drupal\social_auth\SocialAuthUserManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_auth_vkontakte\VkontakteAuthManager $vkontakte_manager
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
    $this->userManager->setPluginId('social_auth_vkontakte');

    // Sets the session keys to nullify if user could not logged in.
    $this->userManager->setSessionKeysToNullify(['access_token', 'oauth2state']);

    $this->setting = $this->config('social_auth_vkontakte.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_manager'),
      $container->get('social_auth_vkontakte.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.data_handler'),
      $container->get('logger.factory')
    );
  }

  /**
   * Response for path 'user/login/vkontakte'.
   *
   * Redirects the user to Vkontakte for authentication.
   */
  public function redirectToVkontakte() {
    /* @var \J4k\OAuth2\Client\Provider\Vkontakte|false $vkontakte */
    $vkontakte = $this->networkManager->createInstance('social_auth_vkontakte')->getSdk();

    // If vkontakte client could not be obtained.
    if (!$vkontakte) {
      drupal_set_message($this->t('Social Auth Vkontakte not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Vkontakte service was returned, inject it to $vkontakteManager.
    $this->vkontakteManager->setClient($vkontakte);

    // Generates the URL where the user will be redirected for Vkontakte login.
    $vkontakte_login_url = $this->vkontakteManager->getAuthorizationUrl();

    $state = $this->vkontakteManager->getState();

    $this->dataHandler->set('oauth2state', $state);

    return new TrustedRedirectResponse($vkontakte_login_url);
  }

  /**
   * Response for path 'user/login/vkontakte/callback'.
   *
   * Vkontakte returns the user here after user has authenticated in Vkontakte.
   */
  public function callback() {
    // Checks if user cancel login via Vkontakte.
    $error = $this->request->getCurrentRequest()->get('error');
    if ($error == 'access_denied') {
      drupal_set_message($this->t('You could not be authenticated.'), 'error');
      return $this->redirect('user.login');
    }

    /* @var \J4k\OAuth2\Client\Provider\Vkontakte|false $vkontakte */
    $vkontakte = $this->networkManager->createInstance('social_auth_vkontakte')->getSdk();

    // If Vkontakte client could not be obtained.
    if (!$vkontakte) {
      drupal_set_message($this->t('Social Auth Vkontakte not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    $state = $this->dataHandler->get('oauth2state');

    // Retreives $_GET['state'].
    $retrievedState = $this->request->getCurrentRequest()->query->get('state');
    if (empty($retrievedState) || ($retrievedState !== $state)) {
      $this->userManager->nullifySessionKeys();
      drupal_set_message($this->t('Vkontakte login failed. Unvalid OAuth2 State.'), 'error');
      return $this->redirect('user.login');
    }

    // Saves access token to session.
    $this->dataHandler->set('access_token', $this->vkontakteManager->getAccessToken());

    $this->vkontakteManager->setClient($vkontakte)->authenticate();

    // Gets user's info from Vkontakte API.
    if (!$profile = $this->vkontakteManager->getUserInfo()) {
      drupal_set_message($this->t('Vkontakte login failed, could not load Vkontakte profile. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Gets (or not) extra initial data.
    $data = $this->userManager->checkIfUserExists($profile->getId()) ? NULL : $this->vkontakteManager->getExtraDetails();

    $info = $profile->toArray();
    $full_name = $info['first_name'] . ' ' . $info['last_name'];

    // If user information could be retrieved.
    return $this->userManager->authenticateUser($full_name, '', $profile->getId(), $this->vkontakteManager->getAccessToken(), $info['photo_max_orig'], $data);
  }

}
