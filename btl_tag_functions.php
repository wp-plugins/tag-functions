<?php 
/*
Plugin Name: Tag Functions
Plugin URI: http://wordpress.org/extend/plugins/tag-functions/
Description: This plugin adds the ability to create a list of tags, similar to a category list.
Author: Brent Loertscher
Version: 1.2
*/ 

class Walker_Tag extends Walker {
	var $tree_type = 'tag';
	var $db_fields = array ('parent' => 'tag_parent', 'id' => 'cat_ID'); //TODO: decouple this

	function start_lvl($output, $depth, $args) {
		if ( 'list' != $args['style'] )
			return $output;

		$indent = str_repeat("\t", $depth);
		$output .= "$indent<ul class='children'>\n";
		return $output;
	}

	function end_lvl($output, $depth, $args) {
		if ( 'list' != $args['style'] )
			return $output;

		$indent = str_repeat("\t", $depth);
		$output .= "$indent</ul>\n";
		return $output;
	}

	function start_el($output, $tag, $depth, $args) {
		extract($args);

		$cat_name = attribute_escape( $tag->cat_name);
		$cat_name = apply_filters( 'list_cats', $cat_name, $tag );
		$link = '<a href="' . get_tag_link( $tag->cat_ID ) . '" ';
		if ( $use_desc_for_title == 0 || empty($tag->tag_description) )
			$link .= 'title="' . sprintf(__( 'View all posts filed under %s' ), $cat_name) . '"';
		else
			$link .= 'title="' . attribute_escape( apply_filters( 'tag_description', $tag->tag_description, $tag )) . '"';
		$link .= '>';
		$link .= $cat_name . '</a>';

		if ( (! empty($feed_image)) || (! empty($feed)) ) {
			$link .= ' ';

			if ( empty($feed_image) )
				$link .= '(';

			$link .= '<a href="' . get_tag_rss_link( 0, $tag->cat_ID, $tag->tag_nicename ) . '"';

			if ( empty($feed) )
				$alt = ' alt="' . sprintf(__( 'Feed for all posts filed under %s' ), $cat_name ) . '"';
			else {
				$title = ' title="' . $feed . '"';
				$alt = ' alt="' . $feed . '"';
				$name = $feed;
				$link .= $title;
			}

			$link .= '>';

			if ( empty($feed_image) )
				$link .= $name;
			else
				$link .= "<img src='$feed_image'$alt$title" . ' />';
			$link .= '</a>';
			if ( empty($feed_image) )
				$link .= ')';
		}

		if ( isset($show_count) && $show_count )
			$link .= ' (' . intval($tag->tag_count) . ')';

		if ( isset($show_date) && $show_date ) {
			$link .= ' ' . gmdate('Y-m-d', $tag->last_update_timestamp);
		}

		if ( $current_tag )
			$_current_tag = get_tag( $current_tag );

		if ( 'list' == $args['style'] ) {
			$output .= "\t<li";
			if ( $current_tag && ($tag->cat_ID == $current_tag) )
				$output .=  ' class="current-cat"';
			elseif ( $_current_tag && ($tag->cat_ID == $_current_tag->tag_parent) )
				$output .=  ' class="current-cat-parent"';
			$output .= ">$link\n";
		} else {
			$output .= "\t$link<br />\n";
		}

		return $output;
	}

	function end_el($output, $page, $depth, $args) {
		if ( 'list' != $args['style'] )
			return $output;

		$output .= "</li>\n";
		return $output;
	}

}

class Walker_TagDropdown extends Walker {
	var $tree_type = 'tag';
	var $db_fields = array ('parent' => 'tag_parent', 'id' => 'cat_ID'); //TODO: decouple this

	function start_el($output, $tag, $depth, $args) {
		$pad = str_repeat('&nbsp;', $depth * 3);

		$cat_name = apply_filters('list_cats', $tag->cat_name, $tag);
		$output .= "\t<option value=\"".$tag->cat_ID."\"";
		if ( $tag->cat_ID == $args['selected'] )
			$output .= ' selected="selected"';
		$output .= '>';
		$output .= $pad.$cat_name;
		if ( $args['show_count'] )
			$output .= '&nbsp;&nbsp;('. $tag->tag_count .')';
		if ( $args['show_last_update'] ) {
			$format = 'Y-m-d';
			$output .= '&nbsp;&nbsp;' . gmdate($format, $tag->last_update_timestamp);
		}
		$output .= "</option>\n";

		return $output;
	}
}

