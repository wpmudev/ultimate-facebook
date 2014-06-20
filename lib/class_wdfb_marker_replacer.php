<?php

/**
 * Handles shortcodes.
 */
class Wdfb_MarkerReplacer {

	var $data;
	var $model;
	var $buttons = array(
		'like_button'     => 'wdfb_like_button',
		'events'          => 'wdfb_events',
		'album'           => 'wdfb_album',
		'connect'         => 'wdfb_connect',
		'recent_comments' => 'wdfb_recent_comments',
		'register_button' => 'wdfb_register_button',
	);

	function __construct() {
		$this->model = new Wdfb_Model;
		$this->data  = Wdfb_OptionsRegistry::get_instance();
	}

	function Wdfb_MarkerReplacer() {
		$this->__construct();
	}

	function get_button_tag( $b ) {
		if ( ! isset( $this->buttons[ $b ] ) ) {
			return '';
		}

		return '[' . $this->buttons[ $b ] . ']';
	}

	function process_register_button_code( $atts, $content = '' ) {
		if ( is_user_logged_in() ) {
			return '';
		}
		if ( ! $this->data->get_option( 'wdfb_connect', 'allow_facebook_registration' ) ) {
			return '';
		}

		$content = ! empty( $content ) ? $content : __( 'Register with Facebook', 'wdfb' );

		$base_url = defined( 'BP_VERSION' )
			? bp_get_signup_page()
			: apply_filters( 'wdfb-registration-registration_page', site_url( '/wp-signup.php', 'login' ) );
		$url      = add_query_arg( array(
			'fb_registration_page' => 1
		), $base_url );

		return '<p><a class="wdfb_register_button" href="' . $url . '"><span>' . $content . '</span></a></p>';
	}

	function process_connect_code( $atts, $content = '' ) {
		if ( ! $this->data->get_option( 'wdfb_connect', 'allow_facebook_registration' ) ) {
			return $content;
		}
		$atts        = shortcode_atts( array(
			'avatar_size' => 32,
			'redirect_to' => false,
		), $atts );
		$content     = $content ? $content : __( 'Log in with Facebook', 'wdfb' );
		$redirect_to = false;
		if ( $atts['redirect_to'] ) {
			$redirection_keywords = array(
				'current',
				'home',
			);
			// Proper link recognition
			if ( ! in_array( $atts['redirect_to'], $redirection_keywords ) ) {
				$redirect_to = esc_url( $atts['redirect_to'] );
			}
			if ( ! $redirect_to && 'home' == $atts['redirect_to'] ) {
				$redirect_to = home_url();
			}
			if ( ! $redirect_to && 'current' == $atts['redirect_to'] ) {
				global $wp;
				$redirect_to = site_url( $wp->request );
			}
		}
		if ( ! class_exists( 'Wdfb_WidgetConnect' ) ) {
			echo '<script type="text/javascript" src="' . WDFB_PLUGIN_URL . '/js/wdfb_facebook_login.js"></script>';
		}
		$user = wp_get_current_user();
		$html = '';
		if ( ! $user->ID ) {
			$html = '<p class="wdfb_login_button">' .
			        wdfb_get_fb_plugin_markup( 'login-button', array(
				        'scope'        => Wdfb_Permissions::get_permissions(),
				        'redirect-url' => ( $redirect_to
					        ? apply_filters( 'wdfb-login-redirect_url', $redirect_to )
					        : wdfb_get_login_redirect()
				        ),
				        'content'      => $content,
			        ) ) .
			        '</p>';
		} else {
			$redirect_to = $redirect_to ? apply_filters( 'wdfb-login-redirect_url', $redirect_to ) : home_url();
			$logout      = wp_logout_url( $redirect_to ); // Props jmoore2026
			$html .= get_avatar( $user->ID, $atts['avatar_size'] );
			$html .= "<br /><a href='{$logout}'>" . __( 'Log out', 'wdfb' ) . "</a>";
		}

		return $html;
	}

