<?php
/*
Plugin Name: Display Green Fins member info from Portal API
Plugin URI: https://reef-world.org
Description: Display Green Fins member information within maps, pages and posts from the Members API. Requires WP Store Locator v2.2.233 or later.
Version: 1.0
Author: James Greenhalgh
Author URI: https://www.linkedin.com/in/jgrnh/
License: GPLv2 or later
Text Domain: rwf-gf-members-api
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please' );

/**
 * List Top 10 Members
 *
 * Outputs a list of the top 10 members from the Portal API, cached for 4 hours using WP transients
 */
function list_top10members_func() {

   $top10members = get_transient( 'rwf_get_top10members' );
   if ( false === $top10members ) {
       // Transient expired, refresh the data

       $url = get_option('rwf_api_endpoint') . '/top-10-members?key=' . get_option('rwf_api_key');
       $response = wp_remote_retrieve_body(wp_remote_get($url));
       $json = json_decode($response, true);

       set_transient( 'rwf_get_top10members', $json, 4 * HOUR_IN_SECONDS );
       $top10members = get_transient( 'rwf_get_top10members' );
   }
   
   // Handle the case when there are no members or the API is malfunctioning
   if ( empty($top10members) || $top10members["success"] == 0) {
      delete_transient( 'rwf_get_top10members' );
      wp_mail("it@reef-world.org", "[Alert] Error on Green Fins Website", "Error: Unable to fetch data from Portal API in list_top10members_func() via 'rwf_get_top10members'. End user has seen an error message.");
      return "<strong>" . __("Error: Unable to fetch data – please refresh this page.", 'rwf-gf-members-api') . "</strong>";
   }

   // We're going to return a grid container.
   $return = "<div class=\"grid-container\">";

   // Loop over the returned members
   $count = 1;
   $i = 0;
   foreach( $top10members['data'] as $member ) {
      $i++;

      // Add a list item for each member to the string
      $return .= <<<LISTING
         <div class="gf-centre-listing gf-clickable-container grid-parent grid-33 tablet-grid-33 mobile-grid-100">

            <div class="gf-centre-listing-image grid-35 tablet-grid-35 mobile-grid-100">
                  <img src="$member[logofilename]">
            </div>

            <div class="gf-centre-listing-meta grid-65 tablet-grid-65 mobile-grid-100">
               <h2>
                  <span class="count">$count</span><a target="_blank" href="$member[website]">$member[name]</a>
               </h2>
               <p class="industry">$member[industry]</p>
               <p class="description">$member[location_name], $member[region_name], <strong>$member[country_name]</strong></p>
            </div>

         </div>
LISTING;
         if( 3 == $i ) {
            $i = 0;
            $return .= <<<CLEARFIX
            <div class="clear"></div>
CLEARFIX;
         }
      $count++;
   }

   $return .= "</div>";

   return $return;
}










/**
 * List Top 5 Members by country
 *
 * Outputs a list of the top 5 members for a given country from the Portal API, cached for 4 hours using WP transients
 */
