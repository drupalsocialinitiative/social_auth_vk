<?php

namespace Drupal\social_auth_vk\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\social_auth_vk\VkAuthManager;
use Drupal\social_api\Plugin\NetworkManager;
use Drupal\social_auth\SocialAuthUserManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Zend\Diactoros\Response\RedirectResponse;

/**
 * Manages requests to VK API.
 *
 * Most of the code here is specific to implement a VK login process. Social
 * Networking services might require different approaches.
 */
class VkAuthController extends ControllerBase {

  /**
   * The network plugin manager.
   *
   * @var \Drupal\social_api\Plugin\NetworkManager
   */
  private $networkManager;

  /**
   * The VK authentication manager.
   *
   * @var \Drupal\social_auth_vk\VkAuthManager
   */
  private $vkManager;

  /**
   * The user manager.
   *
   * @var \Drupal\social_auth\SocialAuthUserManager
   */
  private $userManager;

  /**
   * The session manager.
   *
   * @var \Symfony\Component\HttpFoundation\Session\SessionInterface
   */
  protected $session;

  /**
   * VkLoginController constructor.
   *
   * @param \Drupal\social_api\Plugin\NetworkManager $network_manager
   *   Used to get an instance of social_auth_vk network plugin.
   * @param \Drupal\social_auth\SocialAuthUserManager $user_manager
   *   Manages user login/registration.
   * @param \Drupal\social_auth_vk\VkAuthManager $vk_manager
   *   Used to manage authentication methods.
   * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
   *   Used to store the access token into a session variable.
   */
  public function __construct(NetworkManager $network_manager, SocialAuthUserManager $user_manager, VkAuthManager $vk_manager, SessionInterface $session) {
    $this->networkManager = $network_manager;
    $this->vkManager = $vk_manager;
    $this->userManager = $user_manager;
    $this->session = $session;

    // Sets the plugin id.
    $this->userManager->setPluginId('social_auth_vk');

    // Sets the session keys to nullify if user could not logged in.
    $this->userManager->setSessionKeysToNullify(['social_auth_vk_access_token']);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
        $container->get('plugin.network.manager'),
        $container->get('social_auth.user_manager'),
        $container->get('vk_auth.manager'),
        $container->get('session')
    );
  }

  /**
   * Redirects to VK Services Authentication page.
   *
   * Most of the Social Networks' API require you to redirect users to a
   * authentication page. This method is not a mandatory one, instead you must
   * adapt to the requirements of the module you are implementing.
   *
   * This method is called in 'social_auth_vk.redirect_to_vk' route.
   *
   * @see social_auth_vk.routing.yml
   *
   * This method is triggered when the user loads user/login/vk. It creates
   * an instance of the Network Plugin 'social auth vk' and returns an
   * instance of the \VK object.
   *
   * It later sets the permissions that should be asked for, and redirects the
   * user to VK Accounts to allow him to grant those permissions.
   *
   * After the user grants permission, VK redirects him to a url specified
   * in the VK project settings. In this case, it should redirects to
   * 'user/login/vk/callback', which calls the callback method.
   *
   * @return \Zend\Diactoros\Response\RedirectResponse
   *   Redirection to VK Accounts.
   */
  public function redirectToVk() {
    /* @var \VK $client */
    // Creates an instance of the Network Plugin and gets the SDK.
    $client = $this->networkManager->createInstance('social_auth_vk')->getSdk();

    $redirect_uri = Url::fromRoute('social_auth_vk.callback', [], ['absolute' => TRUE]);
    $redirect_uri = $redirect_uri->toString();
    return new RedirectResponse($client->getAuthorizeURL('email,name,photos', $redirect_uri));
  }

  /**
   * Callback function to login user.
   *
   * Most of the Social Networks' API redirects to callback url. This method is
   * not a mandatory one, instead you must adapt to the requirements of the
   * module you are implementing.
   *
   * This method is called in 'social_auth_vk.callback' route.
   *
   * @see social_auth_vk.routing.yml
   *
   * This method is triggered when the path user/login/vk/callback is
   * loaded. It creates an instance of the Network Plugin 'social auth vk'.
   *
   * It later authenticates the user and creates the service to obtain data
   * about the user.
   */
  public function callback() {
    /* @var \VK $client */
    // Creates the Network Plugin instance and get the SDK.
    $client = $this->networkManager->createInstance('social_auth_vk')->getSdk();

    // Authenticates the user.
    $this->vkManager->setClient($client)->authenticate();

    // Saves access token so that event subscribers can call VK API.
    $this->session->set('social_auth_vk_access_token', $this->vkManager->getAccessToken());

    // Gets user information.
    $user = $this->vkManager->getUserInfo();
    // If user information could be retrieved.
    if ($user) {
      // Returns the redirect value obtained from authenticateUser.
      return $this->userManager->authenticateUser($user['email'], $user['first_name'], $user['uid'], $user['photo_200']);
    }

    drupal_set_message($this->t('You could not be authenticated, please contact the administrator'), 'error');
    return $this->redirect('user.login');
  }

}
