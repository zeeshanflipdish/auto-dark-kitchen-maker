<?php

/**

 * @author Flipdish

 * @copyright 2020

 */

if (!defined('ABSPATH')) die();

// ACF options page
if( function_exists('acf_add_options_page') ) {
	
	acf_add_options_page();
	
}


// Grid custom query
function dp_dfg_custom_query_function() {
  return array(
    'post_type' => 'restaurants',
    'post_status' => 'publish',
    'posts_per_page' => '99',
    'tag' => 'open',
    'orderby'    => array( 'meta_value_num' => 'DESC', 'date' => 'DESC' ),
    'meta_query'     => array(
      'relation' => 'OR',
      array(
        'key'       => 'store-order',
        'compare'   => 'NOT EXISTS'
      ),
      array(
        'key'       => 'store-order',
        'compare'   => 'EXISTS'
      )
      ),
    );
}
add_filter('dpdfg_custom_query_args', 'dp_dfg_custom_query_function');

// include dependencies: Public
require_once plugin_dir_path(__FILE__) . 'public/filter-grid.php';
require_once plugin_dir_path(__FILE__) . 'public/restaurant-single.php';
require_once plugin_dir_path(__FILE__) . 'public/restaurant-api-call.php';
require_once plugin_dir_path(__FILE__) . 'public/styling.php';

// if admin area
if (is_admin()) {

    // include dependencies: Admin
    require_once plugin_dir_path(__FILE__) . 'admin/restaurant-list.php';

    require plugin_dir_path(__FILE__) . 'plugin-update-checker/plugin-update-checker.php';
    $myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
        'https://storage.googleapis.com/wordpress_theme/details.json',
        __FILE__, //Full path to the main plugin file or functions.php.
        'kitchen-maker-factory'
    );
}

// Login Editor
include('login-editor.php');

add_action('admin_menu', 'test_button_menu');

function test_button_menu(){
  add_menu_page('Flipdish Portal Sync', 'FD Portal Sync', 'manage_options', 'fd-portal-sync', 'portal_button_admin_page', 'dashicons-update');

}

