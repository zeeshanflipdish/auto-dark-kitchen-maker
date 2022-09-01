<?php

// If this file is called directly, abort.
if (!defined('ABSPATH')) die();

/***********************************************************************************************************/


//Adding Bootstrap to head html
function fd_add_tohead(){
	?>
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-1BmE4kWBq78iYhFldvKuhfTAU6auU8tT94WrHftjDbrCEXSU1oBoqyl2QvZ6jIW3" crossorigin="anonymous" />
	<?php
}
add_action('wp_head', 'fd_add_tohead');

//Flipdish Menu 
function fd_restaurant_menu(){
	
$prid = get_post_meta(get_the_ID(), 'portal_store_id', true);
	
if(!empty($prid)){
	//Get the Store Information
	$getStoreInformation = file_get_contents("https://api.flipdish.co/Restaurant/PickupRestaurantDetails/" . $prid);
	$json_storeinfo = json_decode($getStoreInformation, true);

	//Get the Menu Information
	$getMenuURL = $json_storeinfo['Data']["MenuUrl"];
	$getMenuInformation = file_get_contents($getMenuURL);
	$json_menu = json_decode($getMenuInformation, true);
	$json_menu_sections = $json_menu['MenuSections'];
	
	//Getting the Currency
	$url = "https://api.flipdish.co/api/v1.0/stores/" . $prid;
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
	$getCurrencyJson = json_decode($resp, true);
	$currencyCode = $getCurrencyJson["Data"]['Currency'];
	$currencySymbol = "";
	if($currencyCode === "USD"){
			$currencySymbol = "$";
	}elseif($currencyCode === "EUR"){
			$currencySymbol = "€";
	}elseif($currencyCode === "GBP"){
			$currencySymbol = "£";
	}elseif($currencyCode === "CAD"){
			$currencySymbol = "$";
	}elseif($currencyCode === "MX"){
			$currencySymbol = "$";
	}elseif($currencyCode === "AED"){
			$currencySymbol = "&#x62f;&#x2e;&#x625;";
	}elseif($currencyCode === "AUD"){
			$currencySymbol = "$";
	}elseif($currencyCode === "CHF"){
			$currencySymbol = "CHF";
	}
	
	//Parsing the Data
	$returnData = '<div class="container"><div class="fd-menu"><div itemscope itemtype="http://schema.org/MenuSection">';
	
	foreach($json_menu_sections as $menu){
		$returnData .= '<div class="fd-menu-sectionname"><h4 itemprop="name">'. $menu['Name'].'</h4></div>';
		$returnData .= '<div class="fd-menu-section row menu-row">';	
			foreach($menu['MenuItems'] as $menuitems){
				$menuImage = $menuitems['ImageUrl'];
				$includeFrom = false;
				$returnData .='<div class="fd-menu-item col-12 col-sm-12 col-lg-6 ';

				if(empty($menuImage)){
					$returnData .= 'fd-no-image';
				}	
				$returnData .='"><div class="fd-card popup"><div class="fd-menu-item-name" itemprop="name"><p>'. $menuitems['Name'] . '</p></div>'; 
				$menuPrice = $menuitems['Price'];
				
				 if($menuPrice == 0){
					 foreach($menuitems['MenuItemOptionSets'] as $MenuItemOptionSets){
						 $menuPrice = $MenuItemOptionSets['MinPrice'];
						 $includeFrom = true;
					 }
				 }
         if(!empty($menuImage)){
          $returnData .= '<div class="fd-menu-item-image"><img itemprop="image" src="'. $menuImage .'?auto=format&w=120&h=120" alt="'. $menuitems['Name'].'"></div>';
        }
				$returnData .= '<div class="fd-menu-item-desc"><p itemprop="name">'. $menuitems['Description'] . '</p></div>';
				
          $newMenuPrice = number_format((float)$menuPrice, 2, '.', '');
					if($currencyCode === "AED" || $currencyCode === "CHF"){
						$returnData .= '<div class="fd-menu-item-price"><span itemscope itemtype="http://schema.org/offers"><span itemprop="price"><p>';
							if($includeFrom == true){ 
								$returnData .= '+' . $newMenuPrice . $currencySymbol . '</p></span></div>';
							}else{
								$returnData .= $newMenuPrice . $currencySymbol . '</p></span></div>';
							}
					}else{
						$returnData .= '<div class="fd-menu-item-price"><span itemscope itemtype="http://schema.org/offers"><span itemprop="price"><p>';
							if($includeFrom == true){
								$returnData .= $currencySymbol . $newMenuPrice .'+</p></span></div>';
							}else{
						        $returnData .= $currencySymbol . $newMenuPrice .'</p></span></div>';
                            }
					}
				$returnData .= '</div></div>'; // fd-menu-item closing div
			} 
		$returnData .= '</div>'; // fd-menu-section closing div
	} 
	$returnData .= '</div></div></div>'; // fd-menu closing div
		
	//Outputting the Data
	return $returnData;
	}
}//End of Function
add_shortcode('fd_restaurant_menu', 'fd_restaurant_menu');

