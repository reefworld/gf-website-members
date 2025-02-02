<?php
/*
Plugin Name: Active, Inactive & Restricted Green Fins member listing and verification (Hub API)
Plugin URI: https://reef-world.org
Description: Display Green Fins member info on maps, pages and posts sourced from Green Fins Hub API. Requires WP Store Locator v2.2.241 or later.
Version: 2025.1
Author: James Greenhalgh
Author URI: https://jamesgreenblue.com
License: GPLv3
Text Domain: rwf-gf-website-members
*/

defined('ABSPATH') or die('No script kiddies please');

/**
 * Verify a member
 *
 * Outputs a single listing normally accessed by a certificate QR code
 * URL format is https://greenfins.net/verify/?member=XNL29
 */
function single_verify_membership_func()
{
   if(isset( $_GET['member'] )){
      $url_member = sanitize_text_field( $_GET['member'] );
   } else {

      $invalid = <<<INVALID
         <div class="grid-100 tablet-grid-100 mobile-grid-100">
            <section class="gf-operation-listing gf-member-restricted">
            <h3>Missing QR Code</h3>
            <strong>Please scan the QR code for the operation that you would like to verify – this is usually found on their membership certificate.</strong>
            </section>
         </div>
      INVALID;

      return $invalid;
   }

   // Fetch members by country and location 
   $args = array(
      'posts_per_page'  => -1,
      'post_type'    => 'wpsl_stores',
      'post_status'  => array('publish', 'pending', 'draft'),
      'order'        => 'ASC',
      'meta_query'   => array(
         'relation' => 'AND',
         array(
            'key'     => 'wpsl_api_centre_id',
            'value'   => $url_member,
         ),
         array(
            'key'     => 'wpsl_api_membership_type',
            'value'   => array('certified', 'digital'),
         ),
      ),
   );
   $members_query = new WP_Query($args);

   // Figure out what we are outputting

   if (!$members_query->have_posts()) {

      $error = <<<ERROR
         <div class="grid-100 tablet-grid-100 mobile-grid-100">
            <section class="gf-operation-listing gf-member-restricted">
            <h3>No data for this Green Fins Member</h3>
               <p><strong>Digital membership:</strong> You need to complete the self-evaluation and agree to your action plan in <a href="https://hub.greenfins.net/">Green Fins Hub</a>.</p>
                  <br>
               <p><strong>Certified membership:</strong> Your most recent assessment needs to be reviewed by the <a href="https://greenfins.net/contact/#national-teams">national team in your country</a>.</p>
            </section>
         </div>
      ERROR;

      return $error;

   } else {

      $return = "<h3>Green Fins Member found:</h3>";
      $return .= '<div class="grid-container">';
      $return .= gf_member_listing($members_query->posts[0], 1, true);
      $return .= "</div>";

      return $return;

   }
}










/**
 * List Top 10 Members
 *
 * Outputs a list of the top 10 members
 */
function list_top10members_func()
{
   $args = array(
      'numberposts'  => 10,
      'post_type'    => 'wpsl_stores',
      'post_status'  => array('publish'),
      'meta_key'     => 'wpsl_api_latest_score',
      'orderby'      => 'meta_value_num',
      'meta_query'   => array(
         'relation' => 'AND',
         'membership_type' => array(
            'key'     => 'wpsl_api_membership_type',
            'value'   => 'CERTIFIED',
         ),
         'membership_status' => array(
            'key'     => 'wpsl_api_membership_status',
            'value'   => 'active',
         ),
      ),
      'order'        => 'ASC',
   );
   $top_10_members = new WP_Query($args);

   // We're going to return a grid container.
   $return = '<div class="grid-container">';

   // Loop over the returned members
   $i = 0;

   foreach ($top_10_members->posts as $key => $top_10_member) {
      $i++;

      // Add a list item for each member to the string
      $return .= gf_member_listing($top_10_member);

      if (3 == $i) {
         $i = 0;
         $return .= <<<CLEARFIX
               <div class="clear"></div>
            CLEARFIX;
      }
   }

   $return .= "</div>";

   return $return;
}









