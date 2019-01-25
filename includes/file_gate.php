<?php

class File_Gate {

  private $File;
  private $Download;
  private $Submission;

  private $form;

  public function __construct() {

    // Execute WP hooks.
    $this->hooks();

    $this->File = new File_Gate_File();
    $this->Download = new File_Gate_Download();
    $this->Submission = new File_Gate_Submission();

    $this->form = new File_Gate_Form( $this->File, $this->Download, $this->Submission );
  }

  public function hooks() {

    // Configure the admin menu.
    add_action( 'admin_menu', array( $this, 'admin_menu' ) );

    // Remove items from admin menu.
    add_action( 'admin_head', array( $this, 'hide_admin_menu_items' ) );

    // Create form shortcode.
    add_shortcode( 'file-gate', array( $this, 'form_shortcode' ) );

    // Create url endpoint to download file.
    add_action( 'rest_api_init', function() {
      register_rest_route( 'file-gate/v1', '/download/(?P<token>[A-Za-z0-9]+)', array(
        'methods' => 'GET',
        'callback' => array( $this, 'download' ),
      ) );
    } );

    // Add new file admin upload form handler.
    add_action( 'admin_post_file_gate_file_form_submit', array( $this, 'create_file_form_submit' ) );

    // Add delete file form handler.
    add_action( 'admin_post_file_gate_file_delete_submit', array( $this, 'delete_file_form_submit' ) );

    // Add export submissions callback.
    add_action( 'admin_post_file_gate_export_submissions', array( $this, 'export_submissions' ) );
  }

  public function download( $data ) {
    $download = $this->Download->find_by_token( $data['token'] );

    if ( $download ) {
      if ( time() < $download->expires_at) {
        if ( file_exists( ABSPATH . $download->filename)) {
          // Increment download count.
          $this->File->increment_downloads( $download->file_id );

          $filename = substr( $download->filename, strrpos( $download->filename, '/' ) + 1 );
          header('Content-disposition: attachment; filename=' . $filename);
          header('Content-type: application/pdf');
          readfile( ABSPATH . $download->filename );
          exit();
        } else {
          return 'Download not found.';
        }
      } else {
        return 'This download has expired. Please complete the form to download file.';
      }
    }

    return '';
  }

  public function form_shortcode( $atts = array(), $content = null ) {
    $file_id = array_key_exists( 'file_id', $atts) ? $atts['file_id'] : false;

    $content = $this->form->html( $file_id );

    return $content;
  }

  public function admin_menu() {
    add_menu_page(
      'File Gate',
      'File Gate',
      'manage_options',
      'file-gate',
      array(
        $this,
        'view_files'
      ),
      'dashicons-lock'
    );

    add_submenu_page(
      'file-gate',
      'Files',
      'Files',
      'manage_options',
      'file-gate',
      array(
        $this,
        'view_files'
      )
    );

    add_submenu_page(
      'file-gate',
      'New File',
      'New File',
      'manage_options',
      'file-gate-create-file',
      array(
        $this,
        'view_create_file'
      )
    );

    add_submenu_page(
      'file-gate',
      'Delete File',
      'Delete File',
      'manage_options',
      'file-gate-delete-file',
      array(
        $this,
        'delete_file'
      )
    );

    add_submenu_page(
      'file-gate',
      'Submissions',
      'Submissions',
      'manage_options',
      'file-gate-submissions',
      array(
        $this,
        'view_submissions'
      )
    );

    add_submenu_page(
      'file-gate',
      'Downloads',
      'Downloads',
      'manage_options',
      'file-gate-downloads',
      array(
        $this,
        'view_downloads'
      )
    );
  }

  public function hide_admin_menu_items() {
    remove_submenu_page( 'file-gate', 'file-gate-delete-file' );
  }

  /**
   * Get files from database table.
   *
   * @todo Finish implementation.
   */
  public function get_files() {
    $file = new stdClass;
    $file->id = 1;
    $file->title = 'Content Marketing Whitepaper';
    $file->filename = 'content-marketing-wp.pdf';

    return array($file);
  }

  public function view_files() {
    $files = $this->File->find_all();
    ?>
    <div class="wrap">
      <h1 class="wp-heading-inline">Files</h1>
      <a class="page-title-action" href="<?php print admin_url( 'admin.php?page=file-gate-create-file' ); ?>">Add New</a>
      <hr class="wp-header-end">
      <table class="wp-list-table widefat fixed striped">
        <thead>
        <tr>
          <th>ID</th>
          <th>Title</th>
          <th>Filename</th>
          <th>Downloads</th>
          <th>Shortcode</th>
          <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($files as $file): ?>
          <tr>
            <td><?php print esc_html($file->id); ?></td>
            <td><?php print esc_html($file->title); ?></td>
            <td><?php print esc_html($file->filename); ?></td>
            <td><?php print esc_html($file->downloads); ?></td>
            <td>[file-gate file_id="<?php print esc_html($file->id); ?>"]</td>
            <td>
              <a href="<?php print admin_url( 'admin.php?page=file-gate-create-file&file_id=' . $file->id ); ?>">Edit</a> |
              <a href="<?php print admin_url( 'admin.php?page=file-gate-delete-file&file_id=' . $file->id ); ?>">Delete</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
  }

