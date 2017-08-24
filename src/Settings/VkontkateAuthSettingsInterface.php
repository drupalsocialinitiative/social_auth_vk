<?php

namespace Drupal\social_auth_vkontkate\Settings;

/**
 * Defines an interface for Social Auth Vkontkate settings.
 */
interface VkontkateAuthSettingsInterface {

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
