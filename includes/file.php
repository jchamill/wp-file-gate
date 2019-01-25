<?php

class File_Gate_File {

  private $db;
  private $table;

  public function __construct() {
    global $wpdb;
    $this->db = $wpdb;
    $this->table = $wpdb->prefix . 'file_gate_files';
  }

  public function create( $title, $email_subject, $email_message, $success_message, $filename ) {
    $this->db->insert(
      $this->table,
      array(
        'title' => $title,
        'filename' => $filename,
        'email_subject' => $email_subject,
        'email_message' => $email_message,
        'success_message' => $success_message,
        'timestamp' => time(),
      )
    );

    return $this->db->insert_id;
  }

  public function update( $id, $title, $email_subject, $email_message, $success_message, $filename = '' ) {
    $data = array(
      'title' => $title,
      'email_subject' => $email_subject,
      'email_message' => $email_message,
      'success_message' => $success_message,
    );
    $placeholders = array(
      '%s',
      '%s',
    );

    if ( ! empty( $filename ) ) {
      $data['filename'] = $filename;
      $placeholders[] = '%s';
    }

    return $this->db->update(
      $this->table,
      $data,
      array('id' => $id),
      $placeholders,
      array('%d')
    );
  }

  public function increment_downloads( $id ) {
    return $this->db->get_row( $this->db->prepare(
      "UPDATE $this->table SET downloads = downloads + 1 WHERE id = %d",
      $id
    ) );
  }

  public function delete( $id ) {
    return $this->db->get_row( $this->db->prepare(
      "DELETE FROM $this->table WHERE id = %d",
      $id
    ) );
  }

  /**
   * Get file from database.
   */
  public function find_by_id( $id ) {
    return $this->db->get_row( $this->db->prepare(
      "SELECT * FROM $this->table WHERE id = %d",
      $id
    ) );
  }

  /**
   * Get files from database.
   */
  public function find_all() {
    return $this->db->get_results( "SELECT * FROM $this->table" );
  }
}
