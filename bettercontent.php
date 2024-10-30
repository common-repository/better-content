<?php

/*
Plugin Name: Better Content
Plugin URI: http://www.iamklaus.org/better-content
Description: Logged in user have the ability to track changes of single (custom) posts by receiving an email.
Author: iamklaus
Version: 1.1
Author URI: http://www.iamklaus.org
*/

include("favorites.php");

register_activation_hook( __FILE__, 'bettercontent_create_db' );
function bettercontent_create_db() {

    global $wpdb;
    $charset_collate = $wpdb->get_charset_collate();
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    
    $table_name = $wpdb->prefix . 'bettercontent_notifyme';
    $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            UNIQUE KEY id (id)
    ) $charset_collate;";
    dbDelta( $sql );
    
    $table_name = $wpdb->prefix . 'bettercontent_favorite';
    $sql = "CREATE TABLE $table_name (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            user_id bigint(20) NOT NULL,
            url VARCHAR(255),
            sort INTEGER,
            UNIQUE KEY id (id)
    ) $charset_collate;";
    dbDelta( $sql );
}

add_action( 'wp_enqueue_scripts', 'ajax_enqueue_scripts' );
function ajax_enqueue_scripts() {
    
	if( is_single() ) {
    	wp_enqueue_style( 'bettercontent', plugins_url( '/css/bettercontent.css', __FILE__ ) );
	}
	
    wp_enqueue_script ('jquery-ui-sortable');
	wp_enqueue_style( 'bettercontent', plugins_url( '/css/bettercontent.css', __FILE__ ) );
	wp_enqueue_script( 'bettercontent', plugins_url( '/js/bettercontent.js', __FILE__ ), array('jquery'), '1.0', true );
	wp_localize_script( 'bettercontent', 'postbettercontent', array( 'ajax_url' => admin_url( 'admin-ajax.php' )	));
}

add_filter( 'the_content', 'post_bettercontent_display' );
function post_bettercontent_display ( $content ) {    

    if(!is_single() || !is_user_logged_in ()) 
        return $content;

    $content .= '<ul class="bettercontent">';
    $content .= post_notifyme_display ( '<li class="bettercontent">', '</li>' );
    $content .= post_favorite_display ( '<li class="bettercontent">',  '</li>' );
    $content .= post_delete_display ( '<li class="bettercontent">',  '</li>' );
    $content .= '</ul>';
    
    return $content;
}

function post_notifyme_display ( $before, $after ) {
    
    global $post;
    global $wpdb;
    $current_user = wp_get_current_user();
    
    $table_name = $wpdb->prefix . 'bettercontent_notifyme';
    $sql = "SELECT count(user_id) FROM ".$table_name." WHERE post_id=".$post->ID." and user_id=".$current_user->ID;
    $result_count = $wpdb->get_var($sql);

    if($result_count > 0) {
	    $content = '<a class="bettercontent-button" id="bettercontent-notifyme" href="' 
	        . admin_url( 'admin-ajax.php?action=post_bettercontent_add_notifyme&post_id='
	        . get_the_ID() ) 
	        . '" data-action="del" data-id="'
	        . get_the_ID() 
	        . '">' . __( 'Unfollow changes', 'bettercontent' ) . '</a>'; 
    } else {
	    $content = '<a class="bettercontent-button" id="bettercontent-notifyme" href="' 
	        . admin_url( 'admin-ajax.php?action=post_bettercontent_add_notifyme&post_id='
	        . get_the_ID() ) 
	        . '" data-action="add" data-id="'
	        . get_the_ID() 
	        . '">' . __( 'Follow changes', 'bettercontent' ) . '</a>'; 
	}
	
	return $before . $content . $after;
}

function post_favorite_display( $before, $after ) {
    global $post;
    global $wpdb;
    $current_user = wp_get_current_user();
    
    $table_name = $wpdb->prefix . 'bettercontent_favorite';
    $sql = "SELECT count(user_id) FROM ".$table_name." WHERE post_id=".$post->ID." and user_id=".$current_user->ID;
    $result_count = $wpdb->get_var($sql);

    if($result_count > 0) {
	    $content = '<a class="bettercontent-button" id="bettercontent-favorite" href="' 
	        . admin_url( 'admin-ajax.php?action=post_bettercontent_add_favorite&post_id='
	        . get_the_ID() ) 
	        . '" data-action="del" data-id="'
	        . get_the_ID() 
	        . '">' . __( 'Remove from favorites', 'bettercontent' ) . '</a>'; 
    } else {
	    $content = '<a class="bettercontent-button" id="bettercontent-favorite" href="' 
	        . admin_url( 'admin-ajax.php?action=post_bettercontent_add_favorite&post_id='
	        . get_the_ID() ) 
	        . '" data-action="add" data-id="'
	        . get_the_ID() 
	        . '">' . __( 'Add to favorites', 'bettercontent' ) . '</a>'; 
	}
	
	return $before . $content . $after;
}