/**
 * List Top 5 Members by country
 *
 * Outputs a list of the top 5 members for a given country
 */
function list_top5bycountry_func($atts = [])
{
   // Override default attributes with user attributes
   $get_atts = shortcode_atts(
      array(
         'country' => 'Please specify a valid country to display the top 5 list'
      ),
      $atts
   );

/*
 * @todo: Re-write this function using the score info which is now available (use top10 WP_Query with memberlocation filters as a model). 
 * @todo: Abstract the top10 grid container into its own function and reuse here.
*/

   return null;
}









/**
 * List Digital Members
 *
 * Outputs a list of Green Fins operations grouped by country and location
 */
function list_digitalmembers_func($atts = [])
{
   // Fetch digital members
   $args = array(
      'facetwp' => true,
      'posts_per_page'  => -1,
      'post_type'    => 'wpsl_stores',
      'post_status'  => array('publish', 'pending', 'draft'),
      'order'        => 'ASC',
      'meta_query'   => array(
         'relation' => 'AND',
         'membership_type' => array(
            'key'     => 'wpsl_api_membership_type',
            'value'   => 'DIGITAL',
         ),
         'membership_status' => array(
            'key'     => 'wpsl_api_membership_status',
            'value'   => array('active', 'inactive'),
         ),
      ),
      'orderby' => array(
         'membership_status' => 'ASC'
      ),
   );
   $members_query = new WP_Query($args);

   // Figure out what we are outputting

   if (!$members_query->have_posts()) {
      return __("<center>This page doesn't have any active or inactive members to display just yet – please check back soon.</center><br><br>", 'rwf-gf-website-members');
   } else {
      
      // build a distinct list of locations from the returned members
      $locations = [];

      // loop through members and restructure by location
      foreach($members_query->posts as $key => $qmember) {


            // using location as keys, if key is not set yet then create an array for it.
            if(!isset($locations[$qmember->wpsl_country])) {
               $locations[$qmember->wpsl_country] = array(
                  'name' => $qmember->wpsl_country,
                  'slug' => sanitize_title($qmember->wpsl_country),
                  'members' => array()
               );
               
            }
            // add the member under that location
            $locations[$qmember->wpsl_country]['members'][] = $qmember;
      }

      // uncomment to sort the locations alphabetically - for now I like that active locations float to the top
      ksort($locations);

      // We're going to return a listing
      $return = '<div class="country-toc gb-button-wrapper gb-button-wrapper-cta-blue">';

      foreach ($locations as $location) {
         $return .= '<a class="gb-button gb-button-text gb-button-cta-blue" href="#' . $location["slug"] . '">' . $location["name"] . '</a>';
      }

      $return .= '</div>';

      foreach ($locations as $location) {

         $return .= '<h2 class="gb-headline gb-headline-text" id="' . $location["slug"] . '">' . $location["name"] . '</h2>';

         // We're going to return a grid container
         $return .= '<div class="grid-container">';

         $c = count($location['members']);

         $i = 0;
         // Loop over the returned members
         foreach ($location['members'] as $key => $member) {
            $return .= gf_member_listing($member, $c, true);

            $i++;
            if (3 == $i) {
               $i = 0;
               $return .= <<<CLEARFIX
                  <div class="clear"></div>
               CLEARFIX;
            }
         }

         // Close the grid container
         $return .= "</div>";

      }

   }

   return $return;
}









/**
 * List Members by Country or industry
 *
 * Outputs a list of certified and digital Green Fins operations
 */
