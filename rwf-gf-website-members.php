<?php
/*
Plugin Name: Display Green Fins members from Assessor Portal API
Plugin URI: https://reef-world.org
Description: Display Green Fins member information within maps, pages and posts from the Members API. Requires WP Store Locator v2.2.233 or later.
Version: 3.7
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
      return '<center><p style="color:red"><strong>Invalid QR – please scan the membership certificate again</strong></p></center>';
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
      return __('<center><p style="color:red"><strong>No data for this Green Fins Member. <br><br>(This feature currently only works for Green Fins Digital Members. The ability to verify Green Fins Certified Members is coming in Q4 2022. Please check back soon!)</strong></p></center>', 'rwf-gf-website-members');
   } else {

      $member = $members_query->posts[0];

      $return = "";

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
      
      // fixing the tags that are output on the member listings
      switch ($member->wpsl_api_membership_status) {
         case "active":
            if($member->wpsl_api_membership_type == "digital"){
               $type_level_status = "digital";
            } else {
               $type_level_status = $member->wpsl_api_membership_type . ' ' . $member->wpsl_api_membership_level;
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
   
      // Add a list item for each member to the string
      $return .= <<<LISTING
            <p><strong>Green Fins Member found:</strong></p>
            <div class="grid-100 tablet-grid-100 mobile-grid-100">
               <section class="gf-operation-listing gf-member-$member->wpsl_api_membership_status gf-member-$member->wpsl_api_membership_type gf-member-$member->wpsl_api_membership_level">
                  <div class="grid-container grid-parent">
                     <div class="gf-operation-listing-image grid-20 tablet-grid-20 mobile-grid-100">
                           <img src="$member->wpsl_api_logo_filename">
                     </div>
   
                     <div class="gf-operation-listing-meta grid-80 tablet-grid-80 mobile-grid-100">
                        <h2>
                           $clean_title
                        </h2>
   
                        <p class="tag typelevelstatus">$type_level_status</p>
                        <p class="tag industry $member->wpsl_api_industry">$member->wpsl_api_industry</p>
   
                           <p><strong>Address:</strong></p>
                              <p class="contact-item">$member->wpsl_address</p>
                              <p class="contact-item">$member->wpsl_city</p>
                              <p class="contact-item">$member->wpsl_country</p>
                           <p class="contact">Contact info:</p>
                              <p class="contact-item"><a target="_blank" href="$url">$clean_url</a></p>
                              <p class="contact-item"><a target="_blank" href="mailto:$member->wpsl_email?subject=I found you on the Green Fins website and would like more information">$member->wpsl_email</a></p>
                              <p class="contact-item"><a target="_blank" href="tel:$member->wpsl_phone">$member->wpsl_phone</a></p>
                     </div>
                  </div>
               </section>
            </div>
         LISTING;
   
      return $return;

   }
}










/**
 * List Top 10 Members
 *
 * Outputs a list of the top 10 members from the Portal API, cached for 4 hours using WP transients
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
   $return = "<div class=\"grid-container\">";

   // Loop over the returned members
   $i = 0;
   $n = 0;
   foreach ($top_10_members->posts as $key => $top_10_member) {
      $i++;
      $n++;

      switch ($top_10_member->wpsl_api_membership_level) {
         case "3":
            $top_10_member->wpsl_api_membership_level = "bronze";
             break;
         case "2":
            $top_10_member->wpsl_api_membership_level = "silver";
             break;
         case "1":
            $top_10_member->wpsl_api_membership_level = "gold";
             break;
         default:
            $top_10_member->wpsl_api_membership_level = "none";
      }

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
 * Outputs a list of the top 5 members for a given country from the Portal API, cached for 4 hours using WP transients
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
 * Outputs a list of Green Fins Digital Members grouped by country and location
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
         if(!isset($locations[$qmember->wpsl_country])) $locations[$qmember->wpsl_country] = array();

         // add the member under that location
         $locations[$qmember->wpsl_country][] = $qmember;
      }

      // uncomment to sort the locations alphabetically - for now I like that active locations float to the top
      ksort($locations);

      // We're going to return a country
      $return = "";

      foreach ($locations as $location) {

         $return .= '<h2 class="gb-headline gb-headline-text">' . $location[0]->wpsl_country . '</h2>';

         // We're going to return a grid container
         $return .= '<div class="grid-container">';

         $i = 0;
         // Loop over the returned members
         foreach ($location as $key => $member) {
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
 * List Members by Country or industry
 *
 * Outputs a list of certified and digital Green Fins members – intended to be used for active countries
 */
