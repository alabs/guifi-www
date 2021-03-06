<?php
/**
 * @file
 * Definition of Drupal\Component\Plugin\Factory\ReflectionFactory.
 */

namespace Drupal\Component\Plugin\Factory;

use ReflectionClass;

/**
 * A plugin factory that maps instance configuration to constructor arguments.
 *
 * Provides logic for any basic plugin type that needs to provide individual
 * plugins based upon some basic logic.
 */
class ReflectionFactory extends DefaultFactory {

  /**
   * Implements Drupal\Component\Plugin\Factory\FactoryInterface::createInstance().
   */
  public function createInstance($plugin_id, array $configuration) {
    $plugin_class = static::getPluginClass($plugin_id, $this->discovery);

    // Lets figure out of there's a constructor for this class and pull
    // arguments from the $options array if so to populate it.
    $reflector = new ReflectionClass($plugin_class);
    if ($reflector->hasMethod('__construct')) {
      $arguments = $this->getInstanceArguments($reflector, $plugin_id, $configuration);
      $instance = $reflector->newInstanceArgs($arguments);
    }
    else {
      $instance = new $plugin_class();
    }

    return $instance;
  }

  /**
   * Inspects the plugin class and build a list of arguments for the constructor.
   *
   * This is provided as a helper method so factories extending this class can
   * replace this and insert their own reflection logic.
   *
   * @param ReflectionClass $reflector
   *   The reflector object being used to inspect the plugin class.
   * @param string $plugin_id
   *   The identifier of the plugin implementation.
   * @param array $configuration
   *   An array of configuration that may be passed to the instance.
   *
   * @return array
   *   An array of arguments to be passed to the constructor.
   */
  protected function getInstanceArguments(ReflectionClass $reflector, $plugin_id, array $configuration) {

    $arguments = array();
    foreach ($reflector->getMethod('__construct')->getParameters() as $param) {
      $param_name = $param->getName();
      $param_class = $param->getClass();

      if ($param_name == 'plugin_id') {
        $arguments[] = $plugin_id;
      }
      elseif ($param_name == 'configuration') {
        $arguments[] = $configuration;
      }
      elseif ($param_class && $param_class->isInstance($this->discovery)) {
        $arguments[] = $this->discovery;
      }
      elseif (isset($configuration[$param_name]) || array_key_exists($param_name, $configuration)) {
        $arguments[] = $configuration[$param_name];
      }
      elseif ($param->isDefaultValueAvailable()) {
        $arguments[] = $param->getDefaultValue();
      }
      else {
        $arguments[] = NULL;
      }
    }
    return $arguments;
  }
}
