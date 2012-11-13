<?php


	if( !class_exists( 'themeists_likethis_most_liked_posts' ) )
	{

		class themeists_likethis_most_liked_posts extends WP_Widget
		{
		
			
			/**
			 * The name shown in the widgets panel
			 *
			 * @author Richard Tape
			 * @package themeists_likethis_most_liked_posts
			 * @since 1.0
			 */
			
			const name 		= 'Themeists Most Liked Posts';

			/**
			 * For helping with translations
			 *
			 * @author Richard Tape
			 * @package themeists_likethis_most_liked_posts
			 * @since 1.0
			 */

			const locale 	= THEMENAME;

			/**
			 * The slug for this widget, which is shown on output
			 *
			 * @author Richard Tape
			 * @package themeists_likethis_most_liked_posts
			 * @since 1.0
			 */
			
			const slug 		= 'themeists_likethis_most_liked_posts';
		

			/* ============================================================================ */
		
			/**
			 * The widget constructor. Specifies the classname and description, instantiates
			 * the widget, loads localization files, and includes necessary scripts and
			 * styles. 
			 *
			 * @author Richard Tape
			 * @package themeists_likethis_most_liked_posts
			 * @since 1.0
			 * @param None
			 * @return None
			 */
			
			function themeists_likethis_most_liked_posts()
			{
		
				//load_plugin_textdomain( self::locale, false, plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . '/lang/' );

		
				$widget_opts = array (

					'classname' => 'themeists_likethis_most_liked_posts', 
					'description' => __( 'The posts which have the most "likes" from people clicking on the like links.', self::locale )

				);

				$control_options = array(

					'width' => '400'

				);

				//Register the widget
				$this->WP_Widget( self::slug, __( self::name, self::locale ), $widget_opts, $control_options );
		
		    	// Load JavaScript and stylesheets
		    	$this->register_scripts_and_styles();
		
			}/* themeists_likethis_most_liked_posts() */
		

			/* ============================================================================ */


			/**
			 * Outputs the content of the widget.
			 *
			 * @author Richard Tape
			 * @package themeists_likethis_most_liked_posts
			 * @since 1.0
			 * @param (array) $args - The array of form elements
			 * @param (array) $instance - The saved options from the widget controls
			 * @return None
			 */
			

			function widget( $args, $instance )
			{
		
				extract( $args, EXTR_SKIP );
		
				echo $before_widget;
		
					//Get vars
		    		$title					=	$instance['title'];
		    		$num_to_show			=	$instance['num_to_show'];

		    		?>
		    			
    				<h4 class="widget-title"><?php echo $title; ?></h4>

    				<?php

    				global $wpdb;
					$querystr = "
					    SELECT $wpdb->posts.* 
					    FROM $wpdb->posts, $wpdb->postmeta
					    WHERE $wpdb->posts.ID = $wpdb->postmeta.post_id 
					    AND $wpdb->postmeta.meta_key = '_likes' 
					    AND $wpdb->posts.post_status = 'publish' 
					    ORDER BY $wpdb->postmeta.meta_value DESC
					    LIMIT " . $num_to_show;

					$pageposts = $wpdb->get_results( $querystr, OBJECT );

					if( $pageposts ) :

						global $post;
						echo "<ul class='themeists_likethis_list'>";

							foreach( $pageposts as $post ) :

								setup_postdata( $post );

							$post_id = get_the_ID();
							$like_amount = get_post_meta( $post_id, "_likes", 1 );

								?>
								<li id="likethis_for_post_<?php echo $post_id; ?>" class="likethis_count_<?php echo $like_amount; ?>">
									<a href="<?php the_permalink(); ?>" rel="bookmark" title="<?php the_title(); ?>">
									<?php the_title(); ?>
									</a> <span class="likes_number"><?php echo $like_amount;?></span>
								</li>

							<?php endforeach;

						echo "</ul>"; 

					endif;

				echo $after_widget;
		
			}/* widget() */


			/* ============================================================================ */

		
			/**
			 * Processes the widget's options to be saved.
			 *
			 * @author Richard Tape
			 * @package themeists_likethis_most_liked_posts
			 * @since 1.0
			 * @param $new_instance	The previous instance of values before the update.
			 * @param @old_instance	The new instance of values to be generated via the update. 
			 * @return $instance The saved values
			 */
			
			function update( $new_instance, $old_instance )
			{
		
				$instance = $old_instance;
		
		    	$instance['title'] 			= 	$new_instance['title'];
		    	$instance['num_to_show'] 	= 	$new_instance['num_to_show'];
		    
				return $instance;
		
			}/* update() */


			/* ============================================================================ */


			/**
			 * Generates the administration form for the widget.
			 *
			 * @author Richard Tape
			 * @package themeists_likethis_most_liked_posts
			 * @since 1.0
			 * @param $instance	The array of keys and values for the widget.
			 * @return None
			 */
			

			function form( $instance )
			{
		
				$instance = wp_parse_args(

					(array)$instance,
					array(
						'title' => 'Most Liked Posts',
						'num_to_show' => '5'
					)

				);
		
		    	?>
		    	
		    		<p>
						<label for="<?php echo $this->get_field_id( 'title' ); ?>">
							<?php _e( "Title", THEMENAME ); ?>
						</label>
						<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo $instance['title']; ?>" />
					</p>

					<p>
						<label for="<?php echo $this->get_field_id( 'num_to_show' ); ?>">
							<?php _e( "Number of posts to show", THEMENAME ); ?>
						</label>
						<input type="text" class="widefat" id="<?php echo $this->get_field_id( 'num_to_show' ); ?>" name="<?php echo $this->get_field_name( 'num_to_show' ); ?>" value="<?php echo $instance['num_to_show']; ?>" />
					</p>
		    	
		    	<?php
		
			}/* form() */


			/* ============================================================================ */
		

			/**
			 * Registers and enqueues stylesheets for the administration panel and the
			 * public facing site.
			 *
			 * @author Richard Tape
			 * @package themeists_likethis_most_liked_posts
			 * @since 1.0
			 * @param None
			 * @return None
			 */
			

			private function register_scripts_and_styles()
			{

				if( is_admin() )
				{

		      		//$this->load_file('friendly_widgets_admin_js', '/themes/'.THEMENAME.'/admin/js/widgets.js', true);

				}
				else
				{ 

		      		//$this->load_file('friendly_widgets', '/themes/'.THEMENAME.'/theme_assets/js/widgets.js', true);

				}

			}/* register_scripts_and_styles() */


			/* ============================================================================ */


			/**
			 * Helper function for registering and enqueueing scripts and styles.
			 *
			 * @author Richard Tape
			 * @package themeists_likethis_most_liked_posts
			 * @since 1.0
			 * @param $name 		The ID to register with WordPress
			 * @param $file_path	The path to the actual file
			 * @param $is_script	Optional argument for if the incoming file_path is a JavaScript source file.
			 * @return None
			 */
			
			function load_file( $name, $file_path, $is_script = false )
			{
		
		    	$url = content_url( $file_path, __FILE__ );
				$file = $file_path;
					
				if( $is_script )
				{

					wp_register_script( $name, $url, '', '', true );
					wp_enqueue_script( $name );

				}
				else
				{

					wp_register_style( $name, $url, '', '', true );
					wp_enqueue_style( $name );

				}
			
			}/* load_file() */
		
		
		}/* class themeists_likethis_most_liked_posts */

	}

	//Register The widget
	//register_widget( "themeists_likethis_most_liked_posts" );
	add_action( 'widgets_init', create_function( '', 'register_widget( "themeists_likethis_most_liked_posts" );' ) );


?>