function portal_button_admin_page() {
 	
  echo '<style> #fd-portal-status{ background: white !important; padding: 0.5em !important; border: 1px solid #C3C4C7 !important; border-left: 4px solid #171460 !important;} #fd-portal-status-title{ visibility: hidden;} </style>';

  if (!current_user_can('manage_options'))  {
    wp_die( __('You do not have sufficient access to this page.')    );
  }

  // Start building the page

	$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
	$flipdishfound = false;
	
	if ( in_array( 'flipdish-ordering-system/flipdish-ordering.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
   			$flipdishfound = true;
	}else{
		wp_die( "<h1>Flipdish Plugin Required</h1>");
		 }
	
	if($flipdishfound == true){
		$options = get_option( 'flipdish_ordering_options', flipdish_ordering_options_default() );
		$whitelabel = $options['fd_portal_id'];

		echo '<div class="wrap">';

			echo '<h2>Flipdish Portal Sync</h2><hr>';
			
			if (!current_user_can('manage_options'))  {
				echo '<h3>Whitelabel Set:  ' . $whitelabel . '</h3>';
			}else{
				echo '<h3>Whitelabel Set: <a href="/wp-admin/admin.php?page=flipdish-ordering">' . $whitelabel . '</a></h3>';
			}

			echo '<form action="options-general.php?page=fd-portal-sync" method="post">';
			echo '<input type="hidden" value="true" name="check_new_portal_updates" />';
				submit_button('Check for New Portal Updates');
			echo '</form>';

			echo '<h3 id="fd-portal-status-title">Status Output:<h3>';

			if (isset($_POST['check_new_portal_updates'])) {
					check_for_new_stores();
					check_for_store_removal();
					check_for_store_updates();
					restaurant_open_check();
			}

		echo '</div>';
	}
}
/*****************************************************/
add_action( 'fd_cron_job_event', 'fd_cron_jobs' );

function fd_cron_jobs(){
	
	
	$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );
	$flipdishfound = false;

	if ( in_array( 'flipdish-ordering-system/flipdish-ordering.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
		$flipdishfound = true;
	}else{
		wp_die( "<h1>Flipdish Plugin Required</h1>");
	}
	
	if($flipdishfound == true){
		
		$options = get_option( 'flipdish_ordering_options', flipdish_ordering_options_default() );
		$whitelabel = $options['fd_portal_id'];

		if(empty($whitelabel)){
			$whitelabel = "fd10975";
		}

		$url = "https://api.flipdish.co/api/v1.0/" . $whitelabel . "/stores";
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

		$headers = array(
			"Accept: application/json",
			"Authorization: Bearer e0af316953766b983171444bb61f3b4b"
		);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		$resp = curl_exec($curl);
		curl_close($curl);
		
		$getStoreInformation = json_decode($resp, true);

		if(isset($getStoreInformation["TotalRecordCount"])){

		  $getTotalNumberofStores = $getStoreInformation["TotalRecordCount"];
		  $getResultsLimit = $getStoreInformation["Limit"];
		  $numPageResults = ceil(($getTotalNumberofStores / $getResultsLimit));

		  for($pageNum = 1; $pageNum <= $numPageResults; $pageNum++){


			$url = "https://api.flipdish.co/api/v1.0/" . $whitelabel . "/stores?page=" . $pageNum;
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_URL, $url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

			$headers = array(
			"Accept: application/json",
			"Authorization: Bearer e0af316953766b983171444bb61f3b4b"
			 );
			 curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

			 $resp = curl_exec($curl);
			 curl_close($curl);

			 $response_returned .= json_encode($resp, true) . '<br><br><hr><hr><br><br>';
		  }
		}
		
		global $fd_email_message_status;
		$fd_email_message_status .= '<h2>Flipdish Portal Sync</h2><hr>';

		global $fd_email_message_status_trigger;
		$fd_email_message_status_trigger = false;

		check_for_new_stores();
		check_for_store_removal();
		check_for_store_updates();

		if($fd_email_message_status_trigger == false){
			$fd_email_message_status .= '<h3>No Store Changes Found</h3>';
		}

		$currenttime = date("h:i:sa");
		$currentdate = date("d-m-Y");

		$to      = 'ryan.mackenzie@flipdish.com, stephen.foy@flipdish.com, zeeshan.khan@flipdish.com';
		$subject = 'WP Cron - ' . $currentdate . ' - ' . $currenttime;
		$body    = $fd_email_message_status . '<hr><h3>JSON Response:</h3><h4><a href="https://jsonformatter.curiousconcept.com/">Convert JSON</h4><br>' . $response_returned;
		$headers = array('Content-Type: text/html; charset=UTF-8');

		$mailresult = wp_mail( $to, $subject, $body, $headers );
	}
}

/******************************************************************************************************************************/

function flipdishadmin_admin_color_scheme() {
	
	
	//Get the theme directory
	$theme_dir = get_stylesheet_directory_uri();

	//Flipdish Admin
	wp_admin_css_color( 'flipdishadmin', __( 'Flipdish Admin' ),
					   $theme_dir . '/flipdishadmin.css',
					   array( '#171460', '#ffffff', '#7470e1' , '#100e44')
					  );
}
add_action('admin_init', 'flipdishadmin_admin_color_scheme');
/******************************************************************************************************************************/

/******************************************************************************************************************************/
function wpb_custom_logo() {
	

echo '
<style type="text/css">
#wpadminbar #wp-admin-bar-wp-logo > .ab-item .ab-icon:before {
background-image: url(' . get_bloginfo('stylesheet_directory') . '/public/images/flipdish-logo-notext1.png) !important;
background-position: 0 0 !important;
color:rgba(0, 0, 0, 0) !important;
}

#wpadminbar #wp-admin-bar-wp-logo.hover > .ab-item .ab-icon {
background-position: 0 0 !important;
}

#wpadminbar .ab-icon, #wpadminbar .ab-item:before, #wpadminbar>#wp-toolbar>#wp-admin-bar-root-default .ab-icon, .wp-admin-bar-arrow{
font: normal 15px dashicons !important; 
}

#adminmenu .wp-submenu a:hover{
    color: #fff !important;
}

#wpadminbar .quicklinks .menupop ul li a:hover{
    color: white !important;
}

#wpadminbar .quicklinks ul li a:hover{
    color: white !important;
}
</style>
';
}
 
//hook into the administrative header output
add_action('wp_before_admin_bar_render', 'wpb_custom_logo');
/******************************************************************************************************************************/

/******************************************************************************************************************************/
function fd_login_area() { ?>
<style type="text/css">
	.login h1 a {
		background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/public/images/flipdish-logo-white-highres.png) !important;
		width:200px !important;
		background-size: 200px !important;
		margin: 0 auto -30px !important;
		}

	.login{
		background: #171460 !important;
		}

	.login a{
		color: white !important;
		font-size: 10pt !important;
		}

	.login a:hover{
		color: white !important;
		font-size: 13pt !important;
		}

	.dashicons{
		color: white !important;
		}

	#loginform{
		border-radius: 10px !important;
		height: 250px !important;
		font-size: 20pt !important;
		/*box-shadow: 0px 7px 29px 0px #fff !important;*/
		}
</style>
<?php }
add_action( 'login_enqueue_scripts', 'fd_login_area' );
/******************************************************************************************************************************/

