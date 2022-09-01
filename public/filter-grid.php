<?php // Flipdish - Filter Grid

// If this file is called directly, abort.
if (!defined('ABSPATH')) die();

/**
 * Read more section
 */
function dpdfg_after_read_more()
{
    $portal_store_id = get_post_meta(get_the_ID(), 'portal_store_id', true);
    $pickup_link = get_post_meta(get_the_ID(), 'my_custom_url', true);
	$frontpage_id = get_option( 'page_on_front' );
	$post_id = get_field("ordering_page_post_id", $frontpage_id);
	

		$returnData = '<div id="store_' . $portal_store_id . '">';
		if((has_tag("open")) && (has_tag("open_for_pickup"))) {
			$returnData .= '<a class="et_pb_button dp-dfg-more-button status_button pickup_status" href="' . $pickup_link . '">PICKUP</a>';
		}
		if((has_tag("open")) && (has_tag("open_for_delivery"))) {
			$returnData .= '<a class="et_pb_button dp-dfg-more-button status_button delivery_status" href="' . get_site_url() . '/order-for-pickup/#/delivery">DELIVERY</a>';
		}
		$returnData .= '</div>';

    return $returnData;
}
add_filter('dpdfg_after_read_more', 'dpdfg_after_read_more');
