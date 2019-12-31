<?php

namespace Drupal\social_auth_vk\Plugin\Network;

use Drupal\social_api\SocialApiException;
use Drupal\social_auth\Plugin\Network\NetworkBase;
use Drupal\social_auth_vk\Settings\VkontakteAuthSettings;
use VK\Client\VKApiClient;

/**
 * Defines a Network Plugin for Social Auth Vkontakte.
 *
 * @package Drupal\simple_vkontakte_connect\Plugin\Network
 *
 * @Network(
 *   id = "social_auth_vk",
 *   social_network = "Vkontakte",
 *   type = "social_auth",
 *   handlers = {
 *     "settings": {
 *       "class": "\Drupal\social_auth_vk\Settings\VkontakteAuthSettings",
 *       "config_id": "social_auth_vk.settings"
 *     }
 *   }
 * )
 */
class VkontakteAuth extends NetworkBase implements VkontakteAuthInterface {

  /**
   * Sets the underlying SDK library.
   *
   * @return \VK\Client\VKApiClient
   *   The initialized 3rd party library instance.
   *
   * @throws \Drupal\social_api\SocialApiException
   *   If the SDK library does not exist.
   */
  protected function initSdk() {
    $class_name = VKApiClient::class;
    if (!class_exists($class_name)) {
      throw new SocialApiException(sprintf('The Vkontakte Library not found. Class: %s.', $class_name));
    }

    if ($this->validateConfig($this->settings)) {
      return new VKApiClient();
    }

    return FALSE;
  }

  /**
   * Checks that module is configured.
   *
   * @param \Drupal\social_auth_vk\Settings\VkontakteAuthSettings $settings
   *   The Vkontakte auth settings.
   *
   * @return bool
   *   True if module is configured.
   *   False otherwise.
   */
  protected function validateConfig(VkontakteAuthSettings $settings) {
    $client_id = $settings->getClientId();
    $client_secret = $settings->getClientSecret();

    $is_valid = $client_id && $client_secret;

    if (!$is_valid) {
      $this->loggerFactory
        ->get('social_auth_vk')
        ->error('Define Client ID and Client Secret on module settings.');
    }

    return $is_valid;
  }

}