/******************************************************************************************************************************/
function remove_catagories() {
	if (!current_user_can('manage_options'))  {
    	echo '<style type="text/css">
				#category-adder { display: none !important;}
			</style>';
	}
}add_action('admin_head', 'remove_catagories');

function my_remove_sub_menus() {
	
	if (!current_user_can('manage_options'))  {
		//remove_meta_box('categorydiv', 'restaurants', 'normal');
		remove_submenu_page('edit.php?post_type=restaurants', 'edit-tags.php?taxonomy=category&amp;post_type=restaurants');
	}
	
}add_action('admin_menu', 'my_remove_sub_menus', 999);
/******************************************************************************************************************************/

/******************************************************************************************************************************/
function hide_siteadmin() {
  
if (current_user_can('editor')) {
	
	remove_menu_page('edit-comments.php');
	remove_menu_page('edit.php');
	remove_menu_page('edit.php?post_type=project');
	remove_menu_page('tools.php');
	remove_menu_page('wpseo_dashboard');
    }
}
add_action('admin_head', 'hide_siteadmin');
/******************************************************************************************************************************/

/******************************************************************************************************************************/
function remove_admin_bar_items($wp_admin_bar) {
    if (current_user_can('editor')) {
        // Plugin related admin-bar items
        $wp_admin_bar->remove_node('comments');
        $wp_admin_bar->remove_node('wpseo_dashboard');
		$wp_admin_bar->remove_node('new-content');
    }
}
add_action('admin_bar_menu', 'remove_admin_bar_items', 999);
/******************************************************************************************************************************/

