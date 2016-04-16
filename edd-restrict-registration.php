<?php
/*
Plugin Name: EDD Restrict Registration
Plugin URI: http://isabelcastillo.com/docs/category/edd-restrict-registration
Description: Allow only EDD customers to register on your site.
Version: 1.0
Author: Isabel Castillo
Author URI: http://isabelcastillo.com
License: GPL2
Text Domain: edd-restrict-registration

Copyright 2015-2016 Isabel Castillo

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

add_action( 'edd_process_register_form', 'eddrr_restrict_registration' );
function eddrr_restrict_registration() {

	if ( empty( $_POST['edd_user_email'] ) ) {
		return;
	}

	// Check if email belongs to an existing guest customer. 

	$customer = EDD()->customers->get_customer_by( 'email', $_POST['edd_user_email'] );

	if ( $customer ) {

		// Did they actually make a purchase?

		if ( empty( $customer->purchase_count ) ) {

			/**
			 * This "customer" has no purchases, so not a true customer.
			 * 
			 * They may have a payment that is failed, abandoned, or pending.
			 * 
			 * We treat these as non-customers and do not allow them to register.
			 * You can override this behavior and allow them to register by setting 
			 * the "eddrr_register_customers_with_no_purchase" filter to TRUE.
			 * 
			 */
			if ( ! apply_filters( 'eddrr_register_customers_with_no_purchase', FALSE ) ) {

				if( ! email_exists( $_POST['edd_user_email'] ) ) {

					edd_set_error( 'no_purchases', apply_filters( 'eddrr_no_purchases_notice', __( 'Only customers with a purchase can register', 'edd-restrict-registration' ) ) );					
				}

			}
		}
	} else {

		// Not a customer
		edd_set_error( 'not_customer', apply_filters( 'eddrr_not_customer_notice', __( 'Only customers can register', 'edd-restrict-registration' ) ) );		

	}
}
