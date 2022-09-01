<?php // Flipdish - Restaurant List

// If this file is called directly, abort.
if (!defined('ABSPATH')) die();


/*
 * Add columns to restaurants post list
 */
function add_acf_columns($columns)
{
    return array_merge($columns, array(
        'portal_store_id' => __('Portal Store Id'),
    ));
}
add_filter('manage_restaurants_posts_columns', 'add_acf_columns');


/*
  * Add columns to restaurants post list
  */
function restaurants_custom_column($column, $post_id)
{
    switch ($column) {
        case 'portal_store_id':
            echo get_post_meta($post_id, 'portal_store_id', true);
            break;
    }
}
add_action('manage_restaurants_posts_custom_column', 'restaurants_custom_column', 10, 2);