/******************************************************************************************************************************/
function check_for_new_stores(){
	
	$options = get_option( 'flipdish_ordering_options', flipdish_ordering_options_default() );
	$whitelabel = $options['fd_portal_id'];

	$url = "https://api.flipdish.co/api/v1.0/" . $whitelabel . "/stores";
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	$headers = array(
		"Accept: application/json",
		"Authorization: Bearer e0af316953766b983171444bb61f3b4b"
	);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$resp = curl_exec($curl);
	curl_close($curl);

	$getStoreInformation = json_decode($resp, true);
	// Fix added to prevent illegal string offset
	if(isset($getStoreInformation["TotalRecordCount"])){
		
	$getTotalNumberofStores = $getStoreInformation["TotalRecordCount"];
	$getResultsLimit = $getStoreInformation["Limit"];
	$numPageResults = ceil(($getTotalNumberofStores / $getResultsLimit));
	$getStoreData = $getStoreInformation["Data"];

	$counter = 0;

	$args = array(
		'post_type' => 'restaurants',
		'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private'),
		'posts_per_page'   => -1 
	);
	$posts = get_posts($args);

	$prid_array = array();
	foreach ( $posts as $post ){
		$prid_array[] = get_post_meta($post->ID, 'portal_store_id', true);
	}

	for($pageNum = 1; $pageNum <= $numPageResults; $pageNum++){
		
		$url = "https://api.flipdish.co/api/v1.0/" . $whitelabel . "/stores?page=" . $pageNum;
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	
		$headers = array(
		"Accept: application/json",
		"Authorization: Bearer e0af316953766b983171444bb61f3b4b"
		 );
		 curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	 
		 $resp = curl_exec($curl);
		 curl_close($curl);
   
		 $getStoreInformation = json_decode($resp, true);
		 $getStoreData = $getStoreInformation["Data"];
		
		foreach($getStoreData as $storeData){

			$storeName = $storeData["Name"];
			$prid = $storeData["StoreId"];
			if($storeData["IsPublished"] == true){
				$publishedStatus = "publish";
			}else{
				$publishedStatus = "draft";
			}

			$unwanted_array = array(    "'" => '', ' '=>'', '-'=>'', '&' => 'and', 'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
									'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
									'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
									'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
									'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
			$slugName = strtr( $storeName, $unwanted_array );	

			$getAddStoreInfo = file_get_contents("https://api.flipdish.co/Restaurant/PickupRestaurantDetails/" . $prid);
			$json_storeinfo = json_decode($getAddStoreInfo, true);

			$isOpen =  $json_storeinfo['Data']["IsOpen"];

			if(!in_array($prid, $prid_array)){
				$new_page = array(
					'post_type'     => 'restaurants',        // Post Type Slug eg: 'page', 'post'
					'post_title'    => $storeName,    		 // Title of the Content
					'post_content'  => '',  // Content
					'post_status'   => $publishedStatus,     // Post Status
					'post_author'   => 1,                    // Post Author ID
					'post_name'     => $slugName,            // Slug of the Post
					'meta_input' => array(
						'portal_store_id' => $prid
					)
				);
				if (!get_page_by_path( $slugName, OBJECT, 'restaurants')) { // Check If Page Not Exits
					$new_page_id = wp_insert_post($new_page, true);
					wp_set_post_categories( $new_page_id , array(14) );
					echo '<style>#fd-portal-status-title{ visibility: visible;</style>';
					echo '<p id="fd-portal-status"><strong>New Store Added:</strong> ' . $storeName . ' - PRID: ' . $prid . ' - Post ID: ' . $new_page_id . '</p>';

					global $fd_email_message_status;
					$fd_email_message_status .= '<p><strong>New Store Added:</strong> ' . $storeName . ' - PRID: ' . $prid . ' - Post ID: ' . $new_page_id ;
					global $fd_email_message_status_trigger;
					$fd_email_message_status_trigger = true;

					if($isOpen == true){
						$tags = wp_get_post_tags( $post->ID );
						$tags_to_delete = array( 'closed', 'opening-soon' );
						$tags_to_keep = array();
						foreach ( $tags as $t ) {
							if ( !in_array( $t->name, $tags_to_delete ) ) {
								$tags_to_keep[] = $t->name;
							}
						}
						wp_set_post_tags( $post->ID, $tags_to_keep, false );
						wp_set_post_tags( $post->ID, array( 'open' ), true );
						global $fd_email_message_status;
						$fd_email_message_status .= ' - Store Status: Set to Open</p>';
					}else{
						if(($json_storeinfo['Data']["SecondsUntilRestaurantOpens"] < 1800) && ($json_storeinfo['Data']["SecondsUntilRestaurantOpens"] != null)){
							$tags = wp_get_post_tags( $post->ID );
							$tags_to_delete = array( 'closed', 'open' );
							$tags_to_keep = array();
							foreach ( $tags as $t ) {
								if ( !in_array( $t->name, $tags_to_delete ) ) {
									$tags_to_keep[] = $t->name;
								}
							}
							wp_set_post_tags( $post->ID, $tags_to_keep, false );
							wp_set_post_tags( $post->ID, array( 'opening-soon' ), true );
							global $fd_email_message_status;
							$fd_email_message_status .= ' - Store Status: Set to Opening Soon</p>';
						}
						else{
							$tags = wp_get_post_tags( $post->ID );
							$tags_to_delete = array( 'open', 'opening-soon' );
							$tags_to_keep = array();
							foreach ( $tags as $t ) {
								if ( !in_array( $t->name, $tags_to_delete ) ) {
									$tags_to_keep[] = $t->name;
								}
							}
							wp_set_post_tags( $post->ID, $tags_to_keep, false );
							wp_set_post_tags( $post->ID, array( 'closed' ), true );
							global $fd_email_message_status;
							$fd_email_message_status .= ' - Store Status: Set to Closed</p>';
						}
					}
					$counter++;
				}
			}
		}// End of getStoreData ForLoop
	}
	echo '<div id="message" class="updated fade"><p>'. $counter .' - New Stores Added</p></div>';
	}
}// End of check_for_new_stores();
/******************************************************************************************************************************/

