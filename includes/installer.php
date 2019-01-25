<?php

class File_Gate_Installer {

  public function __construct() {

    // Create database tables.
    $this->create_files_table();
    $this->create_downloads_table();
    $this->create_submissions_table();
  }

  public function create_files_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'file_gate_files';

    $sql = "CREATE TABLE $table_name (
      id bigint(20) UNSIGNED NOT NULL auto_increment,
      title varchar(100) NOT NULL,
      filename varchar(100) NOT NULL,
      email_subject varchar(100),
      email_message text,
      success_message text,
      downloads mediumint(9) UNSIGNED DEFAULT 0 NOT NULL,
      timestamp int(11) NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
  }

  public function create_downloads_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'file_gate_downloads';

    $sql = "CREATE TABLE $table_name (
      id bigint(20) UNSIGNED NOT NULL auto_increment,
      file_id bigint(20) UNSIGNED NOT NULL,
      submission_id bigint(20) UNSIGNED NOT NULL,
      token varchar(32) NOT NULL,
      expires_at int(11) NOT NULL,
      timestamp int(11) NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
  }

  public function create_submissions_table() {
    global $wpdb;

    $charset_collate = $wpdb->get_charset_collate();
    $table_name = $wpdb->prefix . 'file_gate_submissions';

    $sql = "CREATE TABLE $table_name (
      id bigint(20) UNSIGNED NOT NULL auto_increment,
      email varchar(255) NOT NULL,
      data longtext,
      timestamp int(11) NOT NULL,
      PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
  }
}