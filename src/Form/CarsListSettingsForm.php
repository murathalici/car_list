<?php

namespace Drupal\cars_list\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure Cars List settings for this site.
 */
class CarsListSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'cars_list_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['cars_list.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('cars_list.settings');

    $form['endpoint'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Endpoint'),
      '#default_value' => $config->get('endpoint'),
      '#required' => TRUE,
    ];

    $form['cache_duration'] = [
      '#type' => 'number',
      '#title' => $this->t('Cache Duration (in seconds)'),
      '#default_value' => $config->get('cache_duration'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('cars_list.settings')
      ->set('endpoint', $form_state->getValue('endpoint'))
      ->set('cache_duration', $form_state->getValue('cache_duration'))
      ->save();
  }

}