  public function view_submissions() {
    $submissions = $this->Submission->find_all();
    ?>
    <div class="wrap">
      <h1>Submissions</h1>
      <table class="wp-list-table widefat fixed striped">
        <thead>
        <tr>
          <th>ID</th>
          <th>Email</th>
          <th>Data</th>
          <th>Timestamp</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($submissions as $submission): ?>
          <?php $data = unserialize( $submission->data ); ?>
          <tr>
            <td><?php print esc_html($submission->id); ?></td>
            <td><?php print esc_html($submission->email); ?></td>
            <td>
              <?php if ( ! empty( $data ) ): ?>
                <?php foreach ( $data as $field => $value ): ?>
                  <?php print str_replace( FILE_GATE_FORM_FIELD_PREFIX, '', $field ) . ': ' . $value; ?><br>
                <?php endforeach; ?>
              <?php endif; ?>
            </td>
            <td><?php print esc_html($submission->timestamp); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <p><a href="<?php print admin_url('admin-post.php?action=file_gate_export_submissions'); ?>" class="button">Export Submissions</a></p>
    </div>
    <?php
  }

  public function view_downloads() {
    $downloads = $this->Download->find_all();
    ?>
    <div class="wrap">
      <h1>Downloads</h1>
      <table class="wp-list-table widefat fixed striped">
        <thead>
        <tr>
          <th>ID</th>
          <th>Token</th>
          <th>Expires</th>
          <th>Timestamp</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($downloads as $download): ?>
          <tr>
            <td><?php print esc_html($download->id); ?></td>
            <td><?php print esc_html($download->token); ?></td>
            <td><?php print esc_html($download->expires_at); ?></td>
            <td><?php print esc_html($download->timestamp); ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php
  }

  public function view_create_file() {
    $file = isset( $_GET['file_id'] ) ? $this->File->find_by_id( absint( $_GET['file_id'] ) ) : false;
    ?>
    <div class="wrap">
      <h1>File Gate</h1>
      <form action="<?php print esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
        <input type="hidden" name="action" value="file_gate_file_form_submit">
        <?php wp_nonce_field( 'file_gate_create_file', 'file_gate_nonce', false, true ); ?>
        <?php if ($file): ?>
          <input type="hidden" name="file_id" value="<?php print $file->id; ?>">
        <?php endif; ?>
        <h2><?php print $file ? 'Edit' : 'New'; ?> File</h2>
        <table class="form-table">
          <tbody>
          <tr>
            <th scope="row">Title (Required)</th>
            <td>
              <input class="widefat" type="text" name="title" value="<?php print $file ? esc_attr( $file->title ) : ''; ?>">
            </td>
          </tr>
          <tr>
            <th scope="row">File (Required)</th>
            <td>
              <input type="file" name="file"><br>
              <?php if ( $file ): ?>
                <span class="description">Current File: <?php print esc_attr( $file->filename ); ?></span>
              <?php endif; ?>
            </td>
          </tr>
          <tr>
            <th scope="row">Email Subject</th>
            <td>
              <input class="widefat" type="text" name="email_subject" value="<?php print $file ? esc_attr( $file->email_subject ) : ''; ?>">
            </td>
          </tr>
          <tr>
            <th scope="row">Email Message</th>
            <td>
              <textarea class="widefat" name="email_message"><?php print $file ? $file->email_message : ''; ?></textarea><br>
              <span class="description">Leave blank if you do not want to send an email.<br>Placeholders: %download_url%</span>
            </td>
          </tr>
          <tr>
            <th scope="row">Success Message (Required)</th>
            <td>
              <textarea class="widefat" name="success_message"><?php print $file ? $file->success_message : ''; ?></textarea><br>
              <span class="description">If you are not sending an email, be sure to add a link to the download.<br>Placeholders: %download_url%</span>
            </td>
          </tr>
          </tbody>
        </table>
        <p class="submit">
          <input type="submit" name="submit" value="Save Changes" id="submit" class="button button-primary">
        </p>
      </form>
    </div>
    <?php
  }

