<?php
/*
Plugin Name: Display Green Fins member info from Portal API
Plugin URI: https://reef-world.org
Description: Display Green Fins member information within pages and posts using shortcodes from the Members API
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
      wp_mail("it@reef-world.org", "Alert: Error triggered on Green Fins Wordpress site", "Error: Unable to fetch data from Portal API via 'rwf_get_top10members' in list_top10members_func(). End user has seen an error message, please investigate.");
      return "<strong>" . __("Error: Unable to fetch data from Portal API.", 'rwf-gf-members-api') . "</strong>";
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

       set_transient( 'rwf_get_countries', $json, 4 * HOUR_IN_SECONDS );
       $countries = get_transient( 'rwf_get_countries' );
   }
   // Handle the case when there are no countries or the API is malfunctioning
   if ( empty($countries) || $countries["success"] == 0) {
      delete_transient( 'rwf_get_countries' );
      wp_mail("it@reef-world.org", "Alert: Error triggered on Green Fins Wordpress site", "Error: Unable to fetch data from Portal API via 'rwf_get_countries' in list_top5bycountry_func(). End user has seen an error message, please investigate.");
      return "<strong>" . __("Error: Unable to fetch data from Portal API.", 'rwf-gf-members-api') . "</strong>";
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
      wp_mail("it@reef-world.org", "Alert: Error triggered on Green Fins Wordpress site", "Error: Unable to fetch data from Portal API via 'rwf_get_top5bycountry_' in list_top5bycountry_func(). End user has seen an error message, please investigate.");
      return "<strong>" . __("Error: Unable to fetch data from Portal API.", 'rwf-gf-members-api') . "</strong>";
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

         set_transient( 'rwf_get_countries', $json, 4 * HOUR_IN_SECONDS );
         $countries = get_transient( 'rwf_get_countries' );
      }
      // Handle the case when there are no countries or the API is malfunctioning
      if ( empty($countries) || $countries["success"] == 0) {
         delete_transient( 'rwf_get_countries' );
         wp_mail("it@reef-world.org", "Alert: Error triggered on Green Fins Wordpress site", "Error: Unable to fetch data from Portal API via 'rwf_get_countries' in list_membersbylocation_func(). End user has seen an error message, please investigate.");
         return "<strong>" . __("Error: Unable to fetch data from Portal API.", 'rwf-gf-members-api') . "</strong>";
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
         wp_mail("it@reef-world.org", "Alert: Error triggered on Green Fins Wordpress site", "Error: Unable to fetch data from Portal API via 'rwf_get_regionsbycountry_' in list_membersbylocation_func(). End user has seen an error message, please investigate.");
         return "<strong>" . __("Error: Unable to fetch data from Portal API.", 'rwf-gf-members-api') . "</strong>";
      }

   
   // build an array of the locations in all of the specified country's regions
   $regionsbycountry = $regionsbycountry['data'];
   $locations = [];

   foreach ( $regionsbycountry as $region ){
      $locations_transient_name = 'rwf_get_locationsbyregion_' . $region['name'];
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
            wp_mail("it@reef-world.org", "Alert: Error triggered on Green Fins Wordpress site", "Error: Unable to fetch data from Portal API via 'rwf_get_locationsbyregion_' in list_membersbylocation_func(). End user has seen an error message, please investigate.");
            return "<strong>" . __("Error: Unable to fetch data from Portal API.", 'rwf-gf-members-api') . "</strong>";
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
         wp_mail("it@reef-world.org", "Alert: Error triggered on Green Fins Wordpress site", "Error: Unable to fetch data from Portal API via 'rwf_get_membersbylocation_' in list_membersbylocation_func(). End user has seen an error message, please investigate.");
         return "<strong>" . __("Error: Unable to fetch data from Portal API.", 'rwf-gf-members-api') . "</strong>";
      }

   //figure out what we are outputting
   $display_average_score = $get_atts['display_average_score'];
   if($display_average_score){

      $average_lookup = array_column($locations,"average","name");
   
      if(isset($average_lookup[$location_input])){
         $average_score = $average_lookup[$location_input];
      }

      $return = <<<SCORE
         <div>
            <p class="score">Average $average_score score for $location_input</p>
         </div>
SCORE;
   }
   else {
      // Display the members list.
      $return = "<ul>";

      // Loop over the returned members
      foreach( $membersbylocation['data'] as $member ) {
         if($member['status'] == 'active' || $member['status'] == 'inactive') {
            // Add a list item for each member to the string
            $return .= <<<LISTING
            <li>
               <div>
                  <a target="_blank" href="$member[website]">
                     <img src="$member[logofilename]" class="$member[status]">
                  </a>
                  <h2>
                     <a target="_blank" href="$member[website]">$member[name]</a>
                  </h2>
                  <p class="description">$member[location_name], $member[region_name], $member[country_name]</p>
               </div>
            </li>
LISTING;
         }
      }
      // Close the list
      $return .= "</ul>";
   }

   return $return;

}










/**
 * Populate centres into posts and post meta, the tricky bit is updating the existing records after building an array  - TESTING AREA FOR POPULATING POSTS INTO WP FOR DIVE CENTRES!!!!
 *
 * Love this
 */