function list_top5bycountry_func( $atts = [] ) {
   // override default attributes with user attributes
   $get_atts = shortcode_atts(
      array(
         'country' => 'Please specify a valid country to display the top 5 list'
      ), $atts
   );

   //load the countries list
   $countries = get_transient( 'rwf_get_countries' );
      if ( false === $countries ) {
         // Transient expired, refresh the data

         $url = get_option('rwf_api_endpoint') . '/countries?key=' . get_option('rwf_api_key');
         $response = wp_remote_retrieve_body(wp_remote_get($url));
         $json = json_decode($response, true);

         set_transient( 'rwf_get_countries', $json, 7 * DAY_IN_SECONDS );
         $countries = get_transient( 'rwf_get_countries' );
      }
      // Handle the case when there are no countries or the API is malfunctioning
      if ( empty($countries) || $countries["success"] == 0) {
         delete_transient( 'rwf_get_countries' );
         wp_mail("it@reef-world.org", "[Alert] Error on Green Fins Website", "Error: Unable to fetch the countries list from Portal API in list_top5bycountry_func() via 'rwf_get_countries' (status code: " . $countries["code"] . ") on page " . $get_atts['country'] . ". End user has seen an error message.");
         return "<strong>" . __("Error: Unable to fetch data – please refresh this page.", 'rwf-gf-members-api') . "</strong>";
      }

   // lookup the country id by the user specified text
   $countries = $countries['data'];
   $countryinput = $get_atts['country'];
   $result = array_column($countries,"id","name");
   if(isset($result[$countryinput])){
      $countryid = $result[$countryinput];
   }

   // Handle the case where an invalid option is supplied
   if(!isset($countryid)) {
      $list = "<ul>";
      foreach( $countries as $country ) {
         $list .= <<<LIST
         <li>
               $country[name]
         </li>
LIST;
      }
      $list .= "</ul>";
      return "<strong>" . __("Error: Invalid country specified, please check your spelling to dispay list_top5bycountry. The options are:", 'rwf-gf-members-api') . $list . "</strong>";
   }
   
   $country_transient_name = 'rwf_get_top5bycountry_' . $countryinput;
   $top5bycountry = get_transient( $country_transient_name );
   if ( false === $top5bycountry ) {
       // Transient expired, refresh the data

       $url = get_option('rwf_api_endpoint') . '/countries/' . $countryid . '/top-5-members?key=' . get_option('rwf_api_key');
       $response = wp_remote_retrieve_body(wp_remote_get($url));
       $json = json_decode($response, true);

       set_transient( $country_transient_name, $json, 4 * HOUR_IN_SECONDS );
       $top5bycountry = get_transient( $country_transient_name );
   }
   // Handle the case when there are no members or the API is malfunctioning
   if ( empty($top5bycountry) || $top5bycountry["success"] == 0) {
      delete_transient( $country_transient_name );
      wp_mail("it@reef-world.org", "[Alert] Error on Green Fins Website", "Error: Unable to fetch country top 5 list from Portal API in list_top5bycountry_func() via 'rwf_get_top5bycountry_' for " . $get_atts['country'] . ". End user has seen an error message.");
      return "<strong>" . __("Error: Unable to fetch data – please refresh this page.", 'rwf-gf-members-api') . "</strong>";
   }

   // We're going to return a grid container.
   $return = "<div class=\"grid-container\">";

   // Loop over the returned members
   $count = 1;
   $i = 0;
   foreach( $top5bycountry['data'] as $member ) {
      $i++;

      // Add a list item for each member to the string
      $return .= <<<LISTING
         <div class="gf-centre-listing gf-clickable-container grid-parent grid-33 tablet-grid-33 mobile-grid-100">

            <div class="gf-centre-listing-image grid-35 tablet-grid-35 mobile-grid-100">
                  <img src="$member[logofilename]">
            </div>

            <div class="gf-centre-listing-meta grid-65 tablet-grid-65 mobile-grid-100">
               <h2>
                  <span class="count">$count</span><a target="_blank" href="$member[website]">$member[name]</a>
               </h2>
               <p class="industry">$member[industry]</p>
               <p class="description">$member[location_name], $member[region_name]</strong></p>
            </div>

         </div>
LISTING;
         if( 3 == $i ) {
            $i = 0;
            $return .= <<<CLEARFIX
            <div class="clear"></div>
CLEARFIX;
         }
      $count++;
   }

   // Close the div
   $return .= "</div>";

   return $return;

}









/**
 * List Members by Location
 *
 * Outputs a list of members or the average score for a given location from the Portal API, cached for 4 hours using WP transients
 */