function list_membersby_func($atts = [])
{
   // Override default attributes with user attributes
   $get_atts = shortcode_atts(
      array(
         'country' => false,
         'industry' => false
      ),
      $atts
   );

   // are we fetching for a country or an industry?
   if ($get_atts['country']) {
      $args_key = 'wpsl_country';
      $args_value = $get_atts['country'];
   } else if ($get_atts['industry']) {
      $args_key = 'wpsl_api_industry';
      $args_value = $get_atts['industry'];
   } else {
      return __("<center>Please specify a country or an industry.</center><br><br>", 'rwf-gf-website-members');
   }

   // Fetch members by country or industry
   $args = array(
      'posts_per_page'  => -1,
      'post_type'    => 'wpsl_stores',
      'post_status'  => array('publish', 'pending', 'draft'),
      'order'        => 'ASC',
      'meta_query'   => array(
         'relation' => 'AND',
         array(
            'key'     => $args_key,
            'value'   => $args_value,
         ),
         'membership_level' => array(
            'key'     => 'wpsl_api_membership_level',
            'value'   => array('1', '2', '3', 'none'),
         ),
         'membership_type' => array(
            'key'     => 'wpsl_api_membership_type',
            'value'   => array('certified', 'digital'),
         ),
         'membership_status' => array(
            'key'     => 'wpsl_api_membership_status',
            'value'   => array('active', 'inactive'),
         ),
      ),
      'orderby' => array(
         'membership_status' => 'ASC',
         'membership_type' => 'ASC',
         'membership_level' => 'ASC',
         'title' => 'ASC'
      ),
   );
   $members_query = new WP_Query($args);

   // Figure out what we are outputting

   if (!$members_query->have_posts()) {
      return __("<center>This country doesn't have any active or inactive members to display just yet – please check back soon.</center><br><br>", 'rwf-gf-website-members');
   } else {
      
      // build a distinct list of locations from the returned active members
      $locations = [];
      $inactive_members = [];

      // loop through members and restructure by location
      foreach($members_query->posts as $key => $qmember) {

         if($qmember->wpsl_api_membership_status == "active"){
            // using location as keys, if key is not set yet then create an array for it.
            if(!isset($locations[$qmember->wpsl_city])) {
               $locations[$qmember->wpsl_city] = array(
                  'name' => $qmember->wpsl_city,
                  'slug' => sanitize_title($qmember->wpsl_city),
                  'members' => array()
               );
               
            }
            // add the member under that location
            $locations[$qmember->wpsl_city]['members'][] = $qmember;
         } else {
            $inactive_members[] = $qmember;
         }
      }

      // uncomment to sort the locations alphabetically - for now I like that active locations float to the top
      //ksort($locations);

      // We're going to return a country listing
      $return = '<div class="country-toc gb-button-wrapper gb-button-wrapper-cta-blue">';

      foreach ($locations as $location) {
         $return .= '<a class="gb-button gb-button-text gb-button-cta-blue" href="#' . $location["slug"] . '">' . $location["name"] . '</a>';
      }

      $return .= '</div>';

      foreach ($locations as $location) {

         $return .= '<h2 class="gb-headline gb-headline-text" id="' . $location["slug"] . '">' . $location["name"] . '</h2>';

         // We're going to return a grid container
         $return .= '<div class="grid-container">';

         $c = count($location['members']);
         
         $i = 0;
         // Loop over the returned members
         foreach ($location['members'] as $key => $member) {
            $return .= gf_member_listing($member, $c);

            $i++;
            if (3 == $i) {
               $i = 0;
               $return .= <<<CLEARFIX
                  <div class="clear"></div>
               CLEARFIX;
            }
         }

         // Close the grid container
         $return .= "</div>";

      }

      if ($inactive_members) {
         $return .= '<h2 class="gb-headline gb-headline-text" id="inactive-members">Inactive Members</h2>';
         $return .= '<p class="has-text-align-center">These members were previously active but have not had a Green Fins assessment or self-evaluation to verify their environmental impact in the last 12 months. An inactive status does not mean they are not operational or that they are not following environmental standards.</p>';

         // We're going to return a grid container
         $return .= '<div class="grid-container">';
   
         $i = 0;
         // Loop over the returned members
         foreach ($inactive_members as $key => $member) {
            $return .= gf_member_listing($member);
   
            $i++;
            if (3 == $i) {
               $i = 0;
               $return .= <<<CLEARFIX
                  <div class="clear"></div>
               CLEARFIX;
            }
         }
   
         // Close the grid container
         $return .= "</div>";
      }
   }

   return $return;
}









/**
 * Display individual member
 *
 * Outputs a member listing
 */
