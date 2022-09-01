<?php // Flipdish - Scripts

// If this file is called directly, abort.
if (!defined('ABSPATH')) die();

/**
 * Font-end Scripts

function fd_public_scripts()
{
        wp_enqueue_script(
            'fd-opening-hours',
            get_stylesheet_directory_uri() . '/public/js/opening-hours.min.js',
            array('jquery')
        );
}
add_action('wp_enqueue_scripts', 'fd_public_scripts');
 */