/***********************************************************************************************************/

/***********************************************************************************************************/ 
function fd_restaurant_openinghours_pickup(){
	
$prid = get_post_meta(get_the_ID(), 'portal_store_id', true);
	
	if(!empty($prid)){
	
	$url = "https://api.flipdish.co/api/v1.0/stores/" . $prid;
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
 
	$getOpeningHours = json_decode($resp, true);

	$json_OpeningHours = $getOpeningHours["Data"]['PickupHours'];
	$json_pickupEnabled = $getOpeningHours["Data"]['PickupEnabled'];
	
	if($json_pickupEnabled == true){

		$returnData = '	<div class="OpeningHours-Pickup container">
						<div class="pickup-delivery-card row">
							<h4>Pickup Times</h4>';
		
						
		foreach($json_OpeningHours as $openingTimes){
				//Opening Hours Early Calculation
				$earlystartTime = $openingTimes["Early"]["StartTime"];
				$earlyperiod = $openingTimes["Early"]["Period"];
				$disEarlyTimetable = "";

				if($earlyperiod != "00:00:00"){
					$secs = strtotime($earlyperiod)-strtotime("00:00:00");
					$closingTime = date("H:i:s",strtotime($earlystartTime)+$secs);
					$disEarlyStartTime=substr($earlystartTime,0,-3);
					$disEarlyClosingTime=substr($closingTime,0,-3);
					$disEarlyTimetable = $disEarlyStartTime . " - " . $disEarlyClosingTime . ", ";
				}

				//Opening Hours Late Calculation
				$latestartTime = $openingTimes["Late"]["StartTime"];
				$lateperiod = $openingTimes["Late"]["Period"];	
				$disLateTimetable = "";

				if($lateperiod != "00:00:00"){
					$secs = strtotime($lateperiod)-strtotime("00:00:00");
					$closingTime = date("H:i:s",strtotime($latestartTime)+$secs);
					$disLateStartTime=substr($latestartTime,0,-3);
					$disLateClosingTime=substr($closingTime,0,-3);
					$disLateTimetable = $disLateStartTime . " - " . $disLateClosingTime;
				}
				
				//Adding in the BootStrap breaks
				if($openingTimes["Late"]["DayOfWeek"] == "Sunday"){
					$returnData .= '<div class="col-12 col-sm-6 col-lg-6">';
				}elseif($openingTimes["Late"]["DayOfWeek"] == "Thursday"){
					$returnData .= '</div>';
					$returnData .= '<div class="col-12 col-sm-6 col-lg-6">';
				}
			
				$returnData .= '<div class="row"><div class="DayOfWeek col-6 col-sm-6 col-lg-6"><p>' . $openingTimes["Late"]["DayOfWeek"] . '</p></div><div class="OpeningTimes col-6 col-sm-6 col-lg-6"><p>';
				
				//Checking to see a message is returned
				if(empty($disEarlyTimetable) && empty($disLateTimetable)){
					$returnData .= "Closed";
				}else{
				$returnData .= $disEarlyTimetable . $disLateTimetable;
				}

				$returnData .=  "</p></div></div>";
			
				if($openingTimes["Late"]["DayOfWeek"] == "Saturday"){
					$returnData .= '</div>';
				}
		} 
		$returnData .= '</div></div>';

		//Outputting
		return $returnData;
		}
	}
	
}//End of Function
add_shortcode('fd_restaurant_openinghours_pickup', 'fd_restaurant_openinghours_pickup');
/***********************************************************************************************************/

