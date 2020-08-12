<?php
/*
Plugin Name: Get Green Fins member info from API
description: Add Green Fins member information to pages and posts using shortcodes (cached using WP transients)
Version: 1.0
Author: James Greenhalgh
License: GPLv2 or later
Text Domain: rwf-gf-members-api
*/

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function list_top10members_func( $atts ) {

   $top10members = get_transient( 'get_top10members' );
   if ( false === $top10members ) {
       // Transient expired, refresh the data

       $key = 'iQZ35gareSKwo9YfHVJyvpXPhE8LA7';
       $url = 'https://portal-dev.greenfins.net/api/v1/top-10-members?key=' . $key;
       $data = wp_remote_get($url);
       $response = wp_remote_retrieve_body($data);
       $json = json_decode($response, true);

       set_transient( 'get_top10members', $json, DAY_IN_SECONDS );
       $top10members = get_transient( 'get_top10members' );
   }
   // Use top10members as you will
   
   // Handle the case when there are no members or the API is malfunctioning
   if ( empty($top10members) || $top10members["success"] == 0) {
      delete_transient( 'get_top10members' );
      return "<strong>" . __("No members to show, something went wrong", 'rwf-gf-members-api') . "</strong>";
   }

   // We're going to return a string. First, we open a list.
   $return = "<ul>";

   // Loop over the returned members
   foreach( $top10members['data'] as $member ) {

      // Add a list item for each member to the string
      $return .= "<li>{$member['name']}</li>";

   }

   // Close the list
   $return .= "</ul>";

   return $return;

}
add_shortcode( "list_top10members", "list_top10members_func" );

?>