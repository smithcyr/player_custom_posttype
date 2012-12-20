<?php
/* player support by Cyrus Smith
 * (http://coding-contemplation.blogspot.com) 
 * (https://github.com/smithcyr)
 * 
 * The player post-type was intended as a way of keeping a sports team's
 * player roster up to date with minimal effort by the site administrators.
 * 
 * Players have 4 custom attributes (2 meta and 2 taxonomies):
 *  Class Year - meta
 *  Active Years - taxonomy
 *  Captain "Years" - taxonomy
 *  Team - meta
 * 
 * The roster function displays a list of all the players corresponding to 
 * certain parameters (which are then used with get_posts).
 * This implementation also uses the jQuery plugin objectSorter to allow 
 * javascript sorting of the players in the roster
 * (https://github.com/smithcyr/objectSorter)
 * 
 * This file adds support for the player post-type and the roster function
 * */
 
// Register the objectSorter jQuery plugin for use in the roster 
wp_register_script( 'objectSorter',
                    get_template_directory_uri() . "/js/objectSorter.js",
                    array("jquery"));
// Also register the default css
wp_register_style( 'objectSorter',
                    get_template_directory_uri() . "/js/objectSorter.css");

// Add theme support for thumbnails so that players can have images
add_theme_support( 'post-thumbnails' );

// register the player post type 
function player_custom_init() {
  $labels = array(
    'name' => _x('Players', 'post type general name'),
    'singular_name' => _x('Player', 'post type singular name'),
    'add_new' => _x('Add New', 'player'),
    'add_new_item' => __('Add New Player'),
    'edit_item' => __('Edit Player'),
    'new_item' => __('New Player'),
    'all_items' => __('All Players'),
    'view_item' => __('View Player'),
    'search_items' => __('Search Players'),
    'not_found' =>  __('No players found'),
    'not_found_in_trash' => __('No players found in Trash'), 
    'parent_item_colon' => '',
    'menu_name' => __('Players')
  );
  
  $supports = array( 'title', 'editor', 'thumbnail', 'excerpt' );
  
  $args = array(
    'labels' => $labels,
    'public' => true,
    'exclude_from_search' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'show_in_nav_menus' => false,
    'show_in_menu' => true, 
    'show_in_admin_bar' => true,
    'menu_position' => null, // This defaults to below comments ( 60 > , < 25)
    // 'menu_icon' => 'url to icon for this menu'
    'hierarchical' => false,
    'supports' => $supports,
    'taxonomies' => array('active_years', 'captain'),
    'has_archive' => true, 
    'query_var' => true
  ); 
  register_post_type('player', $args);
}
add_action( 'init', 'player_custom_init' );

