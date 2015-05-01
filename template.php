<?php
/**
 * This template is used to display the EDD Register Guest Customers registration form with [edd_restrict_registration_form]
 */
if ( ! is_user_logged_in() ): 
	wp_enqueue_style( 'edd-restrict-registration', plugin_dir_url( __FILE__ ) . 'eddrr.css' );
	global $edd_options;

	if ( ! empty( $edd_options['eddrr_form_submit_success_message'] ) ) {
		$msg = sanitize_text_field( $edd_options['eddrr_form_submit_success_message'] );
	} else {
		$msg = __( 'Your request was sent successfully. As soon as your registration is confirmed, you will receive your login details via email.', 'edd-restrict-registration' );
	}
	?>
	<form id="edd-restrict-registration" class="edd_form" action="" method="post">
		<span id="edd-restrict-registration-success"></span>
		<script>
		function getQueryVariable(variable) {
			var query = window.location.search.substring(1);
			var vars = query.split("&");
			for (var i=0;i<vars.length;i++) {
				var pair = vars[i].split("=");
				if(pair[0] == variable){return pair[1];}
			}
			return(false);
		}

		var success = getQueryVariable('eddrr_success');
		if ( success ) {
			var element = document.getElementById('edd-restrict-registration-success');
			element.innerHTML = '<span id="eddrr-close-msg">&#8855;</span><?php echo esc_js( $msg ); ?>';

			document.getElementById('eddrr-close-msg').addEventListener('click', function() { 

				// remove the quer arg
				var clean_uri = location.protocol + "//" + location.host + location.pathname;
				window.history.replaceState({}, document.title, clean_uri);
		
				// close the message
				element.style.display = 'none';

			}, false);
		}
		</script>
		<fieldset>
			<?php do_action( 'eddrr_register_form_fields_before' ); ?>
				<p>
					<label for="edd-restrict-registration-name"><?php echo apply_filters( 'eddrr_register_form_name_label', __( 'Your Name', 'edd-restrict-registration' ) ); ?></label>
					<input id="edd-restrict-registration-name" class="edd-input" type="text" name="edd_restrict_registration_name" title="<?php esc_attr_e( 'Name', 'edd-restrict-registration' ); ?>" required />
				</p>

				<p>
					<label for="edd-restrict-registration-email"><?php echo apply_filters( 'eddrr_register_form_email_label', __( 'Your Email <em>(the same one you used on the checkout page)</em>', 'edd-restrict-registration' ) ); ?></label>
					<input id="edd-restrict-registration-email" class="edd-input" type="email" name="edd_restrict_registration_email" title="<?php esc_attr_e( 'Email Address', 'edd-restrict-registration' ); ?>" required />
				</p>

				<p>
					<label for="edd-restrict-registration-username"><?php echo apply_filters( 'eddrr_register_form_username_label', __( 'Desired Username <em>(optional)</em>', 'edd-restrict-registration' ) ); ?></label>
					<input id="edd-restrict-registration-username" class="edd-input" type="text" name="edd_restrict_registration_username" />
				</p>
				<p id="eddrr-hundred-acre-wood">
					<label id="eddrr-hundred-acre-wood-label"><?php _e( 'For EDD Use Only', 'edd-restrict-registration' ); ?></label>
					<input name="eddrr_hundred_acre_wood_field" type="text" id="eddrr-hundred-acre-wood-field" value="" />
				</p>
				<?php do_action( 'eddrr_register_form_before_submit' ); ?>
				<p>
					<input type="hidden" name="eddrr_action" value="user_register" />
					<input class="button" name="eddrr_register_submit" type="submit" value="<?php esc_attr_e( apply_filters( 'eddrr_register_form_submit_value', __( 'Send', 'edd-restrict-registration' ) ) ); ?>" />
				</p>
				<?php do_action( 'eddrr_register_form_after' ); ?>
			</fieldset>
		</form>	
<?php
	endif;
