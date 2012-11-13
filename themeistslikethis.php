<?php
/*
Plugin Name: Themeists Like This
Plugin URI: #
Description: A simple, clean and quick "like" plugin meaning your visitors can show that they appreciate your content. Also provides a widget for showing your most popular content. If used with a themeists theme you get customisation options in your theme options panel
Version: 1.0
Author: Themeists
Author URI: #
License: GPL2

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


if( !class_exists( 'ThemeistsLikeThis' ) ):


	/**
	 * A simple but clean 'like this' plugin. Provides a way for site visitors to press a button which
	 * then adds post meta to indicate how many people have 'liked' something. Based on Rose's Like This plugin
	 * but adds some hooks and filters to extend the functionality as well as providing customisation options
	 * for themeists theme users. Also does the ajax requests 'properly', updates it for WP 3.4+ and converts
	 * it into a more object-oriented approach.
	 *
	 * Also provides a widget to showcase the most popular posts.
	 *
	 * @author Richard Tape
	 * @package ThemeistsLikeThis
	 * @since 1.0
	 */
	
	class ThemeistsLikeThis
	{

		/**
		 * We might not be using a themeists theme (which means we can't add anything to the options panel). By default,
		 * we'll say we are not. We check if the theme's author is Themeists to set this to true during instantiation.
		 *
		 * @author Richard Tape
		 * @package ThemeistsLikeThis
		 * @since 1.0
		 */
		
		var $using_themeists_theme = false;


		/**
		 * Initialise ourselves. Set using_themeists_theme to true if...we are.
		 *
		 * @author Richard Tape
		 * @package ThemeistsLikeThis
		 * @since 1.0
		 */

		function ThemeistsLikeThis()
		{

			$theme_data = wp_get_theme();
			$theme_author = $theme_data->display( 'Author', false );

			if( strtolower( trim( $theme_author ) ) == "themeists" )
				$this->using_themeists_theme = true;

			add_action ( 'publish_post', array( &$this, 'setUpPostLikes' ) );
			add_action ( 'init', array( &$this, 'checkHeaders' ) );
			add_action ( 'wp_enqueue_scripts', array( &$this, 'jsIncludes' ) );

			//Handle the vote without javascript
			add_action( 'wp_ajax_themeists_like_this_vote', array( &$this, 'my_user_vote' ) );
			add_action( 'wp_ajax_nopriv_themeists_like_this_vote', array( &$this, 'my_user_vote' ) );

			if( $this->using_themeists_theme )
			{
				add_action( 'of_set_options_in_advanced_page_end', 	array( &$this, 'add_options_to_themeists_options_panel' ), 10, 1 );
			}

		}/* ThemeistsLikeThis() */


		/* =============================================================================== */


		/**
		 * Getter and Setter method for post meta for when someone likes a post or
		 * if we need to find out if someone has
		 *
		 * @author Richard Tape
		 * @package ThemeistsLikeThis
		 * @since 1.0
		 * @param (int) $post_id - The ID of the post being 'liked'
		 * @param (string) $action - allow us to get or set our meta
		 */
		
		function likeThis( $post_id, $action = 'get' )
		{

			if( !is_numeric( $post_id ) )
			{
				
				error_log( "Error: Value submitted for post_id was not numeric" );
				
				return;

			}


			$data = get_post_meta( $post_id, '_likes' );
			
			if( !is_numeric( $data[0] ) )
			{
				
				$data[0] = 0;

				add_post_meta( $post_id, '_likes', '0', true );

			}
			
			return $data[0];

		}/* likeThis */


		/* =============================================================================== */


		/**
		 * Our output function. Basic - if we're not using a themeists theme - is to output a 
		 * x-number of people like this (through a filter)
		 *
		 * @author Richard Tape
		 * @package ThemeistsLikeThis
		 * @since 1.0
		 * @param (int) $post_id - ID Of the post being requested
		 * @return (print string) -  
		 */
		
		function printLikes( $post_id )
		{

			$likes = self::likeThis( $post_id );

			$nonce = wp_create_nonce( 'themeists_like_this_nonce' );
			$link = admin_url( 'admin-ajax.php?action=themeists_like_this_vote&post_id=' . $post_id . '&nonce=' . $nonce );

			if( isset( $_COOKIE["like_" . $post_id] ) )
			{
				echo apply_filters( 'themeists_likethis_output', '<a href="#" class="likeThis done" id="like-'.$post_id.'">'.$likes.'</a>', $post_id, $link, $nonce );
				return;
			}

			print apply_filters( 'themeists_likethis_output', '<a href="' . $link . '" data-nonce="' . $nonce . '" data-post_id="' . $post_id . '" class="likeThis" id="like-'.$post_id.'">'.$likes.'</a>', $post_id, $link, $nonce );

		}/* printLikes() */


		/* =============================================================================== */


		/**
		 * Add the post meta when someone clicks the like button
		 *
		 * @author Richard Tape
		 * @package ThemeistsLikeThis
		 * @since 1.0
		 * @param (int) $post_id - The ID of the post being liked
		 * @return None
		 */

		function setUpPostLikes( $post_id )
		{

			if( !is_numeric( $post_id ) )
			{
			
				error_log( "Error: Value submitted for post_id was not numeric" );
				return;
			
			}

			add_post_meta( $post_id, '_likes', '0', true );

		}/* setUpPostLikes() */


		/* =============================================================================== */


		/**
		 * If we have a likepost $_POST var, call the likeThis function to update the post
		 *
		 * @author Richard Tape
		 * @package ThemeistsLikeThis
		 * @since 1.0
		 * @param None
		 * @return None
		 */

		function checkHeaders()
		{

			if( isset( $_POST["likepost"] ) )
				self::likeThis( $_POST["likepost"], 'update' );

		}/* checkHeaders */


		/* =============================================================================== */


		/**
		 * Include our javascript after we've checked that jQuery is included
		 *
		 * @author Richard Tape
		 * @package ThemeistsLikeThis
		 * @since 1.0
		 * @param None
		 * @return None
		 */

		function jsIncludes()
		{

			wp_register_script( 'themeists_like_script', plugins_url( 'themeists_like_script.js', __FILE__ ), array('jquery'), '', true );
			wp_localize_script( 'themeists_like_script', 'themeists_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );

			wp_enqueue_script( 'jquery' );
			wp_enqueue_script( 'themeists_like_script' );

			if( of_get_option( 'show_likethis_stylesheet' ) == 1 )
				wp_enqueue_style( 'themeists_likethis_stylesheet', plugins_url( 'themeists_likethis.css', __FILE__ ) );


		}/* jsIncludes() */


		/* =============================================================================== */


		/**
		 * Handle the 'like this' submission without ajax
		 *
		 * @author Richard Tape
		 * @package ThemeistsLikeThis
		 * @since 1.0
		 * @param None
		 * @return None
		 */

		function my_user_vote()
		{

			if ( !wp_verify_nonce( $_REQUEST['nonce'], 'themeists_like_this_nonce' ) )
			exit( 'No!' ); 

			$vote_count = get_post_meta( $_REQUEST["post_id"], "_likes", true );
			$vote_count = ( $vote_count == '' ) ? 0 : $vote_count;
			$new_vote_count = $vote_count + 1;

			do_action( 'themeists_likethis_before_add_vote', $_REQUEST["post_id"], $vote_count, $new_vote_count );

			$vote = update_post_meta( $_REQUEST["post_id"], "_likes", $new_vote_count );

			do_action( 'themeists_likethis_after_add_vote', $_REQUEST["post_id"], $vote_count, $new_vote_count );

			setcookie( "like_" . $_REQUEST["post_id"], $_REQUEST["post_id"], time() + ( 60*60*24*365 ), '/' );

			if( $vote === false )
			{
				
				$result['type'] = "error";
				$result['vote_count'] = $vote_count;

			}
			else
			{
			
				$result['type'] = "success";
				$result['vote_count'] = $new_vote_count;
			
			}

			if( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' )
			{
			
				$result = json_encode( $result );
				
				do_action( 'themeists_likethis_output_pre_send_to_ajax' );

				echo apply_filters( 'themeists_likethis_json_output', $result );

			}
			else
			{

				do_action( 'themeists_likethis_no_ajax_before_redirect', $_REQUEST["post_id"], $vote_count, $new_vote_count );
				
				header( "Location: " . $_SERVER["HTTP_REFERER"] );

			}

			die();

		}/* my_user_vote() */



		/* =============================================================================== */


		/**
		 * Add some customisation options to the options panel, if we're on a Themeists theme
		 *
		 * @author Richard Tape
		 * @package ThemeistsLikeThis
		 * @since 1.0
		 * @param None
		 * @return None
		 */
		
		function add_options_to_themeists_options_panel()
		{

			global $options;

			$options[] = array(
				'name' => __( '"Like This" text customisation', 'themeistslikethis' ),
				'desc' => __( 'What would you like the output of the LikeThis plugin to be? You can use {{like_count}} to output the number of likes for this item. By default it is just the count of likes. But you may want something like "Like this post? {{like_count}} already do!"', 'themeistslikethis' ),
				'id' => 'likethis_output',
				'std' => '{{like_count}}',
				'type' => 'text'
			);

			$options[] = array(
				'name' => __('Output Styles', THEMENAME ),
				'desc' => __('Would you like to output the stylesheet for the likethis plugin? It places hearts next to the likethis links and for the Most Liked widget.', THEMENAME ),
				'id' => 'show_likethis_stylesheet',
				'std' => '1',
				'type' => 'checkbox'
			);

			//Give ourselves a hook so themes can add further options directly after the customisations option above
			do_action( 'themeists_likethis_after_customisation' );

		}/* add_options_to_themeists_options_panel() */
		
		

	}/* end class */

endif;


//Set up the likethis plugin
$themeistsLikeThis = new ThemeistsLikeThis;


//Load the likethis widget
require_once( 'likethis_widget.php' );


?>