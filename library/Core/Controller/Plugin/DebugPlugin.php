<?php

namespace Core\Controller\Plugin;

/**
 * Description of Debug
 *
 * @author mrolli
 */
class DebugPlugin extends \ZFDebug_Controller_Plugin_Debug
{
    /**
     * Load plugins set in config option
     *
     * Overrides parent method to be able to load
     * namespaced plugins.
     *
     * @return void;
     */
    protected function _loadPlugins()
    {
        foreach ($this->_options['plugins'] as $plugin => $options) {
            if (is_numeric($plugin)) {
                # Plugin passed as array value instead of key
                $plugin = $options;
                $options = array();
            }

            // Register an instance
            if (is_object($plugin) && in_array('ZFDebug_Controller_Plugin_Debug_Plugin_Interface', class_implements($plugin))) {
                $this->registerPlugin($plugin);
                continue;
            }

            if (!is_string($plugin)) {
                throw new \Zend_Exception("Invalid plugin name", 1);
            }
            $plugin = ucfirst($plugin);

            // Register a classname
            if (in_array($plugin, \ZFDebug_Controller_Plugin_Debug::$standardPlugins)) {
                // standard plugin
                $pluginClass = 'ZFDebug_Controller_Plugin_Debug_Plugin_' . $plugin;
            } else {
                // we use a custom plugin
                if (!preg_match('~^[\\\\\w]+$~D', $plugin)) {
                    throw new \Zend_Exception("ZFDebug: Invalid plugin name [$plugin]");
                }
                $pluginClass = $plugin;
            }

            if (!class_exists($pluginClass, true)) {
                require_once str_replace('_', DIRECTORY_SEPARATOR, $pluginClass) . '.php';
            }
            $object = new $pluginClass($options);
            $this->registerPlugin($object);
        }
    }
}