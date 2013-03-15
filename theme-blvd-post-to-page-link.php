<?php
/*
Plugin Name: Theme Blvd Post-to-Page Link
Description: Link a post to a page to effect its breadcrumb trail when using a Theme Blvd theme.
Version: 1.0.1
Author: Jason Bobich
Author URI: http://jasonbobich.com
License: GPL2
*/

/*
Copyright 2012 JASON BOBICH

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Add option to framework's Post Options meta box.  */

function themeblvd_ptp_post_meta( $setup ) {
	$pages = get_pages();
	$pages_select = array( '' => ' - None - ' );
	if( ! empty( $pages ) ) {
		foreach( $pages as $page ) {
			$pages_select[$page->ID] = $page->post_title;
		}
	}
	$setup['options'][] = array(
		'id'		=> '_tb_ptp_page',
		'name' 		=> __( 'Link this post to a page?', 'themeblvd' ),
		'desc'		=> __( 'If you link this post to a page, when you\'re viewing the post, the breadcrumbs trail will reflect the page\'s path instead of this actual post\'s path.', 'themeblvd' ),
		'type' 		=> 'select',
		'std'		=> '',
		'options'	=> $pages_select
	);
	return $setup;
}
add_filter( 'themeblvd_post_meta', 'themeblvd_ptp_post_meta' );

/* Override framework's themeblvd_get_breadcrumbs function. */

