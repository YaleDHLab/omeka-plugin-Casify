<?php

/**
 * Plugin to protect certain routes with CAS authentication
 */

define('CASIFY_PLUGIN_DIR', PLUGIN_DIR . '/Casify');

class CasifyPlugin extends Omeka_Plugin_AbstractPlugin
{

  /**
   * @var array Hooks for the plugin.
   */

  public function setUp()
    {
      parent::setUp();

      require_once(CASIFY_PLUGIN_DIR . '/libraries/Casify_ControllerPlugin.php');
      Zend_Controller_Front::getInstance()->registerPlugin(new Casify_ControllerPlugin);
    }
}
