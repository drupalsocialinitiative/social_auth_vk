<?php

namespace Drupal\social_auth_vk\Settings;

/**
 * Defines an interface for Social Auth Vkontakte settings.
 */
interface VkontakteAuthSettingsInterface {

  /**
   * Gets the client ID.
   *
   * @return string
   *   The client ID.
   */
  public function getClientId();

  /**
   * Gets the client secret.
   *
   * @return string
   *   The client secret.
   */
  public function getClientSecret();

}
