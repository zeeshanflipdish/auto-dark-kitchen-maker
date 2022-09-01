<?php // Flipdish - Styling

// If this file is called directly, abort.
if (!defined('ABSPATH')) die();

/**
 * Font-end Styling
 */
function fd_public_styling()
{
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
}
add_action('wp_enqueue_scripts', 'fd_public_styling');



/**
 * Handle website theme
 */
function add_CSS_Variables()
{
?>
    <style>
        :root {
            --thirdBackground: <?php the_field('third_colour', 'option'); ?>;
        }
    </style>
    <script>
        //change logo src with jquery
        jQuery(document).ready(function() {
            jQuery("#logo").attr("src", "<?php the_field('logo-image', 'option'); ?>");
        });
    </script>
<?php
};
add_action('wp_head', 'add_CSS_Variables');