function post_delete_display($before, $after) {

    global $post;
    if ( current_user_can( 'edit_post', $post->ID ) ) {
        $deletepostlink= add_query_arg( 'frontend', 'true', get_delete_post_link( $post->ID ) );
        $content = '<a class="bettercontent-button" onclick="return confirm(\'' . __( 'Do you really want to delete the post?', 'bettercontent' ) . '\')" href="'.$deletepostlink.'">' . __( 'Delete post', 'bettercontent' ) . '</a></span>';
        return $before . $content . $after; 
    }
}

// Redirect after delete post in frontend
add_action('trashed_post','bettercontent_trash_redirection_frontend');
function bettercontent_trash_redirection_frontend($post_id) {
    if ( filter_input( INPUT_GET, 'frontend', FILTER_VALIDATE_BOOLEAN ) ) {
        wp_redirect( get_option('siteurl').'/' );
        exit;
    }
}

add_action( 'wp_ajax_nopriv_post_bettercontent_add_notifyme', 'post_bettercontent_add_notifyme' );
add_action( 'wp_ajax_post_bettercontent_add_notifyme', 'post_bettercontent_add_notifyme' );
function post_bettercontent_add_notifyme() {

    global $wpdb;
    $current_user = wp_get_current_user();
    $table_name = $wpdb->prefix . 'bettercontent_notifyme';

	if ($_REQUEST['post_action'] == 'add') {
	    $wpdb->insert( $table_name, array( 'user_id' => $current_user->ID, 'post_id' => $_REQUEST['post_id']) );
	    $message = __( 'Unfollow changes', 'bettercontent' );
	} elseif ($_REQUEST['post_action'] == 'del') {
	    $wpdb->delete( $table_name, array( 'user_id' => $current_user->ID, 'post_id' => $_REQUEST['post_id']) );
	    $message = __( 'Follow changes', 'bettercontent' );
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	    echo $message;
		die();
	}
	else {
		wp_redirect( get_permalink( $_REQUEST['post_id'] ) );
		exit();
	}
}

add_action( 'wp_ajax_nopriv_post_bettercontent_add_favorite', 'post_bettercontent_add_favorite' );
add_action( 'wp_ajax_post_bettercontent_add_favorite', 'post_bettercontent_add_favorite' );
function post_bettercontent_add_favorite() {

    global $wpdb;
    $current_user = wp_get_current_user();
    $table_name = $wpdb->prefix . 'bettercontent_favorite';

	if ($_REQUEST['post_action'] == 'add') {
	    $wpdb->insert( $table_name, array( 'user_id' => $current_user->ID, 'post_id' => $_REQUEST['post_id']) );
	    $message = __( 'Remove from favorites', 'bettercontent' );
	} elseif ($_REQUEST['post_action'] == 'del') {
	    $wpdb->delete( $table_name, array( 'user_id' => $current_user->ID, 'post_id' => $_REQUEST['post_id']) );
	    $message = __( 'Add to favorites', 'bettercontent' );
	}

	if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
	    echo $message;
		die();
	}
	else {
		wp_redirect( get_permalink( $_REQUEST['post_id'] ) );
		exit();
	}
}

add_action( 'wp_ajax_nopriv_post_bettercontent_favorite_sort', 'post_bettercontent_favorite_sort' );
add_action( 'wp_ajax_post_bettercontent_favorite_sort', 'post_bettercontent_favorite_sort' );
function post_bettercontent_favorite_sort() {

    if (isset($_POST['favorite_sort'])) {
        $sort = 0;
        global $wpdb; 
        $table_name = $wpdb->prefix . 'bettercontent_favorite';
        $json_favorite_sort = stripslashes ( $_POST['favorite_sort'] );
        $favorite_sort = json_decode($json_favorite_sort, true);
        foreach ($favorite_sort as $favorite) {
            $wpdb->update( $table_name, array( 'sort' => $sort), array( "id" => $favorite['favorite_id'])); 
            $sort++;
        }
    }
}

