<?php

namespace Drupal\social_auth_vkontkate\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthDataHandler;
use Drupal\social_auth\SocialAuthUserManager;
use Drupal\social_auth_vkontkate\VkontkateAuthManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Returns responses for Simple Vkontkate Connect module routes.
 */
class VkontkateAuthController extends ControllerBase {

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
   * The vkontkate authentication manager.
   *
   * @var \Drupal\social_auth_vkontkate\VkontkateAuthManager
   */
  private $vkontkateManager;

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
   * VkontkateAuthController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_vkontkate network plugin.
   * @param \Drupal\social_auth\SocialAuthUserManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_auth_vkontkate\VkontkateAuthManager $vkontkate_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request
   *   Used to access GET parameters.
   * @param \Drupal\social_auth\SocialAuthDataHandler $social_auth_data_handler
   *   SocialAuthDataHandler object.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Used for logging errors.
   */
  public function __construct(NetworkManager $network_manager, SocialAuthUserManager $user_manager, VkontkateAuthManager $vkontkate_manager, RequestStack $request, SocialAuthDataHandler $social_auth_data_handler, LoggerChannelFactoryInterface $logger_factory) {

    $this->networkManager = $network_manager;
    $this->userManager = $user_manager;
    $this->vkontkateManager = $vkontkate_manager;
    $this->request = $request;
    $this->dataHandler = $social_auth_data_handler;
    $this->loggerFactory = $logger_factory;

    // Sets the plugin id.
    $this->userManager->setPluginId('social_auth_vkontkate');

    // Sets the session keys to nullify if user could not logged in.
    $this->userManager->setSessionKeysToNullify(['access_token', 'oauth2state']);
    $this->setting = $this->config('social_auth_vkontkate.settings');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('plugin.network.manager'),
      $container->get('social_auth.user_manager'),
      $container->get('social_auth_vkontkate.manager'),
      $container->get('request_stack'),
      $container->get('social_auth.social_auth_data_handler'),
      $container->get('logger.factory')
    );
  }

  /**
   * Response for path 'user/login/vkontkate'.
   *
   * Redirects the user to Vkontkate for authentication.
   */
  public function redirectToVkontkate() {
    /* @var \League\OAuth2\Client\Provider\Vkontkate false $vkontkate */
    $vkontkate = $this->networkManager->createInstance('social_auth_vkontkate')->getSdk();

    // If vkontkate client could not be obtained.
    if (!$vkontkate) {
      drupal_set_message($this->t('Social Auth Vkontkate not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Vkontkate service was returned, inject it to $vkontkateManager.
    $this->vkontkateManager->setClient($vkontkate);

    // Generates the URL where the user will be redirected for Vkontkate login.
    // If the user did not have email permission granted on previous attempt,
    // we use the re-request URL requesting only the email address.
    $vkontkate_login_url = $this->vkontkateManager->getVkontkateLoginUrl();

    $state = $this->vkontkateManager->getState();

    $this->dataHandler->set('oauth2state', $state);

    return new TrustedRedirectResponse($vkontkate_login_url);
  }

  /**
   * Response for path 'user/login/vkontkate/callback'.
   *
   * Vkontkate returns the user here after user has authenticated in Vkontkate.
   */
  public function callback() {
    // Checks if user cancel login via Vkontkate.
    $error = $this->request->getCurrentRequest()->get('error');
    if ($error == 'access_denied') {
      drupal_set_message($this->t('You could not be authenticated.'), 'error');
      return $this->redirect('user.login');
    }

    /* @var \League\OAuth2\Client\Provider\Vkontkate false $vkontkate */
    $vkontkate = $this->networkManager->createInstance('social_auth_vkontkate')->getSdk();

    // If Vkontkate client could not be obtained.
    if (!$vkontkate) {
      drupal_set_message($this->t('Social Auth Vkontkate not configured properly. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    $state = $this->dataHandler->get('oauth2state');

    // Retreives $_GET['state'].
    $retrievedState = $this->request->getCurrentRequest()->query->get('state');
    if (empty($retrievedState) || ($retrievedState !== $state)) {
      $this->userManager->nullifySessionKeys();
      drupal_set_message($this->t('Vkontkate login failed. Unvalid oAuth2 State.'), 'error');
      return $this->redirect('user.login');
    }

    // Saves access token to session.
    $this->dataHandler->set('access_token', $this->vkontkateManager->getAccessToken());

    $this->vkontkateManager->setClient($vkontkate)->authenticate();

    // Gets user's info from Vkontkate API.
    if (!$vkontkate_profile = $this->vkontkateManager->getUserInfo()) {
      drupal_set_message($this->t('Vkontkate login failed, could not load Vkontkate profile. Contact site administrator.'), 'error');
      return $this->redirect('user.login');
    }

    // Store the data mapped with data points define is
    // social_auth_vkontkate settings.
    $data = [];

    if (!$this->userManager->checkIfUserExists($vkontkate_profile->getId())) {
      $api_calls = explode(PHP_EOL, $this->vkontkateManager->getApiCalls());

      // Iterate through api calls define in settings and try to retrieve them.
      foreach ($api_calls as $api_call) {
        $call = $this->vkontkateManager->getExtraDetails($api_call);
        array_push($data, $call);
      }
    }

    $full_name = $vkontkate_profile->toArray()['first_name']. ' ' . $vkontkate_profile->toArray()['last_name'];

    // If user information could be retrieved.
    return $this->userManager->authenticateUser($full_name, '', $vkontkate_profile->getId(), $this->vkontkateManager->getAccessToken(), $vkontkate_profile->toArray()['photo_max_orig'], json_encode($data));
  }

}
