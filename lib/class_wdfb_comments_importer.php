<?php

class Wdfb_CommentsImporter {

	var $model;

	function __construct() {
		$this->model = new Wdfb_Model();
	}

	function Wdfb_CommentsImporter() {
		$this->__construct();
	}

	/**
	 * Get facebook comments for the give Facebook item id
	 * @param $post_id
	 * @param $item_id
	 *
	 * @return bool|void
	 */
	function process_comments( $post_id, $item_id ) {
		if ( empty( $post_id ) || empty( $item_id ) ) {
			return;
		}
		$comments = $this->model->get_item_comments( $item_id );

		if ( ! $comments || ! isset( $comments['data'] ) ) {
			return;
		}
		$comments = $comments['data'];

		if ( ! count( $comments ) ) {
			return false;
		}
		foreach ( $comments as $comment ) {
			$imported = $this->model->comment_already_imported( $comment['id'] );
			if (  !$imported ) {
				$comment_id = $this->add_comment( $post_id, $comment );
			}else{
				//Get Comment id from WordPress
				$comment_id = !empty( $imported->comment_id ) ? $imported->comment_id : '';
			}
			//Get Child comments
			$c_comments = $this->model->get_item_comments( $comment['id'] );
			if ( ! empty( $c_comments ) && ! empty( $c_comments['data'] ) ) {
				$c_comments = $c_comments['data'];
				$this->add_comments_child( $c_comments, $post_id, $comment_id );
			}
		}
	}

	function process_commented_posts( $posts ) {
		if ( ! count( $posts ) ) {
			return false;
		}
		foreach ( $posts as $post ) {
			if ( empty( $post['link'] ) || ( ! empty( $post['type'] ) && $post['type'] != 'link' ) ) {
				continue;
			}
			$post_id = wdfb_url_to_postid( $post['link'] );
			if ( empty( $post_id ) ) {
				continue;
			} // Not a post on this blog. Continue.
			$this->process_comments( $post_id, $post['id'] );

			unset( $post );
		}
		unset( $posts );
	}

	function import_comments() {
		$limit = (int) $this->model->data->get_option( 'wdfb_comments', 'comment_limit' );
		$limit = $limit ? $limit : 10;

		$tokens = $this->model->data->get_option( 'wdfb_api', 'auth_tokens' );
		$tokens = is_array( $tokens ) ? $tokens : array();

		$skips = $this->model->data->get_option( 'wdfb_comments', 'skip_import' );
		$skips = is_array( $skips ) ? $skips : array();

		$reverse = $this->model->data->get_option( 'wdfb_comments', 'reverse_skip_logic' );

		foreach ( $tokens as $fb_uid => $token ) {
			if ( ! $fb_uid ) {
				continue;
			}
			if ( $reverse ) {
				if ( ! in_array( $fb_uid, $skips ) ) {
					continue;
				}
			} else {
				if ( in_array( $fb_uid, $skips ) ) {
					continue;
				}
			}
			$feed = $this->model->get_feed_for( $fb_uid, $limit, 'link,comments' );
			//if (!isset($feed['data'])) return false; // Nothing to import
			if ( ! isset( $feed['data'] ) ) {
				continue;
			} // Nothing to import
			$commented_posts = array();
			foreach ( $feed['data'] as $post ) {
				if ( empty( $post['comments'] ) || empty( $post['comments']['data'] ) ) {
					continue;
				} // Skip uncommented posts
				$commented_posts[] = $post;
			}
			$this->process_commented_posts( $commented_posts );
		}
	}

	/**
	 * @static
	 */
	public static function serve() {
		$me = new Wdfb_CommentsImporter;
		$me->import_comments();
	}

	/**
	 * Inserts a facebook comment as WordPress comment
	 *
	 * @param $post_id
	 * @param $comment
	 */
	function add_comment( $post_id, $comment, $comment_parent = '' ) {
		$data = array(
			'comment_post_ID'      => $post_id,
			'comment_date_gmt'     => date( 'Y-m-d H:i:s', strtotime( $comment['created_time'] ) ),
			'comment_author'       => $comment['from']['name'],
			'comment_author_url'   => 'http://www.facebook.com/' . $comment['from']['id'],
			'comment_content'      => utf8_encode( $comment['message'] ),
			'comment_author_email' => '',
			'comment_author_IP'    => ''
		);
		//Add Comment parent if any
		$comment_parent = ! empty( $comment_parent ) ? intval( $comment_parent ) : '';
		if ( ! empty( $comment_parent ) && $comment_parent && null != get_comment( $comment_parent ) ) {
			$data['comment_parent'] = $comment_parent;
		}

		$meta       = array(
			'fb_comment_id' => $comment['id'],
			'fb_author_id'  => $comment['from']['id'],
		);
		$data       = wp_filter_comment( $data );
		$comment_id = wp_insert_comment( $data );
		add_comment_meta( $comment_id, 'wdfb_comment', $meta );

		if ( $this->model->data->get_option( 'wdfb_comments', 'notify_authors' ) ) {
			wp_notify_postauthor( $comment_id );
		}
		return $comment_id;
	}

	/**
	 * Add the child comments on a post for a given comment id
	 *
	 * @param $n_comments
	 * @param $post_id
	 * @param $comment_parent
	 */
	function add_comments_child( $n_comments, $post_id, $comment_parent ) {
		foreach ( $n_comments as $comment ) {
			//If the comment hasn't already been added
			if ( ! empty( $comment['message'] ) && ! $this->model->comment_already_imported( $comment['id'] ) ) {
				$this->add_comment( $post_id, $comment, $comment_parent );
			}
		}
	}
}