<?php
/*
Plugin Name: EDD Restrict Registration
Plugin URI: http://isabelcastillo.com/docs/category/edd-restrict-registration
Description: Let EDD guest customers register with a simple shortcode, disable registration for everyone else.
Version: 0.8
Author: Isabel Castillo
Author URI: http://isabelcastillo.com
License: GPL2
Text Domain: edd-restrict-registration
Domain Path: languages
GitHub Plugin URI: https://github.com/isabelc/EDD-Restrict-Registration

Copyright 2015 Isabel Castillo

EDD Restrict Registration is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

EDD Restrict Registration is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with EDD Restrict Registration; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class EDD_Restrict_Registration{

	private static $instance = null;

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	private function __construct() {
			add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
			add_filter( 'edd_settings_extensions', array( $this, 'add_settings' ) );
			add_action( 'init', array( $this, 'form_submit_action' ) );
			add_action( 'eddrr_user_register', array( $this, 'process_form' ) );
    }

	public function load_textdomain() {
		load_plugin_textdomain( 'edd-restrict-registration', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}

	/**
	 * Get mail headers.
	 * @return string The Mail Header
	 */
	function get_mail_headers() {

		global $edd_options;

		// Get 'From Name' and 'From Email' from EDD settings
		$from_name = empty( $edd_options['from_name'] ) ? wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) : $edd_options['from_name'];
		$from_email = empty( $edd_options['from_email'] ) ? get_bloginfo( 'admin_email' ) : $edd_options['from_email'];
		$headers     = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";

		return $headers;
	}

	/**
	 * Send rejection email.
	 */
	function send_rejection_email( $name, $email ) {

		$time = date("Y-m-d g:i a");
		
		if ( get_option( 'eddrr_prevent_duplicate_email_sends' ) != ( $email . $time ) ) {


			global $edd_options;

			$rejection_msg_default = __( 'Dear', 'edd-restrict-registration' ) . " {registrant_name},\n\n" . __( 'Thank you for trying to register on our site. We are sorry, but your email address is not recognized. Please register with the email that you used to complete a purchase on our site.', 'edd-restrict-registration' ) . "\n\n" . __( 'Best Regards,', 'edd-restrict-registration' ) . "\n\n" . __( 'The team at', 'edd-restrict-registration' ) . " {sitename}";

			$rejection_msg = empty( $edd_options['eddrr_rejection_message'] ) ? $rejection_msg_default : $edd_options['eddrr_rejection_message'];

			$message = $this->do_email_tags( $rejection_msg, $name, '' );

			$rejection_subject = empty( $edd_options['eddrr_rejection_subject'] ) ? __( 'Your registration is not complete', 'edd-restrict-registration' ) : $edd_options['eddrr_rejection_subject'];

			$headers = $this->get_mail_headers();

			if ( wp_mail( $email, $rejection_subject, $message, $headers ) ) {
				update_option( 'eddrr_prevent_duplicate_email_sends', $email . $time );
			}
		}
	}

	/**
	 * Send reminder email to existing registered customer.
	 */
	function send_reminder_email( $name, $email, $username = '' ) {

		$time = date("Y-m-d g:i a");
		
		if ( get_option( 'eddrr_prevent_duplicate_email_sends' ) != ( $email . $time ) ) {

			global $edd_options;

			$exists_message_default = __( 'Hello', 'edd-restrict-registration' ) . " {registrant_name},\n\n" . __( 'It seems you already have an account on our site.', 'edd-restrict-registration' ) . "\n\n" . __( 'Your username is:', 'edd-restrict-registration' ) . "  {registrant_username}\n\n" .	__( 'Warm Regards,', 'edd-restrict-registration' ) . "\n\n" . __( 'The team at', 'edd-restrict-registration' ) . " {sitename}\n{siteurl}";

			$exists_message = empty ( $edd_options['eddrr_already_exists_message'] ) ? $exists_message_default : $edd_options['eddrr_already_exists_message'];
				
			$message = $this->do_email_tags( $exists_message, $name, $username );

			$subject = empty( $edd_options['eddrr_already_exists_subject'] ) ? __( 'Your registration details', 'edd-restrict-registration' ) : $edd_options['eddrr_already_exists_subject'];

			$headers = $this->get_mail_headers();

			if ( wp_mail( $email, $subject, $message, $headers ) ) {
				update_option( 'eddrr_prevent_duplicate_email_sends', $email . $time );
			}
		}
	}

	/**
	 * Hooks our registration form submit action.
	 *
	 * @since 1.0
	 * @return void
	*/
	function form_submit_action() {

		// Block spam bots
		if ( empty( $_POST['eddrr_hundred_acre_wood_field'] ) ) {

			if ( isset( $_POST['eddrr_action'] ) ) {
				if ( 'user_register' == $_POST['eddrr_action'] ) {
					do_action( 'eddrr_user_register', $_POST );

				}
			}
		}
	}

	/**
	 * Process the registration form.
	 * @param array $data Data sent from the register form
	 */
	function process_form( $data ) {

		if ( ! isset( $_POST['edd_restrict_registration_email'] ) ) {
			return false;
		}

		if ( ! isset( $_POST['edd_restrict_registration_name'] ) ) {
			return false;
		}

		// sanitize data
		$email 				= sanitize_text_field( $data['edd_restrict_registration_email'] );
		$email 				= sanitize_email( $email );
		$name 				= sanitize_text_field( $data['edd_restrict_registration_name'] );
		$desired_username 	= sanitize_text_field( $data['edd_restrict_registration_username'] );
		$desired_username 	= sanitize_user( $desired_username );

	
		if ( $email && $name ) {

			/** 
			 * Check if posted email is from an existing guest customer. 
			 * If yes, register them as a user, and send them email notification of their login details.
			 * If email is already a registered user's email, send them their existing username.
			 * If email is not matched with any customer, reply to email asking them to register with the email they used on checkout page.
			 *
			*/
		
			$time = date("Y-m-d g:i a");

			$customer = EDD()->customers->get_customer_by( 'email', $email );

			// Is it a customer?

			if ( $customer ) {


				// Did they actually make a purchase?

				if ( ! empty( $customer->purchase_count ) ) {

					// Is this customer a registered user?

					// I've seen that user_id can be -1 or null ... so target guest buyers like so...
					$user_id = ! empty( $customer->user_id ) ? intval( $customer->user_id ) : 0;
					if ( $user_id != -1 ) {

						// get username
						$user_info = get_userdata( $user_id );
      					$username = $user_info->user_login;

						$this->send_reminder_email( $name, $email, $username );


					} else {

						// register this customer

						unset( $new_user_login );

						// If desired username is not taken, give it, otherwise use email for username

						if ( $desired_username ) {

							$taken_name = get_user_by( 'login', $desired_username );
							$new_user_login = empty( $taken_name ) ? $desired_username : $email;
						} else {

							// if email is not already taken AS A USERNAME, assign it.
							$taken_name = get_user_by( 'login', $email );
							$new_user_login = empty( $taken_name ) ? $email : '';
						}

						// only add user if we have a unique userlogin and email
						if ( ! empty( $new_user_login ) && ( ! email_exists( $email ) ) ) {
							$password = wp_generate_password();
							$new_user_id = wp_insert_user(
										array(
											'user_email' 	=> $email,
											'user_login' 	=> $new_user_login,
											'user_pass'		=> $password,
											'first_name'	=> $name,
											)
										);
					
							if ( $new_user_id && ( get_option( 'eddrr_prevent_duplicate_email_sends' ) != ( $email . $time ) ) ) {


								// update customer's user id in the Customers table
								$customer_to_update = new EDD_Customer( $email );
								if ( empty( $customer_to_update->id ) ) {
									return false;
								}
								$data_to_update = array(
									'user_id'	=> $new_user_id
								);					
								$customer_to_update->update( $data_to_update );	

								wp_new_user_notification( $new_user_id, $password );
								update_option( 'eddrr_prevent_duplicate_email_sends', $email . $time );
			
							}
						}

					}

					
				} else {

					// This "customer" has no purchases, so not a customer.

					$this->send_rejection_email( $name, $email );

				}
				
			} else {

				// Not a customer.

				$this->send_rejection_email( $name, $email );

			} // End customer check

		}

		// add a query arg to queue the success message
		$url = esc_url_raw( add_query_arg( 'eddrr_success', 'success' ) );
		$url = wp_kses_decode_entities( $url );
		wp_redirect( $url );
		exit;
	}
	/**
	 * Add settings to the "Downloads > Settings > Extensions" section
	 * @since 1.0
	 */
	public function add_settings( $settings ) {
		$eddrr_settings = array(
			array(
				'id' => 'eddrr_settings_header',
				'name' => '<h3 class="title">'. __( 'EDD Restrict Registration', 'edd-restrict-registration' ) . '</h3>',
				'type' => 'header'
			),
			array(
				'id' => 'eddrr_form_submit_success_message',
				'name' => __( 'Success Message When Form is Submitted', 'edd-restrict-registration' ), 
				'desc' => __( 'Enter the message that is displayed when a visitor submits the registration form.', 'edd-restrict-registration' ),
				'type' => 'textarea',
				'std' => __( 'Your request was sent successfully. As soon as your registration is confirmed, you will receive your login details via email.', 'edd-restrict-registration' )
			),
			array(
				'id' => 'eddrr_rejection_subject',
				'name' => __( 'Rejection Email Subject', 'edd-restrict-registration' ), 
				'desc' => __( 'Enter the subject line for the rejection email', 'edd-restrict-registration' ),
				'type' => 'text',
				'std' => __( 'Your registration is not complete', 'edd-restrict-registration' )
			),
			array(
				'id' => 'eddrr_rejection_message',
				'name' => __( 'Rejection Email Message', 'edd-restrict-registration' ), 
				'desc' => __( 'Enter the message that is sent to someone who is not a customer. HTML links (a tags) are accepted. These other special tags are allowed:', 'edd-restrict-registration' ) . '<br />{registrant_name}<br />{sitename}<br />{siteurl}',
				'type' => 'textarea',
				'std' => __( 'Dear', 'edd-restrict-registration' ) . " {registrant_name},\n\n" . __( 'Thank you for trying to register on our site. We are sorry, but your email address is not recognized. Please register with the email that you used to complete a purchase on our site.', 'edd-restrict-registration' ) . "\n\n" . __( 'Best Regards,', 'edd-restrict-registration' ) . "\n\n" . __( 'The team at', 'edd-restrict-registration' ) . " {sitename}"
			),
			array(
				'id' => 'eddrr_already_exists_subject',
				'name' => __( 'Email Subject If Account Already Exists', 'edd-restrict-registration' ),
				'desc' => __( 'Enter the subject line for the email sent to registrant if their account already exists', 'edd-restrict-registration' ),
				'type' => 'text',
				'std' => __( 'Your registration details', 'edd-restrict-registration' )

			),
			array(
				'id' => 'eddrr_already_exists_message',
				'name' => __( 'Email Message If Account Already Exists', 'edd-restrict-registration' ), 
				'desc' => __( 'Enter the message that is sent to registrant if their account already exists. HTML links (a tags) are accepted. These other special tags are allowed:', 'edd-restrict-registration' ) . '<br />{registrant_name}<br />{registrant_username}<br />{sitename}<br />{siteurl}',
				'type' => 'textarea',
				'std' => __( 'Hello', 'edd-restrict-registration' ) . " {registrant_name},\n\n" . __( 'It seems you already have an account on our site.', 'edd-restrict-registration' ) . "\n\n" . __( 'Your username is:', 'edd-restrict-registration' ) . "  {registrant_username}\n\n" .	__( 'Warm Regards,', 'edd-restrict-registration' ) . "\n\n" . __( 'The team at', 'edd-restrict-registration' ) . " {sitename}\n{siteurl}"
			),
		);
		// Add settings to EDD settings
		return array_merge( $settings, $eddrr_settings );
	}

	/**
	* Replace email tags with proper content
	*
	* @param string $content Content to search for email tags
	* @param string $name Name from form
	* @param string $username Registrant's existing username
	*
	* @since 1.0
	*
	* @return string Content with email tags filtered out.
	*/
	public function do_email_tags( $content, $name, $username = '' ) {
	
		$search = array( '{registrant_name}', '{registrant_username}', '{sitename}', '{siteurl}' );
		$replace = array( $name, $username, wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ), get_bloginfo('url') );
		$new_content = str_replace ( $search, $replace, $content );
		return $new_content;
	}

	/**
	 * Upon deactivation, delete the option that prevents sending email to visitors more than once a minute
	 */
	public static function deactivate() {
		delete_option( 'eddrr_prevent_duplicate_email_sends' );
	}

	/**
	 * The registration form shortcode.
	 *
	 * Outputs the registration form that allows visitors to apply for registration.	 *
	 * @since 	1.0
	 * @param 	null $content
	 * @return 	string Output generated from the template part
	 */
	public function eddrr_shortcode( $atts, $content = null ) {
		ob_start();
		load_template( dirname( __FILE__ ) . '/template.php' );
		$display = ob_get_clean();
		return $display;
	}
}
register_deactivation_hook( __FILE__, array( 'EDD_Restrict_Registration', 'deactivate' ) );
$edd_restrict_registration = EDD_Restrict_Registration::get_instance();
add_shortcode( 'edd_restrict_registration_form', array( $edd_restrict_registration, 'eddrr_shortcode' ) );
