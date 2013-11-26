<?php

/**
 * @file
 * Definition of \Drupal\views_send\Plugin\views\field\ViewsSend.
 */

namespace Drupal\views_send\Plugin\views\field;

use Drupal\Component\Annotation\PluginID;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;

/**
 * Defines a simple send mass mail form element.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("views_send_bulk_form")
 */
class ViewsSend extends FieldPluginBase {

  /**
   * Overrides \Drupal\views\Plugin\views\Plugin\field\FieldPluginBase::query().
   */
  function query() {
    // Do nothing.
  }

  /**
   * Overrides \Drupal\views\Plugin\views\Plugin\field\FieldPluginBase::pre_render().
   */
  public function pre_render(&$values) {
    parent::pre_render($values);

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
   * Overrides \Drupal\views\Plugin\views\Plugin\field\FieldPluginBase::render().
   */
  function render(ResultRow $values) {
    return '<!--form-item-' . $this->options['id'] . '--' . $this->view->row_index . '-->';
  }

  /**
   * Implements \Drupal\views\Plugin\views\Plugin\field\FieldPluginBase::views_form(). 
   */
  function views_form(&$form, &$form_state) {
    // The view is empty, abort.
    if (empty($this->view->result)) {
      return;
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

  /**
   * Implements \Drupal\views\Plugin\views\Plugin\field\FieldPluginBase::views_form_validate(). 
   */
  function views_form_validate($form, &$form_state) {
    $field_name = $this->options['id'];
    $selection = array_filter($form_state['values'][$field_name]);

    if (empty($selection)) {
      form_set_error($field_name, $form_state, t('Please select at least one item.'));
    }
  }
}
