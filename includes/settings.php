<?php

class File_Gate_Settings {
  /**
   * Holds the values to be used in the fields callbacks
   */
  private $options;

  /**
   * Start up
   */
  public function __construct() {
    add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
    add_action( 'admin_init', array( $this, 'page_init' ) );
  }

  /**
   * Add options page
   */
  public function add_plugin_page() {
    // This page will be under "Settings".
    add_options_page(
      'File Gate',
      'File Gate',
      'manage_options',
      'file-gate-settings',
      array( $this, 'create_admin_page' )
    );
  }

  /**
   * Options page callback
   */
  public function create_admin_page() {
    // Set class property
    $this->options = get_option( 'file_gate_form' );
    ?>
    <div class="wrap">
      <h1>File Gate</h1>
      <form method="post" action="options.php">
        <?php
        // This prints out all hidden setting fields.
        settings_fields( 'file_gate_form_group' );
        do_settings_sections( 'file-gate-settings' );
        submit_button();
        ?>
      </form>
    </div>
    <?php
  }

  /**
   * Register and add settings
   */
  public function page_init() {
    register_setting(
      'file_gate_form_group', // Option group
      'file_gate_form', // Option name
      array( $this, 'sanitize' ) // Sanitize
    );

    add_settings_section(
      'file_gate_form_section', // ID
      'Form Options', // Title
      array( $this, 'print_section_info' ), // Callback
      'file-gate-settings' // Page
    );

    add_settings_field(
      'link_expiration', // ID
      'Link Expiration (Hours)', // Title
      array( $this, 'link_expiration_callback' ), // Callback
      'file-gate-settings', // Page
      'file_gate_form_section' // Section
    );

    add_settings_field(
      'form_fields', // ID
      'Form', // Title
      array( $this, 'form_fields_callback' ), // Callback
      'file-gate-settings', // Page
      'file_gate_form_section' // Section
    );
  }

  /**
   * Sanitize each setting field as needed
   *
   * @param array $input Contains all settings fields as array keys
   * @return array
   */
  public function sanitize( $input ) {
    $new_input = array();

    if( isset( $input['link_expiration'] ) ) {
      $new_input['link_expiration'] = absint( $input['link_expiration'] );
    }

    if( isset( $input['form_fields'] ) ) {
      $new_input['form_fields'] = sanitize_textarea_field( $input['form_fields'] );
    }

    return $new_input;
  }

  /**
   * Print the Section text
   */
  public function print_section_info() {
    print 'File Gate Form Configuration';
  }

  /**
   * Get the settings option array and print one of its values
   */
  public function link_expiration_callback() {
    printf(
      '<input type="text" id="link_expiration" name="file_gate_form[link_expiration]" value="%s">',
      isset( $this->options['link_expiration'] ) ? esc_attr( $this->options['link_expiration'] ) : ''
    );
  }

  /**
   * Get the settings option array and print one of its values
   */
  public function form_fields_callback() {
    printf(
      '<textarea id="form_fields" name="file_gate_form[form_fields]" class="widefat">%s</textarea>',
      isset( $this->options['form_fields'] ) ? esc_attr( $this->options['form_fields'] ) : ''
    );
  }
}

if( is_admin() ) {
  new File_Gate_Settings();
}