function list_membersbylocation_func( $atts = [] ) {
   // override default attributes with user attributes
   $get_atts = shortcode_atts(
      array(
         'country' => 'Please specify a valid country & location to display the members list',
         'location' => 'Please specify a valid country & location to display the members list',
         'display_average_score' => false
      ), $atts
   );

   //load the countries list
   $countries = get_transient( 'rwf_get_countries' );
      if ( false === $countries ) {
         // Transient expired, refresh the data

         $url = get_option('rwf_api_endpoint') . '/countries?key=' . get_option('rwf_api_key');
         $response = wp_remote_retrieve_body(wp_remote_get($url));
         $json = json_decode($response, true);

         set_transient( 'rwf_get_countries', $json, 7 * DAY_IN_SECONDS );
         $countries = get_transient( 'rwf_get_countries' );
      }
      // Handle the case when there are no countries or the API is malfunctioning
      if ( empty($countries) || $countries["success"] == 0) {
         delete_transient( 'rwf_get_countries' );
         wp_mail("it@reef-world.org", "[Alert] Error on Green Fins Website", "Error: Unable to fetch the countries list from Portal API in list_membersbylocation_func() via 'rwf_get_countries' (status code: " . $countries["code"] . ") on page " . $get_atts['country'] . " > " . $get_atts['location'] . " > Display average: [" . $get_atts['display_average_score'] .  "]. End user has seen an error message.");
         return "<strong>" . __("Error: Unable to fetch data – please refresh this page.", 'rwf-gf-members-api') . "</strong>";
      }

   // lookup the country id by the user specified text
   $countries = $countries['data'];
   $countryinput = $get_atts['country'];
   $result = array_column($countries,"id","name");

   if(isset($result[$countryinput])){
      $countryid = $result[$countryinput];
   }

   // Handle the case where an invalid option is supplied
   if(!isset($countryid)) {
      $list = "<ul>";
      foreach( $countries as $country ) {
         $list .= <<<LIST
         <li>
               $country[name]
         </li>
LIST;
      }
      $list .= "</ul>";
      return "<strong>" . __("Error: Invalid country specified, please check your country spelling to display list_membersbylocation. The options are:", 'rwf-gf-members-api') . $list . "</strong>";
   }
   
   $regions_transient_name = 'rwf_get_regionsbycountry_' . $countryinput;
   $regionsbycountry = get_transient( $regions_transient_name );
      if ( false === $regionsbycountry ) {
         // Transient expired, refresh the data

         $url = get_option('rwf_api_endpoint') . '/countries/' . $countryid . '/regions?key=' . get_option('rwf_api_key');
         $response = wp_remote_retrieve_body(wp_remote_get($url));
         $json = json_decode($response, true);

         set_transient( $regions_transient_name, $json, 4 * HOUR_IN_SECONDS );
         $regionsbycountry = get_transient( $regions_transient_name );
      }
      // Handle the case when there are no results or the API is malfunctioning
      if ( empty($regionsbycountry) || $regionsbycountry["success"] == 0) {
         delete_transient( $regions_transient_name );
         wp_mail("it@reef-world.org", "[Alert] Error on Green Fins Website", "Error: Unable to fetch data from Portal API in list_membersbylocation_func() via 'rwf_get_regionsbycountry_' for " . $get_atts['country'] . " and " . $get_atts['location'] . ". End user has seen an error message.");
         return "<strong>" . __("Error: Unable to fetch data – please refresh this page.", 'rwf-gf-members-api') . "</strong>";
      }


   // build an array of the locations in all of the specified country's regions
   $regionsbycountry = $regionsbycountry['data'];
   $locations = [];

   foreach ( $regionsbycountry as $region ){
      $locations_transient_name = 'rwf_get_locationsbyregion_' . $countryinput . '_' . $region['name']; //we need this level of verbosity because there are region name collisions
      $locationsbyregion = get_transient( $locations_transient_name );
         if ( false === $locationsbyregion ) {
            // Transient expired, refresh the data
   
            $url = get_option('rwf_api_endpoint') . '/regions/' . $region['id'] . '/locations?key=' . get_option('rwf_api_key');
            $response = wp_remote_retrieve_body(wp_remote_get($url));
            $json = json_decode($response, true);
   
            set_transient( $locations_transient_name, $json, 4 * HOUR_IN_SECONDS );
            $locationsbyregion = get_transient( $locations_transient_name );
         }
         // Handle the case when there are no results or the API is malfunctioning
         if ( empty($locationsbyregion) || $locationsbyregion["success"] == 0) {
            delete_transient( $locations_transient_name );
            wp_mail("it@reef-world.org", "[Alert] Error on Green Fins Website", "Error: Unable to fetch data from Portal API in list_membersbylocation_func() via 'rwf_get_locationsbyregion_' for " . $get_atts['country'] . " and " . $get_atts['location'] . ". End user has seen an error message.");
            return "<strong>" . __("Error: Unable to fetch data – please refresh this page.", 'rwf-gf-members-api') . "</strong>";
         }

      //flatten
      $locationsbyregion = $locationsbyregion['data'];
      //append the new locations onto any existing to build the array with each loop
      $locations = array_merge($locations, $locationsbyregion);
   }

   $results = array_column($locations,"id","name");
   $location_input = $get_atts['location'];

   if(isset($results[$location_input])){
      $locationid = $results[$location_input];
   }

   // Handle the case where an invalid option is supplied
   if(!isset($locationid)) {
      $list = "<ul>";
      foreach( $locations as $location ) {
         $list .= <<<LIST
         <li>
               $location[name]
         </li>
LIST;
      }
      $list .= "</ul>";
      return "<strong>" . __("Error: Invalid location specified, please check your location spelling to display list_membersbylocation. The options are:", 'rwf-gf-members-api') . $list . "</strong>";
   }


   $members_transient_name = 'rwf_get_membersbylocation_' . $location_input;
   $membersbylocation = get_transient( $members_transient_name );
      if ( false === $membersbylocation ) {
         // Transient expired, refresh the data

         $url = get_option('rwf_api_endpoint') . '/locations/' . $locationid . '/members?key=' . get_option('rwf_api_key');
         $response = wp_remote_retrieve_body(wp_remote_get($url));
         $json = json_decode($response, true);

         set_transient( $members_transient_name, $json, 4 * HOUR_IN_SECONDS );
         $membersbylocation = get_transient( $members_transient_name );
      }
      // Handle the case when there are no results or the API is malfunctioning
      if ( empty($membersbylocation) || $membersbylocation["success"] == 0) {
         delete_transient( $members_transient_name );
         wp_mail("it@reef-world.org", "[Alert] Error on Green Fins Website", "Error: Unable to fetch data from Portal API in list_membersbylocation_func() via 'rwf_get_membersbylocation_' for" . $get_atts['country'] . " and " . $get_atts['location'] . ". End user has seen an error message.");
         return "<strong>" . __("Error: Unable to fetch data – please refresh this page.", 'rwf-gf-members-api') . "</strong>";
      }

   //figure out what we are outputting
   $display_average_score = $get_atts['display_average_score'];
   if($display_average_score){

      $average_lookup = array_column($locations,"average","name");
   
      if(isset($average_lookup[$location_input])){
         $average_score = round($average_lookup[$location_input]);
         $average_score_percent = 100 - ($average_score / 330 * 100); //We want a zero score to fill the progress bar

         if ($average_score < 28) {
            $average_score_style = 'lower';
         }
         elseif ($average_score < 205) {
            $average_score_style = 'middle';
         }         
         else {
            $average_score_style = 'upper';
         }         
      }

      $return = <<<SCORE

      <div class="gb-container gb-container-f07c0c81"><div class="gb-inside-container"></div></div>



<div class="gb-container gb-container-a57dea89 gf-section-average-score"><div class="gb-inside-container">
<h1 class="gb-headline gb-headline-a284ca0f">Average Score</h1>



<p>This is the average environmental performance score of members in this location</p>

         <h2 class="gf-score">$average_score</h2>
         <div class="gf-score-meter">
            <span class="gf-score-$average_score_style" style="width: $average_score_percent%"></span>
         </div>

         <div class="gb-container gb-container-d429c552"><div class="gb-inside-container">
         <div class="gb-grid-wrapper gb-grid-wrapper-e77d8655">
         <div class="gb-grid-column gb-grid-column-fe72985d"><div class="gb-container gb-container-fe72985d"><div class="gb-inside-container"></div></div></div>
         
         
         
         <div class="gb-grid-column gb-grid-column-34fb67fb"><div class="gb-container gb-container-34fb67fb"><div class="gb-inside-container">
         <h2 class="gb-headline gb-headline-90c84c3a">330</h2>
         
         
         
         <h2 class="gb-headline gb-headline-c96e550c">Poor Environmental Performance</h2>
         </div></div></div>
         
         
         
         <div class="gb-grid-column gb-grid-column-12f58e34"><div class="gb-container gb-container-12f58e34"><div class="gb-inside-container">
         <h2 class="gb-headline gb-headline-46b7cf1b">165</h2>
         
         
         
         <h2 class="gb-headline gb-headline-ff77cb36">Needs Improvement</h2>
         </div></div></div>
         
         
         
         <div class="gb-grid-column gb-grid-column-39cfbbb8"><div class="gb-container gb-container-39cfbbb8"><div class="gb-inside-container">
         <h2 class="gb-headline gb-headline-0f99cdfd">0</h2>
         
         
         
         <h2 class="gb-headline gb-headline-d7236770">Great Environmental Performance</h2>
         </div></div></div>
         
         
         
         <div class="gb-grid-column gb-grid-column-8ce327e6"><div class="gb-container gb-container-8ce327e6"><div class="gb-inside-container"></div></div></div>
         </div>
         </div></div>
         
         
         
         <p>Annually, Green Fins members have their environmental performance evaluated by trained assessors. Each assessment results in a score based on a traffic light rating system for how well risks to the environment are managed. The lower the score, the better the environmental performance. Each member’s score is confidential and continued membership is based on ongoing improvement.</p>
         
         
         
         <div class="gb-container gb-container-e88999f6"><div class="gb-inside-container">
         <div class="gb-grid-wrapper gb-grid-wrapper-d80d1d7a">
         <div class="gb-grid-column gb-grid-column-e0f04d97"><div class="gb-container gb-container-e0f04d97"><div class="gb-inside-container">
         <h2 class="gb-headline gb-headline-8040ba89">Active</h2>
         
         
         
         <p>A member that has been assessed within the last 18 months and successfully reduced their environmental impact.</p>
         </div></div></div>
         
         
         
         <div class="gb-grid-column gb-grid-column-f3d10a1f"><div class="gb-container gb-container-f3d10a1f"><div class="gb-inside-container">
         <h2 class="gb-headline gb-headline-5a18dd14">Inactive</h2>
         
         
         
         <p>A member who was previously active but has not had an assessment to verify their environmental impact in the last 18 months.</p>
         </div></div></div>
         
         
         
         <div class="gb-grid-column gb-grid-column-db558dc6"><div class="gb-container gb-container-db558dc6"><div class="gb-inside-container">
         <h2 class="gb-headline gb-headline-040888e5">Suspended</h2>
         
         
         
         <p>A member that has not managed to reduce their threat to the marine environment or has been involved in an activity that is seen as seriously detrimental to the marine environment. Suspended members are not listed.</p>
         </div></div></div>
         </div>
         </div></div>
         </div></div>
         <div class="gb-container gb-container-19322339"><div class="gb-inside-container"></div></div>

SCORE;
   }
   else {
   // We're going to return a grid container.
   $return = "<div class=\"grid-container\">";

   $i = 0;
      // Loop over the returned members
      foreach( $membersbylocation['data'] as $member ) {
         if($member['status'] == 'active') {
            $i++;
            // Add a list item for each member to the string
            $return .= <<<LISTING
            <div class="gf-centre-listing gf-member-$member[status] gf-clickable-container grid-parent grid-33 tablet-grid-33 mobile-grid-100">

               <div class="gf-centre-listing-image grid-35 tablet-grid-35 mobile-grid-100">
                     <img src="$member[logofilename]">
               </div>

               <div class="gf-centre-listing-meta grid-65 tablet-grid-65 mobile-grid-100">
                  <h2>
                     <a target="_blank" href="$member[website]">$member[name]</a>
                  </h2>
                  
                     <p class="status">$member[status]</p>
                     <p class="industry">$member[industry]</p>
                     <p><strong>Address:</strong> $member[address1], $member[address2], $member[address3]</p>
                     <p class="contact">Contact info:</p>
                     <p class="contact-item">$member[email]</p>
                     <p class="contact-item">$member[telephone]</p>
               </div>

            </div>
LISTING;
         }
         if( 3 == $i ) {
            $i = 0;
            $return .= <<<CLEARFIX
            <div class="clear"></div>
CLEARFIX;
         }
      }

      foreach( $membersbylocation['data'] as $member ) {
         if($member['status'] == 'inactive') {
            $i++;
            // Add a list item for each member to the string
            $return .= <<<LISTINGI
            <div class="gf-centre-listing gf-member-$member[status] gf-clickable-container grid-parent grid-33 tablet-grid-50 mobile-grid-100">

               <div class="gf-centre-listing-image grid-35 tablet-grid-35 mobile-grid-100">
                     <img src="$member[logofilename]">
               </div>

               <div class="gf-centre-listing-meta grid-65 tablet-grid-65 mobile-grid-100">
                  <h2>
                     <a target="_blank" href="$member[website]">$member[name]</a>
                  </h2>
                  
                     <p class="status">$member[status]</p>
                     <p class="industry">$member[industry]</p>
                     <p><strong>Address:</strong> $member[address1], $member[address2], $member[address3]</p>
                     <p class="contact">Contact info:</p>
                     <p class="contact-item">$member[email]</p>
                     <p class="contact-item">$member[telephone]</p>
               </div>

            </div>
LISTINGI;
         }
         if( 3 == $i ) {
            $i = 0;
            $return .= <<<CLEARFIXI
            <div class="clear"></div>
CLEARFIXI;
         }
      }
      // Close the div
      $return .= "</div>";
   }

   return $return;

}










