<?php

class File_Gate_Submission {

  private $db;
  private $table;

  public function __construct() {
    global $wpdb;
    $this->db = $wpdb;
    $this->table = $wpdb->prefix . 'file_gate_submissions';
  }

  public function create( $email, $data ) {
    $data = ! empty( $data ) ? serialize( $data ) : '';
    $this->db->insert(
      $this->table,
      array(
        'email' => $email,
        'data' => $data,
        'timestamp' => time(),
      )
    );

    return $this->db->insert_id;
  }

  public function update( $id, $data ) {
    $data = ! empty( $data ) ? serialize( $data ) : '';

    $data = array( 'data' => $data );
    $placeholders = array('%s');

    return $this->db->update(
      $this->table,
      $data,
      array('id' => $id),
      $placeholders,
      array('%d')
    );
  }

  /**
   * Get download from database.
   */
  public function find_by_email( $email ) {
    return $this->db->get_row( $this->db->prepare(
      "SELECT * FROM $this->table WHERE email = %s",
      $email
    ) );
  }

  /**
   * Get submissions from database.
   */
  public function find_all() {
    return $this->db->get_results( "SELECT * FROM $this->table" );
  }
}
