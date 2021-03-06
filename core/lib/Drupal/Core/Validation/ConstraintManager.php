<?php

/**
 * @file
 * Contains \Drupal\Core\Validation\ConstraintManager.
 */

namespace Drupal\Core\Validation;

use Drupal\Component\Plugin\Factory\DefaultFactory;
use Drupal\Component\Plugin\PluginManagerBase;
use Drupal\Component\Plugin\Discovery\StaticDiscoveryDecorator;
use Drupal\Component\Plugin\Discovery\DerivativeDiscoveryDecorator;
use Drupal\Component\Plugin\Discovery\ProcessDecorator;
use Drupal\Core\Plugin\Discovery\AlterDecorator;
use Drupal\Core\Plugin\Discovery\AnnotatedClassDiscovery;
use Drupal\Core\Plugin\Discovery\CacheDecorator;

/**
 * Constraint plugin manager.
 *
 * Manages validation constraints based upon
 * \Symfony\Component\Validator\Constraint, whereas Symfony constraints are
 * added in manually during construction. Constraint options are passed on as
 * plugin configuration during plugin instantiation.
 *
 * While core does not prefix constraint plugins, modules have to prefix them
 * with the module name in order to avoid any naming conflicts. E.g. a "profile"
 * module would have to prefix any constraints with "Profile".
 *
 * Constraint plugins may specify data types to which support is limited via the
 * 'type' key of plugin definitions. Valid values are any types registered via
 * the typed data API, or an array of multiple type names. For supporting all
 * types FALSE may be specified. The key defaults to an empty array, i.e. no
 * types are supported.
 */
class ConstraintManager extends PluginManagerBase {

  /**
   * Overrides \Drupal\Component\Plugin\PluginManagerBase::__construct().
   */
  public function __construct() {
    $this->discovery = new AnnotatedClassDiscovery('Validation', 'Constraint');
    $this->discovery = new StaticDiscoveryDecorator($this->discovery, array($this, 'registerDefinitions'));
    $this->discovery = new DerivativeDiscoveryDecorator($this->discovery);
    $this->discovery = new ProcessDecorator($this->discovery, array($this, 'processDefinition'));
    $this->discovery = new AlterDecorator($this->discovery, 'validation_constraint');
    $this->discovery = new CacheDecorator($this->discovery, 'validation_constraints:' . language(LANGUAGE_TYPE_INTERFACE)->langcode);

    $this->factory = new DefaultFactory($this);
  }

  /**
   * Callback for registering definitions for constraints shipped with Symfony.
   *
   * @see ConstraintManager::__construct()
   */
  public function registerDefinitions() {
    $this->discovery->setDefinition('Null', array(
      'label' => t('Null'),
      'class' => '\Symfony\Component\Validator\Constraints\Null',
      'type' => FALSE,
    ));
    $this->discovery->setDefinition('NotNull', array(
      'label' => t('Not null'),
      'class' => '\Symfony\Component\Validator\Constraints\NotNull',
      'type' => FALSE,
    ));
    $this->discovery->setDefinition('Blank', array(
      'label' => t('Blank'),
      'class' => '\Symfony\Component\Validator\Constraints\Blank',
      'type' => FALSE,
    ));
    $this->discovery->setDefinition('NotBlank', array(
      'label' => t('Not blank'),
      'class' => '\Symfony\Component\Validator\Constraints\NotBlank',
      'type' => FALSE,
    ));
    $this->discovery->setDefinition('Email', array(
      'label' => t('E-mail'),
      'class' => '\Symfony\Component\Validator\Constraints\Email',
      'type' => array('string'),
    ));
  }

  /**
   * Process definition callback for the ProcessDecorator.
   */
  public function processDefinition(&$definition, $plugin_id) {
    // Make sure 'type' is set and either an array or FALSE.
    if (!isset($definition['type'])) {
      $definition['type'] = array();
    }
    elseif ($definition['type'] !== FALSE && !is_array($definition['type'])) {
      $definition['type'] = array($definition['type']);
    }
  }

  /**
   * Returns a list of constraints that support the given type.
   *
   * @param string $type
   *   The type to filter on.
   *
   * @return array
   *   An array of constraint plugin definitions supporting the given type,
   *   keyed by constraint name (plugin ID).
   */
  public function getDefinitionsByType($type) {
    $definitions = array();
    foreach ($this->getDefinitions() as $plugin_id => $definition) {
      if ($definition['type'] === FALSE || in_array($type, $definition['type'])) {
        $definitions[$plugin_id] = $definition;
      }
    }
    return $definitions;
  }
}