/**
 * Populate members into posts and post meta, the tricky bit is updating the existing records after building an array.
 *
 * Love this
 */
function rwf_gf_populate_members_as_posts_func() {

   //load the countries list
   $countries = get_transient( 'rwf_get_countries' );
      if ( false === $countries ) {
         // Transient expired, refresh the data

         $url = get_option('rwf_api_endpoint') . '/countries?key=' . get_option('rwf_api_key');
         $response = wp_remote_retrieve_body(wp_remote_get($url));
         $json = json_decode($response, true);

         set_transient( 'rwf_get_countries', $json, 7 * DAY_IN_SECONDS );
         $countries = get_transient( 'rwf_get_countries' );
      }
      // Handle the case when there are no countries or the API is malfunctioning
      if ( empty($countries) || $countries["success"] == 0) {
         delete_transient( 'rwf_get_countries' );
         wp_mail("it@reef-world.org", "[Alert] Error on Green Fins Website", "Error: Unable to fetch the countries list from Portal API in populate_centres_func() via 'rwf_get_countries' (status code: " . $countries["code"] . "). Please re-trigger the function to populate Wordpress from the Portal API.");
         return "<strong>" . __("Error: Unable to fetch data – please refresh this page.", 'rwf-gf-members-api') . "</strong>";
      }

   //flatten
   $countries = $countries['data'];

   //declare empty arrays for use later
   $regions = [];
   $locations = [];
   $members = [];

   foreach ( $countries as $country ){
      $regions_transient_name = 'rwf_get_regionsbycountry_' . $country['name'];
      $regionsbycountry = get_transient( $regions_transient_name );
         if ( false === $regionsbycountry ) {
            // Transient expired, refresh the data
            $url = get_option('rwf_api_endpoint') . '/countries/' . $country['id'] . '/regions?key=' . get_option('rwf_api_key');
            $response = wp_remote_retrieve_body(wp_remote_get($url));
            $json = json_decode($response, true);
   
            set_transient( $regions_transient_name, $json, 4 * HOUR_IN_SECONDS );
            $regionsbycountry = get_transient( $regions_transient_name );
         }
         // Handle the case when there are no results or the API is malfunctioning
         if ( empty($regionsbycountry) || $regionsbycountry["success"] == 0) {
            delete_transient( $regions_transient_name );
            wp_mail("it@reef-world.org", "[Alert] Error on Green Fins Website", "Error: Unable to fetch data from Portal API in populate_centres_func() via 'rwf_get_regionsbycountry_' for " . $country['name'] . ". Please re-trigger the function to populate Wordpress from the Portal API.");
            return "<strong>" . __("Error: Unable to fetch data – please refresh this page.", 'rwf-gf-members-api') . "</strong>";
         }

      //flatten
      $regionsbycountry = $regionsbycountry['data'];
      //append the new locations onto any existing to build the array with each loop
      //$regions = array_merge($regions, $regionsbycountry);

         foreach ( $regionsbycountry as $region ){
            $locations_transient_name = 'rwf_get_locationsbyregion_' . $country['name'] . '_' . $region['name']; //we need this level of verbosity because there are region name collisions
            $locationsbyregion = get_transient( $locations_transient_name );
               if ( false === $locationsbyregion ) {
                  // Transient expired, refresh the data
                  $url = get_option('rwf_api_endpoint') . '/regions/' . $region['id'] . '/locations?key=' . get_option('rwf_api_key');
                  $response = wp_remote_retrieve_body(wp_remote_get($url));
                  $json = json_decode($response, true);
         
                  set_transient( $locations_transient_name, $json, 4 * HOUR_IN_SECONDS );
                  $locationsbyregion = get_transient( $locations_transient_name );
               }
               // Handle the case when there are no results or the API is malfunctioning
               if ( empty($locationsbyregion) || $locationsbyregion["success"] == 0) {
                  delete_transient( $locations_transient_name );
                  wp_mail("it@reef-world.org", "[Alert] Error on Green Fins Website", "Error: Unable to fetch data from Portal API in populate_centres_func() via 'rwf_get_locationsbyregion_' for " . $country['name'] . " and " . $region['name'] . ". Please re-trigger the function to populate Wordpress from the Portal API.");
                  return "<strong>" . __("Error: Unable to fetch data – please refresh this page.", 'rwf-gf-members-api') . "</strong>";
               }

            //flatten
            $locationsbyregion = $locationsbyregion['data'];
            //append the new locations onto any existing to build the array with each loop
            $locations = array_merge($locations, $locationsbyregion);
         }
   }

   foreach ( $locations as $location ){
      $members_transient_name = 'rwf_get_membersbylocation_' . $location['name'];
      $membersbylocation = get_transient( $members_transient_name );
         if ( false === $membersbylocation ) {
            // Transient expired, refresh the data
            $url = get_option('rwf_api_endpoint') . '/locations/' . $location['id'] . '/members?key=' . get_option('rwf_api_key');
            $response = wp_remote_retrieve_body(wp_remote_get($url));
            $json = json_decode($response, true);
   
            set_transient( $members_transient_name, $json, 4 * HOUR_IN_SECONDS );
            $membersbylocation = get_transient( $members_transient_name );
         }
         // Handle the case when there are no results or the API is malfunctioning
         if ( empty($membersbylocation) || $membersbylocation["success"] == 0) {
            delete_transient( $members_transient_name );
            wp_mail("it@reef-world.org", "[Alert] Error on Green Fins Website", "Error: Unable to fetch data from Portal API in populate_centres_func() via 'rwf_get_membersbylocation_' for " . $location['name'] . ". Please re-trigger the function to populate Wordpress from the Portal API.");
            return "<strong>" . __("Error: Unable to fetch data – please refresh this page.", 'rwf-gf-members-api') . "</strong>";
         }

      // Flatten
      $membersbylocation = $membersbylocation['data'];
      // Append the new locations onto any existing to build the array with each loop
      $members = array_merge($members, $membersbylocation);
   }

   // We've built the members list, time to make some posts
   // Try to disable the time limit to prevent timeouts.
   @set_time_limit( 0 );

   // seed the database
   foreach ($members as $member){

      //look for existing centre records
      $args = array(
         'post_type' => 'wpsl_stores',
         'meta_key' => 'wpsl_api_centre_id', //this meta key is created using the wpsl filter custom_meta_box_fields() below
         'meta_value' => $member['id'],
         'post_status' => 'any',
         'posts_per_page' => -1
         );
      $wpsl_post_exists = get_posts($args);

      // Make sure we set the correct post status.
      if ( $member['status'] == 'active') {
         $post_status = 'publish';
      } else {
         $post_status = 'hidden';
      }

      if($wpsl_post_exists){
         // update the existing member record
         $wpsl_post_id = $wpsl_post_exists[0]->ID;
         $post = array (
            'ID'           => $wpsl_post_id,
            'post_type'    => 'wpsl_stores',
            'post_status'  => $post_status,
            'post_title'   => $member['name'],
            'post_content' => $member['industry']              
         );
      } else {
         // make a new post for the member
         $post = array (
            'post_type'    => 'wpsl_stores',
            'post_status'  => $post_status,
            'post_title'   => $member['name'],
            'post_content' => $member['industry']              
         );
      }

      $post_id = wp_insert_post( $post );

      // add or update the post meta with the member's info
      if ( $post_id ) {
         $postmetas = array (
            'api_centre_id'      => $member['id'],
            'api_logo_filename'  => $member['logofilename'],
            'address'            => $member['address1'],
            'address2'           => $member['address2'],
            'city'               => $member['address3'],
            'state'              => $member['region_name'],
            'country'            => $member['country_name'],
            'lat'                => $member['lat'],
            'lng'                => $member['lng'],
            'phone'              => $member['telephone'],
            'url'                => $member['website'],
            'email'              => $member['email']            
         );

         foreach ( $postmetas as $meta => $value ) {
            if ( isset( $value ) && !empty( $value ) ) {
               update_post_meta( $post_id, 'wpsl_' . $meta, $value );
            }
         }
      }
      
   }
   wp_mail("it@reef-world.org", "Success: rwf_gf_populate_members_as_posts_func()", "rwf_gf_populate_members_as_posts_func() ran successfully, the member map positions have been updated");

   exit;
}











