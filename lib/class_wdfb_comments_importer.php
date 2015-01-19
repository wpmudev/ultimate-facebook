<?php

class Wdfb_CommentsImporter {

	var $model;

	function __construct() {
		$this->model = new Wdfb_Model();
	}

	function Wdfb_CommentsImporter() {
		$this->__construct();
	}

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
			if ( $this->model->comment_already_imported( $comment['id'] ) ) {
				continue;
			} // We already have this comment, continue.
			$data       = array(
				'comment_post_ID'      => $post_id,
				'comment_date_gmt'     => date( 'Y-m-d H:i:s', strtotime( $comment['created_time'] ) ),
				'comment_author'       => $comment['from']['name'],
				'comment_author_url'   => 'http://www.facebook.com/' . $comment['from']['id'],
				'comment_content'      => utf8_encode( $comment['message'] ),
				'comment_author_email' => '',
				'comment_author_IP'    => ''
			);
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
			$feed = $this->model->get_feed_for( $fb_uid, $limit );
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
}