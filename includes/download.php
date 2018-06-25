<?php

class File_Gate_Download {

  private $db;
  private $table;
  private $files_table;

  public function __construct() {
    global $wpdb;
    $this->db = $wpdb;
    $this->table = $wpdb->prefix . 'file_gate_downloads';
    $this->files_table = $wpdb->prefix . 'file_gate_files';
  }

  public function random_token() {
    return bin2hex(openssl_random_pseudo_bytes(16));
  }

  public function create( $file_id, $submission_id ) {
    $form_options = get_option( 'file_gate_form', false );
    $link_expiration = ( $form_options && !empty( $form_options['link_expiration'] ) ) ? $form_options['link_expiration'] : 24;

    $this->db->insert(
      $this->table,
      array(
        'file_id' => $file_id,
        'submission_id' => $submission_id,
        'token' => $this->random_token(),
        'expires_at' => time() + (60 * 60 * $link_expiration),
        'timestamp' => time(),
      )
    );

    return $this->db->insert_id;
  }

  /**
   * Get download from database.
   */
  public function find_by_id( $id ) {
    return $this->db->get_row( $this->db->prepare(
      "SELECT download.token, download.expires_at, file.title, file.filename, file.email_subject, file.email_message FROM $this->table AS download
      LEFT JOIN $this->files_table AS file ON download.file_id = file.id
      WHERE download.id = %d",
      $id
    ) );
  }

  /**
   * Get download from database.
   */
  public function find_by_token( $token ) {
    return $this->db->get_row( $this->db->prepare(
      "SELECT download.expires_at, download.file_id, file.title, file.filename FROM $this->table AS download
      LEFT JOIN $this->files_table AS file ON download.file_id = file.id
      WHERE download.token = %s",
      $token
    ) );
  }

  /**
   * Get downloads from database.
   */
  public function find_all() {
    return $this->db->get_results( "SELECT * FROM $this->table" );
  }
}