/**
 * Remove fax and add API ID to the WPSL additional information
 */
function custom_meta_box_fields( $meta_fields ) {
    
    $meta_fields[__( 'Additional Information', 'wpsl' )] = array(
        'phone' => array(
            'label' => __( 'Tel', 'wpsl' )
        ),
        'email' => array(
            'label' => __( 'Email', 'wpsl' )
        ),
        'url' => array(
            'label' => __( 'Url', 'wpsl' )
        ),
        'api_centre_id' => array(
            'label' => __( 'Api_Centre_Id', 'wpsl' )
        ),
        'api_logo_filename' => array(
         'label' => __( 'Api_Logo_Filename', 'wpsl' )
        )
    );

    return $meta_fields;
}

/**
 * Hide the start marker
 */
function custom_js_settings( $settings ) {

    $settings['startMarker'] = '';

    return $settings;
}

/**
 * Collect the Used Categories to display categories in the map info window (for bronze, silver, gold, top 10)
 */
function custom_store_meta( $store_meta, $store_id ) {
    
    $terms = wp_get_post_terms( $store_id, 'wpsl_store_category' );
    
    $store_meta['terms'] = '';
    
    if ( $terms ) {
        if ( !is_wp_error( $terms ) ) {
            if ( count( $terms ) > 1 ) {
                $location_terms = array();

                foreach ( $terms as $term ) {
                    $location_terms[] = $term->name;
                }

                $store_meta['terms'] = implode( ', ', $location_terms );
            } else {
                $store_meta['terms'] = $terms[0]->name;    
            }
        }
    }
    
    return $store_meta;
}