function list_membersbycountry_func($atts = [])
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
      
      // build a distinct list of locations from the returned members
      $locations = [];

      // loop through members and restructure by location
      foreach($members_query->posts as $key => $qmember) {

         // using location as keys, if key is not set yet then create an array for it.
         if(!isset($locations[$qmember->wpsl_city])) {
            $locations[$qmember->wpsl_city] = array();
         }

         switch ($qmember->wpsl_api_membership_level) {
            case "3":
               $qmember->wpsl_api_membership_level = "bronze";
                break;
            case "2":
               $qmember->wpsl_api_membership_level = "silver";
                break;
            case "1":
               $qmember->wpsl_api_membership_level = "gold";
                break;
            default:
               $qmember->wpsl_api_membership_level = "none";
         }

         // add the member under that location
         $locations[$qmember->wpsl_city][] = $qmember;
      }

      // uncomment to sort the locations alphabetically - for now I like that active locations float to the top
      //ksort($locations);

      // We're going to return a country
      $return = "";

      foreach ($locations as $location) {

         $return .= '<h2 class="gb-headline gb-headline-text">' . $location[0]->wpsl_city . '</h2>';

         // We're going to return a grid container
         $return .= '<div class="grid-container">';

         $i = 0;
         // Loop over the returned members
         foreach ($location as $key => $member) {
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
function gf_member_listing($member)
{
   $return = "";

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
   
   // fixing the tags that are output on the member listings
   if ($member->wpsl_api_membership_status == "active"){
      if($member->wpsl_api_membership_type == "digital"){
         $type_level_status = "digital";
         $gf_stamp = '<a target=_blank" href="/about-green-fins/#digital-membership" alt="Green Fins membership stamp" title="Digital Members self-manage their sustainability journey."><img class="stamp" src="/wp-content/gf-stamps/gf_stamp_digital.png"></a>';
      } else {
         $type_level_status = $member->wpsl_api_membership_type . ' ' . $member->wpsl_api_membership_level;
         $gf_stamp = '<a target=_blank" href="/about-green-fins/#certified-membership" alt="Green Fins membership stamp" title="Certified Members receive in-person assessments and training."><img class="stamp" src="/wp-content/gf-stamps/gf_stamp_' . $member->wpsl_api_membership_level . '.png"></a>';
      }

   } else {
      $type_level_status = "inactive";
   }

   if($member->wpsl_api_industry) {
      $gf_industry = '<p class="tag industry ' . $member->wpsl_api_industry . '">'. $member->wpsl_api_industry . '</p>';
   }

   // Add a list item for each member to the string
   $return .= <<<LISTING
         <div class="grid-33 tablet-grid-33 mobile-grid-100">
            <section class="gf-operation-listing gf-member-$member->wpsl_api_membership_status gf-member-$member->wpsl_api_membership_type gf-member-$member->wpsl_api_membership_level">
               <div class="logo-container">
                  <img class="logo" src="$member->wpsl_api_logo_filename">
                  $gf_stamp
               </div>

               <h2>
                  $clean_title
               </h2>

               <p class="tag typelevelstatus">$type_level_status member</p>
               $gf_industry

               <p>$member->wpsl_city, $member->wpsl_country</p>
               <p class="contact">Contact info:</p>
                  <p class="contact-item"><a target="_blank" href="$url">$clean_url</a></p>
                  <p class="contact-item"><a target="_blank" href="mailto:$member->wpsl_email?subject=I found you on the Green Fins website and would like more information">$member->wpsl_email</a></p>
                  <p class="contact-item"><a target="_blank" href="tel:$member->wpsl_phone">$member->wpsl_phone</a></p>
            </section>
         </div>
      LISTING;

   return $return;
}








/**
 * (depreciated) List Members by Location
 *
 * Outputs a list of members or the average score for a given location from the Portal API
 */
function list_membersbylocation_func($atts = [])
{
   // Override default attributes with user attributes
   $get_atts = shortcode_atts(
      array(
         'country' => 'Please specify a valid country & location to display the members list',
         'location' => 'Please specify a valid country & location to display the members list',
         'display_average_score' => false
      ),
      $atts
   );

   if ($get_atts['display_average_score']) {
      // We are going to return an average score section
      $average_lookup = get_transient('rwf_get_averagescore_' . $get_atts['country'] . "_" . $get_atts['location']);

      if (isset($average_lookup)) {
         $average_score = round($average_lookup);
         $average_score_percent = 100 - ($average_score / 330 * 100); //We want a zero score to fill the progress bar

         if ($average_score < 28) {
            $average_score_style = 'lower';
         } elseif ($average_score < 205) {
            $average_score_style = 'middle';
         } else {
            $average_score_style = 'upper';
         }

         $atts_location = $get_atts['location']; // Needed inside the HEREDOC
      }

      $return = <<<SCORE
         <div class="gb-container gb-container-f07c0c81">
            <div class="gb-inside-container"></div>
         </div>

         <div class="gb-container gb-container-a57dea89 gf-section-average-score">
            <div class="gb-inside-container">
               <h1 class="gb-headline gb-headline-a284ca0f">Average Score: $average_score</h1>

               <p>This is the average environmental performance score of members in <strong>$atts_location</strong></p>

               <div class="gf-score-meter">
                     <span class="gf-score-$average_score_style" style="width: $average_score_percent%"></span>
               </div>

               <div class="gb-container gb-container-d429c552">
                     <div class="gb-inside-container">
                        <div class="gb-grid-wrapper gb-grid-wrapper-e77d8655">
                           <div class="gb-grid-column gb-grid-column-fe72985d">
                                 <div class="gb-container gb-container-fe72985d">
                                    <div class="gb-inside-container"></div>
                                 </div>
                           </div>

                           <div class="gb-grid-column gb-grid-column-34fb67fb">
                                 <div class="gb-container gb-container-34fb67fb">
                                    <div class="gb-inside-container">
                                       <h2 class="gb-headline gb-headline-90c84c3a">330</h2>



                                       <h2 class="gb-headline gb-headline-c96e550c">Poor Environmental Performance</h2>
                                    </div>
                                 </div>
                           </div>

                           <div class="gb-grid-column gb-grid-column-12f58e34">
                                 <div class="gb-container gb-container-12f58e34">
                                    <div class="gb-inside-container">
                                       <h2 class="gb-headline gb-headline-46b7cf1b">165</h2>



                                       <h2 class="gb-headline gb-headline-ff77cb36">Needs Improvement</h2>
                                    </div>
                                 </div>
                           </div>

                           <div class="gb-grid-column gb-grid-column-39cfbbb8">
                                 <div class="gb-container gb-container-39cfbbb8">
                                    <div class="gb-inside-container">
                                       <h2 class="gb-headline gb-headline-0f99cdfd">0</h2>



                                       <h2 class="gb-headline gb-headline-d7236770">Great Environmental Performance</h2>
                                    </div>
                                 </div>
                           </div>

                           <div class="gb-grid-column gb-grid-column-8ce327e6">
                                 <div class="gb-container gb-container-8ce327e6">
                                    <div class="gb-inside-container"></div>
                                 </div>
                           </div>
                        </div>
                     </div>
               </div>

               <p>Annually, Green Fins members have their environmental performance evaluated by trained assessors. Each
                     assessment results in a score based on a traffic light rating system for how well risks to the environment
                     are managed. The lower the score, the better the environmental performance. Each member’s score is
                     confidential and continued membership is based on ongoing improvement.</p>

               <div class="gb-container gb-container-e88999f6">
                     <div class="gb-inside-container">
                        <div class="gb-grid-wrapper gb-grid-wrapper-d80d1d7a">
                           <div class="gb-grid-column gb-grid-column-e0f04d97">
                                 <div class="gb-container gb-container-e0f04d97">
                                    <div class="gb-inside-container">
                                       <h2 class="gb-headline gb-headline-8040ba89">Active</h2>

                                       <p>A member that has been assessed within the last 18 months and successfully reduced
                                             their environmental impact.</p>
                                    </div>
                                 </div>
                           </div>

                           <div class="gb-grid-column gb-grid-column-f3d10a1f">
                                 <div class="gb-container gb-container-f3d10a1f">
                                    <div class="gb-inside-container">
                                       <h2 class="gb-headline gb-headline-5a18dd14">Inactive</h2>

                                       <p>A member who was previously active but has not had an assessment to verify their
                                             environmental impact in the last 18 months.</p>
                                    </div>
                                 </div>
                           </div>

                           <div class="gb-grid-column gb-grid-column-db558dc6">
                                 <div class="gb-container gb-container-db558dc6">
                                    <div class="gb-inside-container">
                                       <h2 class="gb-headline gb-headline-040888e5">Restricted</h2>

                                       <p>A member that has not managed to reduce their threat to the marine environment or has
                                             been involved in an activity that is seen as seriously detrimental to the marine
                                             environment. Restricted members are not listed.</p>
                                    </div>
                                 </div>
                           </div>
                        </div>
                     </div>
               </div>
            </div>
         </div>
         <div class="gb-container gb-container-19322339">
            <div class="gb-inside-container"></div>
         </div>

      SCORE;

      return $return;

   } else {
      
      // Fetch members by country and location 
      $args = array(
         'posts_per_page'  => -1,
         'post_type'    => 'wpsl_stores',
         'post_status'  => array('publish', 'pending', 'draft'),
         'order'        => 'ASC',
         'meta_query'   => array(
            'relation' => 'AND',
            array(
               'key'     => 'wpsl_country',
               'value'   => $get_atts['country'],
            ),
            array(
               'key'     => 'wpsl_city',
               'value'   => $get_atts['location'],
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
      $members = new WP_Query($args);

      // Figure out what we are outputting

      if (!$members->have_posts()) {
         return __("<center>This location doesn't have any active or inactive members to display just yet – please check back soon.</center><br><br>", 'rwf-gf-website-members');
      } else {
         // We're going to return a grid container.

         $return = "<div class=\"grid-container\">";

         $i = 0;
         // Loop over the returned members
         foreach ($members->posts as $key => $member) {

            // Not all listings have links
            if ($member->wpsl_url === 'http://') { 
               $clean_title = $member->post_title;
               $clean_url = '';
            }
            else {
               $clean_title = '<a target="_blank" href="' . $member->wpsl_url . '">' . $member->post_title .'</a>';
               $clean_url = rtrim( str_replace( array( 'http://', 'https://', 'www.' ), array( '', '', '' ), $member->wpsl_url ) ,"/");
            }

            $i++;
            // Add a list item for each member to the string
            $return .= gf_member_listing($member);

            if (3 == $i) {
               $i = 0;
               $return .= <<<CLEARFIX
                  <div class="clear"></div>
               CLEARFIX;
            }
         }

         // Close the div
         $return .= "</div>";
      }
      
   }

   return $return;
}











/**
 * Populate Green Fins Digital Members into posts and post meta
 *
 *
 */
function rwf_gf_digital_members_as_posts_func()
{
   // For logging output (including timing how long it takes to execute)
   $start_microtime = microtime(true);
   $trace_id = uniqid('dmapf:');
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

   $url = '/operations?type=DIGITAL';
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
            'post_content' => "This record is maintained in the Green Fins Hub, to edit it please visit https://hub.greenfins.net"
         );
         $member_record_updated_count++;
      } else {
         // make a new post for the member
         $post = array(
            'post_type'    => 'wpsl_stores',
            'post_status'  => $post_status,
            'post_title'   => $member['name'],
            'post_content' => "This record is maintained in the Green Fins Hub, to edit it please visit https://hub.greenfins.net"
         );
         $member_record_created_count++;
      }

      $post_id = wp_insert_post($post);

      if ($post_id) {
         // Add or update the post meta with the member's info and fetch a local copy of the logo
         
         switch ($member['membership_level']) {
            case "Certified Bronze Member":
               $member['membership_level'] = "3";
               $term_id = array( 417 ); //overwrite with certified-bronze-member
                break;
            case "Certified Silver Member":
               $member['membership_level'] = "2";
               $term_id = array( 416 ); //overwrite with certified-silver-member
                break;
            case "Certified Gold Member":
               $member['membership_level'] = "1";
               $term_id = array( 415 ); //overwrite with certified-gold-member
                break;
            default:
               $member['membership_level'] = "none";
               $term_id = array(); //unset inactive certified memebrs
         }

         if ($member['membership_type'] == "DIGITAL" && $member['membership_status'] == 'ACTIVE') {
            $term_id = array( 414 ); //digital-member
         } else {
            $term_id = array(); //unset inactive digital memebrs
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


         // Build the array for the store post meta
         $postmetas = array(
            'api_centre_id'         => $member['id'],
            'api_logo_filename'     => WP_CONTENT_URL . '/gf-member-logos/' . $member_logo_basename, //retrofitting local hosting of images, we used to hotlink from the Portal
            'api_industry'          => $member['industry'],
            'api_membership_type'   => strtolower($member['membership_type']),
            'api_membership_status' => strtolower($member['membership_status']),
            'api_membership_level'  => $member['membership_level'],
            'api_latest_score'      => $member['latest_score'],
            'address'               => $member['address'],
            'address2'              => "",
            'city'                  => $member["location"]["name"],
            'state'                 => $member["region"]["name"],
            'country'               => $member["country"]["name"],
            'lat'                   => $member['lat'],
            'lng'                   => $member['lng'],
            'phone'                 => "",
            'url'                   => $member['website'],
            'email'                 => $member['email']
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
         'before' => date('Y-m-d', strtotime('-1 day')) 
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
 * Populate Green Fins Certified Members into posts and post meta, the tricky bit is updating the existing records after building an array.
 *
 * Love this
 */
function rwf_gf_certified_members_as_posts_func()
{
   // For logging output (including timing how long it takes to execute)
   $start_microtime = microtime(true);
   $trace_id = uniqid('cmapf:');
   $member_record_created_count = 0;
   $member_record_updated_count = 0;
   $member_logo_updated_count = 0;
   rwf_write_log("[" . $trace_id . "] started, please wait");

   function rwf_portal_fetch($description, $url, $trace_id)
   {
      $response = wp_remote_get(esc_url_raw(get_option('rwf_portal_endpoint') . $url));
      // Handle the case when something has been misconfigured
      if (is_wp_error($response)) {
         rwf_write_log("[" . $trace_id . "] error – Unable to fetch the " . $description . " (error message: " . $response->get_error_message() . ")");
         exit;
      }

      $api_response = json_decode(wp_remote_retrieve_body($response), true);
      // Handle the case when the call fails
      if ($api_response["success"] == 0) {
         rwf_write_log("[" . $trace_id . "] error – Unable to fetch the " . $description . " (error message: " . $api_response["error_message"] . ")");
         exit;
      }
      return $api_response['data'];
   }

   // Declare empty arrays for use later
   $members = [];

   // Load the countries list
   $url = '/countries?key=' . get_option('rwf_portal_key');
   $countries = rwf_portal_fetch('countries list', $url, $trace_id);

   foreach ($countries as $country) {
      // Load the regions list
      $url = '/countries/' . $country['id'] . '/regions?key=' . get_option('rwf_portal_key');
      $regionsbycountry = rwf_portal_fetch('regions list for ' . $country['guid'], $url, $trace_id);

      foreach ($regionsbycountry as $region) {
         // Load the locations list
         $url = '/regions/' . $region['id'] . '/locations?key=' . get_option('rwf_portal_key');
         $locationsbyregion = rwf_portal_fetch('locations list for ' . $region['guid'], $url, $trace_id);

         foreach ($locationsbyregion as $location) {
            // Persist the location averages as transients so that the average score for each location can be used in other functions (we don't have a good way to store this in WPSL)
            $transient_name = 'rwf_get_averagescore_' . $country['name'] . "_" . $location['name'];
            set_transient($transient_name, $location['average'], MONTH_IN_SECONDS);

            // Load the members list
            $url = '/locations/' . $location['id'] . '/members?key=' . get_option('rwf_portal_key');
            $membersbylocation = rwf_portal_fetch('members list for ' . $location['guid'], $url, $trace_id);

            // Append the new locations onto any existing to build the array with each loop
            $members = array_merge($members, $membersbylocation);
         }
      }
   }

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
      if ($member['status'] == 'active') {
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
            'post_content' => "This record is maintained in the Green Fins Assessor Portal, to edit it please visit https://portal.greenfins.net"
         );
         $member_record_updated_count++;
      } else {
         // make a new post for the member
         $post = array(
            'post_type'    => 'wpsl_stores',
            'post_status'  => $post_status,
            'post_title'   => $member['name'],
            'post_content' => "This record is maintained in the Green Fins Assessor Portal, to edit it please visit https://portal.greenfins.net"
         );
         $member_record_created_count++;
      }

      $post_id = wp_insert_post($post);

      if ($post_id) {
         // Add or update the post meta with the member's info and fetch a local copy of the logo

         switch ($member['membership_level']) {
            case "Certified Bronze Member":
               $member['membership_level'] = "3";
               $term_id = array( 417 ); //certified-bronze-member
                break;
            case "Certified Silver Member":
               $member['membership_level'] = "2";
               $term_id = array( 416 ); //certified-silver-member
                break;
            case "Certified Gold Member":
               $member['membership_level'] = "1";
               $term_id = array( 415 ); //certified-gold-member
                break;
            default:
               $member['membership_level'] = "none";
               $term_id = array();
         }

         // Set file destination.
         $member_logo_basename = basename($member['logofilename']);

         if (!in_array($member_logo_basename, $existing_member_logos)) { // also covers off default.jpg
            // Not an existing image, so lets fetch it
            $tmp_file = download_url($member['logofilename']);

            // If error storing temporarily, return the error.
            if (is_wp_error($tmp_file)) {
               rwf_write_log("[" . $trace_id . "] error – Unable to download: " . $member['logofilename'] . " with error " . $tmp_file->get_error_message());
            } else {
               // Copy the file to the final destination and delete temporary file.
               copy($tmp_file, $member_logo_filepath . $member_logo_basename);
               $member_logo_updated_count++;
            }
            @unlink($tmp_file);
         }

         // Build the array for the store post meta
         $postmetas = array(
            'api_centre_id'         => $member['id'],
            'api_logo_filename'     => WP_CONTENT_URL . '/gf-member-logos/' . $member_logo_basename, //retrofitting local hosting of images, we used to hotlink from the Portal
            'api_industry'          => $member['industry'],
            'api_membership_type'   => strtolower("CERTIFIED"),
            'api_membership_status' => $member['status'],
            'api_membership_level'  => $member['membership_level'],
            'api_latest_score'      => $member['latest_score'],
            'address'               => $member['address1'],
            'address2'              => $member['address2'],
            'city'                  => $member['location_name'],
            'state'                 => $member['region_name'],
            'country'               => $member['country_name'],
            'lat'                   => $member['lat'],
            'lng'                   => $member['lng'],
            'phone'                 => $member['telephone'],
            'url'                   => $member['website'],
            'email'                 => $member['email']
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
      "Green Fins Members API Plugin Settings",    // Page title
      "Green Fins Members API",                    // Menu title
      "manage_options",                            // Minimum capability (manage_options is an easy way to target administrators)
      "rwf_gf_members_api_plugin",                 // Menu slug
      "rwf_gf_members_api_plugin_options"          // Callback that prints the markup
   );
}

// Print the markup for the page
function rwf_gf_members_api_plugin_options()
{
   if (!current_user_can("manage_options")) {
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

      <h1><?php _e("Green Fins Members API Plugin", "rwf-gf-website-members"); ?></h1>

      <p>
         This plugin displays Green Fins member information within maps, on pages and posts (using shortcodes).
      </p>
      <p>
         Data is fetched from the Green Fins Assessor Portal via the Members API and stored in WP Store Locator (requires v2.2.233 or later). Average score information is cached using <a href="<?php echo get_bloginfo("url") . "/wp-admin/tools.php?page=pw-transients-manager&s=rwf_get_"; ?>" target="_new">transients</a> (enable WP Transients Manager for this link to work).
      </p>
      <p>
         <strong>For help with this plugin, please contact James Greenhalgh (james@reef-world.org)</strong>
      </p>
      <p>
         Available shortcodes:
      <ul>
         <li><code>[list_top10members]</code> displays the Top 10 members</li>
         <li><code>[list_top5bycountry country=""]</code> displays the Top 5 members for the specified country.</li>
         <li><code>[list_membersbylocation country="" location=""]</code> displays the active and inactive members for the specified country and location.</li>
         <li><code>[list_membersbylocation country="" location="" display_average_score="true"]</code> displays the average score meter section for the specified country and location.</li>
      </ul>
      </p>

      <br>

      <h2><?php _e("Plugin Settings", "rwf-gf-website-members"); ?></h2>

      <table class="form-table" role="presentation">
         <tr>
            <th scope="row"><label>
                  <?php _e("Portal Endpoint:", "rwf-gf-website-members"); ?></label>
            </th>
            <td>
               <input class="" type="text" name="rwf_portal_endpoint" value="<?php echo get_option('rwf_portal_endpoint'); ?>" />
            </td>
         </tr>
         <tr>
            <th scope="row"><label>
                  <label><?php _e("Portal Key:", "rwf-gf-website-members"); ?></label>
            </th>
            <td>
               <input class="" type="text" name="rwf_portal_key" value="<?php echo get_option('rwf_portal_key'); ?>" />
            </td>
         </tr>
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
      <div style="border: 1px solid #ccc; padding: 0 20px; width: 90%;">
         <p>Next <strong>Portal</strong> refresh is scheduled for: 
            <strong>
               <?php
                  $var = wp_get_scheduled_event("admin_post_trigger_rwf_gf_certified_members_as_posts");               
                  if(!empty($var)) {
                     echo date('Y-m-d H:i:s', $var->timestamp) . ' UTC';
                  } else {
                     echo "Not scheduled, please investigate";
                  }
               ?>
            </strong>
         </p>
         <p>Manual refresh: <a href="/wp-admin/admin-post.php?action=trigger_rwf_gf_certified_members_as_posts">rwf_gf_certified_members_as_posts_func()</a> ( <a href="javascript:window.location.reload();">refresh log</a> ) </p>
      </div>
      <div style="margin-top: 15px; border: 1px solid #ccc; padding: 0 20px; width: 90%;">
         <p>Next <strong>Hub</strong> refresh is scheduled for: 
            <strong>
               <?php
               $var = wp_get_scheduled_event("admin_post_trigger_rwf_gf_digital_members_as_posts");               
               if(!empty($var)) {
                  echo date('Y-m-d H:i:s', $var->timestamp) . ' UTC';
               } else {
                  echo "Not scheduled, please investigate";
               }
               ?>
            </strong>
         </p>
         <p>Manual refresh: <a href="/wp-admin/admin-post.php?action=trigger_rwf_gf_digital_members_as_posts">rwf_gf_digital_members_as_posts_func()</a> ( <a href="javascript:window.location.reload();">refresh log</a> ) </p>
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
      $portal_key = (!empty($_POST["rwf_portal_key"])) ? $_POST["rwf_portal_key"] : NULL;
      $portal_endpoint = (!empty($_POST["rwf_portal_endpoint"])) ? $_POST["rwf_portal_endpoint"] : NULL;

      $hub_key = (!empty($_POST["rwf_hub_key"])) ? $_POST["rwf_hub_key"] : NULL;
      $hub_endpoint = (!empty($_POST["rwf_hub_endpoint"])) ? $_POST["rwf_hub_endpoint"] : NULL;

      $heartbeat = (!empty($_POST["rwf_heartbeat_key"])) ? $_POST["rwf_heartbeat_key"] : NULL;

      // API Validation to go here

      // Update the values
      update_option("rwf_portal_key", $portal_key, TRUE);
      update_option("rwf_portal_endpoint", $portal_endpoint, TRUE);
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
      if (!wp_next_scheduled('admin_post_trigger_rwf_gf_certified_members_as_posts')) {
         wp_schedule_event(time(), 'hourly', 'admin_post_trigger_rwf_gf_certified_members_as_posts');
      }
      if (!wp_next_scheduled('admin_post_trigger_rwf_gf_digital_members_as_posts')) {
         wp_schedule_event(time(), 'hourly', 'admin_post_trigger_rwf_gf_digital_members_as_posts');
      }

      // 'admin_post_' allows us to trigger the function from the admin area without needing WP Crontrol to be installed. 
      add_action('admin_post_trigger_rwf_gf_certified_members_as_posts', 'rwf_gf_certified_members_as_posts_func');
      add_action('admin_post_trigger_rwf_gf_digital_members_as_posts', 'rwf_gf_digital_members_as_posts_func');

      // Used throughout the find-a-member pages
      add_shortcode("list_top10members", "list_top10members_func");
      add_shortcode("list_top5bycountry", "list_top5bycountry_func");
      add_shortcode("list_membersbylocation", "list_membersbylocation_func"); //depreciated
      add_shortcode("list_membersbycountry", "list_membersbycountry_func");
      add_shortcode("list_digitalmembers", "list_digitalmembers_func");
      add_shortcode("single_verify_membership", "single_verify_membership_func");
   }
   add_action('init', 'rwf_gf_members_api_init');

?>