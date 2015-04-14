<?php

/**
 * @file
 * Contains \Drupal\views_send\SettingsForm.
 */

namespace Drupal\views_send;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Configure update settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'views_send_settingsform';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'views_send.settings',
    ];
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $config = $this->config('views_send.settings');

    $throttle_values = array(1, 10, 20, 30, 50, 100, 200, 500, 1000, 2000, 5000, 10000, 20000);
    $throttle = array_combine($throttle_values, $throttle_values);
    $throttle[0] = t('Unlimited');

    $form['throttle'] = array(
      '#type' => 'select',
      '#title' => t('Cron throttle'),
      '#options' => $throttle,
      '#default_value' => $config->get('throttle'),
      '#description' => t('Sets the numbers of messages sent per cron run. Failure to send will also be counted. Cron execution must not exceed the PHP maximum execution time of %max seconds. You find the time spend to send e-mails in the !recent_logs.', array('%max' => ini_get('max_execution_time'), '!recent_logs' => l(t('Recent log entries'), 'admin/reports/dblog'))),
    );

    $form['spool_expire'] = array(
      '#type' => 'select',
      '#title' => t('Mail spool expiration'),
      '#options' => array(0 => t('Immediate'), 1 => t('1 day'), 7 => t('1 week'), 14 => t('2 weeks')),
      '#default_value' => $config->get('spool_expire'),
      '#description' => t('E-mails are spooled. How long must messages be retained in the spool after successfull sending.'),
    );

    $form['debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Log e-mails'),
      '#default_value' => $config->get('debug'),
      '#description' => t('When checked all outgoing mesages are logged in the system log. A logged e-mail does not guarantee that it is send or will be delivered. It only indicates that a message is send to the PHP mail() function. No status information is available of delivery by the PHP mail() function.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, array &$form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::submitForm().
   */
  public function submitForm(array &$form, array &$form_state) {
    $config = $this->config('views_send.settings');

    $config
      ->set('throttle', $form_state['values']['throttle'])
      ->set('spool_expire', $form_state['values']['spool_expire'])
      ->set('debug', $form_state['values']['debug'])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
