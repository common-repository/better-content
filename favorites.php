<?php
/**
 * Better Content Favorites Widget
 */
class bettercontent_favorites_widget extends WP_Widget {
	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		$widget_ops = array( 
			'classname' => 'bettercontent_favorites_widget',
			'description' => 'My Widget is awesome',
		);
		parent::__construct( 'bettercontent_favorites_widget', 'BetterContent Favorites Widget', $widget_ops );
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {

		if ( array_key_exists('before_widget', $args) ) echo $args['before_widget'];

		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}

		global $current_user;
	    global $wpdb;
	    $table_name = $wpdb->prefix . 'bettercontent_favorite';
	    $sql = "SELECT id, post_id FROM ".$table_name." WHERE user_id=".$current_user->ID . " ORDER BY sort";
	    $result = $wpdb->get_results($sql);

        echo '<ul class="bettercontent-sortable">';
	
	    foreach( $result as $results ) {
	        $post_title = get_the_title ($results->post_id);
	        $post_url = get_permalink ($results->post_id);
	        echo '<li data-favorite_id="' . $results->id . '"><a href="' . $post_url . '">' . $post_title . '</a></li>' ;
	    }
	    
	    echo '</ul>';

		if ( array_key_exists('after_widget', $args) ) echo $args['after_widget'];		
		
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance ) {
		
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		}
		else {
			$title = __( 'Favorites', 'text_domain' );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php 
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance ) {
		
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		return $instance;
	}
}

add_action( 'widgets_init', function () { register_widget( 'bettercontent_favorites_widget' );
});

?>