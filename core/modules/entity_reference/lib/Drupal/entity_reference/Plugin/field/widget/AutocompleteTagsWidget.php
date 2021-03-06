<?php

/**
 * @file
 * Contains \Drupal\entity_reference\Plugin\field\widget\AutocompleteTagsWidget.
 */

namespace Drupal\entity_reference\Plugin\field\widget;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;
use Drupal\entity_reference\Plugin\field\widget\AutocompleteWidgetBase;

/**
 * Plugin implementation of the 'entity_reference autocomplete-tags' widget.
 *
 * @Plugin(
 *   id = "entity_reference_autocomplete_tags",
 *   module = "entity_reference",
 *   label = @Translation("Autocomplete (Tags style)"),
 *   description = @Translation("An autocomplete text field."),
 *   field_types = {
 *     "entity_reference"
 *   },
 *   settings = {
 *     "match_operator" = "CONTAINS",
 *     "size" = 60,
 *     "autocomplete_path" = "entity_reference/autocomplete/tags",
 *     "placeholder" = ""
 *   },
 *   multiple_values = FIELD_BEHAVIOR_CUSTOM
 * )
 */
class AutocompleteTagsWidget extends AutocompleteWidgetBase {

  /**
   * Overrides \Drupal\entity_reference\Plugin\field\widget\AutocompleteWidgetBase::elementValidate()
   */
  public function elementValidate($element, &$form_state, $form) {
    $value = array();
    // If a value was entered into the autocomplete.
    $handler = entity_reference_get_selection_handler($this->field, $this->instance);
    $bundles = entity_get_bundles($this->field['settings']['target_type']);
    $auto_create = isset($this->instance['settings']['handler_settings']['auto_create']) ? $this->instance['settings']['handler_settings']['auto_create'] : FALSE;

    if (!empty($element['#value'])) {
      $entities = drupal_explode_tags($element['#value']);
      $value = array();
      foreach ($entities as $entity) {
        $match = FALSE;

        // Take "label (entity id)', match the id from parenthesis.
        if (preg_match("/.+\((\d+)\)/", $entity, $matches)) {
          $match = $matches[1];
        }
        else {
          // Try to get a match from the input string when the user didn't use
          // the autocomplete but filled in a value manually.
          $match = $handler->validateAutocompleteInput($entity, $element, $form_state, $form, !$auto_create);
        }

        if ($match) {
          $value[] = array('target_id' => $match);
        }
        elseif ($auto_create && (count($this->instance['settings']['handler_settings']['target_bundles']) == 1 || count($bundles) == 1)) {
          // Auto-create item. see entity_reference_field_presave().
          $value[] = array('target_id' => 'auto_create', 'label' => $entity);
        }
      }
    }
    // Change the element['#parents'], so in form_set_value() we
    // populate the correct key.
    array_pop($element['#parents']);
    form_set_value($element, $value, $form_state);
  }
}
