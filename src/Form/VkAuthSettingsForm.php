<?php

namespace Drupal\social_auth_vk\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\social_auth\Form\SocialAuthSettingsForm;

/**
 * Settings form for Social Auth Example.
 */
class VkAuthSettingsForm extends SocialAuthSettingsForm {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array_merge(['social_auth_vk.settings'], parent::getEditableConfigNames());
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
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('social_auth_vk.settings');

    $form['vk_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('VK Client settings'),
      '#open' => TRUE,
    ];

    $form['vk_settings']['client_id'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Application ID'),
      '#default_value' => $config->get('client_id'),
      '#description' => $this->t('Copy the Application ID here'),
    ];

    $form['vk_settings']['private_key'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Secure key'),
      '#default_value' => $config->get('private_key'),
      '#description' => $this->t('Copy the Secure key here'),
    ];

    $form['vk_settings']['client_secret'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Service token'),
      '#default_value' => $config->get('client_secret'),
      '#description' => $this->t('Copy the Service token here'),
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
      ->set('private_key', $values['private_key'])
      ->set('client_secret', $values['client_secret'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