function populate_centres_func() {

   //load the countries list
   $countries = get_transient( 'rwf_get_countries' );
      if ( false === $countries ) {
         // Transient expired, refresh the data

         $url = get_option('rwf_api_endpoint') . '/countries?key=' . get_option('rwf_api_key');
         $response = wp_remote_retrieve_body(wp_remote_get($url));
         $json = json_decode($response, true);

         set_transient( 'rwf_get_countries', $json, 4 * HOUR_IN_SECONDS );
         $countries = get_transient( 'rwf_get_countries' );
      }
      // Handle the case when there are no countries or the API is malfunctioning
      if ( empty($countries) || $countries["success"] == 0) {
         delete_transient( 'rwf_get_countries' );
         wp_mail("it@reef-world.org", "Alert: Error triggered on Green Fins Wordpress site", "Error: Unable to fetch data from Portal API via 'rwf_get_countries' in populate_centres_func(). End user has seen an error message, please investigate.");
         return "<strong>" . __("Error: Unable to fetch data from Portal API.", 'rwf-gf-members-api') . "</strong>";
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
            wp_mail("it@reef-world.org", "Alert: Error triggered on Green Fins Wordpress site", "Error: Unable to fetch data from Portal API via 'rwf_get_regionsbycountry_' in populate_centres_func(). End user has seen an error message, please investigate.");
            return "<strong>" . __("Error: Unable to fetch data from Portal API.", 'rwf-gf-members-api') . "</strong>";
         }

      //flatten
      $regionsbycountry = $regionsbycountry['data'];
      //append the new locations onto any existing to build the array with each loop
      $regions = array_merge($regions, $regionsbycountry);
   }

   foreach ( $regions as $region ){
      $locations_transient_name = 'rwf_get_locationsbyregion_' . $region['name'];
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
            wp_mail("it@reef-world.org", "Alert: Error triggered on Green Fins Wordpress site", "Error: Unable to fetch data from Portal API via 'rwf_get_locationsbyregion_' in populate_centres_func(). End user has seen an error message, please investigate.");
            return "<strong>" . __("Error: Unable to fetch data from Portal API.", 'rwf-gf-members-api') . "</strong>";
         }

      //flatten
      $locationsbyregion = $locationsbyregion['data'];
      //append the new locations onto any existing to build the array with each loop
      $locations = array_merge($locations, $locationsbyregion);
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
            wp_mail("it@reef-world.org", "Alert: Error triggered on Green Fins Wordpress site", "Error: Unable to fetch data from Portal API via 'rwf_get_membersbylocation_' in populate_centres_func(). End user has seen an error message, please investigate.");
            return "<strong>" . __("Error: Unable to fetch data from Portal API.", 'rwf-gf-members-api') . "</strong>";
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
         'meta_key' => 'wpsl_api_centre_id', //this meta key is created using a wpsl filter in the template functions.php
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
            'post_content' => $member['guid']              
         );
      } else {
         // make a new post for the member
         $post = array (
            'post_type'    => 'wpsl_stores',
            'post_status'  => $post_status,
            'post_title'   => $member['name'],
            'post_content' => $member['guid']              
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
   return "<p>Success</p>";
   //exit;

/*DEBUG OUTPUT!

   // We're going to return a string. First, we open a list.
   $return = "<ul>";

   // Loop over the returned members
   $count = 1;
   foreach( $members as $member ) {

      // Add a list item for each member to the string
      $return .= <<<LISTING
      <li>
         <div>
            <h2>
            $count. <a target="_blank" href="$member[website]">$member[name], $member[status]</a>
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
*/
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
 * Use a custom template from the map to suit our design
 */
function custom_templates( $templates ) {

    /**
     * The 'id' is for internal use and must be unique ( since 2.0 ).
     * The 'name' is used in the template dropdown on the settings page.
     * The 'path' points to the location of the custom template,
     * in this case the folder of your active theme.
     */
    $templates[] = array (
        'id'   => 'rwfgfmaponly',
        'name' => 'RWF Green Fins map only template',
        'path' => get_stylesheet_directory() . '/' . 'wpsl-templates/rwf-gf-map-only.php',
    );

    return $templates;
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
 * Central location to create all shortcodes. This ensures that the plugin doesn't hurt page load times.
 */
function rwf_gf_members_api_init() {
   add_action( "admin_menu", "rwf_gf_members_api_plugin_menu_func" );

   add_shortcode( "list_top10members", "list_top10members_func" );
   add_shortcode( "list_top5bycountry", "list_top5bycountry_func" );
   add_shortcode( "list_membersbylocation", "list_membersbylocation_func" );
   add_shortcode( "populate_centres", "populate_centres_func" );

   // map filters
   add_filter( 'wpsl_meta_box_fields', 'custom_meta_box_fields' );
   add_filter( 'wpsl_js_settings', 'custom_js_settings' );
   add_filter( 'wpsl_templates', 'custom_templates' );
   add_filter( 'wpsl_store_meta', 'custom_store_meta', 10, 2 );
   add_filter( 'wpsl_info_window_template', 'custom_info_window_template' );
   add_filter( 'wpsl_frontend_meta_fields', 'custom_frontend_meta_fields' );
}
add_action( 'init', 'rwf_gf_members_api_init' );


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
         This plugin displays Green Fins member information on pages and posts using shortcodes from the Members API (cached for 4 hours using <a href="<?php echo get_bloginfo("url") . "/wp-admin/tools.php?page=pw-transients-manager&s=rwf_get_"; ?>" target="_new">transients</a>).
      </p>
      <p>
         <strong>For help with this plugin, please contact James Greenhalgh (james@reef-world.org)</strong>
      </p>
      <p>
         Available shortcodes:
         <ul>
            <li><code>[list_top10members]</code></li>
            <li><code>[list_top5bycountry country=""]</code></li>
            <li><code>[list_membersbylocation country="" location=""]</code></li>
            <li><code>[list_membersbylocation country="" location="" display_average_score="true"]</code></li>
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

add_action( 'admin_post_update_rwf_gf_members_api_plugin_settings', 'rwf_gf_members_api_plugin_handle_save' );
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

?>