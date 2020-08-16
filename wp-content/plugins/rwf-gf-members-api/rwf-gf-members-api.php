<?php
/*
Plugin Name: Display Green Fins member info from Portal API
Plugin URI: https://reef-world.org
Description: Display Green Fins member information within pages and posts using shortcodes (cached using WP transients) from the Portal API
Version: 1.0
Author: James Greenhalgh
Author URI: https://www.instagram.com/jamesgreenblue/
License: GPLv2 or later
Text Domain: rwf-gf-members-api
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please' );

/**
 * List Top 10 Members
 *
 * Outputs a list of the top 10 members from the Portal API, cached for 4 hours using WP transients
 */
function list_top10members_func( $atts ) {

   $top10members = get_transient( 'get_top10members' );
   if ( false === $top10members ) {
       // Transient expired, refresh the data

       $key = 'iQZ35gareSKwo9YfHVJyvpXPhE8LA7';
       $url = 'https://portal-dev.greenfins.net/api/v1/top-10-members?key=' . $key;
       $data = wp_remote_get($url);
       $response = wp_remote_retrieve_body($data);
       $json = json_decode($response, true);

       set_transient( 'get_top10members', $json, 4 * HOUR_IN_SECONDS );
       $top10members = get_transient( 'get_top10members' );
   }
   
   // Handle the case when there are no members or the API is malfunctioning
   if ( empty($top10members) || $top10members["success"] == 0) {
      delete_transient( 'get_top10members' );
      wp_mail("it@reef-world.org", "Alert: Error triggered on Green Fins Wordpress site", "Error: Unable to fetch data from Portal API. Please investigate.");
      return "<strong>" . __("Error: Unable to fetch data from Portal API. Please notify it@reef-world.org", 'rwf-gf-members-api') . "</strong>";
   }

   // We're going to return a string. First, we open a list.
   $return = "<ul>";

   // Loop over the returned members
   $count = 1;
   foreach( $top10members['data'] as $member ) {

      // Add a list item for each member to the string
      $return .= <<<LISTING
      <li>
         <div>
            <a target="_blank" href="$member[website]">
               <img src="$member[logofilename]">
            </a>
            <h2>
            $count. <a target="_blank" href="$member[website]">$member[name]</a>
            </h2>
            <p class="description">$member[location_name], $member[region_name], $member[country_name]</p>
         </div>
      </li>
LISTING;

      $count++;
   }

   // Close the list
   $return .= "</ul>";

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
         'country' => 'Please specify a valid country to display the top 5 list',
      ), $atts
   );

   //load the countries list
   $countries = get_transient( 'get_countries' );
   if ( false === $countries ) {
       // Transient expired, refresh the data

       $key = 'iQZ35gareSKwo9YfHVJyvpXPhE8LA7';
       $url = 'https://portal-dev.greenfins.net/api/v1/countries?key=' . $key;
       $data = wp_remote_get($url);
       $response = wp_remote_retrieve_body($data);
       $json = json_decode($response, true);

       set_transient( 'get_countries', $json, 4 * HOUR_IN_SECONDS );
       $countries = get_transient( 'get_countries' );
   }
   // Handle the case when there are no countries or the API is malfunctioning
   if ( empty($countries) || $countries["success"] == 0) {
      delete_transient( 'get_countries' );
      wp_mail("it@reef-world.org", "Alert: Error triggered on Green Fins Wordpress site", "Error: Unable to fetch data from Portal API. Please investigate.");
      return "<strong>" . __("Error: Unable to fetch data from Portal API. Please notify it@reef-world.org", 'rwf-gf-members-api') . "</strong>";
   }

   // lookup the country id by the user specified text
   $countries = $countries['data'];
   $userinput = $get_atts['country'];
   $result = array_column($countries,"id","name");
   if(isset($result[$userinput])){
      $countryid = $result[$userinput];
   }

   // Handle the case where an invalid option is supplied
   if(!isset($countryid)) {
      return "<strong>" . __("Error: Invalid country specified, please check your spelling.", 'rwf-gf-members-api') . "</strong>";
   }
   
   $transient_name = 'get_top5bycountry_' . $userinput;

   $top5bycountry = get_transient( $transient_name );
   if ( false === $top5bycountry ) {
       // Transient expired, refresh the data

       $key = 'iQZ35gareSKwo9YfHVJyvpXPhE8LA7';
       $url = 'https://portal-dev.greenfins.net/api/v1/countries/' . $countryid . '/top-5-members?key=' . $key;
       $data = wp_remote_get($url);
       $response = wp_remote_retrieve_body($data);
       $json = json_decode($response, true);

       set_transient( $transient_name, $json, 4 * HOUR_IN_SECONDS );
       $top5bycountry = get_transient( $transient_name );
   }
   // Handle the case when there are no members or the API is malfunctioning
   if ( empty($top5bycountry) || $top5bycountry["success"] == 0) {
      delete_transient( $transient_name );
      wp_mail("it@reef-world.org", "Alert: Error triggered on Green Fins Wordpress site", "Error: Unable to fetch data from Portal API. Please investigate.");
      return "<strong>" . __("Error: Unable to fetch data from Portal API. Please notify it@reef-world.org", 'rwf-gf-members-api') . "</strong>";
   }

   // We're going to return a string. First, we open a list.
   $return = "<ul>";

   // Loop over the returned members
   $count = 1;
   foreach( $top5bycountry['data'] as $member ) {

      // Add a list item for each member to the string
      $return .= <<<LISTING
      <li>
         <div>
            <a target="_blank" href="$member[website]">
               <img src="$member[logofilename]">
            </a>
            <h2>
            $count. <a target="_blank" href="$member[website]">$member[name]</a>
            </h2>
            <p class="description">$member[location_name], $member[region_name], $member[country_name]</p>
         </div>
      </li>
LISTING;

      $count++;
   }

   // Close the list
   $return .= "</ul>";

   return $return;

}

/**
 * Central location to create all shortcodes.
 */
function rwf_shortcodes_init() {
   add_shortcode( "list_top10members", "list_top10members_func" );
   add_shortcode( "list_top5bycountry", "list_top5bycountry_func" );
}

add_action( 'init', 'rwf_shortcodes_init' );
?>