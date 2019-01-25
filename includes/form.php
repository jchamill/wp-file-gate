<?php

class File_Gate_Form {

  private $File;
  private $Download;
  private $Submission;

  private $errors = array();
  private $submitted = false;
  private $download_token = false;

  public function __construct( $File, $Download, $Submission ) {
    $this->hooks();

    $this->File = $File;
    $this->Download = $Download;
    $this->Submission = $Submission;
  }

  public function hooks() {
    add_action( 'wp_loaded', array( $this, 'form_submit' ) );
  }

  public function html( $file_id ) {

    // Verify this file exists.
    $file = $this->File->find_by_id( $file_id );
    if ( ! $file ) {
      return false;
    }

    // Get form fields.
    $fields = $this->get_fields();

    $html = '';

    $html .= '<div id="file-gate-form">';

    if ( ! empty( $this->errors ) ) {
      $html .= '<div class="fg-errors">';

      $messages = array();
      foreach ( $this->errors as $code => $error_names ) {
        foreach ( $error_names as $name ) {
          if ( isset( $fields[$name] ) ) {
            if ( 'r' == $code ) {
              $messages[] = '<p><em>&ldquo;' . $fields[$name]->label . '&rdquo;</em> field is required.</p>';
            } else if ( 'v' == $code ) {
              $messages[] = '<p><em>&ldquo;' . $fields[$name]->label . '&rdquo;</em> field is not valid.</p>';
            }
          }
        }
      }

      $html .= implode( '', $messages );

      $html .= '</div>';
    }

    if ( $this->submitted && empty( $this->errors ) ) {
      $download_url = $this->get_download_url( $this->download_token );
      $success_message = $file->success_message;
      $success_message = str_replace( '%download_url%', $download_url, $success_message );
      $html .= $success_message;
    } else {

      $html .= '<form action="' . $this->get_form_action() . '" method="post">';
      $html .= '<input type="hidden" name="' . FILE_GATE_FORM_FIELD_PREFIX . 'file-id" value="' . esc_attr( $file_id ) . '">';
      foreach ( $fields as $field ) {
        $help_text = $field->required ? 'required' : 'optional';
        $value = isset( $_POST[$field->name] ) && ! empty ( $_POST[$field->name] ) ? $_POST[$field->name] : '';
        $html .= '<p>';
        $html .= '<label>' . $field->label . ' (' . $help_text . ')</label>';
        $html .= '<input type="' . $field->type . '" name="' . $field->name . '" value="' . esc_attr( $value ) . '">';
        $html .= '</p>';
      }
      $html .= '<p class="' . FILE_GATE_FORM_FIELD_PREFIX . 'submit"><input type="submit" value="Submit" name="' . FILE_GATE_FORM_FIELD_PREFIX . 'submit"></p>';
      $html .= '</form>';
    }

    $html .= '</div>';

    return $html;
  }

  public function get_fields() {
    $form_options = get_option( 'file_gate_form', false );
    $form_fields = ( $form_options && !empty( $form_options['form_fields'] ) ) ? $form_options['form_fields'] : 'email*|Your Email';

    $fields = array();
    $lines = explode( PHP_EOL, $form_fields );

    foreach ( $lines as $line ) {
      $required = false;
      $parts = explode( '|', trim( $line ), 2 );
      if ( substr( $parts[0], strlen( $parts[0] ) - 1 ) == '*' ) {
        $required = true;
        $parts[0] = substr( $parts[0], 0, strlen( $parts[0] ) - 1 );
      }
      $field = new stdClass();
      $field->name = $this->filter_name( $parts[0] );
      $field->required = $required;
      $field->label = isset( $parts[1] ) ? $parts[1] : $parts[0];
      $field->type = 'text';
      // Email is always required and should be email field.
      if (FILE_GATE_FORM_FIELD_PREFIX . 'email' == $field->name) {
        $field->required = true;
        $field->type = 'email';
      }
      $fields[$field->name] = $field;
    }

    return $fields;
  }

  public function form_submit() {

    if ( ! isset( $_POST[FILE_GATE_FORM_FIELD_PREFIX . 'file-id'] ) ) {
      return;
    }

    $this->submitted = true;

    $fields = $this->get_fields();

    $this->errors = $this->form_validate( $fields, $_POST );

    if ( empty ($this->errors ) ) {
      // Set email and data from posted data.
      $email = '';
      $data = array();
      foreach ( $fields as $field ) {
        if ( FILE_GATE_FORM_FIELD_PREFIX . 'email' == $field->name ) {
          $email = $this->sanitize( $_POST[$field->name] );
        } else {
          $data[$field->name] = $this->sanitize( $_POST[$field->name] );
        }
      }

      if ( empty( $email ) ) {
        throw new Exception('File Gate: Email field was empty during form submit.');
      }

      // Check for existing submission.
      $submission = $this->Submission->find_by_email( $email );

      // Either update or create submission.
      if ( $submission ) {
        $this->Submission->update( $submission->id, $data );
        $submission_id = $submission->id;
      } else {
        $submission_id = $this->Submission->create( $email, $data );
      }

      // Create new download.
      $file_id = absint( $_POST[FILE_GATE_FORM_FIELD_PREFIX . 'file-id'] );
      $download_id = $this->Download->create( $file_id, $submission_id );

      // Get download (has a join on the files table).
      $download = $this->Download->find_by_id( $download_id );
      $this->download_token = $download->token;

      if ( ! empty( $download->email_message ) ) {
        $download_url = $this->get_download_url( $download->token );

        // Email subject.
        $email_subject = ! empty( $download->email_subject ) ? $download->email_subject : 'Your Download';

        // Build email message.
        $email_message = $download->email_message;
        $email_message = str_replace( '%download_url%', $download_url, $email_message );

        // Send email.
        wp_mail( $email, $email_subject, $email_message );
      }

    }
  }

  public function get_download_url($token) {
    $protocol = ( $_SERVER['HTTPS'] ) ? 'https://' : 'http://';
    return $protocol . $_SERVER['HTTP_HOST'] . '/wp-json/file-gate/v1/download/' . $token;
  }

  public function form_validate( $fields, $data ) {
    $errors = array();

    foreach ( $fields as $field ) {
      if ( $field->required && empty( $data[$field->name] ) ) {
        if ( ! array_key_exists( 'r', $errors ) ) {
          $errors['r'] = array();
        }
        $errors['r'][] = $field->name;
      } else if ( FILE_GATE_FORM_FIELD_PREFIX . 'email' == $field->name && ! is_email( $data[$field->name] ) ) {
        $errors['v'] = array( FILE_GATE_FORM_FIELD_PREFIX . 'email' );
      }
    }

    return $errors;
  }

  public function get_form_action() {
    return esc_url_raw( $_SERVER['REQUEST_URI'] ) . '#file-gate-form';
  }

  public function filter_name( $input ) {
    // Add prefix to avoid POST data collisions.
    return FILE_GATE_FORM_FIELD_PREFIX . preg_replace( '/[^a-z0-9-]+/', '-', strtolower( $input ) );
  }

  public function sanitize( $input ) {
    return htmlspecialchars( stripslashes( $input ), ENT_QUOTES, 'UTF-8' );
  }
}
