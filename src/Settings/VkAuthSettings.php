<?php

namespace Drupal\social_auth_vk\Settings;

use Drupal\social_api\Settings\SettingsBase;

/**
 * Returns the client information.
 *
 * This is the class defined in the settings handler of the Network Plugin
 * definition. The immutable configuration used by this class is also declared
 * in the definition.
 *
 * @see \Drupal\social_auth_vk\Plugin\Network\VkAuth
 *
 * This should return the values required to request the social network. In this
 * case, VK requires a Client ID, a Private key and a Client Secret.
 */
class VkAuthSettings extends SettingsBase implements VkAuthSettingsInterface {

  /**
   * Client ID.
   *
   * @var string
   */
  protected $clientId;

  /**
   * Private Key
   *
   * @var string
   */
  protected $privateKey;

  /**
   * Client secret.
   *
   * @var string
   */
  protected $clientSecret;

  /**
   * {@inheritdoc}
   */
  public function getClientId() {
    if (!$this->clientId) {
      $this->clientId = $this->config->get('client_id');
    }
    return $this->clientId;
  }

  /**
   * {@inheritdoc}
   */
  public function getPrivateKey() {
    if (!$this->privateKey) {
      $this->privateKey = $this->config->get('private_key');
    }
    return $this->privateKey;
  }

  /**
   * {@inheritdoc}
   */
  public function getClientSecret() {
    if (!$this->clientSecret) {
      $this->clientSecret = $this->config->get('client_secret');
    }
    return $this->clientSecret;
  }

}
