<?php

namespace Drupal\social_auth_vk\Settings;

/**
 * Defines an interface for Social Auth VK settings.
 */
interface VkAuthSettingsInterface {

  /**
   * Gets the client ID.
   *
   * @return string
   *   The client ID.
   */
  public function getClientId();

  /**
   * Gets the Private Key.
   *
   * @return string
   *   The Private Key.
   */
  public function getPrivateKey();

  /**
   * Gets the client secret.
   *
   * @return string
   *   The client secret.
   */
  public function getClientSecret();

}