  public function create_file_form_submit() {

    $errors = array();

    if ( wp_verify_nonce( $_POST['file_gate_nonce'], 'file_gate_create_file' ) ) {

      $file = isset( $_POST['file_id'] ) ? $this->File->find_by_id( absint( $_POST['file_id'] ) ) : false;
      $file_path = '';
      $file_uploaded = false;

      if ( file_exists( $_FILES['file']['tmp_name'] ) || is_uploaded_file( $_FILES['file']['tmp_name'] ) ) {
        // @todo Create customizable file path.
        //$protocol = ( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
        $site_url = get_site_url();
        $upload_dir = wp_upload_dir();
        $full_path = $upload_dir['basedir'] . '/private/';
        $path = str_replace($site_url, '', $upload_dir['baseurl']) . '/private/';

        $file_name = $_FILES['file']['name'];
        $file_tmp_name = $_FILES['file']['tmp_name'];
        //$file_type = $_FILES['file']['type'];
        //$file_size = $_FILES['file']['size'];
        $file_extension = strtolower( end( explode( '.', $file_name ) ) );

        $increment = 1;
        while ( file_exists( $full_path . basename( $file_name ) ) )  {
          $file_name = basename( $_FILES['file']['name'], '.' . $file_extension ) . '_' . $increment . '.' . $file_extension;
          $increment++;
        }

        $upload_path = $full_path . basename( $file_name );
        $file_path = $path . basename( $file_name );

        $allowed_extensions = array( 'pdf' );

        if ( ! in_array( $file_extension, $allowed_extensions ) ) {
          $errors[] = 'This file extension is not allowed.';
        }

        if ( empty( $errors ) ) {
          if ( move_uploaded_file( $file_tmp_name, $upload_path ) ) {
            $file_uploaded = true;
          } else {
            $errors[] = 'An unexpected error occurred, please try again.';
          }
        }
      } else if ( ! $file ) {
        $errors[] = 'File is required.';
      }

      if ( empty( $errors ) ) {
        $title = $this->sanitize( $_POST['title'] );
        $email_subject = $this->sanitize( $_POST['email_subject'] );
        $email_message = wp_kses_post( $_POST['email_message'] );
        $success_message = wp_kses_post( $_POST['success_message'] );

        if ( $file ) {
          $this->File->update( $file->id, $title, $email_subject, $email_message, $success_message, $file_path);
          if ( $file_uploaded ) {
            unlink( ABSPATH . $file->filename );
          }
        } else {
          $this->File->create( $title, $email_subject, $email_message, $success_message, $file_path );
        }
      }
    }

    $redirect_url = ( empty( $errors ) ) ? 'admin.php?page=file-gate' : 'admin.php?page=file-gate-create-file&error=1';
    wp_redirect( admin_url( $redirect_url ) );
  }

  public function delete_file() {
    $file = isset( $_GET['file_id'] ) ? $this->File->find_by_id( absint( $_GET['file_id'] ) ) : false;
    ?>
    <div class="wrap">
      <h1>File Gate</h1>
      <?php if ( $file ): ?>
        <form action="<?php print esc_url(admin_url('admin-post.php')); ?>" method="post" enctype="multipart/form-data">
          <input type="hidden" name="action" value="file_gate_file_delete_submit">
          <?php wp_nonce_field( 'file_gate_delete_file', 'file_gate_nonce', false, true ); ?>
          <input type="hidden" name="file_id" value="<?php print $file->id; ?>">
          <h2>Delete <?php print $file->title; ?></h2>
          <p>Are you sure you want to delete this file?</p>
          <p class="submit">
            <input type="submit" name="submit" value="Delete File" id="submit" class="button button-primary">
          </p>
        </form>
      <?php else: ?>
        <p>File does not exists.</p>
      <?php endif; ?>
    </div>
    <?php
  }

  public function delete_file_form_submit() {

    if ( wp_verify_nonce( $_POST['file_gate_nonce'], 'file_gate_delete_file' ) ) {

      $file = isset( $_POST['file_id'] ) ? $this->File->find_by_id( absint( $_POST['file_id'] ) ) : false;

      if ( $file ) {
        // @todo Make sure there are no downloads requested before deleting.
        $this->File->delete( $file->id );
        unlink( ABSPATH . $file->filename );
      }
    }

    wp_redirect( admin_url( 'admin.php?page=file-gate' ) );
  }

  /**
   * URL: /wp-admin/admin-post.php?action=file_gate_export_submissions
   */
  public function export_submissions() {
    $submissions = $this->Submission->find_all();

    $out = '';

    // Get all possible columns.
    $columns = array();
    foreach ( $submissions as $submission ) {
      $data = unserialize( $submission->data );
      if ( ! empty( $data ) ) {
        foreach ( $data as $field => $value ) {
          if ( ! array_key_exists( $field, $columns ) ) {
            $columns[$field] = $field;
          }
        }
      }
    }

    foreach ( $submissions as $submission ) {
      $out .= '"' . $submission->email . '","';
      $data = unserialize( $submission->data );
      foreach ( $columns as $column ) {
        $out .= array_key_exists( $column, $data ) ? $data[$column] : '';
        $out .= '","';
      }
      $out .= date('Y-m-d H:i:s', $submission->timestamp) . '"' . "\n";
    }

    header('Content-type: text/csv');
    header('Content-Disposition: attachment; filename=submissions-' . date('YmdHis') . '.csv');
    print $out;
    exit();
  }

  public function sanitize( $input ) {
    return htmlspecialchars(stripslashes( $input ), ENT_QUOTES, 'UTF-8');
  }

}