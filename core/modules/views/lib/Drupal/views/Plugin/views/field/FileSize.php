<?php

/**
 * @file
 * Definition of Drupal\views\Plugin\views\field\FileSize.
 */

namespace Drupal\views\Plugin\views\field;

use Drupal\Core\Annotation\Plugin;

/**
 * Render a numeric value as a size.
 *
 * @ingroup views_field_handlers
 *
 * @Plugin(
 *   id = "file_size"
 * )
 */
class FileSize extends FieldPluginBase {

  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['file_size_display'] = array('default' => 'formatted');

    return $options;
  }

  public function buildOptionsForm(&$form, &$form_state) {
    parent::buildOptionsForm($form, $form_state);
    $form['file_size_display'] = array(
      '#title' => t('File size display'),
      '#type' => 'select',
      '#options' => array(
        'formatted' => t('Formatted (in KB or MB)'),
        'bytes' => t('Raw bytes'),
      ),
    );
  }

  function render($values) {
    $value = $this->get_value($values);
    if ($value) {
      switch ($this->options['file_size_display']) {
        case 'bytes':
          return $value;
        case 'formatted':
        default:
          return format_size($value);
      }
    }
    else {
      return '';
    }
  }

}