/***********************************************************************************************************/ 
function fd_restaurant_openinghours_delivery(){
	
$prid = get_post_meta(get_the_ID(), 'portal_store_id', true);
	if(!empty($prid)){
	
	$url = "https://api.flipdish.co/api/v1.0/stores/" . $prid;
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
 
	$getOpeningHours = json_decode($resp, true);

	$json_OpeningHours = $getOpeningHours["Data"]['DeliveryHours'];
	$json_DeliveryEnabled = $getOpeningHours["Data"]['DeliveryEnabled'];
	
	if($json_DeliveryEnabled == true){

		$returnData = '<div class="OpeningHours-Delivery container">
						<div class="pickup-delivery-card row">
							<h4>Delivery Times</h4>';
						
		foreach($json_OpeningHours as $openingTimes){
				//Opening Hours Early Calculation
				$earlystartTime = $openingTimes["Early"]["StartTime"];
				$earlyperiod = $openingTimes["Early"]["Period"];
				$disEarlyTimetable = "";

				if($earlyperiod != "00:00:00"){
					$secs = strtotime($earlyperiod)-strtotime("00:00:00");
					$closingTime = date("H:i:s",strtotime($earlystartTime)+$secs);
					$disEarlyStartTime=substr($earlystartTime,0,-3);
					$disEarlyClosingTime=substr($closingTime,0,-3);
					$disEarlyTimetable = $disEarlyStartTime . " - " . $disEarlyClosingTime . ", ";
				}

				//Opening Hours Late Calculation
				$latestartTime = $openingTimes["Late"]["StartTime"];
				$lateperiod = $openingTimes["Late"]["Period"];	
				$disLateTimetable = "";

				if($lateperiod != "00:00:00"){
					$secs = strtotime($lateperiod)-strtotime("00:00:00");
					$closingTime = date("H:i:s",strtotime($latestartTime)+$secs);
					$disLateStartTime=substr($latestartTime,0,-3);
					$disLateClosingTime=substr($closingTime,0,-3);
					$disLateTimetable = $disLateStartTime . " - " . $disLateClosingTime;
				}
				
				//Adding in the BootStrap breaks
				if($openingTimes["Late"]["DayOfWeek"] == "Sunday"){
					$returnData .= '<div class="col-12 col-sm-6 col-lg-6">';
				}elseif($openingTimes["Late"]["DayOfWeek"] == "Thursday"){
					$returnData .= '</div>';
					$returnData .= '<div class="col-12 col-sm-6 col-lg-6">';
				}
			
				$returnData .= '<div class="row"><div class="DayOfWeek col-6 col-sm-6 col-lg-6"><p>' . $openingTimes["Late"]["DayOfWeek"] . '</p></div><div class="OpeningTimes col-6 col-sm-6 col-lg-6"><p>';
				
				//Checking to see a message is returned
				if(empty($disEarlyTimetable) && empty($disLateTimetable)){
					$returnData .= "Closed";
				}else{
				$returnData .= $disEarlyTimetable . $disLateTimetable;
				}

				$returnData .=  "</p></div></div>";
			
				if($openingTimes["Late"]["DayOfWeek"] == "Saturday"){
					$returnData .= '</div>';
				}
		} 
		$returnData .= '</div></div>';

		//Outputting
		return $returnData;
		}
	}
}//End of Function
add_shortcode('fd_restaurant_openinghours_delivery', 'fd_restaurant_openinghours_delivery');
/***********************************************************************************************************/

/***********************************************************************************************************/ 
function fd_restaurant_buttons(){

 $portal_store_id = get_post_meta(get_the_ID(), 'portal_store_id', true);
	
	$pickup_link = get_post_meta(get_the_ID(), 'my_custom_url', true);
	$delivery_link = get_post_meta(get_the_ID(), 'delivery_url', true);
	$collection_text = get_field('order_for_collection_text', 'options');
	$delivery_text = get_field('order_for_delivery_text', 'options');
	

	$returnData = '<div id="store_' . $portal_store_id . '">';
	if((has_tag("open")) && (has_tag("open_for_pickup"))) {

		if( $collection_text ) {
			$returnData .= '<a class="et_pb_button dp-dfg-more-button status_button pickup_status" href="' . $pickup_link . '">' . $collection_text . '</a>';
		} else {
			$returnData .= '<a class="et_pb_button dp-dfg-more-button status_button pickup_status" href="' . $pickup_link . '">PICKUP</a>';
		}
	}
	if((has_tag("open")) && (has_tag("open_for_delivery"))) {
		if( $delivery_text ) {
			$returnData .= '<a class="et_pb_button dp-dfg-more-button status_button pickup_status" href="' . $delivery_link . '">' . $delivery_text . '</a>';
		} else {
			$returnData .= '<a class="et_pb_button dp-dfg-more-button status_button delivery_status" href="' . $delivery_link . '">DELIVERY</a>';
		}
	}
	if((has_tag("closed"))) {
		$returnData .= '<button type="button" class="et_pb_button dp-dfg-more-button opening_soon" disabled>Opening Soon</button>';
	}
	$returnData .= '</div>';

    return $returnData;
		
}//End of Function
add_shortcode('fd_restaurant_buttons', 'fd_restaurant_buttons');
/***********************************************************************************************************/