/**
 * Include additional meta data in the JSON response on the front end.
 */
function custom_frontend_meta_fields( $store_fields ) {

    $store_fields['wpsl_api_logo_filename'] = array( 
        'name' => 'api_logo_filename' 
    );
    
    return $store_fields;
}


/**
 * Marker Info Window Template with modifications to display the category (for bronze, silver, gold, top 10)
 */
function custom_info_window_template() {
   
   global $wpsl_settings, $wpsl;

   $info_window_template = '<div data-store-id="<%= id %>" class="wpsl-info-window">' . "\r\n";
   $info_window_template .= "\t\t" . '<p>' . "\r\n";
   $info_window_template .= "\t\t\t" . '<img src="<%= api_logo_filename %>" alt="Logo for <%= store %>" width="50px"><br>' . "\r\n";
   $info_window_template .= "\t\t\t" .  wpsl_store_header_template() . "\r\n";  
   $info_window_template .= "\t\t\t" . '<span><%= address %></span>' . "\r\n";
   $info_window_template .= "\t\t\t" . '<% if ( address2 ) { %>' . "\r\n";
   $info_window_template .= "\t\t\t" . '<span><%= address2 %></span>' . "\r\n";
   $info_window_template .= "\t\t\t" . '<% } %>' . "\r\n";
   $info_window_template .= "\t\t\t" . '<span>' . wpsl_address_format_placeholders() . '</span>' . "\r\n";
   $info_window_template .= "\t\t" . '</p>' . "\r\n";
   
   // Include the category names.
   $info_window_template .= "\t\t" . '<% if ( terms ) { %>' . "\r\n";
   $info_window_template .= "\t\t" . '<p>' . __( 'Categories:', 'wpsl' ) . ' <%= terms %></p>' . "\r\n";
   $info_window_template .= "\t\t" . '<% } %>' . "\r\n";
   

   $info_window_template .= "\t\t" . '<% if ( phone ) { %>' . "\r\n";
   $info_window_template .= "\t\t" . '<span><strong>' . esc_html( $wpsl->i18n->get_translation( 'phone_label', __( 'Phone', 'wpsl' ) ) ) . '</strong>: <%= formatPhoneNumber( phone ) %></span>' . "\r\n";
   $info_window_template .= "\t\t" . '<% } %>' . "\r\n";
   $info_window_template .= "\t\t" . '<% if ( url ) { %>' . "\r\n";
   $info_window_template .= "\t\t" . '<span><strong>' . esc_html( $wpsl->i18n->get_translation( 'url_label', __( 'Url', 'wpsl' ) ) ) . '</strong>: <a target="_blank" href="<%= url %>"><%= url %></a></span>' . "\r\n";
   $info_window_template .= "\t\t" . '<% } %>' . "\r\n";
   $info_window_template .= "\t\t" . '<% if ( email ) { %>' . "\r\n";
   $info_window_template .= "\t\t" . '<span><strong>' . esc_html( $wpsl->i18n->get_translation( 'email_label', __( 'Email', 'wpsl' ) ) ) . '</strong>: <%= formatEmail( email ) %></span>' . "\r\n";
   $info_window_template .= "\t\t" . '<% } %>' . "\r\n";


   $info_window_template .= "\t\t" . '<%= createInfoWindowActions( id ) %>' . "\r\n";
   $info_window_template .= "\t" . '</div>' . "\r\n";
   
   return $info_window_template;
}