function get_tag_rss_link($echo = false, $cat_ID, $tag_nicename) {
	$permalink_structure = get_option('permalink_structure');

	if ( '' == $permalink_structure ) {
		$link = get_option('home') . '?feed=rss2&amp;cat=' . $cat_ID;
	} else {
		$link = get_tag_link($cat_ID);
		$link = $link . user_trailingslashit('feed', 'feed');
	}

	$link = apply_filters('tag_feed_link', $link);

	if ( $echo )
		echo $link;
	return $link;
}

function btl_list_tags($args = '') {
	if ( is_array($args) )
		$r = &$args;
	else
		parse_str($args, $r);

	$defaults = array('show_option_all' => '', 'orderby' => 'name',
		'order' => 'ASC', 'show_last_update' => 0, 'style' => 'list',
		'show_count' => 0, 'hide_empty' => 1, 'use_desc_for_title' => 1,
		'child_of' => 0, 'feed' => '', 'feed_image' => '', 'exclude' => '',
		'hierarchical' => true, 'title_li' => __('Categories'));
	$r = array_merge($defaults, $r);
	if ( !isset($r['pad_counts']) && $r['show_count'] && $r['hierarchical'] )
		$r['pad_counts'] = true;
	if ( isset($r['show_date']) )
		$r['include_last_update_time'] = $r['show_date'];
	extract($r);

	$tags = get_tags($r);

	$output = '';
	if ( $title_li && 'list' == $style )
			$output = '<li class="tags">' . $r['title_li'] . '<ul>';

	if ( empty($tags) ) {
		if ( 'list' == $style )
			$output .= '<li>' . __("No tags") . '</li>';
		else
			$output .= __("No tags");
	} else {
		global $wp_query;
		
		if( !empty($show_option_all) )
			if ('list' == $style )  
				$output .= '<li><a href="' .  get_bloginfo('url')  . '">' . $show_option_all . '</a></li>';
			else
				$output .= '<a href="' .  get_bloginfo('url')  . '">' . $show_option_all . '</a>';
		
		if ( is_tag() )
			$r['current_tag'] = $wp_query->get_queried_object_id();

		if ( $hierarchical )
			$depth = 0;  // Walk the full depth.
		else
			$depth = -1; // Flat.

		$output .= walk_tag_tree($tags, $depth, $r);
	}

	if ( $title_li && 'list' == $style )
		$output .= '</ul></li>';

	echo apply_filters('btl_list_tags', $output);
}

function btl_dropdown_tags($args = '') {
	$defaults = array(
		'show_option_all' => '', 'show_option_none' => '', 
		'orderby' => 'ID', 'order' => 'ASC', 
		'show_last_update' => 0, 'show_count' => 0, 
		'hide_empty' => 1, 'child_of' => 0, 
		'exclude' => '', 'echo' => 1, 
		'selected' => 0, 'hierarchical' => 0, 
		'name' => 'cat', 'class' => 'postform'
	);
	
	$defaults['selected'] = ( is_tag() ) ? get_query_var('cat') : 0;
	
	$r = wp_parse_args( $args, $defaults );
	$r['include_last_update_time'] = $r['show_last_update'];
	extract( $r );

	$tags = get_tags($r);

	$output = '';
	if ( ! empty($tags) ) {
		$output = "<select name='$name' id='$name' class='$class'>\n";

		if ( $show_option_all ) {
			$show_option_all = apply_filters('list_cats', $show_option_all);
			$output .= "\t<option value='0'>$show_option_all</option>\n";
		}

		if ( $show_option_none) {
			$show_option_none = apply_filters('list_cats', $show_option_none);
			$output .= "\t<option value='-1'>$show_option_none</option>\n";
		}

		if ( $hierarchical )
			$depth = 0;  // Walk the full depth.
		else
			$depth = -1; // Flat.

		$output .= walk_tag_dropdown_tree($tags, $depth, $r);
		$output .= "</select>\n";
	}

	$output = apply_filters('btl_dropdown_cats', $output);

	if ( $echo )
		echo $output;

	return $output;
}