/******************************************************************************************************************************/
function check_for_store_updates(){
	$options = get_option( 'flipdish_ordering_options', flipdish_ordering_options_default() );
	$whitelabel = $options['fd_portal_id'];

	$url = "https://api.flipdish.co/api/v1.0/" . $whitelabel . "/stores";
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	$headers = array(
		"Accept: application/json",
		"Authorization: Bearer e0af316953766b983171444bb61f3b4b"
	);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$resp = curl_exec($curl);
	curl_close($curl);

	$getStoreInformation = json_decode($resp, true);
	
	// Fix added to prevent illegal string offset
	if(isset($getStoreInformation["TotalRecordCount"])){
		
	$getTotalNumberofStores = $getStoreInformation["TotalRecordCount"];
	$getResultsLimit = $getStoreInformation["Limit"];
	$numPageResults = ceil(($getTotalNumberofStores / $getResultsLimit));
	$getStoreData = $getStoreInformation["Data"];

	$counter = 0;
	
	if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')   
         $custom_url = "https://";   
    else  
         $custom_url = "http://";    
    $custom_url.= $_SERVER['HTTP_HOST'];   

	$custom_url.= "/order/";

	$counter = 0;

	$args = array(
		'post_type' => 'restaurants',
		'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private'),
		'posts_per_page'   => -1 
	);
	$posts = get_posts($args);

	for($pageNum = 1; $pageNum <= $numPageResults; $pageNum++){
		
		$url = "https://api.flipdish.co/api/v1.0/" . $whitelabel . "/stores?page=" . $pageNum;
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	
		$headers = array(
		"Accept: application/json",
		"Authorization: Bearer e0af316953766b983171444bb61f3b4b"
		 );
		 curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	 
		 $resp = curl_exec($curl);
		 curl_close($curl);
   
		 $getStoreInformation = json_decode($resp, true);
		 $getStoreData = $getStoreInformation["Data"];

		foreach ( $posts as $post ){
			$portal_store_id = get_post_meta($post->ID, 'portal_store_id', true);
			$post_store_name = get_the_title($post->ID);
			$post_slug_name = $post->post_name;
			$post_status = $post->post_status;
			$custom_link_url = get_post_meta($post->ID, 'my_custom_url', true);
			$use_custom_link = get_post_meta($post->ID, 'use_custom_url', true);
		
			foreach($getStoreData as $storeData){
				$storeName = $storeData["Name"];
				$prid = $storeData["StoreId"];
				if($storeData["IsPublished"] == true){
					$publishedStatus = "publish";
				}else{
					$publishedStatus = "draft";
				}

				if($portal_store_id == $prid){
					
					if ($use_custom_link == false || !($custom_link_url)){
						$getVRID = $storeData["StoreGroupId"];
						$newurl = $custom_url . "#/restaurant/" . $getVRID . "/collection/" . $portal_store_id;
						update_field('my_custom_url', $newurl, $post->ID);
					}

					//Name Check
					if($post_store_name != $storeName){
						$post_update = array(
							'ID'         => $post->ID,
							'post_title' => $storeName
						);
						wp_update_post( $post_update );
					}
					if($post_status != $publishedStatus){
						$post_update = array(
							'ID'         => $post->ID,
							'post_status' => $publishedStatus
						);
						echo '<style>#fd-portal-status-title{ visibility: visible;</style>';
						echo '<p id="fd-portal-status"><strong>Post Status Updated:</strong> ' . $post_store_name . ': ' . $post_status . ' --> ' . $publishedStatus . '</p>';
						
						global $fd_email_message_status;
						$fd_email_message_status .= '<p><strong>Post Status Updated:</strong> ' . $post_store_name . ': ' . $post_status . ' --> ' . $publishedStatus . '</p>';
						global $fd_email_message_status_trigger;
						$fd_email_message_status_trigger = true;
						
						wp_update_post( $post_update );
						$counter++;
					}

					$unwanted_array = array(    "." => '-',"'" => '', ' '=>'', '-'=>'', '&' => 'and', 'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
											'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
											'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
											'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
											'ö'=>'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' );
					$slugName = strtr( $storeName, $unwanted_array );		
					$newslugname = strtolower($slugName);
					if($post_slug_name != $newslugname){
						$post_update = array(
							'ID'         => $post->ID,
							'post_name'  => $newslugname 
						);
						echo '<style>#fd-portal-status-title{ visibility: visible;</style>';
						echo '<p id="fd-portal-status"><strong>Store Name Updated:</strong> ' . $post_slug_name . ' --> ' . $newslugname . '</p>';
						
						global $fd_email_message_status;
						$fd_email_message_status .= '<p><strong>Store Name Updated:</strong> ' . $post_slug_name . ' --> ' . $newslugname . '</p>';
						
						global $fd_email_message_status_trigger;
						$fd_email_message_status_trigger = true;
						
						wp_update_post( $post_update );
						$counter++;
					}
				}	
			}// End of getStoreData ForLoop
		}//End of foreach posts loop
	}// End of For loop for API return result limit
	echo '<div id="message" class="updated fade"><p>'. $counter .' - Stores have been updated!</p></div>';
	}
}// End of check_for_store_updates();
/******************************************************************************************************************************/