/**
 * Admin menu for the API key and endpoint management
 */
function rwf_gf_members_api_plugin_menu_func() {
   add_submenu_page( "options-general.php",  // Which menu parent
                  "Green Fins Members API Plugin Settings",            // Page title
                  "Green Fins Members API",            // Menu title
                  "manage_options",       // Minimum capability (manage_options is an easy way to target administrators)
                  "rwf_gf_members_api_plugin",            // Menu slug
                  "rwf_gf_members_api_plugin_options"     // Callback that prints the markup
               );
}

// Print the markup for the page
function rwf_gf_members_api_plugin_options() {
   if ( !current_user_can( "manage_options" ) )  {
      wp_die( __( "You do not have sufficient permissions to access this page." ) );
   }

   if ( isset($_GET['status']) && $_GET['status']=='success') { 
      ?>
         <div id="message" class="updated notice is-dismissible">
            <p><?php _e("Settings updated!", "rwf-gf-members-api"); ?></p>
            <button type="button" class="notice-dismiss">
               <span class="screen-reader-text"><?php _e("Dismiss this notice.", "rwf-gf-members-api"); ?></span>
            </button>
         </div>
      <?php
   }

   ?>
   <form method="post" action="<?php echo admin_url( 'admin-post.php'); ?>">

      <input type="hidden" name="action" value="update_rwf_gf_members_api_plugin_settings" />

      <h1><?php _e("Green Fins Members API Plugin", "rwf-gf-members-api"); ?></h1>

      <p>
         This plugin displays Green Fins member information within maps, on pages and posts (using shortcodes) from the Assessor Portal via the Members API.
      </p>
      <p>
         Data is cached for 4 hours using <a href="<?php echo get_bloginfo("url") . "/wp-admin/tools.php?page=pw-transients-manager&s=rwf_get_"; ?>" target="_new">transients</a> (enable WP Transients Manager for this link to work). Requires WP Store Locator v2.2.233 or later.
      </p>
      <p>
         <strong>For help with this plugin, please contact James Greenhalgh (james@reef-world.org)</strong>
      </p>
      <p>
         Available shortcodes:
         <ul>
            <li><code>[list_top10members]</code> displays the Top 10 members</li>
            <li><code>[list_top5bycountry country=""]</code> displays the Top 5 members for the specified country.</li>
            <li><code>[list_membersbylocation country="" location=""]</code> displays the active and inactive members for the specified country and location. If the location is misspelt then a list of available options will be displayed.</li>
            <li><code>[list_membersbylocation country="" location="" display_average_score="true"]</code> displays the average score meter section for the specified country and location.</li>
         </ul>
      </p>

      <br>

      <h2><?php _e("Plugin Settings", "rwf-gf-members-api"); ?></h2>

      <table class="form-table" role="presentation">
         <tr>
            <th scope="row"><label>
               <?php _e("API Endpoint:", "rwf-gf-members-api"); ?></label>
            </th>
            <td>
               <input class="" type="text" name="rwf_api_endpoint" value="<?php echo get_option('rwf_api_endpoint'); ?>" />
            </td>
         </tr>
         <tr>
            <th scope="row"><label>
               <label><?php _e("API Key:", "rwf-gf-members-api"); ?></label>
            </th>
            <td>
               <input class="" type="text" name="rwf_api_key" value="<?php echo get_option('rwf_api_key'); ?>" />
            </td>
         </tr>
      </table>

      <br>

      <input class="button button-primary" type="submit" value="<?php _e("Save", "rwf-gf-members-api"); ?>" />

   </form>
   <?php

}