function gf_member_listing($member, $count = 0, $show_eer = false)
{
   $return = "";
   $gf_stamp ='';
   $gf_industry = '';
   $gf_eer = '';

   $url = $member->wpsl_url;
   if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
         $url = "https://" . $url;
   }

   // Not all listings have links
   if ($url === 'http://') { 
      $clean_title = $member->post_title;
      $clean_url = '';
   }
   else {
      $clean_title = '<a target="_blank" href="' . $url . '">' . $member->post_title .'</a>';
      $clean_url = rtrim( str_replace( array( 'http://', 'https://', 'www.' ), array( '', '', '' ), $url ) ,"/");
   }
   
   switch ($member->wpsl_api_membership_level) {
      case "3":
         $member->wpsl_api_membership_level = "bronze";
          break;
      case "2":
         $member->wpsl_api_membership_level = "silver";
          break;
      case "1":
         $member->wpsl_api_membership_level = "gold";
          break;
      case "restricted":
          break;
      default:
         $member->wpsl_api_membership_level = "none";
   }

   // fixing the tags that are output on the member listings
   switch ($member->wpsl_api_membership_status) {
      case "active":
         if ($member->wpsl_api_membership_level == "restricted"){
            $type_level_status = "restricted";
         } else if($member->wpsl_api_membership_type == "digital"){
            $type_level_status = "digital";
            $gf_stamp = '<a target=_blank" href="/about-green-fins/#digital-membership" alt="Green Fins membership stamp" title="Digital Members self-manage their sustainability journey."><img class="stamp" src="/wp-content/gf-stamps/gf_stamp_digital.png"></a>';
         } else {
            $type_level_status = $member->wpsl_api_membership_type . ' ' . $member->wpsl_api_membership_level;
            $gf_stamp = '<a target=_blank" href="/about-green-fins/#certified-membership" alt="Green Fins membership stamp" title="Certified Members receive in-person assessments and training."><img class="stamp" src="/wp-content/gf-stamps/gf_stamp_' . $member->wpsl_api_membership_level . '.png"></a>';
         }
            break;
      case "restricted":
         $type_level_status = "restricted";
            break;
      case "closed":
         $type_level_status = "closed";
            break;
      default:
         $type_level_status = "inactive";
   }

   if($member->wpsl_api_industry) {
      $gf_industry = '<p class="tag industry ' . $member->wpsl_api_industry . '">'. $member->wpsl_api_industry . '</p>';
   }

   if($show_eer == true && $member->wpsl_api_external_eco_recognition == "eligible") {
      $gf_eer = '<a target="_blank" href="https://greenfins.net/external-eco-recognition"><p class="tag eer">Eligible for External Eco Recognition</p></a>';
   }

   if($count == 1) {
      $push = 'push-33';
   }

   // Add a list item for each member to the string
   $return .= <<<LISTING
         <div class="$push grid-33 tablet-grid-33 mobile-grid-100">
            <section class="gf-operation-listing gf-member-$member->wpsl_api_membership_status gf-member-$member->wpsl_api_membership_type gf-member-$member->wpsl_api_membership_level gf-member-eer-$member->wpsl_api_external_eco_recognition">
               <div class="logo-container">
                  <img class="logo" src="$member->wpsl_api_logo_filename">
                  $gf_stamp
               </div>

               <h2>
                  $clean_title
               </h2>

               <a target="_blank" href="https://greenfins.net/verify?member=$member->wpsl_api_centre_id"><p class="tag typelevelstatus">$type_level_status member</p></a>
               $gf_industry
               $gf_eer

               <p>$member->wpsl_city, $member->wpsl_country</p>
               <p class="contact">Contact info:</p>
                  <p class="contact-item"><a target="_blank" href="$url">$clean_url</a></p>
                  <p class="contact-item"><a target="_blank" href="mailto:$member->wpsl_email?subject=I found you on the Green Fins website and would like more information">$member->wpsl_email</a></p>
            </section>
         </div>
      LISTING;

   return $return;
}









/**
 * Populate Green Fins Members into posts and post meta
 *
 *
 */