function walk_tag_tree() {
	$walker = new Walker_Tag;
	$args = func_get_args();
	return call_user_func_array(array(&$walker, 'walk'), $args);
}

function walk_tag_dropdown_tree() {
	$walker = new Walker_TagDropdown;
	$args = func_get_args();
	return call_user_func_array(array(&$walker, 'walk'), $args);
}

function widget_tags_init () {

   if ( !function_exists('register_sidebar_widget') )
      return;

   function btl_widget_tags($args) {
      extract($args);
      $options = get_option('widget_tags');
      $c = $options['count'] ? '1' : '0';
      $h = $options['hierarchical'] ? '1' : '0';
      $d = $options['dropdown'] ? '1' : '0';
      $title = empty($options['title']) ? __('Tags') : $options['title'];

      echo $before_widget;
      echo $before_title . $title . $after_title; 

      $cat_args = "orderby=name&show_count={$c}&hierarchical={$h}";

      if($d) {
         btl_dropdown_tags($cat_args . '&show_option_none= ' . __('Select Category'));
      ?>

         <script lang='javascript'><!--
         var dropdown = document.getElementById("cat");
         function onCatChange() {
            if ( dropdown.options[dropdown.selectedIndex].value > 0 ) {
               location.href = "<?php echo get_option('siteurl'); ?>/?cat="+dropdown.options[dropdown.selectedIndex].value;
            }
         }
         dropdown.onchange = onCatChange;
         --></script>

      <?php
      } else {
      ?>
         <ul>
            <?php btl_list_tags($cat_args . '&title_li='); ?>
            </ul>
      <?php
      }

      echo $after_widget;
   }

   function btl_widget_tags_control() {
      $options = $newoptions = get_option('widget_tags');
      if ( $_POST['tags-submit'] ) {
         $newoptions['count'] = isset($_POST['tags-count']);
         $newoptions['hierarchical'] = isset($_POST['tags-hierarchical']);
         $newoptions['dropdown'] = isset($_POST['tags-dropdown']);
         $newoptions['title'] = strip_tags(stripslashes($_POST['tags-title']));
      }
      if ( $options != $newoptions ) {
         $options = $newoptions;
         update_option('widget_tags', $options);
      }
      $count = $options['count'] ? 'checked="checked"' : '';
      $hierarchical = $options['hierarchical'] ? 'checked="checked"' : '';
      $dropdown = $options['dropdown'] ? 'checked="checked"' : '';
      $title = attribute_escape($options['title']);
      ?>
      <p><label for="tags-title"><?php _e('Title:'); ?> <input style="width: 250px;" id="tags-title" name="tags-title" type="text" value="<?php echo $title; ?>" /></label></p>
         <p style="text-align:right;margin-right:40px;"><label for="tags-count"><?php _e('Show post counts'); ?> <input class="checkbox" type="checkbox" <?php echo $count; ?> id="tags-count" name="tags-count" /></label></p>
         <p style="text-align:right;margin-right:40px;"><label for="tags-hierarchical" style="text-align:right;"><?php _e('Show hierarchy'); ?> <input class="checkbox" type="checkbox" <?php echo $hierarchical; ?> id="tags-hierarchical" name="tags-hierarchical" /></label></p>
         <p style="text-align:right;margin-right:40px;"><label for="tags-dropdown" style="text-align:right;"><?php _e('Display as a drop down'); ?> <input class="checkbox" type="checkbox" <?php echo $dropdown; ?> id="tags-dropdown" name="tags-dropdown" /></label></p>
         <input type="hidden" id="tags-submit" name="tags-submit" value="1" />
   <?php
   }

   wp_register_sidebar_widget('tags', __('Tags'), 'btl_widget_tags', $class);
   wp_register_widget_control('tags', __('Tags'), 'btl_widget_tags_control', $dims150);
}

add_action ('widgets_init', 'widget_tags_init');

?>