	function process_recent_comments_code( $atts = array(), $content = '' ) {
		$atts      = shortcode_atts( array(
			'limit'       => 5,
			'avatar_size' => false,
			'hide_text'   => false,
		), $atts );
		$limit     = (int) $atts['limit'];
		$size      = (int) $atts['avatar_size'];
		$hide_text = in_array( $atts['hide_text'], array( "true", "yes", "on", "1" ) );
		$out       = '';

		global $wpdb;
		$comments = $wpdb->get_results( "SELECT * FROM {$wpdb->comments} AS c, {$wpdb->commentmeta} AS mc WHERE mc.meta_key='wdfb_comment' AND c.comment_ID=mc.comment_id ORDER BY c.comment_date LIMIT {$limit}" );
		if ( ! $comments ) {
			return $out;
		}

		$out .= '<ul class="wdfb-recent_facebook_comments">';
		foreach ( $comments as $comment ) {
			$meta = unserialize( $comment->meta_value );
			$out .= '<li>';

			$out .= '<div class="wdfb-comment_author vcard">';
			if ( $size ) {
				$out .= '<img src="' . WDFB_PROTOCOL . 'graph.facebook.com/' . esc_attr( $meta['fb_author_id'] ) . '/picture" class="avatar avatar-' . $size . ' photo" height="' . $size . '" width="' . $size . '" />';
			}
			$out .= '<cite class="fn"><a href="' . WDFB_PROTOCOL . 'www.facebook.com/profile.php?id=' . esc_attr( $meta['fb_author_id'] ) . '">' . esc_html( $comment->comment_author ) . '</a></cite>';
			$out .= '</div>';

			if ( ! $hide_text ) {
				$out .= '<div class="wdfb-comment_body">';
				$out .= esc_html( $comment->comment_content );
				$out .= '</div>';
			}

			$out .= '<div class="wdfb-comment_meta">';
			$out .= mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $comment->comment_date );
			$out .= __( '&nbsp;on&nbsp;', 'wdfb' );
			$out .= '<a href="' . get_permalink( $comment->comment_post_ID ) . '">' . get_the_title( $comment->comment_post_ID ) . '</a>';
			$out .= '</div>';

			$out .= '</li>';
		}
		$out .= '</ul>';