function rwf_gf_members_api_plugin_handle_save() {

   // Get the options that were sent
   $key = (!empty($_POST["rwf_api_key"])) ? $_POST["rwf_api_key"] : NULL;
   $endpoint = (!empty($_POST["rwf_api_endpoint"])) ? $_POST["rwf_api_endpoint"] : NULL;

   // API Validation to go here

   // Update the values
   update_option( "rwf_api_key", $key, TRUE );
   update_option( "rwf_api_endpoint", $endpoint, TRUE );

   // Redirect back to settings page
   $redirect_url = get_bloginfo("url") . "/wp-admin/options-general.php?page=rwf_gf_members_api_plugin&status=success";
   header("Location: ".$redirect_url);
   exit;
}











/**
 * Central location to initiate all shortcodes and actions. This ensures that the plugin doesn't hurt page load times.
 */
function rwf_gf_members_api_init() {
   add_action( "admin_menu", "rwf_gf_members_api_plugin_menu_func" );
   add_action( 'admin_post_update_rwf_gf_members_api_plugin_settings', 'rwf_gf_members_api_plugin_handle_save' );
   if ( ! wp_next_scheduled( 'rwf_gf_populate_members_as_posts' ) ) {
      wp_schedule_event( time(), 'twicedaily', 'rwf_gf_populate_members_as_posts' );
   }
   add_action( 'rwf_gf_populate_members_as_posts', 'rwf_gf_populate_members_as_posts_func' );

   add_shortcode( "list_top10members", "list_top10members_func" );
   add_shortcode( "list_top5bycountry", "list_top5bycountry_func" );
   add_shortcode( "list_membersbylocation", "list_membersbylocation_func" );

   // map filters
   add_filter( 'wpsl_meta_box_fields', 'custom_meta_box_fields' );
   add_filter( 'wpsl_js_settings', 'custom_js_settings' );
   add_filter( 'wpsl_store_meta', 'custom_store_meta', 10, 2 );
   add_filter( 'wpsl_info_window_template', 'custom_info_window_template' );
   add_filter( 'wpsl_frontend_meta_fields', 'custom_frontend_meta_fields' );
}
add_action( 'init', 'rwf_gf_members_api_init' );
?>