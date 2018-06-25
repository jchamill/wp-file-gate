<?php
/**
 * Plugin Name: File Gate
 * Plugin URI: http://www.jacksonspalding.com
 * Description: Require users to complete form with valid email to download a file.
 * Version: 0.0.1
 * Author: Jackson Spalding
 * Author URI: http://www.jacksonspalding.com
 */

if ( ! defined( 'ABSPATH' ) ) {
  exit;
}

define( 'FILE_GATE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FILE_GATE_FORM_FIELD_PREFIX', 'fg-' );

require_once FILE_GATE_PLUGIN_DIR . 'includes/installer.php';
require_once FILE_GATE_PLUGIN_DIR . 'includes/file.php';
require_once FILE_GATE_PLUGIN_DIR . 'includes/download.php';
require_once FILE_GATE_PLUGIN_DIR . 'includes/submission.php';
require_once FILE_GATE_PLUGIN_DIR . 'includes/form.php';
require_once FILE_GATE_PLUGIN_DIR . 'includes/settings.php';
require_once FILE_GATE_PLUGIN_DIR . 'includes/file_gate.php';


// Install plugin.
function file_gate_install() {
  new File_Gate_Installer();
}

register_activation_hook( __FILE__, 'file_gate_install' );


// Init plugin.
function file_gate_init() {
  static $instance;
  if ( is_null( $instance ) ) {
    $instance = new File_Gate();
  }
  return $instance;
}

add_action( 'plugins_loaded', 'file_gate_init', 10 );