/******************************************************************************************************************************/
function check_for_store_removal(){
	
	$options = get_option( 'flipdish_ordering_options', flipdish_ordering_options_default() );
	$whitelabel = $options['fd_portal_id'];

	$url = "https://api.flipdish.co/api/v1.0/" . $whitelabel . "/stores";
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	$headers = array(
		"Accept: application/json",
		"Authorization: Bearer e0af316953766b983171444bb61f3b4b"
	);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$resp = curl_exec($curl);
	curl_close($curl);

	$getStoreInformation = json_decode($resp, true);
	
	// Fix added to prevent illegal string offset
	if(isset($getStoreInformation["TotalRecordCount"])){
		
	$getTotalNumberofStores = $getStoreInformation["TotalRecordCount"];
	$getResultsLimit = $getStoreInformation["Limit"];
	$numPageResults = ceil(($getTotalNumberofStores / $getResultsLimit));

	$counter = 0;

	$args = array(
		'post_type' => 'restaurants',
		'post_status' => array('publish', 'pending', 'draft', 'auto-draft', 'future', 'private'),
		'posts_per_page'   => -1 
	);
	$posts = get_posts($args);

		for($pageNum = 1; $pageNum <= $numPageResults; $pageNum++){
		
		$url = "https://api.flipdish.co/api/v1.0/" . $whitelabel . "/stores?page=" . $pageNum;
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	
		$headers = array(
		"Accept: application/json",
		"Authorization: Bearer e0af316953766b983171444bb61f3b4b"
		 );
		 curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	 
		 $resp = curl_exec($curl);
		 curl_close($curl);
   
		 $getStoreInformation = json_decode($resp, true);
		 $getStoreData = $getStoreInformation["Data"];

			 foreach($getStoreData as $storeData){
				$prid_array[] = $storeData["StoreId"];
			}// End of getStoreData ForLoop
		}//End of For loop for API search results limitation.
		
		// Fix added to check if PRIDs array if not empty only then proceed with moving stores to trash
		if(!empty($prid_array) || $prid_array != null){

			foreach ( $posts as $post ){
				$portal_store_id = get_post_meta($post->ID, 'portal_store_id', true);
				$portal_store_name = get_the_title($post->ID);

				if(!in_array($portal_store_id, $prid_array)){
					echo '<style>#fd-portal-status-title{ visibility: visible;</style>';
					echo '<p id="fd-portal-status"><strong>Store Moved to Trash:<strong> ' . $portal_store_name . ' - ' . $portal_store_id . '</p>';

					global $fd_email_message_status;
					$fd_email_message_status .= '<p><strong>Store Moved to Trash:<strong> ' . $portal_store_name . ' - ' . $portal_store_id . '</p>';
					global $fd_email_message_status_trigger;
					$fd_email_message_status_trigger = true;

					wp_trash_post($post->ID);
					$counter++;
				}

			}//End of foreach posts loop
		echo '<div id="message" class="updated fade"><p>'. $counter .' - Stores have been removed!</p></div>';
		}
	}
}// End of check_for_store_removal();
/******************************************************************************************************************************/

