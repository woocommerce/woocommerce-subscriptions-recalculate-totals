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
 * @package     WooCommerce Subscriptions
 * @author      Prospress Inc.
 * @since       1.1
 */

function wcs_recalculate_totals() {

	// If requested, backup and reset the "wcs_subscriptions_with_totals_updated" option.
	if ( isset( $_GET['reset-updated-subs-option'] ) ) {
		$old_option = get_option( 'wcs_subscriptions_with_totals_updated', array() );
		update_option( '_old_wcs_subscriptions_with_totals_updated', $old_option, false );
		update_option( 'wcs_subscriptions_with_totals_updated', array(), false );
		return;
	}

	// Use a URL param to act as a flag for when to run the fixes - avoids running fixes in multiple requests at the same time.
	if ( ! isset( $_GET['wcs-recalculate-totals'] ) ) {
		return;
	}

	$payment_gateways       = WC()->payment_gateways->payment_gateways();
	$checked_subscriptions  = get_option( 'wcs_subscriptions_with_totals_updated', array() );
	$subscriptions_to_check = get_posts(
		array(
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
		)
	);

	$logger = new WC_Logger();
	$logger->add( 'wcs-recalculate-totals', '----------- Initiating Recalculate subscription totals Fixer -----------' );
	$logger->add( 'wcs-recalculate-totals', 'Checked subscriptions = ' . var_export( $checked_subscriptions, true ) );
	$logger->add( 'wcs-recalculate-totals', 'Subscriptions to check = ' . var_export( $subscriptions_to_check, true ) );

	foreach ( $subscriptions_to_check as $subscription_id ) {

		$logger->add( 'wcs-recalculate-totals', '------ Checking subscription with ID = ' . var_export( $subscription_id, true ) );

		$subscription = wcs_get_subscription( $subscription_id );
		$order = wc_get_order( $subscription_id );
		$order_data = $order->get_data();
		$products_data = array();

		if ( $subscription ) {

			$calc_total = $subscription->calculate_totals();

			if ( isset( $_GET['readd'] ) ) {
				// Backup line items (product_id => quantity).
				foreach ( $subscription->get_items() as $item_id => $item ) {
					$qtty = $item['quantity'];
					$product_id = $item['product_id'];
					if ( isset( $item['variation_id'] ) && '' !== $item['variation_id'] ) {
						$product_id = $item['variation_id'];
					}
					$products_data[ $product_id ] = $qtty;
				}
				$logger->add( 'wcs-recalculate-totals', '* Saved line items ' . var_export( $products_data, true ) );

				// Delete all order items.
				$subscription->remove_order_items( 'line_item' );
				$logger->add( 'wcs-recalculate-totals', '* Removed order items' );

				// Add the saved order items.
				foreach ( $products_data as $product_id => $qty ) {
					if ( $qty > 0 ) {
						$product = wc_get_product( $product_id );
						$subscription->add_product( $product, $qty );
						$logger->add( 'wcs-recalculate-totals', "* Added $qty units of product #$product_id" );
					}
				}

				// Update totals and add an order note.
				$subscription->update_taxes();
				$calc_total = $subscription->calculate_totals();
				$subscription->save();
				$order->add_order_note( 'Order totals recalculated automatically (woocommerce-subscriptions-recalculate-totals)' );
				$logger->add( 'wcs-recalculate-totals', '* Recalculated totals' );

			}
		}

		$checked_subscriptions[] = $subscription_id;

		// Update the record on each iteration in case we can't make it through 50 subscriptions in one request.
		update_option( 'wcs_subscriptions_with_totals_updated', $checked_subscriptions, false );
	}
}
add_action( 'init', 'wcs_recalculate_totals' );

function general_admin_notice() {

	if ( ! isset( $_GET['wcs-recalculate-totals'] ) ) {
		return;
	}

	$checked_subscriptions  = get_option( 'wcs_subscriptions_with_totals_updated', array() );
	$subscriptions_to_check = get_posts(
		array(
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
		)
	);

	if ( ! empty( $checked_subscriptions ) && empty( $subscriptions_to_check ) ) {
		echo '<div class="notice notice-success is-dismissible">
             <p><b>All subscriptions have been recalculated! :)</b></p>
             <p>If you want to recalculate all your subscriptions again, you will need to use <mark style="background: #e5e5e5;">"reset-updated-subs-option=true"</mark> parameter</p>
         </div>';
	}

	$logger = new WC_Logger();
	$logger->add( 'wcs-recalculate-totals', '******** All subscriptions have been recalculated! ********' );

}
add_action( 'admin_notices', 'general_admin_notice' );
