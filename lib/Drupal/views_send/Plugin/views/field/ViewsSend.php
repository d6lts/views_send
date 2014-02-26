<?php

/**
 * @file
 * Contains \Drupal\views_send\Plugin\views\field\ViewsSend.
 */

namespace Drupal\views_send\Plugin\views\field;

use Drupal\system\Plugin\views\field\BulkForm;;
use Drupal\views\ResultRow;

/**
 * Defines a simple send mass mail form element.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("views_send_bulk_form")
 */
class ViewsSend extends BulkForm {

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::preRender().
   */
  public function preRender(&$values) {
    parent::preRender($values);

    // If the view is using a table style, provide a placeholder for a
    // "select all" checkbox.
    if (!empty($this->view->style_plugin) && $this->view->style_plugin instanceof \Drupal\views\Plugin\views\style\Table) {
      // Add the tableselect css classes.
      $this->options['element_label_class'] .= 'select-all';
      // Hide the actual label of the field on the table header.
      $this->options['label'] = '';
    }
  }

  /**
   * Overrides \Drupal\views\Plugin\views\field\FieldPluginBase::render().
   */
  function render(ResultRow $values) {
    return '<!--form-item-' . $this->options['id'] . '--' . $this->view->row_index . '-->';
  }

  /**
   * Overrides \Drupal\system\Plugin\views\field\BulkForm::viewsForm(). 
   */
  function viewsForm(&$form, &$form_state) {
    // The view is empty, abort.
    if (empty($this->view->result)) {
      return;
    }

    // Add the custom CSS for all steps of the form.
    $form['#attached']['css'][] = drupal_get_path('module', 'views_send') . '/views_send.css';

    if ($form_state['step'] == 'views_form_views_form') {
      $form['actions']['submit']['#value'] = t('Send e-mail');
      $form['#prefix'] = '<div class="views-send-selection-form">';
      $form['#suffix'] = '</div>';

      // Adds the "select all" functionality for non-table style plugins.
      if (!($this->view->style_plugin instanceof Drupal\views\Plugin\views\style\Table)) {
        $form['select_all_markup'] = array(
          '#type' => 'markup',
          '#markup' => _theme('views_send_select_all'),
        );
      }

      // Add the tableselect javascript.
      $form['#attached']['library'][] = array('system', 'drupal.tableselect');

      // Render checkboxes for all rows.
      $form[$this->options['id']] = array(
        '#tree' => TRUE,
      );
      foreach ($this->view->result as $row_index => $row) {
        $form[$this->options['id']][$row_index] = array(
          '#type' => 'checkbox',
          '#default_value' => FALSE,
          '#attributes' => array('class' => array('views-send-select')),
        );
      }
    }
    else {
      $form_state['step']($form, $form_state, $this->view);
    }
  }

  /**
   * Overrides \Drupal\system\Plugin\views\field\BulkForm::viewsFormSubmit(). 
   */
  function viewsFormSubmit(&$form, &$form_state) {

    switch ($form_state['step']) {
      case 'views_form_views_form':
        $field_name = $this->options['id'];
        $selection = array_filter($form_state['values'][$field_name]);
        $form_state['selection'] = array_keys($selection);

        $form_state['step'] = 'views_send_config_form';
        $form_state['rebuild'] = TRUE;
        break;

      case 'views_send_config_form':
        $display = $form['display']['#value'];
        $config = \Drupal::config('views_send.user_settings');
        $config_basekey = $display . '.uid:' . \Drupal::currentUser()->id();
        if ($form_state['values']['views_send_remember']) {
          foreach ($form_state['values'] as $key => $value) {
            $key = ($key == 'format') ? 'views_send_message_format' : $key;
            if (substr($key, 0, 11) == 'views_send_') {
              $config->set($config_basekey . '.' . substr($key,11), $value);
            }
          }
          $config->save();
        } else {
          $config->clear($config_basekey);
          $config->save();
        }
        $form_state['configuration'] = $form_state['values'];

        // If a file was uploaded, process it.
        if (VIEWS_SEND_MIMEMAIL && Drupal::currentUser()->hasPermission('attachments with views_send') && 
            isset($_FILES['files']) && is_uploaded_file($_FILES['files']['tmp_name']['views_send_attachments'])) {
          // attempt to save the uploaded file
          $dir = file_default_scheme() . '://views_send_attachments';
          file_prepare_directory($dir, FILE_CREATE_DIRECTORY);
          $file = file_save_upload('views_send_attachments', $form_state, array(), $dir);
          // set error if file was not uploaded
          if (!$file) {
            //form_set_error('views_send_attachment', $form_state, t('Error uploading file.'));
          }
          else {
            // set files to form_state, to process when form is submitted
            // @todo: when we add a multifile formfield then loop through to add each file to attachments array
            $form_state['configuration']['views_send_attachments'][] = (array)$file;
          }
        }

        $form_state['step'] = 'views_send_confirm_form';
        $form_state['rebuild'] = TRUE;
        break;

      case 'views_send_confirm_form':

        // Queue the email for sending.
        views_send_queue_mail($form_state['configuration'], $form_state['selection'], $this->view);

        // Redirect.
        $query = drupal_get_query_parameters($_GET, array('q'));
        $form_state['redirect'] = array($this->view->getUrl(), array('query' => $query));
        break;
    }
  }
  
  /**
   * Overrides \Drupal\system\Plugin\views\field\BulkForm::::viewsFormValidate(). 
   */
  function viewsFormValidate(&$form, &$form_state) {
    if ($form_state['step'] != 'views_form_views_form') {
      return;
    }
    // Only the first initial form is handled here.
    $field_name = $this->options['id'];
    $selection = array_filter($form_state['values'][$field_name]);

    if (empty($selection)) {
      form_set_error($field_name, $form_state, t('Please select at least one item.'));
    }
  }
}