		return $out;
	}

	function process_like_button_code( $atts, $content = '' ) {
		global $wp_current_filter;

		// Check allowed
		$allow = $this->data->get_option( 'wdfb_button', 'allow_facebook_button' );
		if ( ! apply_filters( 'wdfb-show_facebook_button', $allow ) ) {
			return '';
		}

		// Check nesting (i.e. posts within post, reformatted with apply_filters)
		$filters = array_count_values( $wp_current_filter );
		if ( isset( $filters['the_content'] ) && $filters['the_content'] > 1 ) {
			return '';
		}

		$atts   = shortcode_atts( array(
			'forced' => false,
		), $atts );
		$forced = ( $atts['forced'] && 'no' != $atts['forced'] ) ? true : false;

		$in_types  = $this->data->get_option( 'wdfb_button', 'not_in_post_types' );
		$in_types  = is_array( $in_types ) ? $in_types : array();
		$post_type = get_post_type();
		if ( ( $post_type && in_array( get_post_type(), $in_types ) ) && ! $forced ) {
			return '';
		}

		$is_activity = defined( 'BP_VERSION' ) && isset( $filters['bp_get_activity_content_body'] );
		if ( $is_activity && ! @in_array( '_buddypress_activity', $in_types ) ) {
			return '';
		} // Reverse logic for BuddyPress Activity check.

		$send   = $this->data->get_option( 'wdfb_button', 'show_send_button' );
		$layout = $this->data->get_option( 'wdfb_button', 'button_appearance' );
		$url    = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		/*
				$width = ("standard" == $layout) ? 300 : (
					("button_count" == $layout) ? 150 : 60
				);
		*/
		$width = 450;
		$width = apply_filters( 'wdfb-like_button-width', $width );

		$scheme = $this->data->get_option( 'wdfb_button', 'color_scheme' );
		$scheme = $scheme ? $scheme : 'light';

		if (
			( is_home() && $this->data->get_option( 'wdfb_button', 'show_on_front_page' ) )
			||
			( is_archive() && $this->data->get_option( 'wdfb_button', 'show_on_archive_page' ) )
			||
			( defined( 'BP_VERSION' ) && $is_activity && ! wdfb_is_single_bp_activity() )
		) {
			$tmp_url = $is_activity && function_exists( 'bp_activity_get_permalink' ) ? bp_activity_get_permalink( bp_get_activity_id() ) : ( in_the_loop() ? get_permalink() : false );
			$href    = ! empty( $tmp_url ) ? $tmp_url : $url;
			$locale  = wdfb_get_locale();

			$height = ( "box_count" == $layout ) ? 60 : 25;
			$height = apply_filters( 'wdfb-like_button-height', $height );

			$use_xfbml = false;
			if (
				( defined( 'BP_VERSION' ) && $is_activity && ! wdfb_is_single_bp_activity() && $this->data->get_option( 'wdfb_button', 'bp_activity_xfbml' ) )
				||
				( is_home() && $this->data->get_option( 'wdfb_button', 'show_on_front_page' ) && $this->data->get_option( 'wdfb_button', 'shared_pages_use_xfbml' ) )
				||
				( is_archive() && $this->data->get_option( 'wdfb_button', 'show_on_archive_page' ) && $this->data->get_option( 'wdfb_button', 'shared_pages_use_xfbml' ) )
			) {
				$use_xfbml = true;
			}

			$href = apply_filters( 'wdfb-like_button-href_attribute', WDFB_PROTOCOL . preg_replace( '/^https?:\/\//', '', $href ) );

			return $use_xfbml
				? '<div class="wdfb_like_button">' . wdfb_get_fb_plugin_markup( 'like', compact( array(
							'href',
							'send',
							'layout',
							'width',
							'scheme'
						) ) ) . '</div>'
				: "<div class='wdfb_like_button'><iframe src='http://www.facebook.com/plugins/like.php?&amp;href=" . rawurlencode( $href ) . "&amp;send=false&amp;layout={$layout}&amp;show_faces=false&amp;action=like&amp;colorscheme={$scheme}&amp;font&amp;height={$height}&amp;width={$width}&amp;locale={$locale}' scrolling='no' frameborder='0' style='border:none; overflow:hidden; height:{$height}px; width:{$width}px;' allowTransparency='true'></iframe></div>";
		}

		$href = apply_filters( 'wdfb-like_button-href_attribute', WDFB_PROTOCOL . preg_replace( '/^https?:\/\//', '', $url ) );

		return '<div class="wdfb_like_button">' .
		       wdfb_get_fb_plugin_markup( 'like', compact( array(
			       'href',
			       'send',
			       'layout',
			       'width',
			       'scheme'
		       ) ) ) .
		       '</div>';
	}

	function process_events_code( $atts, $content = '' ) {
		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$atts = shortcode_atts( array(
			'for'             => false,
			'starting_from'   => false,
			'only_future'     => false,
			'show_image'      => "true",
			'show_location'   => "true",
			'show_start_date' => "true",
			'show_end_date'   => "true",
			'order'           => false,
		), $atts );

		if ( ! $atts['for'] ) {
			return '';
		} // We don't know whose events to show

		$api    = new Wdfb_EventsBuffer;
		$events = $api->get_for( $atts['for'] );
		if ( ! is_array( $events ) || empty( $events ) ) {
			return $content;
		}

		if ( $atts['order'] ) {
			$events = $this->_sort_by_time( $events, $atts['order'] );
		}

		$show_image       = ( "true" == $atts['show_image'] ) ? true : false;
		$show_location    = ( "true" == $atts['show_location'] ) ? true : false;
		$show_start_date  = ( "true" == $atts['show_start_date'] ) ? true : false;
		$show_end_date    = ( "true" == $atts['show_end_date'] ) ? true : false;
		$timestamp_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		$date_threshold = $atts['starting_from'] ? strtotime( $atts['starting_from'] ) : false;
		if ( $atts['only_future'] && 'false' != $atts['only_future'] ) {
			$now            = time();
			$date_threshold = ( $date_threshold && $date_threshold > $now ) ? $date_threshold : $now;
		}

		$current_tz = function_exists( 'date_default_timezone_get' ) ? @date_default_timezone_get() : 'UTC';
		ob_start();
		foreach ( $events as $event ) {
			if ( function_exists( 'date_default_timezone_set' ) && ! empty( $event['timezone'] ) ) {
				$start_time = isset ( $event['start_time'] ) ? strtotime( $event['start_time'] ) : '';
				$end_time   = isset ( $event['end_time'] ) ? strtotime( $event['end_time'] ) : '';
				date_default_timezone_set( $event['timezone'] );
				$event['start_time'] = !empty ( $start_time ) ?  date( 'Y-m-d H:i:s', $start_time ) : '';
				$event['end_time']   = !empty ( $end_time ) ?  date( 'Y-m-d H:i:s', $end_time ) : '';
				date_default_timezone_set( $current_tz );
			}
			if ( $date_threshold > strtotime( $event['start_time'] ) ) {
				continue;
			}
			include( WDFB_PLUGIN_BASE_DIR . '/lib/forms/event_item.php' );
		}
		$ret = ob_get_contents();
		ob_end_clean();

		return "<div><ul>{$ret}</ul></div>";
	}

	function process_album_code( $atts, $content = '' ) {

		$post_id = get_the_ID();
		if ( ! $post_id ) {
			return '';
		}

		$atts = shortcode_atts( array(
			'id'           => false,
			'limit'        => false,
			'photo_class'  => 'thickbox',
			'album_class'  => false,
			'photo_width'  => 75,
			'photo_height' => false,
			'crop'         => false,
			'link_to'      => 'source',
			'columns'      => false,
		), $atts );

		if ( ! $atts['id'] ) {
			return '';
		} // We don't know what album to show
		$img_w = (int) $atts['photo_width'];
		$img_h = (int) $atts['photo_height'];

		$fb_open = ( 'source' != $atts['link_to'] );

		$api    = new Wdfb_AlbumPhotosBuffer;
		$photos = $api->get_for( $atts['id'], $atts['limit'] );
		if ( ! is_array( $photos ) ) {
			return $content;
		}

		$ret = false;
		$i   = 0;

		$display_idx = ( $img_w >= 130 )
			? ( ( $img_w >= 180 ) ? 0 : 1 )
			: ( ( $img_w >= 75 ) ? 2 : 3 );

		$columns = (int) $atts['columns'];
		$current = 1;
		foreach ( $photos as $photo ) {
			$photo_idx = isset( $photo['images'][ $display_idx ] ) ? $display_idx : count( $photo['images'] ) - 1;
			$style     = $atts['crop'] ? "style='display:block;float:left;height:{$img_h}px;overflow:hidden'" : '';
			$url       = $fb_open
				? WDFB_PROTOCOL . 'www.facebook.com/photo.php?fbid=' . $photo['id']
				: $photo['images'][0]['source'];
			$name      = ! empty( $photo['name'] ) ? 'title="' . esc_attr( $photo['name'] ) . '"' : '';
			$ret .= '<a href="' . $url .
			        '" class="' . $atts['photo_class'] . '" rel="' . $atts['id'] . '-photo" ' . $style . ' ' . $name . '>' .
			        '<img src="' . $photo['images'][ $photo_idx ]['source'] . '" ' .
			        ( $img_w ? "width='{$img_w}'" : '' ) .
			        ( $img_h && ! $atts['crop'] ? "height='{$img_h}'" : '' ) .
			        ' />' .
			        '</a>';
			if ( $columns && ( ++$i % $columns ) == 0 ) {
				$ret .= '<br ' . ( $style ? 'style="clear:left"' : '' ) . '/>';
			}
			if ( (int) $atts['limit'] && $current >= (int) $atts['limit'] ) {
				break;
			}
			$current ++;
		}

		return "<div class='{$atts['album_class']}'>{$ret}</div>";
	}

	/**
	 * Helper for sorting events by their start_time.
	 */
	function _sort_by_time( $events, $direction = "ASC" ) {
		usort( $events, create_function(
			'$a,$b',
			'if (strtotime($a["start_time"]) == strtotime($b["start_time"])) return 0;' .
			'return (strtotime($a["start_time"]) > strtotime($b["start_time"])) ? 1 : -1;'
		) );

		return ( "DESC" == $direction ) ? array_reverse( $events ) : $events;
	}

	/**
	 * Registers shortcode handlers.
	 */
	function register() {
		foreach ( $this->buttons as $key => $shortcode ) {
			add_shortcode( $shortcode, array( $this, "process_{$key}_code" ) );
		}
	}
}