function themeblvd_get_breadcrumbs() {
	global $post;
	
	if( defined('TB_FRAMEWORK_VERSION') && version_compare( TB_FRAMEWORK_VERSION, '2.2.0', '>=' ) ) {

		// Filterable attributes
		$atts = array(
			'delimiter' => ' <span class="divider">/</span>',
			'home' => themeblvd_get_local('home'),
			'home_link' => home_url(),
			'before' => '<span class="current">',
			'after' => '</span>'
		);
		$atts = apply_filters( 'themeblvd_breadcrumb_atts', $atts );
		
		// Start output
		$output = '<div id="breadcrumbs">'; 
		$output .= '<div class="breadcrumbs-inner">';
		$output .= '<div class="breadcrumbs-content">';
		$output .= '<div class="breadcrumb">'; // This enables bootstrap styles
		$output .= '<a href="'.$atts['home_link'].'" class="home-link" title="'.$atts['home'].'">'.$atts['home'].'</a>'.$atts['delimiter'].' ';
	
	} else {

		// Filterable attributes
		$atts = array(
			'delimiter' => '&raquo;',
			'home' => themeblvd_get_local('home'),
			'home_link' => home_url(),
			'before' => '<span class="current">',
			'after' => '</span>'
		);
		$atts = apply_filters( 'themeblvd_breadcrumb_atts', $atts );
		// Start output
		$output = '<div id="breadcrumbs">';
		$output .= '<div class="breadcrumbs-inner">';
		$output .= '<div class="breadcrumbs-content">';
		$output .= '<a href="'.$atts['home_link'].'" class="home-link" title="'.$atts['home'].'">'.$atts['home'].'</a>'.$atts['delimiter'].' ';
	
	}

	if ( is_category() ) {
		global $wp_query;
		$cat_obj = $wp_query->get_queried_object();
		$thisCat = $cat_obj->term_id;
		$thisCat = get_category($thisCat);
		$parentCat = get_category($thisCat->parent);
		if ($thisCat->parent != 0) $output .= (get_category_parents($parentCat, TRUE, ' '.$atts['delimiter'].' '));
		$output .= $atts['before'].single_cat_title('', false).$atts['after'];
	} else if ( is_day() ) {
		$output .= '<a href="'.get_year_link(get_the_time('Y')).'">'.get_the_time('Y').'</a> '.$atts['delimiter'].' ';
		$output .= '<a href="'.get_month_link(get_the_time('Y'),get_the_time('m')).'">'.get_the_time('F').'</a> '.$atts['delimiter'].' ';
		$output .= $atts['before'].get_the_time('d').$atts['after'];
	} else if ( is_month() ) {
		$output .= '<a href="'.get_year_link(get_the_time('Y')).'">'.get_the_time('Y').'</a> '.$atts['delimiter'].' ';
		$output .= $atts['before'].get_the_time('F').$atts['after'];
	} else if ( is_year() ) {
		$output .= $atts['before'].get_the_time('Y').$atts['after'];
	} else if ( is_single() ) {
		
		/*--------------------------------*/
		/*	Modification (start)
		/*--------------------------------*/
		
		// Grab linked page option
		$linked_page_id = get_post_meta( $post->ID, '_tb_ptp_page', true );
		// Show breadcrumbs for single post depending on if the option exists or not.
		if( $linked_page_id ) {
			// Show breadcrumb trail of linked page
			$linked_page = get_page($linked_page_id);
			
			if ( ! $linked_page->post_parent ) {
				$output .= '<a href="'.get_permalink($linked_page->ID).'">'.$linked_page->post_title.'</a> '.$atts['delimiter'].' '.$atts['before'].get_the_title().$atts['after'];
			} else {
				$parent_id = $linked_page->post_parent;
				$breadcrumbs = array();
				while ($parent_id) {
					$page = get_page($parent_id);
					$breadcrumbs[] = '<a href="'.get_permalink($page->ID).'">'.get_the_title($page->ID).'</a>';
					$parent_id = $page->post_parent;
				}
				$breadcrumbs = array_reverse($breadcrumbs);
				foreach ($breadcrumbs as $crumb) $output .= $crumb.' '.$atts['delimiter'].' ';
				$output .= '<a href="'.get_permalink($linked_page->ID).'">'.$linked_page->post_title.'</a> '.$atts['delimiter'].' ';
				$output .= $atts['before'].get_the_title().$atts['after'];
			}
		} else {
			// Default single post structure
			if ( get_post_type() != 'post' ) {
				$post_type = get_post_type_object(get_post_type());
				$slug = $post_type->rewrite;
				$output .= '<a href="'.$atts['home_link'].'/'.$slug['slug'].'/">'.$post_type->labels->singular_name.'</a> '.$atts['delimiter'].' ';
				$output .= $atts['before'].get_the_title().$atts['after'];
			} else {
				$cat = get_the_category(); $cat = $cat[0];
				$output .= get_category_parents($cat, TRUE, ' '.$atts['delimiter'].' ');
				$output .= $atts['before'].get_the_title().$atts['after'];
			}
		}
		
		/*--------------------------------*/
		/*	Modification (end)
		/*--------------------------------*/
		
	} else if ( is_search() ) {
		$output .= $atts['before'].themeblvd_get_local('crumb_search').' "'.get_search_query().'"'.$atts['after'];
	} else if ( !is_single() && !is_page() && get_post_type() != 'post' && !is_404() ) {
		$post_type = get_post_type_object(get_post_type());
		$output .= $atts['before'].$post_type->labels->singular_name.$atts['after'];
	} else if ( is_attachment() ) {
		$parent = get_post($post->post_parent);
		$cat = get_the_category($parent->ID);
		if( ! empty( $cat ) ) {
			$cat = $cat[0];
			$output .= get_category_parents($cat, TRUE, ' '.$atts['delimiter'].' ');
		}
		$output .= '<a href="'.get_permalink($parent).'">'.$parent->post_title.'</a> '.$atts['delimiter'].' ';
		$output .= $atts['before'].get_the_title().$atts['after'];
	} else if ( is_page() && !$post->post_parent ) {
		$output .= $atts['before'].get_the_title().$atts['after'];
	} else if ( is_page() && $post->post_parent ) {
		$parent_id  = $post->post_parent;
		$breadcrumbs = array();
		while ($parent_id) {
			$page = get_page($parent_id);
			$breadcrumbs[] = '<a href="'.get_permalink($page->ID).'">'.get_the_title($page->ID).'</a>';
			$parent_id  = $page->post_parent;
		}
		$breadcrumbs = array_reverse($breadcrumbs);
		foreach ($breadcrumbs as $crumb) $output .= $crumb.' '.$atts['delimiter'].' ';
		$output .= $atts['before'].get_the_title().$atts['after'];
	} else if ( is_tag() ) {
		$output .= $atts['before'].themeblvd_get_local('crumb_tag').' "'.single_tag_title('', false).'"'.$atts['after'];
	} else if ( is_author() ) {
		global $author;
		$userdata = get_userdata($author);
		$output .= $atts['before'].themeblvd_get_local('crumb_author').' '.$userdata->display_name.$atts['after'];
	} else if ( is_404() ) {
	  $output .= $atts['before'].themeblvd_get_local('crumb_404').$atts['after'];
	}
	if ( get_query_var('paged') ) {
		if ( is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author() ) $output .= ' (';
		$output .= themeblvd_get_local('page').' '.get_query_var('paged');
		if ( is_category() || is_day() || is_month() || is_year() || is_search() || is_tag() || is_author() ) $output .= ')';
	}

	if( defined('TB_FRAMEWORK_VERSION') && version_compare( TB_FRAMEWORK_VERSION, '2.2.0', '>=' ) ) {

		$output .= '</div><!-- .breadcrumb (end) -->';
		$output .= '</div><!-- .breadcrumbs-content (end) -->';
		$output .= '</div><!-- .breadcrumbs-inner (end) -->';
		$output .= '</div><!-- #breadcrumbs (end) -->';
		
	} else {

		$output .= '</div><!-- .breadcrumbs-content (end) -->';
		$output .= '</div><!-- .breadcrumbs-inner (end) -->';
		$output .= '</div><!-- #breadcrumbs (end) -->';

	}
	return $output;
}