function rwf_gf_fetch_members_as_posts_func()
{
   // For logging output (including timing how long it takes to execute)
   $start_microtime = microtime(true);
   $trace_id = uniqid('id:');
   $member_record_created_count = 0;
   $member_record_updated_count = 0;
   $member_logo_updated_count = 0;
   rwf_write_log("[" . $trace_id . "] started, please wait");

   function rwf_hub_fetch($description, $url, $trace_id)
   {
      $args = array(
         'headers' => array(
             'Authorization' => get_option('rwf_hub_key')
         )
      );

      $response = wp_remote_get(esc_url_raw(get_option('rwf_hub_endpoint') . $url), $args);
      // Handle the case when something has been misconfigured
      if (is_wp_error($response)) {
         rwf_write_log("[" . $trace_id . "] error – Unable to fetch the " . $description . " (error message: " . $response->get_error_message() . ")");
         exit;
      }

      $api_response = json_decode(wp_remote_retrieve_body($response), true);
      // Handle the case when the call fails
      if ($response["response"]["code"] == 0) {
         rwf_write_log("[" . $trace_id . "] error – Unable to fetch the " . $description . " (error message: " . $response["response"]["message"] . ")");
         exit;
      }
      
      // Test accounts follow a convention of (brackets), remove these from the response
      foreach ($api_response as $element => $values) {
         if (str_starts_with($values["name"], '(') && str_ends_with($values["name"], ')')){
            unset($api_response[$element]);
         }
      }

      return $api_response;
   }

   $url = '/operations';
   $members = rwf_hub_fetch('members list', $url, $trace_id);

   // We've built the members list, time to make some posts
   // Try to disable the time limit to prevent timeouts.
   @set_time_limit(0);

   // Include dependencies - we are looping and running outside the context of /wp-admin/ 
   require_once(ABSPATH . 'wp-admin/includes/file.php');
   $member_logo_filepath = ABSPATH . 'wp-content/gf-member-logos/';
   $existing_member_logos = scandir($member_logo_filepath);

   // Seed the database
   foreach ($members as $member) {

      // Look for existing centre records
      $args = array(
         'post_type' => 'wpsl_stores',
         'meta_key' => 'wpsl_api_centre_id', // This meta key is created using the wpsl filter custom_meta_box_fields() below
         'meta_value' => $member['id'],
         'post_status' => 'any',
         'posts_per_page' => -1
      );
      $wpsl_post_exists = get_posts($args);
      
      // Make sure we set the correct post status.
      if ($member['membership_status'] == 'ACTIVE') {
         $post_status = 'publish';
      } else {
         $post_status = 'pending';
      }

      if ($wpsl_post_exists) {
         // update the existing member record
         $wpsl_post_id = $wpsl_post_exists[0]->ID;
         $post = array(
            'ID'           => $wpsl_post_id,
            'post_type'    => 'wpsl_stores',
            'post_status'  => $post_status,
            'post_title'   => $member['name'],
            'post_content' => "This record is maintained in Green Fins Hub, to edit it please visit https://hub.greenfins.net"
         );
         $member_record_updated_count++;
      } else {
         // make a new post for the member
         $post = array(
            'post_type'    => 'wpsl_stores',
            'post_status'  => $post_status,
            'post_title'   => $member['name'],
            'post_content' => "This record is maintained in Green Fins Hub, to edit it please visit https://hub.greenfins.net"
         );
         $member_record_created_count++;
      }

      $post_id = wp_insert_post($post);

      if ($post_id) {
         // Add or update the post meta with the member's info and fetch a local copy of the logo
         
         if ($member['membership_type'] == "DIGITAL" && $member['membership_status'] == 'ACTIVE') {
            $term_id = array( 414 ); //overwrite category with digital-member

         } else if ($member['membership_type'] == "CERTIFIED" && $member['membership_status'] == 'ACTIVE') {

            switch ($member['membership_level']) {
               case "Certified Bronze Member":
                  $member['membership_level'] = "3";
                  $term_id = array( 417 ); //overwrite category with certified-bronze-member
                   break;
               case "Certified Silver Member":
                  $member['membership_level'] = "2";
                  $term_id = array( 416 ); //overwrite category with certified-silver-member
                   break;
               case "Certified Gold Member":
                  $member['membership_level'] = "1";
                  $term_id = array( 415 ); //overwrite category with certified-gold-member
                   break;
               case "Restricted":
                  $member['membership_level'] = "restricted";
                  $term_id = array( 445 ); ////overwrite category with restricted
                   break;
               default:
                  $member['membership_level'] = "none";
                  $term_id = array(); //unset category
            }

         } else {
            $term_id = array(); //unset category for inactive memebrs
            switch ($member['membership_level']) {
               case "Restricted":
                  $member['membership_level'] = "restricted";
                   break;
               default:
                  $member['membership_level'] = "none";
            }
         }

         // Set file destination.
         $member_logo_basename = basename($member['logo_url']);

         if (!in_array($member_logo_basename, $existing_member_logos)) { // also covers off default.jpg
            // Not an existing image, so lets fetch it
            $tmp_file = download_url($member['logo_url']);

            // If error storing temporarily, return the error.
            if (is_wp_error($tmp_file)) {
               rwf_write_log("[" . $trace_id . "] error – Unable to download: " . $member['logo_url'] . " with error " . $tmp_file->get_error_message());
            } else {
               // Copy the file to the final destination and delete temporary file.
               copy($tmp_file, $member_logo_filepath . $member_logo_basename);
               $member_logo_updated_count++;
            }
            @unlink($tmp_file);
         } 

         if ($member['email'] == "hub+please-change-me@greenfins.net") { // email is a required field in Green Fins Hub and was missing on some migrated records
            $member['email'] = "";
         }

         if ($member['latest_score'] == "0") { // update_post_meta() doesn't support saving a 0 value
            $member['latest_score'] = "0x0"; 
         }

         // Build the array for the store post meta
         $postmetas = array(
            'api_centre_id'                     => $member['id'],
            'api_logo_filename'                 => WP_CONTENT_URL . '/gf-member-logos/' . $member_logo_basename, //retrofitting local hosting of images, we used to hotlink from the Portal
            'api_industry'                      => $member['industry'],
            'api_membership_type'               => strtolower($member['membership_type']),
            'api_membership_status'             => strtolower($member['membership_status']),
            'api_membership_level'              => $member['membership_level'],
            'api_latest_score'                  => $member['latest_score'],
            'api_external_eco_recognition'      => $member['external_eco_recognition'] ? "eligible" : "not",
            'address'                           => $member['address'],
            'address2'                          => "",
            'city'                              => $member["location"]["name"],
            'state'                             => $member["region"]["name"],
            'country'                           => $member["country"]["name"],
            'lat'                               => $member['lat'],
            'lng'                               => $member['lng'],
            'phone'                             => "",
            'url'                               => $member['website'],
            'email'                             => $member['email']
         );

         foreach ($postmetas as $meta => $value) {
            if (isset($value) && !empty($value)) {
               update_post_meta($post_id, 'wpsl_' . $meta, $value);
            }
         }

         //set the wpsl_store_category for map display and filtering
         wp_set_post_terms($post_id, $term_id, 'wpsl_store_category');
      }
   }


   // Tidy up archived operations, if they have been archived at source the post date will be in the past
   $args = array(
      'post_type' => 'wpsl_stores',
      'post_status' => 'any',
      'posts_per_page' => -1,
      'date_query' => array(
         'before' => date('Y-m-d', strtotime('-1 hour')) // if only a date was passed in it would be converted to 00:00:00 on that date – adding a time ensures the date is inclusive
      )
   );
   $archived_members_exist = get_posts($args);

   if ($archived_members_exist) {
      foreach ($archived_members_exist as $member) {
         wp_trash_post( $member->ID );
      }
   }
   

   if (WP_CONTENT_URL == "https://greenfins.net/wp-content") {
      // We are on production so send heartbeat for status.reef-world.org
      wp_remote_head( get_option('rwf_heartbeat_key') );
   }

   rwf_write_log("[" . $trace_id . "] executed in " . (microtime(true) - $start_microtime) . " seconds (" . $member_record_created_count . " created, " . $member_record_updated_count . " updated, " . $member_logo_updated_count . " logo(s) updated)");
   exit;
}











