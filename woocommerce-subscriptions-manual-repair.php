<?php
/**
 * Plugin Name: WooCommerce Subscriptions - Recalculate subscription totals
 * Plugin URI: https://github.com/Prospress/woocommerce-subscriptions-recalculate-totals
 * Description: In some cases, if the tax settings change after some subscripitons have been created, their totals need to be recalculated in order to include the proper taxes. This plugin recalculates all the subscripitons totals.
 * Author: Prospress Inc.
 * Version: 1.0.1
 * Author URI: http://prospress.com
 *
 * GitHub Plugin URI: Prospress/woocommerce-subscriptions-recalculate-totals
 * GitHub Branch: master
 *
 * Copyright 2017 Prospress, Inc.  (email : freedoms@prospress.com)
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @package		WooCommerce Subscriptions
 * @author		Prospress Inc.
 * @since		1.0
 */

function wcs_recalculate_totals() {

	// Use a URL param to act as a flag for when to run the fixes - avoids running fixes in multiple requests at the same time
	if ( ! isset( $_GET['wcs-recalculate-totals'] ) ) {
		return;
	}

	$payment_gateways       = WC()->payment_gateways->payment_gateways();
	$checked_subscriptions  = get_option( 'wcs_subscriptions_with_totals_updated', array() );
	$subscriptions_to_check = get_posts( array(
		'fields'         => 'ids',
		'orderby'        => 'ID',
		'order'          => 'DESC',
		'post_type'      => 'shop_subscription',
		'posts_per_page' => 50,
		'post__not_in'   => $checked_subscriptions,
		'post_status'    => array(
			'wc-active',
			'wc-on-hold',
		),
	) );

	$logger = new WC_Logger();
	$logger->add( 'wcs-recalculate-totals', '----------- Initiating Recalculate subscription totals Fixer -----------' );
	$logger->add( 'wcs-recalculate-totals', 'Checked subscriptions = ' . var_export( $checked_subscriptions, true ) );
	$logger->add( 'wcs-recalculate-totals', 'Subscriptions to check = ' . var_export( $subscriptions_to_check, true ) );

	foreach ( $subscriptions_to_check as $subscription_id ) {

		$logger->add( 'wcs-recalculate-totals', '* Checking subscription with ID = ' . var_export( $subscription_id, true ) );

		$subscription = wcs_get_subscription( $subscription_id );
	
		if ( $subscription ) {
			$order_total = $subscription->get_total();
			$calc_total = $subscription->calculate_totals();
			$logger->add( 'wcs-recalculate-totals', '* * #'. var_export( $subscription_id, true ).' Subscription totals recalculated. Original total = "'.$order_total.'" / New total = "'.$calc_total.'"' );

		}

		

		$checked_subscriptions[] = $subscription_id;

		// Update the record on each iteration in case we can't make it through 50 subscriptions in one request
		update_option( 'wcs_subscriptions_with_totals_updated', $checked_subscriptions, false );
	}
}
add_action( 'admin_footer', 'wcs_recalculate_totals' );