// Add the custom taxonomies: active_years, class_year, captain
function player_tax_init(){
  $labels = array(
    'name' => _x( 'Active Years', 'taxonomy general name' ),
    'singular_name' => _x( 'Active Year', 'taxonomy singular name' ),
    'search_items' =>  __( 'Search Active Years' ),
    'popular_items' => __( 'Popular Years' ),
    'all_items' => __( 'All Active Years' ),
    'parent_item' => null,
    'parent_item_colon' => null,
    'edit_item' => __( 'Edit Active Years' ), 
    'update_item' => __( 'Update Active Years' ),
    'add_new_item' => __( 'Add Active Year' ),
    'new_item_name' => __( 'New Active Year' ),
    'separate_items_with_commas' => __( 'Separate years with commas' ),
    'add_or_remove_items' => __( 'Add or remove years' ),
    'choose_from_most_used' => __( 'Choose from the most prevalent years' )
  ); 
  
  $args = array(
    'hierarchical' => false,
    'labels' => $labels,
    'show_ui' => true,
    'query_var' => true,
    'capabilities' =>  array (
            'manage_terms' => 'manage-options', //by default only admin
            'edit_terms' => 'manage-options',
            'delete_terms' => 'manage-options',
            'assign_terms' => 'edit_posts'  // means administrator', 'editor', 'author', 'contributor'
            )
  );
  
  register_taxonomy( 'active_years', 'player', $args );
  
  $labels = array( 
        'name' => _x( 'Years Team Captain', 'taxonomy general name' ),
        'singular_name' => _x( 'Year Team Captain', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search Years Team Captained' ),
        'popular_items' => __( 'Popular Years Team Captained' ),
        'all_items' => __( 'All Captain Years' ),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __( 'Edit Captain Years' ), 
        'update_item' => __( 'Update Captain Years' ),
        'add_new_item' => __( 'Add Captain Year' ),
        'new_item_name' => __( 'New Captain Year' ),
        'separate_items_with_commas' => __( 'Separate years with commas' ),
        'add_or_remove_items' => __( 'Add or remove years' ),
        'choose_from_most_used' => __( 'Choose from the most prevalent years' )
      );  
  
  register_taxonomy( 'captain', 'player', $args );
}
add_action( 'init', 'player_tax_init');

// Add the metabox to the admin panel for editing players so that 
// class year, captain and active years can be edited 
function player_add_year_box () {
  foreach (array('active_years', 'captain') as $box)
    remove_meta_box('tagsdiv-'.$box,'player','side');
  add_meta_box('player_data',__('Player Information'),'player_year_box_html','player','side','core');
  
}
add_action( 'add_meta_boxes', 'player_add_year_box');

// Output the html for the taxonomy selection metabox
function player_year_box_html ($post) {
  $curr_date = getdate();
  $max_year = (integer) $curr_date['year'] + (integer)($curr_date['mon'] >= 8);

  $active_years = array_map(function($a) {return $a->name;},wp_get_object_terms($post->ID, 'active_years'));
  $class_year = get_post_meta($post->ID,'class_year',true);
  $captain_years = array_map(function($a){return $a->name;},wp_get_object_terms($post->ID, 'captain'));  
  $team = get_post_meta($post->ID,'player_team',true);
  echo '<input type="hidden" name="player_meta" id="player_meta" value="' . 
            wp_create_nonce( 'player_meta' ) . '" />';
  
  ?>
  <fieldset>
  <ul class="container">
    <li>
        <div class="fourcol metarow">
        <label for="mens_team">Men's Team</label>
        <input id="mens_team" type="radio" name="team" value="mens" <?php echo ($team == "mens" ? "checked" :"" );?>/>
        </div>
        <div class="fourcol metarow">
        <label for="womens_team">Women's Team</label>
        <input id="womens_team" type="radio" name="team" value="womens" <?php  echo ($team == "womens" ? "checked" :"" )?>/>
        </div>
        <div class="fourcol metarow">
        <label for="player_class_year">Class Year</label>
        <select id="player_class_year" name="class_year">
        <?php 
        for ($year = $max_year + 3; $year > 2002; $year-=1) 
          echo '<option value="' . $year . '"' . ($class_year == $year ? 'selected="selected"' : "") . '>' . $year . '</option>';
        ?>
        </select>
        </div>
    </li>
    <li>
      <ul>
        <li class="fourcol metarow">Year</li>
        <li class="fourcol metarow">Active</li>
        <li class="fourcol metarow">Captain</li>
      </ul>
    </li>
  <?php for ($year = $max_year; $year > 2002; $year-=1) { ?>
    <li class="yearpos" data-year="<?php echo $year; ?>">
      <label class="fourcol metarow"><?php echo $year; ?></label>
      <input class="fourcol metarow" type="checkbox" name="active_years[]" value="<?php echo $year; ?>" <?php if(in_array($year, $active_years)) echo 'checked="checked"' ?>/>
      <input class="fourcol metarow" type="checkbox" name="captain[]" value="<?php echo $year; ?>" <?php if(in_array($year, $captain_years)) echo 'checked="checked"' ?>/>
    </li>
  <?php } ?>
  </ul>
  </fieldset>
  <style>
    .fourcol {width: 32%;}
    .metarow {display: inline-block; text-align: center;}
  </style>
  <script type="text/javascript">
    (function ($) {
      var $yearpos = $('.yearpos');
      var $classyear = $('#player_class_year');
      function checkyear () {
        $classyear.children("option").removeAttr("disabled");
        $yearpos.children('input[name="active_years[]"]:checkbox:checked').each(function() {
          var value = parseInt($(this).val());
          $classyear.children("option").each( function (){
            if (parseInt($(this).val()) > 3 + value || parseInt($(this).val()) < value)
              $(this).attr("disabled","disabled");
          });
        });
      }
      function checkclass () {
        var value = parseInt($classyear.children('option:selected').val());
        $yearpos.each(function() {
          if (parseInt($(this).attr('data-year')) < -3 + value || parseInt($(this).attr('data-year')) > value)
            $(this).hide()
          else
            $(this).show()
        });
      }
      
      $classyear.change( checkclass );
      $yearpos.delegate('input',"change", checkyear);
      (function () {
        checkclass();
        checkyear();
      })();
    })(jQuery);
  </script>
  <?php
  // the javascript above is a simple method to limit the available choices for 
  // player parameters so that many edge-case scenarios can be avoided
}

// Adds support for saving the changed custom player values
function player_save_year_data($post_id){
  if ( !wp_verify_nonce( $_POST['player_meta'], 'player_meta' )) 
    return;

  // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
  if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
    return;
 
  // Check permissions
  if ( 'player' == $_POST['post_type'] ) {
    if ( !current_user_can( 'edit_post', $post_id ) )
      return;
  }

  // authenticated, retrieve and save data
  $post = get_post($post_id);
  if (($post->post_type == 'player')) { 
     update_post_meta( $post_id, 'player_team', $_POST['team'] );
     update_post_meta( $post_id, 'class_year', $_POST['class_year']);
     wp_set_object_terms( $post_id, $_POST['active_years'], 'active_years' );       
     wp_set_object_terms( $post_id, $_POST['captain'], 'captain' );
  }  
}
add_action('save_post', 'player_save_year_data');

// A utility function to parse shortcode input for the year range query
function parse_year_range ($input_string) {
  $Q_range = explode( ',', $input_string); /* split by comma deliniation */
  $L = count($Q_range);
  $count_offset = 0;
  for ( $j = 0; $j < $L + $count_offset; $j++ ) {
    
    $buff = explode('-',$Q_range[$j]); /* split each section by '-' and
                                        * fill in interim years if valid*/
    if(count($buff) == 2) {
      $merge_buff = array();
      if (settype($buff[0],"integer") and settype($buff[1],"integer")) {
        for ($i = min($buff[0],$buff[1]);$i <= max($buff[0],$buff[1]);$i++){
          array_push($merge_buff , $i);
        }
        array_splice($Q_range, $j, 1, $merge_buff);
        $count_offset += count($merge_buff) - 1;
        $j += count($merge_buff) - 1;
      } else {
        unset($Q_range[$j]);
      }
    } elseif ( count($buff) > 2 ) {
      unset($Q_range[$j]);
    } elseif ( ! settype($Q_range[$j],"integer") ) {
      unset($Q_range[$j]);
    }
  }
  $Q_range = array_values($Q_range);
  return $Q_range;
}

/* Query the db and display list of returned elements
 * returns a list of player objects returned by get_posts()
 * @param array $atts - object holds the parameters used to construct the db query
 *  $atts = {
 *    captain: [optional] (mixed) year or boolean or string as array (what year(s) the player was captain)
 *    team: [optional] (string)'mens' or 'womens'
 *    class: [optional] (mixed) year or string as array (what year the player graduates)
 *    range: [optional] (mixed) year or string as array (where a range is described)
 *  }
 * */

function player_query ($atts) {
  
  if ( isset($atts['captain']) or isset($atts['range']) ) {
    
    $Q_tax = array('relation' => 'AND');
    
    // active_years
    if ( $atts['range'] ) {
      $Q_years = parse_year_range( $atts['range'] );
      if (!empty($Q_years)){
        array_push($Q_tax, array(
            'taxonomy' => 'active_years',
            'field' => 'slug',
            'terms' => $Q_years,
            'operator' => 'IN'
          )
        );
      }
    }
    
    // captain status or the years the player was a captain
    if ( isset($atts['captain']) ) {
      if ( $atts['captain'] === 'true') {
        if ($Q_range) 
          array_push($Q_tax,array(
              'taxonomy' => 'captain',
              'field' => 'slug',
              'terms' => $Q_range,
              'operator' => 'IN'
            )
          );
        else 
          array_push($Q_tax,array(
              'taxonomy' => 'captain',
              'field' => 'slug',
              'terms' => array(NULL),
              'operator' => 'NOT IN'
            )
          );
      } elseif ( $atts['captain'] === 'false' ) {
        array_push($Q_tax, 
          array( 
            'taxonomy' => 'captain',
            'field' => 'slug',
            'terms' => array(NULL),
            'operator' => 'IN'
          )
        );
      } else {
        $Q_captain = parse_year_range($atts['captain']);
        if (!empty($Q_captain)) 
          array_push($Q_tax, 
            array(
              'taxonomy' => 'captain',
              'field' => 'slug',
              'terms' => $Q_captain,
              'operator' => 'IN'
            )
          );
      }
    }
  }
  

  if (isset($atts['class']) or isset($atts['team'])) {
    // the class year of the player
    $Q_meta = array();
    if ( isset($atts['class']) ) {
      $Q_class = parse_year_range( $atts['class'] );
      if (!empty($Q_class)) 
        array_push($Q_meta,
          array(
            'key' => 'class_year',
            'value' => $Q_class,
            'compare' => 'IN',
            'type' => 'numeric'
          )
        );
    }
    
    // the team of the player
    if ( isset($atts['team']) ) {
      if ( in_array($atts['team'] , array('mens','womens') )) 
        array_push($Q_meta,
          array(
            'key' => 'player_team',
            'value' => $atts['team'],
            'compare' => '='
          )
        );
    }
    
  }
  
  $args = array( 'post_type' => 'player' );
  if (settype($atts['offset'], "integer"))
    $args['offset'] = $atts['offset'];
  
  if ($Q_tax) $args['tax_query'] = $Q_tax; 
  if ($Q_meta) $args['meta_query'] = $Q_meta; 
  
  /* query construction is done */
  
  return get_posts( $args );

}
       
/* Returns the HTML for displaying a roster of players 
 * @param array $attributes = {
 *  captain: [optional] (mixed) year or boolean or string as array (what year(s) the player was captain)
 *  team: [optional] (string) the player's team ('mens', 'womens')
 *  class: [optional] (mixed) year or string as array (what year the player graduates)
 *  range: [optional] (mixed) year or string as array (where a range is described)
 *  excerpt: [optional] (boolean) true = include the player's excerpt
 *  inc_captain: [optional] (boolean) true = include the player's captain years
 *  inc_team: [optional] (boolean) true = include the player's team
 *  offset: [optional] (integer) (used to display pages if the functionality is added)
 *  sort: [optional] (mixed) JSON object string (options array used with the objectSorter jQuery plugin)
 *                           OR boolean, true = default sort
 * }
 * NOTE -- all arrays are comma delineated
 * */
function player_list ($attributes) {
  wp_enqueue_style('objectSorter');

  global $post;
  $current_post = $post->ID;
  
  $atts = array_merge( array(
    'excerpt' => false,
    'inc_team' => false,
    'offset' => false
  ), $attributes );
  
  $post_list = player_query( $atts );
  $current_year = function () {
    $curr_date = getdate();
    return (integer)$curr_date['year'] + (integer)($curr_date['mon'] >= 8);
  };
  ob_start();
  
  echo "<div id='roster'>"; 
  
  foreach ( $post_list as $a_post): 
    echo "<div class='player" . ($a_post->ID == $current_post ? " current" : "") . "'>";
    if ($a_post->post_title)  {
      $caps = wp_get_object_terms($a_post->ID,'captain',array("fields"=>"names"));
      arsort($caps);
      $is_cap = (isset($atts['inc_captain']) && $atts['inc_captain'] === true) 
                  ? implode(", ",$caps)
                  : (in_array((string) $current_year(), $caps) ? "captain" : "");
      //echo var_export(wp_get_object_terms($a_post->ID,'captain',array("fields"=>"names")));
      echo "<a class='player_name' href='" . get_permalink($a_post->ID) . "' >" .
      $a_post->post_title . "</a>" . "<div class='player_class_year'>" . 
      get_post_meta($a_post->ID,'class_year',true) . "</div>" .
      "<div class='player_captain'>" . $is_cap . "</div>";
       
    }
      
    if ($atts['inc_team'])
    
      echo "<div class='player_team'>" . get_post_meta($a_post->ID,'player_team',true) . "</div>";
    
    echo "</div>";
  endforeach;
  echo "</div>";
  echo "<style>#roster{display:none; padding-bottom: 15px;}</style>";

  $content = ob_get_contents(); // set $content to buffered object
  ob_end_clean(); // throw away the buffered object
  
  if (isset($atts['sort']) && $atts['sort']) {
    wp_enqueue_style('objectSorter_style',get_template_directory_uri() . "/objectSorter.css");
    wp_enqueue_script('objectSorter');
    $GLOBALS['objs_script'] = ($atts['sort'] === true) ? 
            '<script type="text/javascript">' 
            . "(function($) {
                  $('#roster').objectSorter( 
                    {
                     container: 'player',
                     sortby: {player_name:['Name',0,true],
                              player_class_year:['Class',1,true],
                              player_captain:['Captain',0,true]". 
                              ($atts['inc_team']?", player_team:['Team',0,true]":"")
                              
                           . "},
                     initial_sort: {elclass:'player_name',
                                    type:0,
                                    direction:1}
                    }
                  );
                  $('#roster').css('display','table');
                })(jQuery);"
            . '</script>' :
            '<script type="text/javascript">' 
            . "$(function() {
                  $('#roster').objectSorter(" . 
                      $atts['sort'] .
                      " );
               });"
            . '</script>';
    function footer_func() {
      echo $GLOBALS['objs_script'];
    }
    add_action('wp_footer','footer_func',20);
  }
  wp_reset_query(); // return to the last query made before this function call
  return $content; 
}

/* Now we need to add a hook to the page update for players and for the pages with rosters
 * so that the page cache will be refreshed when needed. 
 * */
function roster_page_refresh ($post_id) {
  if (get_post_type($post_id) == "player" && get_option('rostered'))
    foreach (array_keys(get_option('rostered')) as $PID)
      wp_cache_post_change($PID);
  else if (get_post_meta($post_id, 'side_roster', true) != 'none' ) {
    $current_posts = get_option('rostered');
    if ($current_posts && !isset($current_posts[$post_id])) {
      $current_posts[$post_id] = true;
      update_option('rostered',$current_posts);
    }
    else 
      update_option('rostered',array($post_id => true));
    wp_cache_post_change($post_id);
  } else {
    $current_posts = get_option('rostered');
    if ($current_posts && 
        isset($current_posts[$post_id]) && 
        get_post_meta($post_id, 'side_roster', true) == 'none' ) {
      unset($current_posts[$post_id]);
      wp_cache_post_change($post_id);
      update_option('rostered',$current_posts);
    }
  }
}
add_action('save_post', 'roster_page_refresh');