/**
 * Logging Helper Function
 *
 * 
 */
function rwf_write_log($log_entry)
{
   $log_file_path =  plugin_dir_path(__FILE__) . 'logs/rwf-gf-website-members.log.' . date('Y-m-d') . '.log';
   $log_file = fopen($log_file_path, "a");
   fwrite($log_file, date('Y-m-d H:i:s') . ' : ' . $log_entry . PHP_EOL);
   fclose($log_file);
}











/**
 * Admin menu for the API key and endpoint management
 */
function rwf_gf_members_api_plugin_menu_func()
{
   add_submenu_page(
      "options-general.php",                    // Which menu parent
      "Green Fins Members via Hub API Plugin Settings",    // Page title
      "Green Fins Hub API",                    // Menu title
      "gf_trigger_api",                            // Minimum capability (manage_options is an easy way to target administrators)
      "rwf_gf_members_api_plugin",                 // Menu slug
      "rwf_gf_members_api_plugin_options"          // Callback that prints the markup
   );
}

// Print the markup for the page
function rwf_gf_members_api_plugin_options()
{
   if (!current_user_can("gf_trigger_api")) {
      wp_die(__("You do not have sufficient permissions to access this page."));
   }

   if (isset($_GET['status']) && $_GET['status'] == 'success') {
?>
      <div id="message" class="updated notice is-dismissible">
         <p><?php _e("Settings updated!", "rwf-gf-website-members"); ?></p>
         <button type="button" class="notice-dismiss">
            <span class="screen-reader-text"><?php _e("Dismiss this notice.", "rwf-gf-website-members"); ?></span>
         </button>
      </div>
   <?php
   }

   ?>
   <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">

      <input type="hidden" name="action" value="update_rwf_gf_members_api_plugin_settings" />

      <h1><?php _e("Green Fins Members via Hub API Plugin Settings", "rwf-gf-website-members"); ?></h1>

      <p>
         This plugin displays Green Fins member information within maps, on pages and posts (using shortcodes).
      </p>
      <p>
         Data is fetched sourced from Green Fins Hub API and stored in WP Store Locator (requires WP Store Locator v2.2.236 or later).
      </p>
      <p>
         <strong>For help with this plugin, please contact James Greenhalgh (james@reef-world.org)</strong>
      </p>
      <p>
         Available shortcodes:
      <ul>
         <li><code>[list_top10members]</code> displays the Top 10 members</li>
         <!-- <li><code>[list_top5bycountry country=""]</code> displays the Top 5 members for the specified country.</li> -->
         <li><code>[list_membersby country=""]</code> displays the active and inactive members for the specified country grouped by location.</li>
         <li><code>[list_membersby industry="liveaboard"]</code> displays the active and inactive members for the specified industry grouped by location.</li>
         <li><code>[list_digitalmembers]</code> displays the active and inactive digital members grouped by location.</li>
      </ul>
      </p>

      <br>

      <h2><?php _e("Plugin Settings", "rwf-gf-website-members"); ?></h2>

      <table class="form-table" role="presentation">
         <tr>
            <th scope="row"><label>
                  <?php _e("Hub Endpoint:", "rwf-gf-website-members"); ?></label>
            </th>
            <td>
               <input class="" type="text" name="rwf_hub_endpoint" value="<?php echo get_option('rwf_hub_endpoint'); ?>" />
            </td>
         </tr>
         <tr>
            <th scope="row"><label>
                  <label><?php _e("Hub Key:", "rwf-gf-website-members"); ?></label>
            </th>
            <td>
               <input class="" type="text" name="rwf_hub_key" value="<?php echo get_option('rwf_hub_key'); ?>" />
            </td>
         </tr>
         <tr>
            <th scope="row"><label>
                  <label><?php _e("Heartbeat Key:", "rwf-gf-website-members"); ?></label>
            </th>
            <td>
               <input class="" type="text" name="rwf_heartbeat_key" value="<?php echo get_option('rwf_heartbeat_key'); ?>" />
            </td>
         </tr>
      </table>

      <br>

      <input class="button button-primary" type="submit" value="<?php _e("Update Plugin Settings", "rwf-gf-website-members"); ?>" />

   </form>

   <br><br>

   <h1>Trigger</h2>
      <p>The refresh function is configured to run hourly – if needed the script may be manually triggered below. <span style="color:red">Please refresh the log immediately after a manual trigger and wait at least 5 minutes before triggering again.</span></p>
      <div style="margin-top: 15px; border: 1px solid #ccc; padding: 0 20px; width: 90%;">
         <p>Next <strong>Hub</strong> refresh is scheduled for: 
            <strong>
               <?php
               $var = wp_get_scheduled_event("admin_post_trigger_rwf_gf_fetch_members_as_posts");               
               if(!empty($var)) {
                  echo date('Y-m-d H:i:s', $var->timestamp) . ' UTC';
               } else {
                  echo "Not scheduled, please investigate";
               }
               ?>
            </strong>
         </p>
         <p>Manual refresh: <a href="/wp-admin/admin-post.php?action=trigger_rwf_gf_fetch_members_as_posts">rwf_gf_fetch_members_as_posts_func()</a> ( <a href="javascript:window.location.reload();">refresh log</a> ) </p>
      </div>
      <p>(server time: <strong><?php echo date('H:i:s'); ?> UTC</strong>)</p>
      <h1>Debug Log</h2>
         <p>Log output for <strong><?php echo date('Y-m-d'); ?></strong>. To see logs for previous days please look in the plugin's /logs/ folder. </p>
         <div style="margin-top: 20px; padding: 10px 20px; height: 500px; width: 90%; overflow-y: scroll; background: #fff;">
            <pre><?php
                  $log_file_path =  plugin_dir_path(__FILE__) . 'logs/rwf-gf-website-members.log.' . date('Y-m-d') . '.log';
                  echo file_get_contents($log_file_path);
                  ?></pre>
         </div>

      <?php
   }

   function rwf_gf_members_api_plugin_handle_save()
   {
      // Get the options that were sent
      $hub_key = (!empty($_POST["rwf_hub_key"])) ? $_POST["rwf_hub_key"] : NULL;
      $hub_endpoint = (!empty($_POST["rwf_hub_endpoint"])) ? $_POST["rwf_hub_endpoint"] : NULL;

      $heartbeat = (!empty($_POST["rwf_heartbeat_key"])) ? $_POST["rwf_heartbeat_key"] : NULL;

      // API Validation to go here

      // Update the values
      update_option("rwf_hub_key", $hub_key, TRUE);
      update_option("rwf_hub_endpoint", $hub_endpoint, TRUE);
      update_option("rwf_heartbeat_key", $heartbeat, TRUE);

      // Redirect back to settings page
      $redirect_url = get_bloginfo("url") . "/wp-admin/options-general.php?page=rwf_gf_members_api_plugin&status=success";
      header("Location: " . $redirect_url);
      exit;
   }











   /**
    * Central location to initiate all shortcodes and actions. This ensures that the plugin doesn't hurt page load times.
    */
   function rwf_gf_members_api_init()
   {
      add_action("admin_menu", "rwf_gf_members_api_plugin_menu_func");
      add_action('admin_post_update_rwf_gf_members_api_plugin_settings', 'rwf_gf_members_api_plugin_handle_save');
      if (!wp_next_scheduled('admin_post_trigger_rwf_gf_fetch_members_as_posts')) {
         wp_schedule_event(time(), 'hourly', 'admin_post_trigger_rwf_gf_fetch_members_as_posts');
      }

      // 'admin_post_' allows us to trigger the function from the admin area without needing WP Crontrol to be installed. 
      add_action('admin_post_trigger_rwf_gf_fetch_members_as_posts', 'rwf_gf_fetch_members_as_posts_func');

      // Used throughout the find-a-member pages
      add_shortcode("list_top10members", "list_top10members_func");
      add_shortcode("list_top5bycountry", "list_top5bycountry_func");
      add_shortcode("list_membersby", "list_membersby_func");
      add_shortcode("list_digitalmembers", "list_digitalmembers_func");
      add_shortcode("single_verify_membership", "single_verify_membership_func");
   }
   add_action('init', 'rwf_gf_members_api_init');

?>