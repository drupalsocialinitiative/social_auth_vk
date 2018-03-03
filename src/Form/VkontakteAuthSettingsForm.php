<?php

namespace Drupal\social_auth_vk\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\PathValidatorInterface;
use Drupal\Core\Routing\RequestContext;
use Drupal\Core\Routing\RouteProviderInterface;
use Drupal\social_auth\Form\SocialAuthSettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Settings form for Social Auth Vkontakte.
 */
class VkontakteAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * The request context.
   *
   * @var \Drupal\Core\Routing\RequestContext
   */
  protected $requestContext;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param \Drupal\Core\Routing\RouteProviderInterface $route_provider
   *   Used to check if route exists.
   * @param \Drupal\Core\Path\PathValidatorInterface $path_validator
   *   Used to check if path is valid and exists.
   * @param \Drupal\Core\Routing\RequestContext $request_context
   *   Holds information about the current request.
   */
  public function __construct(ConfigFactoryInterface $config_factory, RouteProviderInterface $route_provider, PathValidatorInterface $path_validator, RequestContext $request_context) {
    parent::__construct($config_factory, $route_provider, $path_validator);
    $this->requestContext = $request_context;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this class.
    return new static(
    // Load the services required to construct this class.
      $container->get('config.factory'),
      $container->get('router.route_provider'),
      $container->get('path.validator'),
      $container->get('router.request_context')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'social_auth_vk_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(
      parent::getEditableConfigNames(),
      ['social_auth_vk.settings']
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_vk.settings');

    $form['vkontakte_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Vkontakte Client settings'),
      '#open' => TRUE,
      '#description' => $this->t('You need to first create a Vkontakte App at <a href="@vkontakte-dev">@vkontakte-dev</a>', ['@vkontakte-dev' => 'https://vk.com/apps?act=manage']),
    ];

    $form['vkontakte_settings']['client_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('Copy the Client ID here.'),
    ];

    $form['vkontakte_settings']['client_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Secure key'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Copy the Client Secret here.'),
    ];

    $form['vkontakte_settings']['authorized_redirect_url'] = [
      '#type' => 'textfield',
      '#disabled' => TRUE,
      '#title' => $this->t('Authorized redirect URIs'),
      '#description' => $this->t('Copy this value to <em>Authorized redirect URIs</em> field of your Vkontakte App settings.'),
      '#default_value' => $GLOBALS['base_url'] . '/user/login/vkontakte/callback',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('social_auth_vk.settings')
      ->set('client_id', $values['client_id'])
      ->set('client_secret', $values['client_secret'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