/******************************************************************************************************************************/
function restaurant_open_check(){
	
	$options = get_option( 'flipdish_ordering_options', flipdish_ordering_options_default() );
	$whitelabel = $options['fd_portal_id'];

	$url = "https://api.flipdish.co/api/v1.0/" . $whitelabel . "/stores";
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_URL, $url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	$headers = array(
		"Accept: application/json",
		"Authorization: Bearer e0af316953766b983171444bb61f3b4b"
	);
	curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	$resp = curl_exec($curl);
	curl_close($curl);

	$getStoreInformation = json_decode($resp, true);
	
	// Fix added to prevent illegal string offset
	if(isset($getStoreInformation["TotalRecordCount"])){
	
	$getTotalNumberofStores = $getStoreInformation["TotalRecordCount"];
	$getResultsLimit = $getStoreInformation["Limit"];
	$numPageResults = ceil(($getTotalNumberofStores / $getResultsLimit));

	$counter = 0;

	$args = array(
		'post_type' => 'restaurants',
		'post_status' => 'publish',
		'posts_per_page'   => -1 
	);
	$posts = get_posts($args);

	for($pageNum = 1; $pageNum <= $numPageResults; $pageNum++){
		
		$url = "https://api.flipdish.co/api/v1.0/" . $whitelabel . "/stores?page=" . $pageNum;
		$curl = curl_init($url);
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
	
		$headers = array(
		"Accept: application/json",
		"Authorization: Bearer e0af316953766b983171444bb61f3b4b"
		 );
		 curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
	 
		 $resp = curl_exec($curl);
		 curl_close($curl);
   
		 $getStoreInformation = json_decode($resp, true);
		
		// Fix added to avoid illegal offset warning - 24-05-2022
		if(isset($getStoreInformation["Data"])) {
			
		 $getStoreData = $getStoreInformation["Data"];

		foreach ( $posts as $post ){
			$portal_store_id = get_post_meta($post->ID, 'portal_store_id', true);
			$post_store_name = get_the_title($post->ID);

			foreach($getStoreData as $storeData){
				$prid = $storeData["StoreId"];
				$OpenForPickup = $storeData['OpenForPickup'];
				$OpenForDelivery = $storeData['OpenForDelivery'];
				if($OpenForPickup == true || $OpenForDelivery == true){
					$isOpen = true;
				}else{
					$isOpen = false;
				}
				
				if($portal_store_id == $prid){
					if($isOpen == true){
						$tags = wp_get_post_tags( $post->ID );
						$tags_to_delete = array('closed');
						$tags_to_keep = array();
						foreach ( $tags as $t ) {
							if ( !in_array( $t->name, $tags_to_delete ) ) {
								$tags_to_keep[] = $t->name;
							}
						}
						wp_set_post_tags( $post->ID, $tags_to_keep, false );
						wp_set_post_tags( $post->ID, array('open'), true );	

					}else{
							$tags = wp_get_post_tags( $post->ID );
							$tags_to_delete = array('open');
							$tags_to_keep = array();
							foreach ( $tags as $t ) {
								if ( !in_array( $t->name, $tags_to_delete ) ) {
									$tags_to_keep[] = $t->name;
								}
							}
							wp_set_post_tags( $post->ID, $tags_to_keep, false );
							wp_set_post_tags( $post->ID, array( 'closed'), true );
						}
					if($OpenForPickup == true){
						$tags = wp_get_post_tags( $post->ID );
						$tags_to_delete = array('closed_for_pickup');
						$tags_to_keep = array();
						foreach ( $tags as $t ) {
							if ( !in_array( $t->name, $tags_to_delete ) ) {
								$tags_to_keep[] = $t->name;
							}
						}
						wp_set_post_tags( $post->ID, $tags_to_keep, false );
						wp_set_post_tags( $post->ID, array('open_for_pickup'), true );	

					}else{
						$tags = wp_get_post_tags( $post->ID );
						$tags_to_delete = array('open_for_pickup');
						$tags_to_keep = array();
						foreach ( $tags as $t ) {
							if ( !in_array( $t->name, $tags_to_delete ) ) {
								$tags_to_keep[] = $t->name;
							}
						}
						wp_set_post_tags( $post->ID, $tags_to_keep, false );
						wp_set_post_tags( $post->ID, array( 'closed_for_pickup'), true );
					}	
					if($OpenForDelivery == true){
						$tags = wp_get_post_tags( $post->ID );
						$tags_to_delete = array('closed_for_delivery');
						$tags_to_keep = array();
						foreach ( $tags as $t ) {
							if ( !in_array( $t->name, $tags_to_delete ) ) {
								$tags_to_keep[] = $t->name;
							}
						}
						wp_set_post_tags( $post->ID, $tags_to_keep, false );
						wp_set_post_tags( $post->ID, array('open_for_delivery'), true );	

					}else{
						$tags = wp_get_post_tags( $post->ID );
						$tags_to_delete = array('open_for_delivery');
						$tags_to_keep = array();
						foreach ( $tags as $t ) {
							if ( !in_array( $t->name, $tags_to_delete ) ) {
								$tags_to_keep[] = $t->name;
							}
						}
						wp_set_post_tags( $post->ID, $tags_to_keep, false );
						wp_set_post_tags( $post->ID, array( 'closed_for_delivery'), true );
					}	
				}//Portal Store Check
			}// End of getStoreData ForLoop
		}//End of foreach posts loop
		}
	}//End of results store check API call.
}
}
restaurant_open_check();

/* Custom og tags */

function change_title($title){
	$title = get_field('share-title','options');
	if($title):
		return $title;
	endif;
}
add_filter( 'wpseo_opengraph_title', 'change_title' );

/*function change_desc($desc){
	$desc = get_field('share-description','options');
	if($desc):
		return $desc;
	endif;
}
add_filter( 'wpseo_opengraph_desc', 'change_desc' ); */


add_action( 'wpseo_add_opengraph_images', 'add_images' );
function add_images( $object ) {
	
  $image = get_field('logo-image','options');
  if($image):
  	$object->add_image( $image );
  endif;
	
}

/* Change restaurants featured image to ACF upload_image */

add_action('acf/save_post', function ($post_id) {
    $value = get_field('upload_image', $post_id);
    if ($value && get_post_type() == 'restaurants') {
        if (!is_numeric($value)) {
            $value = $value['ID'];
        }
        update_post_meta($post_id, '_thumbnail_id', $value);
    }
}, 11);

/* Hide featured image meta box from side for all user roles except Admin */
function my_remove_meta_boxes() {
    if ( ! current_user_can( 'administrator' ) ) {
        remove_meta_box( 'postimagediv', 'restaurants', 'side' );
    }
}
add_action( 'add_meta_boxes', 'my_remove_meta_boxes' );

/* Add a shortcode to pull featured image for restaurants. Divi's image module not working to set a fallback image so this used as an alternative */
function featured_image_r() 
{
	$image = get_the_post_thumbnail();
	return $image;
}

add_shortcode( 'featured_image', 'featured_image_r');

/* Fallback image */
function fallback_image_restaurant( $html ) {
    // If there is no post thumbnail,
    // Return a default image
    if ( '' == $html && get_post_type() == 'restaurants' ) {
        return '<img src="' . get_stylesheet_directory_uri() . '/fallback_img.jpg" class="" />';
    }
    // Else, return the post thumbnail
    return $html;
}
add_filter( 'post_thumbnail_html', 'fallback_image_restaurant' );