add_action( 'post_updated', 'bettercontent_notifyme_post_change', 10, 3 ); 
function bettercontent_notifyme_post_change($post_id, $post_after, $post_before) {
        
    // If this is just a revision, don't send the email.
    if ( wp_is_post_revision( $post_id ) )
        return;
            
    $post_title = get_the_title( $post_id );
    $post_url = get_permalink( $post_id );
    
    
    if ($post_after->post_status == "trash") {
        $subject = __( 'A post has been trashed', 'bettercontent' );
        $message = __( 'A post has been trashed on your website:', 'bettercontent') . '<br><br>';
        $message .= $post_title . ": " . $post_url . '<br><br>';
    } else {
        $left_string = $post_before->post_title."\n".$post_before->post_content;
        $right_string = $post_after->post_title."\n".$post_after->post_content;
        if(!($diff_table = wp_text_diff($left_string, $right_string))) return;
        $diff_table = htmlspecialchars_decode($diff_table);

        $css = "<style type=\"text/css\">";
        $css .= "table.diff { width: 100%; }";
        $css .= "table.diff .diff-sub-title th { text-align: left; background-color: #f00;}";
        $css .= "table.diff th { text-align: left; }";
        $css .= "table.diff .diff-deletedline { background-color:#fdd; width: 50%; }";
        $css .= "table.diff .diff-deletedline del { background-color:#f99; text-decoration: none; }";
        $css .= "table.diff .diff-addedline { background-color:#dfd; width: 50%; }";
        $css .= "table.diff .diff-addedline ins { background-color:#9f9; text-decoration: none; }";
        $css .= "</style>";
        
        $subject = __('A post has been updated', 'bettercontent');
        $message = $css;
        $message .= __('A post has been updated on your website:' , 'bettercontent') . '<br><br>';
        $message .= $post_url."<br><br>";
        $message .= $diff_table . "<br><br>";
    }

    if ( $current_user = wp_get_current_user() ) {
        if (function_exists( 'bp_loggedin_user_domain' )) {
            $message .= __('Modified by', 'bettercontent') . ': <a href="'. bp_loggedin_user_domain() . '">'. $current_user->display_name . '</a>';
        
        } else {
            $message .= __('Modified by', 'bettercontent') . ': <a href="'. get_author_posts_url($current_user->ID). '">'. $current_user->display_name . '</a>';
        }
    }
    
    global $wpdb;
    $table_name = $wpdb->prefix . 'bettercontent_notifyme';
    $sql = "SELECT user_id, post_id FROM ".$table_name." WHERE post_id=".$post_id;
    $result = $wpdb->get_results($sql);
    $headers = array('Content-Type: text/html; charset=UTF-8');

    foreach( $result as $results ) {
        $user_info = get_userdata($results->user_id);
        wp_mail( $user_info->user_email, $subject, $message, $headers);
    }
}

add_action( 'comment_post', 'bettercontent_notifyme_post_comment', 10, 2 );
function bettercontent_notifyme_post_comment( $comment_ID, $comment_approved ) {

	if( 1 === $comment_approved ) {
	    $comment = get_comment($comment_ID);
        $post = get_post($comment->comment_post_ID);
	    
        global $wpdb;
        $table_name = $wpdb->prefix . 'bettercontent_notifyme';
        $sql = "SELECT user_id, post_id FROM ".$table_name." WHERE post_id=".$post->ID;
        $result = $wpdb->get_results($sql);
        $headers = array('Content-Type: text/html; charset=UTF-8');
        
        $subject = __('New comment on the post you follow' , 'bettercontent');
        $message = __('New comment on' , 'bettercontent') . ': <a href="' . get_permalink( $post->ID ) . '">' . $post->post_title . '</a><br><br>';
        $message .= '"' . $comment->comment_content . '"<br><br>';
        
        if ( $current_user = wp_get_current_user() ) {
            if (function_exists( 'bp_loggedin_user_domain' )) {
                $message .= __('Comment by', 'bettercontent') . ': <a href="'. bp_loggedin_user_domain() . '">'. $current_user->display_name . '</a>';
            
            } else {
                $message .= __('Comment by', 'bettercontent') . ': <a href="'. get_author_posts_url($current_user->ID). '">'. $current_user->display_name . '</a>';
            }
        }
        
        foreach( $result as $results ) {
            $user_info = get_userdata($results->user_id);
            wp_mail( $user_info->user_email, $subject, $message, $headers);
        }
	}
}