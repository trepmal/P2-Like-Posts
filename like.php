<?php
/*
 * Plugin Name: Likes
 * Description: "Like" posts
 * Plugin URI:
 * Version: 2012-11-05
 * Author: Kailey Lampert
 * Author URI: http://kaileylampert.com
 */

// give our set of plugins a special filter for sorting actions
if ( ! has_action( 'p2_action_links', 'p2_append_actions') ) {
	add_action( 'p2_action_links', 'p2_append_actions');
	function p2_append_actions( ) {

		if ( ! is_user_logged_in() ) return;
		$items = apply_filters( 'p2_action_items', array() );
		ksort( $items );
		$items = implode( ' | ', $items );

		echo " | $items";
	}
}

$p2_like = new P2_Like();

class P2_Like {

	function __construct() {
		add_action( 'init', array( &$this, 'init' ) );
	}

	function init() {
		register_taxonomy( 'like', 'post', array(
				'hierarchical' => false,
				'label' => 'Like',
				'sort' => true,
				'public' => true,
				// eventually customize our "who likes this" display, in the meantime use standard metabox
				// 'show_ui' => false,
				'rewrite' => array('slug' => 'like'),
			)
		);

		if ( ! is_user_logged_in() ) return;

		add_action( 'wp_enqueue_scripts', array( &$this, 'wp_enqueue_scripts' ) );
		add_action( 'wp_ajax_toggle_like', array( &$this, 'toggle_like_cb' ) );
		add_filter( 'p2_action_items', array( &$this, 'p2_action_items' ) );

		add_action( 'admin_bar_menu', array( &$this, 'admin_bar_menu' ) );
	}
	function wp_enqueue_scripts() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'like', plugins_url( 'js/like.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script( 'like', 'like', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
	}

	function p2_action_items( $items ) {
		if ( is_page() ) return $items;

		$cl = $this->current_like( get_the_ID() );

		$items[-20] = '<span class="like-posts" title="'. $this->get_who_likes( get_the_ID() ) .'">
						<span class="count-like">'. $this->get_total_likes( get_the_ID() ) .'</span>
						<a href="#" class="toggle-like"'. ( $cl == 'Like' ? '' : ' style="color:#c55;"' ) .'">&hearts;</a>
					</span>';
		return $items;
	}

	function get_total_likes( $post_id ) {
		$terms = wp_get_post_terms( $post_id, 'like' );
		if ( is_wp_error( $terms ) )
			return 0;
		return count( $terms );
	}

	function get_who_likes( $post_id ) {
		$terms = wp_get_post_terms( $post_id, 'like' );
		$who = array();
		if ( is_wp_error( $terms ) )
			return '';
		$who = wp_list_pluck( $terms, 'name' );
		return implode(', ', $who);
	}

	// a little wonky
	// returns the action the user can take depending on their current status
	// i.e. if the user likes the post, the function will return "Unlike"
	function current_like( $post_id ) {
		$username = get_userdata( get_current_user_id() )->user_login;
		if ( has_term( $username, 'like', $post_id ) )
			return 'Unlike';
		return 'Like';
	}

	// ajax callback
	function toggle_like_cb() {
		$id = $_POST['post_id'];

		$username = get_userdata( get_current_user_id() )->user_login;
		// check if user currently likes the post
		// if they currently like the item, proceed to unlike it
		if ( $this->current_like( $id ) == 'Unlike' ) {
			// can't remove a single term very nicely
			// collect terms
			$terms = wp_get_post_terms( $id, 'like' );
			$names = wp_list_pluck( $terms, 'name' );

			// remove unwanted term
			$names = array_flip( $names );
			unset( $names[ $username ] );
			$keep = array_flip( $names );

			//put the rest back
			wp_set_post_terms( $id, $keep, 'like' );

			$like = 'Like';
		} else {
			// adding a term is easy
			wp_set_post_terms( $id, $username, 'like', true );
			$like = 'Unlike';
		}
		// send back new button text, total likes count, and who likes
		$r = array( 'like' => $like, 'total' => $this->get_total_likes( $id ), 'who' => $this->get_who_likes( $id ) );
		die( json_encode( $r ) );

	}

	function admin_bar_menu( $wp_admin_bar ) {

		if ( is_admin() ) return;

		$login = wp_get_current_user()->user_login;

		$node = array (
			'parent' => 'my-account',
			'id' => 'my-likes',
			'title' => 'My Likes',
			'href' => get_term_link( $login, 'like' )
		);

		$wp_admin_bar->add_menu( $node );

	}

}