function dpdfg_custom_thumbnail($thumbnail_output, $props, $post_id) {
	$img = '<img class="dp-dfg-featured-image" src="'.get_stylesheet_directory_uri().'/fallback_img.jpg" alt="" data-ratio="0.71">';
	if(empty($thumbnail_output)):
		return $img;
  	else:
		return $thumbnail_output;
	endif;
}
add_filter('dpdfg_custom_thumbnail', 'dpdfg_custom_thumbnail', 10, 3);

/**
 *To convert shortcode into menu link
 */
function my_dynamic_menu_items( $menu_items ) {

    // A list of placeholders to replace.
    // You can add more placeholders to the list as needed.
    $placeholders = array(
        '#partner_with_us#' => array(
            'shortcode' => 'acf',
            'atts' => array("field" => "partner_with_us", "post_id" => "options"), // Shortcode attributes.
            'content' => '', // Content for the shortcode.
        ),
    );

    foreach ( $menu_items as $menu_item ) {
        if ( isset( $placeholders[ $menu_item->url ] ) ) {
            global $shortcode_tags;
            $placeholder = $placeholders[ $menu_item->url ];
            if ( isset( $shortcode_tags[ $placeholder['shortcode'] ] ) ) {
                $menu_item->url = call_user_func( 
                    $shortcode_tags[ $placeholder['shortcode'] ]
                    , $placeholder['atts']
                    , $placeholder['content']
                    , $placeholder['shortcode']
                );
            }
        }
    }
    return $menu_items;
}
add_filter( 'wp_nav_menu_objects', 'my_dynamic_menu_items' );

/**
 * Add 2nd color to buttons from ACF
 * Disables sticky header on /order/ if checked from options page
 * If no promo makes the top bar full
 */
add_action('wp_head', 'custom_acf_options_css', 100);

function custom_acf_options_css()
{
	
	$color = get_field('text_color','options');
	
	// Sticky header functionality
	$sticky = get_field('disable_sticky_header','options');
	$sticky_css = '';
	if(isset($sticky[0]) && $sticky[0] == 'yes'):
	$page_url_id = get_page_by_path( 'order' );
	$sticky_css = ".page-id-".$page_url_id->ID." .et-l--header.sticky{position: static !important;}";
	endif;

	// For promo
	$promo_text = get_field('promo_text','options');
	$promo_css = '';
	if(empty($promo_text)):
	$promo_css = ".top_bar .et_pb_column_1_tb_header.et-last-child{display:none !important;} .top_bar .et_pb_row_0_tb_header .et_pb_column_0_tb_header{width: 100% !important;}";
	endif;

	// For buttons
	echo "<style>body #page-container #et-main-area #main-content .et_pb_section .et_pb_button{color:".$color." !important;} .top_bar .et_pb_code_inner, .top_bar .promo_text{color:".$color." !important;} ".$sticky_css." ".$promo_css."</style>";
	
}

/**
 * Enqueue custom scripts
 */
function flipdish_scripts() {

	wp_enqueue_script( 'flipdish-js', get_stylesheet_directory_uri() . '/custom/js/custom.js', array( 'jquery' ), '', true );

}
add_action( 'wp_enqueue_scripts', 'flipdish_scripts' );

/**
 * Function created below to convert ACF fields into %%variablename%% for yoast
 */

if( function_exists('acf_add_options_page') ) {
	
	acf_add_options_page();
	
}

$seo_variables = get_fields('options'); 
if ( function_exists('get_fields') ){		
	if( $seo_variables ) {
		$seo_variables_array = array();
		foreach( $seo_variables as $name => $value ):
			$seo_variables_array[$name] = $value;		   
        endforeach;
	}
}

function ss_get_all_values($var1){
	global $seo_variables_array;
//	echo($seo_variables_array[$var1]);
	return $seo_variables_array[$var1];
}

function ss_register_custom_yoast_variables() {
$seo_variables = get_fields('options');
if ( function_exists('get_fields') ) {
	if( $seo_variables ) {
		$seo_variables_array = array();
		foreach( $seo_variables as $name => $value ):		
		    wpseo_register_var_replacement( $name, 'ss_get_all_values', 'advanced');
        endforeach;
		}
	}
}

// Add action
add_action('wpseo_register_extra_replacements', 'ss_register_custom_yoast_variables');

/**
 * Adds logo through user's profile picture.
 */
add_filter( 'get_avatar_url','custom_fd_logo' , 10, 5 );
function custom_fd_logo($avatar, $size, $alt) {
 
      $avatar = get_field('logo-image', 'options');
      return $avatar;

}