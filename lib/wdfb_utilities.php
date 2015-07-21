<?php
/**
 * Misc utilities, helpers and handlers.
 */


/**
 * Helper function for generating the registration fields array.
 */
function wdfb_get_registration_fields_array() {
	global $current_site;
	$data          = Wdfb_OptionsRegistry::get_instance();
	$model         = new Wdfb_Model;
	$wp_grant_blog = false;

	if( !$model->registration_allowed() ) {
		return array();
	}

	$fields = array(
		array( "name" => "name" ),
		array( "name" => "email" ),
		array( "name" => "first_name" ),
		array( "name" => "last_name" ),
		array( "name" => "gender" ),
		array( "name" => "location" ),
		array( "name" => "birthday" ),
	);
	if ( $wp_grant_blog ) {
		$fields[]  = array(
			'name'        => 'blog_title',
			'description' => __( 'Your blog title', 'wdfb' ),
			'type'        => 'text',
		);
		$newdomain = is_subdomain_install()
			? 'youraddress.' . preg_replace( '|^www\.|', '', $current_site->domain )
			: $current_site->domain . $current_site->path . 'youraddress';
		$fields[]  = array(
			'name'        => 'blog_domain',
			'description' => sprintf( __( 'Your blog address (%s)', 'wdfb' ), $newdomain ),
			'type'        => 'text',
		);
	}
	if ( ! $data->get_option( 'wdfb_connect', 'no_captcha' ) ) {
		$fields[] = array( "name" => "captcha" );
	}

	return apply_filters( 'wdfb-registration_fields_array', $fields );
}

/**
 * Helper function for processing registration fields array into a string.
 */
function wdfb_get_registration_fields() {
	$ret    = array();
	$fields = wdfb_get_registration_fields_array();
	foreach ( $fields as $field ) {
		$tmp = array();
		foreach ( $field as $key => $value ) {
			$tmp[] = "'{$key}':'{$value}'";
		}
		$ret[] = '{' . join( ',', $tmp ) . '}';
	}
	$ret = '[' . join( ',', $ret ) . ']';

	return apply_filters( 'wdfb-registration_fields_string', $ret );
}

/**
 * Helper function for finding out the proper locale.
 */
function wdfb_get_locale() {
	$data   = Wdfb_OptionsRegistry::get_instance();
	$locale = $data->get_option( 'wdfb_api', 'locale' );

	return $locale ? $locale : preg_replace( '/-/', '_', get_locale() );
}

/**
 * Determine front-end footer hook.
 * @return string Actual footer hook to use.
 */
function wdfb_get_footer_hook() {
	$footer_hook = 'get_footer';
	if ( defined( 'WDFB_FOOTER_HOOK' ) ) {
		$footer_hook = is_string( WDFB_FOOTER_HOOK ) ? WDFB_FOOTER_HOOK : 'wp_footer';
	}

	return $footer_hook;
}

/**
 * Helper function for getting the login redirect URL.
 */
function wdfb_get_login_redirect( $force_admin_redirect = false ) {
	$redirect_url = false;
	$data         = Wdfb_OptionsRegistry::get_instance();
	$url          = $data->get_option( 'wdfb_connect', 'login_redirect_url' );
	if ( $url ) {
		$base         = $data->get_option( 'wdfb_connect', 'login_redirect_base' );
		$base         = ( 'admin_url' == $base ) ? 'admin_url' : 'site_url';
		$redirect_url = $base( $url );
	} else {
		if ( ! defined( 'BP_VERSION' ) && $force_admin_redirect ) {
			// Forcing admin url redirection
			$redirect_url = admin_url();
		} else {
			// Non-admin URL redirection, no specific settings

			if ( isset( $_GET['redirect_to'] ) ) {
				// ... via GET parameter
				$redirect_url = $_GET['redirect_to'];
			} else {
				// ... via heuristics and settings
				global $post, $wp;
				if ( is_singular() && is_object( $post ) && isset( $post->ID ) ) {
					// Set to permalink for current item, if possible
					$redirect_url = apply_filters( 'wdfb-login-redirect_url-item_url', get_permalink( $post->ID ) );
				}
				$fallback_url = ( defined( 'WDFB_EXACT_REDIRECT_URL_FALLBACK' ) && WDFB_EXACT_REDIRECT_URL_FALLBACK ) ? site_url( $wp->request ) : home_url();
				// Default to home URL otherwise
				$redirect_url = $redirect_url ? $redirect_url : $fallback_url;
			}
		}
	}

	return apply_filters( 'wdfb-login-redirect_url', $redirect_url );
}

/**
 * Expands some basic supported user macros.
 */
function wdfb_expand_user_macros( $str ) {
	$user = wp_get_current_user();
	if ( $user && ! empty( $user->ID ) ) {
		$str = preg_replace( '/\bUSER_ID\b/', $user->ID, $str );
		$str = preg_replace( '/\bUSER_LOGIN\b/', $user->user_login, $str );
	} else {
		$str = add_query_arg( array( 'wdfb_expand' => 'true' ), $str );
	}

	return $str;
}

add_filter( 'wdfb-login-redirect_url', 'wdfb_expand_user_macros', 1 );

/**
 * Expands some basic supported BuddyPress macros.
 */
function wdfb_expand_buddypress_macros( $str ) {
	if ( ! defined( 'BP_VERSION' ) ) {
		return $str;
	}

	if ( function_exists( 'bp_get_activity_root_slug' ) ) {
		$str = preg_replace( '/\bBP_ACTIVITY_SLUG\b/', bp_get_activity_root_slug(), $str );
	}
	if ( function_exists( 'bp_get_groups_slug' ) ) {
		$str = preg_replace( '/\bBP_GROUPS_SLUG\b/', bp_get_groups_slug(), $str );
	}
	if ( function_exists( 'bp_get_members_slug' ) ) {
		$str = preg_replace( '/\bBP_MEMBERS_SLUG\b/', bp_get_members_slug(), $str );
	}

	return $str;
}

add_filter( 'wdfb-login-redirect_url', 'wdfb_expand_buddypress_macros', 1 );


/**
 * Creates post excerpt.
 */
function wdfb_get_excerpt( $post ) {
	if ( ! is_object( $post ) || ( empty( $post->post_excerpt ) && empty( $post->post_content ) ) ) {
		return '';
	} //return $post;
	$content = ! empty( $post->post_excerpt ) ? $post->post_excerpt : $post->post_content;

	if ( preg_match( '/(<!--more(.*?)?-->)/', $content, $matches ) ) {
		$tmp     = explode( $matches[0], $content, 2 );
		$content = $tmp[0];
	}

	return wp_strip_all_tags( strip_shortcodes( $content ) );
}


/**
 * Helper function for fetching the image for OpenGraph info.
 */
function wdfb_get_og_image( $id = false ) {
	$data = Wdfb_OptionsRegistry::get_instance();
	$use  = $data->get_option( 'wdfb_opengraph', 'always_use_image' );
	if ( $use ) {
		return apply_filters(
			'wdfb-opengraph-image',
			apply_filters( 'wdfb-opengraph-image-always_used_image', $use )
		);
	}

	// Try to find featured image
	if ( function_exists( 'get_post_thumbnail_id' ) ) { // BuddyPress :/
		$thumb_id = get_post_thumbnail_id( $id );
	} else {
		$thumb_id = false;
	}
	if ( $thumb_id ) {
		$opt               = get_option( 'wdfb_autopost' );
		$opt['image_size'] = empty( $opt['image_size'] ) ? ( ! empty( $sizes['large'] ) ? 'large' : 'full' ) : $opt['image_size'];
		$image             = wp_get_attachment_image_src( $thumb_id, $opt['image_size'] );
		if ( $image ) {
			return apply_filters(
				'wdfb-opengraph-image',
				apply_filters( 'wdfb-opengraph-image-featured_image', $image[0] )
			);
		}
	}

	// If we're still here, post has no featured image.
	// Fetch the first one.
	// Thank you for this fix, grola!
	if ( $id ) {
		$post = get_post( $id );
		$html = $post->post_content;

		$apply_the_content_filter = apply_filters( 'wdfb-opengraph_apply_the_content_filter', true );
		if ( $apply_the_content_filter && ! function_exists( 'load_membership_plugins' ) && ! defined( 'GRUNION_PLUGIN_DIR' ) && ! ( defined( 'WDFB_OG_IMAGE_SKIP_CONTENT_FILTER' ) && WDFB_OG_IMAGE_SKIP_CONTENT_FILTER ) ) {
			$html = apply_filters( 'the_content', $html );
		}
	} else if ( is_home() && $data->get_option( 'wdfb_opengraph', 'fallback_image' ) ) {
		return apply_filters(
			'wdfb-opengraph-image',
			apply_filters( 'wdfb-opengraph-image-fallback_image', $data->get_option( 'wdfb_opengraph', 'fallback_image' ) )
		);
	} else {
		$html = get_the_content();
		if ( ! function_exists( 'load_membership_plugins' ) ) {
			$html = apply_filters( 'the_content', $html );
		}
	}
	preg_match_all( '/<img .*src=["\']([^ ^"^\']*)["\']/', $html, $matches );
	if ( @$matches[1][0] ) {
		return apply_filters(
			'wdfb-opengraph-image',
			apply_filters( 'wdfb-opengraph-image-post_image', $matches[1][0] )
		);
	}

	// Post with no images? Pffft.
	// Return whatever we have as fallback.
	return apply_filters(
		'wdfb-opengraph-image',
		apply_filters( 'wdfb-opengraph-image-fallback_image', $data->get_option( 'wdfb_opengraph', 'fallback_image' ) )
	);
}

/**
 * Construct OpenGraph properties from name/value pairs.
 *
 * @param string $name Property identifier
 * @param string $value Property value
 */
function wdfb_get_opengraph_property( $name, $value, $auto_prefix = true ) {
	if ( ! $name && ! $value ) {
		return false;
	} // Zero out empty tags
	$name  = esc_attr( $name );
	$name  = $auto_prefix ? "og:{$name}" : $name;
	$value = esc_attr( $value );

	return apply_filters( 'wdfb-opengraph-property', "<meta property='{$name}' content='{$value}' />\n", $name, $value );
}

/**
 * Facebook XFBML tag format utility function (default).
 * Called by dispatcher, @see wdfb_get_fb_plugin_markup for parameters.
 * @return string
 */
function wdfb_get_fb_plugin_markup_xfbml( $type, $args ) {
	$markup = '';
	switch ( $type ) {
		case "like":
			$markup = '<fb:like href="' .
			          $args['href'] . '" send="' .
			          ( $args['send'] ? 'true' : 'false' ) . '" layout="' .
			          $args['layout'] . '" width="' .
			          $args['width'] . '" colorscheme="' .
			          ( ! empty( $args['scheme'] ) ? $args['scheme'] : 'light' ) .
			          '" show_faces="true" font=""></fb:like>';
			break;

		case "login-button":
			$markup = '<fb:login-button scope="' .
			          $args['scope'] .
			          '" redirect-url="' .
			          $args['redirect-url'] . '"  onlogin="_wdfb_notifyAndRedirect();">' .
			          $args['content'] .
			          '</fb:login-button>';
			break;

		case "comments":
			$responsive = defined( 'WDFB_USE_RESPONSIVE_FB_COMMENTS_HACK' ) && WDFB_USE_RESPONSIVE_FB_COMMENTS_HACK;
			$markup     = '<div class="wdfb-fb_comments"><fb:comments href="' . $args['link'] . '" ' .
			              'xid="' . $args['xid'] . '"  ' .
			              'num_posts="' . $args['num_posts'] . '"  ' .
			              'width="' . $args['width'] . 'px"  ' .
			              'reverse="' . $args['reverse'] . '"  ' .
			              'colorscheme="' . $args['scheme'] . '"  ' .
			              ( $responsive ? 'mobile="false" ' : '' ) .
			              'publish_feed="true"></fb:comments></div>';
			if ( $responsive ) {
				add_action( wdfb_get_footer_hook(), 'wdfb__responsive_fb_comments_hack_style' );
			}
			break;

		case "activity":
			$markup = '<fb:activity site="' .
			          $args['url'] . '" width="' .
			          $args['width'] . '" height="' .
			          $args['height'] . '" header="' .
			          $args['show_header'] . '" colorscheme="' .
			          $args['color_scheme'] . '" recommendations="' .
			          $args['recommendations'] . '" linktarget="' .
			          $args['links'] . '"></fb:activity>';
			break;
	}

	return $markup;
}

/**
 * Facebook HTML5 tag format utility function.
 * Called by dispatcher, @see wdfb_get_fb_plugin_markup for parameters.
 * @return string
 */
function wdfb_get_fb_plugin_markup_html5( $type, $args ) {
	$markup = '';
	switch ( $type ) {
		case "like":
			$markup = '<div class="fb-like" data-href="' .
			          $args['href'] . '" data-send="' .
			          ( $args['send'] ? 'true' : 'false' ) . '" data-layout="' .
			          $args['layout'] . '" data-width="' .
			          $args['width'] . '" data-scheme="' .
			          ( ! empty( $args['scheme'] ) ? $args['scheme'] : 'light' ) .
			          '" data-show-faces="true"></div>';
			break;

		case "login-button":
			$markup = '<div class="fb-login-button" data-scope="' . $args['scope'] . '" data-redirect-url="' . $args['redirect-url'] . '"  data-onlogin="_wdfb_notifyAndRedirect();">' . $args['content'] . '</div>';
			break;

		case "comments":
			$responsive = defined( 'WDFB_USE_RESPONSIVE_FB_COMMENTS_HACK' ) && WDFB_USE_RESPONSIVE_FB_COMMENTS_HACK;
			$markup     = '<div class="wdfb-fb_comments"><div class="fb-comments" data-href="' . $args['link'] . '" ' .
			              'data-xid="' . $args['xid'] . '"  ' .
			              'data-num-posts="' . $args['num_posts'] . '"  ' .
			              'data-width="' . $args['width'] . '"  ' .
			              'data-reverse="' . $args['reverse'] . '"  ' .
			              'data-colorscheme="' . $args['scheme'] . '"  ' .
			              ( $responsive ? 'data-mobile="false" ' : '' ) .
			              'data-publish-feed="true"></div></div>';
			if ( $responsive ) {
				add_action( wdfb_get_footer_hook(), 'wdfb__responsive_fb_comments_hack_style' );
			}
			break;

		case "activity":
			$markup = '<div class="fb-activity" data-site="' .
			          $args['url'] . '" data-width="' .
			          $args['width'] . '" data-height="' .
			          $args['height'] . '" data-header="' .
			          $args['show_header'] . '" data-recommendations="' .
			          $args['recommendations'] . '" data-colorscheme="' .
			          $args['color_scheme'] . '" data-linktarget="' .
			          $args['links'] . '"></div>';
			break;
	}

	return $markup;
}

/**
 * Facebook markup dispatcher.
 * Allows for multiple tag formats support.
 *
 * @param string $type Tag type to render.
 * @param array $args A hash of arguments to use in rendering.
 * @param string $forced_format Optional output format to force.
 *
 * @return string Tag output.
 */
function wdfb_get_fb_plugin_markup( $type, $args, $forced_format = false ) {
	$_formats = array( 'html5', 'xfbml' );
	$is_html5 = false;
	if ( $forced_format && in_array( $forced_format, $_formats ) ) {
		$is_html5 = ( 'html5' == $forced_format );
	} else {
		$is_html5 = defined( 'WDFB_USE_HTML5_TAGS' ) && WDFB_USE_HTML5_TAGS;
	}

	return apply_filters( 'wdfb-tags-use_html5', $is_html5 ) ? wdfb_get_fb_plugin_markup_html5( $type, $args ) : wdfb_get_fb_plugin_markup_xfbml( $type, $args );
}

/**
 * Template tag for FB comments.
 * @return string Facebook comments markup.
 * @example
 * <code>
 * // In e.g. single.php
 * if (function_exists('wdfb_get_fb_comments')) echo wdfb_get_fb_comments();
 * </code>
 */
function wdfb_get_fb_comments() {
	$data = Wdfb_OptionsRegistry::get_instance();
	$link = get_permalink();
	$xid  = rawurlencode( $link );

	$width = (int) $data->get_option( 'wdfb_comments', 'fb_comments_width' );
	$width = $width ? $width : '550';

	$num_posts = (int) $data->get_option( 'wdfb_comments', 'fb_comments_number' );
	$reverse   = $data->get_option( 'wdfb_comments', 'fb_comments_reverse' ) ? 'true' : 'false';

	$scheme = $data->get_option( 'wdfb_comments', 'fb_color_scheme' );
	$scheme = $scheme ? $scheme : 'light';

	return wdfb_get_fb_plugin_markup( 'comments', compact( array(
		'link',
		'xid',
		'num_posts',
		'width',
		'reverse',
		'scheme'
	) ) );
}

function wdfb__responsive_fb_comments_hack_style() {
	echo <<<EoResponsiveFbCommentsHack
<style type="text/css">
.wdfb-fb_comments {
	width: 100%;
}
#fbcomments, .wdfb-fb_comments span[style], .fb_iframe_widget, .fb_iframe_widget[style], .fb_iframe_widget iframe[style], #fbcomments iframe[style] {width: 100% !important;}
</style>
EoResponsiveFbCommentsHack;
}


/**
 * BuddyPress singular activity boolean flag.
 * Because `bp_is_single_item()` is basically broken.
 * Hence using the modified heuristic attrocity from bp-activity-screens.php.
 */
function wdfb_is_single_bp_activity() {
	if ( ! defined( 'BP_VERSION' ) ) {
		return false;
	}
	if ( ! function_exists( 'bp_is_activity_component' ) || ! bp_is_activity_component() ) {
		return false;
	}
	if ( ! bp_current_action() || ! is_numeric( bp_current_action() ) ) {
		return false;
	}

	return true;
}

/**
 * Actual BuddyPress activity permalink.
 * Returns the actual permalink for activity updates, not the 302 redirect.
 */
function wdfb_bp_activity_get_permalink( $activity_id ) {
	if ( defined( 'WDFB_BP_PERMALINK_SHORT_OUT' ) && WDFB_BP_PERMALINK_SHORT_OUT ) {
		return bp_activity_get_permalink( $activity_id );
	}
	$activity = new BP_Activity_Activity( $activity_id );
	if ( ! is_object( $activity ) || empty( $activity->type ) || empty( $activity->primary_link ) || empty( $activity->id ) ) {
		return bp_activity_get_permalink( $activity_id, $activity );
	}
	if ( ! in_array( $activity->type, array(
		'activity_comment',
		'activity_update'
	) )
	) {
		return bp_activity_get_permalink( $activity_id, $activity );
	}

	return trailingslashit( preg_replace( '/^https?:\/\//', '', $activity->primary_link ) ) . $activity->id;
}

/**
 * Description abstraction, to make sure we sugarcoat the BP uglyness.
 */
function wdfb_get_singular_description() {
	$content = '';
	if ( wdfb_is_single_bp_activity() ) {
		$activity = bp_activity_get_specific( array(
			'activity_ids' => bp_current_action(),
			'show_hidden'  => true,
			'spam'         => 'ham_only',
		) );
		$activity = empty( $activity['activities'][0] ) || bp_action_variables() ? '' : $activity['activities'][0];
		$content  = apply_filters_ref_array( 'bp_get_activity_content_body', array( $activity->content, &$activity ) );
	} else {
		global $post;
		$content = $post->post_excerpt ? $post->post_excerpt : strip_shortcodes( $post->post_content );
	}

	return htmlspecialchars( wp_strip_all_tags( $content ), ENT_QUOTES );
}


/**
 * Wrapper for URL to post ID matching.
 */
function wdfb_url_to_postid( $url ) {
	$post_id = false;
	// Do our best to unwrap Jetpack shortlinks
	if ( wdfb__has_jetpack() && preg_match( '/https?:' . preg_quote( '//wp.me', '/' ) . '/i', $url ) ) {
		// We may have a Jetpack-encoded link.
		$path    = trim( parse_url( $url, PHP_URL_PATH ), '/' );
		$type    = substr( $path, 0, 1 );
		$no_type = substr( $path, 1 );
		if ( false !== strstr( $no_type, '-' ) ) {
			list( $raw_blog_id, $raw_post_id ) = explode( '-', $no_type );
			if ( 's' === $type ) {
				$post_id = wdfb__post_name_to_id( $raw_post_id );
			} else {
				$post_id = wdfb__base62_to_decimal( $raw_post_id );
			}
		}
	} else {
		// We hopefully have a regular link/shortlink
		$post_id = url_to_postid( $url );
	}
	if ( ! $post_id ) {
		$post_id = apply_filters( 'wdfb-comments-url_to_post_id-fallback', $post_id, $url );
	}

	return apply_filters( 'wdfb-comments-url_to_post_id-post_id', $post_id );
}


/**
 * Applying the proper message for registration email notification.
 */
function wdfb_add_registration_filter() {
	add_filter( 'wdfb-registration_message', 'wdfb_add_email_message' );
}

add_action( 'wdfb-registration_email_sent', 'wdfb_add_registration_filter' );

/**
 * Creates a proper registration email notification message.
 */
function wdfb_add_email_message( $msg ) {
	return
		apply_filters(
			'wdfb-registration_message-user',
			__( '<p>An email with your login credentials has been sent to your email address.</p>', 'wdfb' )
		) .
		$msg;
}

// Default blog domain sanitization
if ( ! ( defined( 'WDFB_SKIP_DEFAULT_BLOG_DOMAIN_SANITIZATION' ) && WDFB_SKIP_DEFAULT_BLOG_DOMAIN_SANITIZATION ) ) {

	/**
	 * Cleanup and sanitize possible full url entries into clean subdomain value.
	 *
	 * @param string $domain Sanitized domain
	 * @param string $original Original domain
	 *
	 * @return string Sanitized subdomain
	 */
	function wdfb__extract_subdomain_name( $domain, $original ) {
		if ( false === strpos( $original, '.' ) ) {
			return $domain;
		}
		$host      = parse_url( esc_url( $original ), PHP_URL_HOST );
		$parts     = explode( '.', $host );
		$subdomain = $parts[0];

		return preg_replace( '/[^a-z0-9]/', '', strtolower( $subdomain ) );
	}

	if ( function_exists( 'is_subdomain_install' ) && is_subdomain_install() ) {
		add_filter( 'wdfb-registration-blog_domain-sanitize_domain', 'wdfb__extract_subdomain_name', 10, 2 );
	}
}

/**
 * Error registry class for exception transport.
 */
class Wdfb_ErrorRegistry {
	private static $_errors = array();

	private function __construct() {
	}

	public static function store( $exception ) {
		self::$_errors[] = $exception;
	}

	public static function clear() {
		self::$_errors = array();
	}

	public static function get_errors() {
		return self::$_errors;
	}

	public static function get_last_error() {
		return end( self::$_errors );
	}

	public static function get_last_error_message() {
		$e = self::get_last_error();

		return ( $e && is_object( $e ) && $e instanceof Exception )
			? $e->getMessage()
			: false;
	}
}

/**
 * Utility to help converting the Jetpack-encoded shortlink format.
 * Barely adapted from
 * http://stackoverflow.com/questions/4964197/converting-a-number-base-10-to-base-62-a-za-z0-9
 * Original code by Eineki http://stackoverflow.com/users/29125/eineki
 * Thanks!
 */
function wdfb__base62_to_decimal( $num, $b = 62 ) {
	$base  = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$limit = strlen( $num );
	$res   = strpos( $base, $num[0] );
	for ( $i = 1; $i < $limit; $i ++ ) {
		$res = $b * $res + strpos( $base, $num[ $i ] );
	}

	return $res;
}

/**
 * Post name to ID helper.
 */
function wdfb__post_name_to_id( $post_name ) {
	global $wpdb;

	return $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_name=%s AND post_status='publish'", $post_name ) );
}

/**
 * Jetpack recognition helper.
 */
function wdfb__has_jetpack() {
	//return (bool)(class_exists('Jetpack') && function_exists('wpme_get_shortlink'));
	return defined( 'JETPACK__API_BASE' );
}

/**
 * Custom post types URL remapping filter.
 * Code by BetterWP.net
 * taken almost verbatim from http://betterwp.net/wordpress-tips/url_to_postid-for-custom-post-types/
 */
function wdfb__cpt_url_to_post_id( $post_id, $url ) {
	if ( $post_id ) {
		return $post_id;
	}

	global $wp_rewrite, $wp;

	// Check to see if we are using rewrite rules
	$rewrite = $wp_rewrite->wp_rewrite_rules();

	// Not using rewrite rules, and 'p=N' and 'page_id=N' methods failed, so we're out of options
	if ( empty( $rewrite ) ) {
		return 0;
	}

	// Get rid of the #anchor
	$url_split = explode( '#', $url );
	$url       = $url_split[0];

	// Get rid of URL ?query=string
	$url_split = explode( '?', $url );
	$url       = $url_split[0];

	// Add 'www.' if it is absent and should be there
	if ( false !== strpos( home_url(), '://www.' ) && false === strpos( $url, '://www.' ) ) {
		$url = str_replace( '://', '://www.', $url );
	}

	// Strip 'www.' if it is present and shouldn't be
	if ( false === strpos( home_url(), '://www.' ) ) {
		$url = str_replace( '://www.', '://', $url );
	}

	// Strip 'index.php/' if we're not using path info permalinks
	if ( ! $wp_rewrite->using_index_permalinks() ) {
		$url = str_replace( 'index.php/', '', $url );
	}

	if ( false !== strpos( $url, home_url() ) ) {
		// Chop off http://domain.com
		$url = str_replace( home_url(), '', $url );
	} else {
		// Chop off /path/to/blog
		$home_path = parse_url( home_url() );
		$home_path = isset( $home_path['path'] ) ? $home_path['path'] : '';
		$url       = str_replace( $home_path, '', $url );
	}

	$url     = trim( $url, '/' );
	$request = $url;
	// Look for matches.
	$request_match = $request;
	foreach ( (array) $rewrite as $match => $query ) {
		// If the requesting file is the anchor of the match, prepend it
		// to the path info.
		if ( ! empty( $url ) && ( $url != $request ) && ( strpos( $match, $url ) === 0 ) ) {
			$request_match = $url . '/' . $request;
		}

		if ( ! preg_match( "!^$match!", $request_match, $matches ) ) {
			continue;
		}

		// Got a match.
		// Trim the query of everything up to the '?'.
		$query = preg_replace( "!^.+\?!", '', $query );

		// Substitute the substring matches into the query.
		$query = addslashes( WP_MatchesMapRegex::apply( $query, $matches ) );

		// Filter out non-public query vars
		parse_str( $query, $query_vars );
		$query = array();
		foreach ( (array) $query_vars as $key => $value ) {
			if ( in_array( $key, $wp->public_query_vars ) ) {
				$query[ $key ] = $value;
			}
		}

		// Taken from class-wp.php
		foreach ( $GLOBALS['wp_post_types'] as $post_type => $t ) {
			if ( $t->query_var ) {
				$post_type_query_vars[ $t->query_var ] = $post_type;
			}
		}

		foreach ( $wp->public_query_vars as $wpvar ) {
			if ( isset( $wp->extra_query_vars[ $wpvar ] ) ) {
				$query[ $wpvar ] = $wp->extra_query_vars[ $wpvar ];
			} elseif ( isset( $_POST[ $wpvar ] ) ) {
				$query[ $wpvar ] = $_POST[ $wpvar ];
			} elseif ( isset( $_GET[ $wpvar ] ) ) {
				$query[ $wpvar ] = $_GET[ $wpvar ];
			} elseif ( isset( $query_vars[ $wpvar ] ) ) {
				$query[ $wpvar ] = $query_vars[ $wpvar ];
			}

			if ( ! empty( $query[ $wpvar ] ) ) {
				if ( ! is_array( $query[ $wpvar ] ) ) {
					$query[ $wpvar ] = (string) $query[ $wpvar ];
				} else {
					foreach ( $query[ $wpvar ] as $vkey => $v ) {
						if ( ! is_object( $v ) ) {
							$query[ $wpvar ][ $vkey ] = (string) $v;
						}
					}
				}

				if ( isset( $post_type_query_vars[ $wpvar ] ) ) {
					$query['post_type'] = $post_type_query_vars[ $wpvar ];
					$query['name']      = $query[ $wpvar ];
				}
			}
		}

		// Do the query
		$query = new WP_Query( $query );

		return ! empty( $query->posts ) && $query->is_singular ? $query->post->ID : 0;
	}


	return $post_id;
}

add_filter( 'wdfb-comments-url_to_post_id-fallback', 'wdfb__cpt_url_to_post_id', 10, 2 );

/**
 * Allow for default curlopt timeout define.
 */
function wdfb_fb_core_curlopt_increase_timeout( $options ) {
	if ( ! ( defined( 'WDFB_FACEBOOK_CURLOPT_TIMEOUT' ) && WDFB_FACEBOOK_CURLOPT_TIMEOUT ) ) {
		return $options;
	}
	$options[ CURLOPT_CONNECTTIMEOUT ] = WDFB_FACEBOOK_CURLOPT_TIMEOUT;

	return $options;
}

add_filter( 'wdfb-fb_core-facebook_curl_options', 'wdfb_fb_core_curlopt_increase_timeout' );


function wdfb_cleanup_admin_pages( $list ) {
	return array_merge( $list, array(
		'tools_page_codestyling-localization/codestyling-localization',
	) );
}

add_filter( 'wdfb-scripts-prevent_inclusion_ids', 'wdfb_cleanup_admin_pages' );

//Open Comments for all posts, if override settings is enabled
//Later when we inject comments, we disable wordpress comments if override enabled
if ( ! ( defined( 'WDFB_COMMENTS_RESPECT_WP_DISCUSSION_SETTINGS' ) && WDFB_COMMENTS_RESPECT_WP_DISCUSSION_SETTINGS ) ) {
	function wdfb_wp_core__trump_discussion_settings() {
		$data = Wdfb_OptionsRegistry::get_instance();
		if ( $data->get_option( 'wdfb_comments', 'override_wp_comments_settings' ) ) {
			add_filter( 'comments_open', '__return_true' );
		}
	}

	add_action( 'init', 'wdfb_wp_core__trump_discussion_settings' );
}

if ( ! ( defined( 'WDFB_SKIP_AUTOBLOG_LOOP_PREVENTION' ) && WDFB_SKIP_AUTOBLOG_LOOP_PREVENTION ) ) {
	function wdfb__stop_abfb_loop() {
		add_filter( 'wdfb-autopost-post_update', '__return_false' );
	}

	add_action( 'autoblog_pre_process_feeds', 'wdfb__stop_abfb_loop' );
}

// ----- API filters -----

/**
 * Repopulate user account with connected API settings, if needed.
 */
function wdfb__check_connected_api_accounts( $accounts, $user_id ) {
	if ( ! empty( $accounts ) ) {
		return $accounts;
	}
	do_action( 'wdfb-api-handle_fb_auth_tokens' );
	remove_filter( 'wdfb-api-connected_accounts', 'wdfb__check_connected_api_accounts', 0, 2 );
	$model = new Wdfb_Model;

	return $model->get_api_accounts( $user_id );
}

add_filter( 'wdfb-api-connected_accounts', 'wdfb__check_connected_api_accounts', 0, 2 );

// Account access limiting
if ( defined( 'WDFB_LIMIT_ACCOUNTS_ACCESS_TO' ) && WDFB_LIMIT_ACCOUNTS_ACCESS_TO ) {
	function wdfb__limit_accounts_access_to( $accounts ) {
		$allowed   = array_map( 'trim', explode( ',', WDFB_LIMIT_ACCOUNTS_ACCESS_TO ) );
		$processed = array();
		foreach ( $accounts as $account => $info ) {
			if ( in_array( $account, $allowed ) ) {
				$processed[ $account ] = $info;
			}
		}

		return $processed;
	}

	add_filter( 'wdfb-api-connected_accounts', 'wdfb__limit_accounts_access_to', 10 );
}


// ----- Profile sync filters -----


/**
 * Education complex field data mapping processor - school names.
 */
function wdfb__education_complex_profile_field_schools( $data ) {
	if ( ! is_array( $data ) || empty( $data ) ) {
		return $data;
	}
	$ret = array();
	foreach ( $data as $sch ) {
		if ( ! isset( $sch['school']['name'] ) ) {
			continue;
		}
		if ( in_array( $sch['school']['name'], $ret ) ) {
			continue;
		}
		$ret[] = $sch['school']['name'];
	}

	return join( ', ', $ret );
}

add_filter( 'wdfb-profile_sync-education-schools', 'wdfb__education_complex_profile_field_schools' );

/**
 * Education complex field data mapping processor - graduation.
 */
function wdfb__education_complex_profile_field_graduation( $data ) {
	if ( ! is_array( $data ) || empty( $data ) ) {
		return $data;
	}
	$ret = array();
	foreach ( $data as $sch ) {
		if ( ! isset( $sch['school']['name'] ) ) {
			continue;
		}
		if ( ! isset( $sch['year']['name'] ) ) {
			continue;
		}

		$str = $sch['school']['name'] . ' (' . $sch['year']['name'] . ')';
		if ( in_array( $str, $ret ) ) {
			continue;
		}

		$ret[] = $str;
	}

	return join( ', ', $ret );
}

add_filter( 'wdfb-profile_sync-education-graduation_dates', 'wdfb__education_complex_profile_field_graduation' );

/**
 * Education complex field data mapping processor - subjects.
 */
function wdfb__education_complex_profile_field_subjects( $data ) {
	if ( ! is_array( $data ) || empty( $data ) ) {
		return $data;
	}
	$ret = array();
	foreach ( $data as $sch ) {
		if ( empty( $sch['concentration'] ) ) {
			continue;
		}
		foreach ( $sch['concentration'] as $subject ) {
			if ( in_array( $subject['name'], $ret ) ) {
				continue;
			}
			$ret[] = $subject['name'];
		}
	}

	return join( ', ', $ret );
}

add_filter( 'wdfb-profile_sync-education-subjects', 'wdfb__education_complex_profile_field_subjects' );

/**
 * Work complex field data mapping processor - employers.
 */
function wdfb__work_complex_profile_field_employers( $data ) {
	if ( ! is_array( $data ) || empty( $data ) ) {
		return $data;
	}
	$ret = array();
	foreach ( $data as $wrk ) {
		if ( empty( $wrk['employer']['name'] ) ) {
			continue;
		}
		if ( in_array( $wrk['employer']['name'], $ret ) ) {
			continue;
		}
		$ret[] = $wrk['employer']['name'];
	}

	return join( ', ', $ret );
}

add_filter( 'wdfb-profile_sync-work-employers', 'wdfb__work_complex_profile_field_employers' );

/**
 * Work complex field data mapping processor - position_history.
 */
function wdfb__work_complex_profile_field_position_history( $data ) {
	if ( ! is_array( $data ) || empty( $data ) ) {
		return $data;
	}
	$ret = array();
	foreach ( $data as $wrk ) {
		if ( empty( $wrk['employer']['name'] ) ) {
			continue;
		}
		$position = ! empty( $wrk['position']['name'] ) ? $wrk['position']['name'] : __( 'N/A', 'wdfb' );
		$str      = $wrk['employer']['name'] . " ({$position})";
		if ( in_array( $str, $ret ) ) {
			continue;
		}
		$ret[] = $str;
	}

	return join( ', ', $ret );
}

add_filter( 'wdfb-profile_sync-work-position_history', 'wdfb__work_complex_profile_field_position_history' );

/**
 * Work complex field data mapping processor - employer_history.
 */
function wdfb__work_complex_profile_field_employer_history( $data ) {
	if ( ! is_array( $data ) || empty( $data ) ) {
		return $data;
	}
	$ret = array();
	foreach ( $data as $wrk ) {
		if ( empty( $wrk['employer']['name'] ) ) {
			continue;
		}
		$position   = ! empty( $wrk['position']['name'] ) ? $wrk['position']['name'] : __( 'N/A', 'wdfb' );
		$timespan   = false;
		$start_date = ! empty( $wrk['start_date'] ) && ! preg_match( '/^0{4}/', $wrk['start_date'] ) ? $wrk['start_date'] : false;
		if ( $start_date ) {
			$end_date = ! empty( $wrk['end_date'] ) && ! preg_match( '/^0{4}/', $wrk['end_date'] ) ? $wrk['end_date'] : false;
			$end_date = $end_date ? $end_date : __( 'Present', 'wdfb' );
			$timespan = ", {$start_date} - {$end_date}";
		}

		$str = $wrk['employer']['name'] . " ({$position}{$timespan})";
		if ( in_array( $str, $ret ) ) {
			continue;
		}
		$ret[] = $str;
	}

	return join( ', ', $ret );
}

add_filter( 'wdfb-profile_sync-work-employer_history', 'wdfb__work_complex_profile_field_employer_history' );

/**
 * Connection processor helper.
 */
function wdfb__profile_sync_connections_process_connection( $name, $model ) {
	try {
		$data = $model->fb->api( "/me/{$name}" );
	} catch ( Exception $e ) {
		$data = false;
	}
	$data = ! empty( $data['data'] ) ? $data['data'] : array();
	$ret  = array();
	foreach ( $data as $item ) {
		if ( empty( $item['name'] ) ) {
			continue;
		}
		if ( in_array( $item['name'], $ret ) ) {
			continue;
		}
		$ret[] = $item['name'];
	}

	return join( ', ', $ret );
}

/**
 * Books connection complex field data mapping processor.
 */
function wdfb__connection_complex_profile_field_books( $data, $name, $model ) {
	return wdfb__profile_sync_connections_process_connection( 'books', $model );
}

add_filter( 'wdfb-profile_sync-connection-books', 'wdfb__connection_complex_profile_field_books', 10, 3 );
/**
 * Games connection complex field data mapping processor.
 */
function wdfb__connection_complex_profile_field_games( $data, $name, $model ) {
	return wdfb__profile_sync_connections_process_connection( 'games', $model );
}

add_filter( 'wdfb-profile_sync-connection-games', 'wdfb__connection_complex_profile_field_games', 10, 3 );
/**
 * Music connection complex field data mapping processor.
 */
function wdfb__connection_complex_profile_field_movies( $data, $name, $model ) {
	return wdfb__profile_sync_connections_process_connection( 'movies', $model );
}

add_filter( 'wdfb-profile_sync-connection-movies', 'wdfb__connection_complex_profile_field_movies', 10, 3 );
/**
 * Movies connection complex field data mapping processor.
 */
function wdfb__connection_complex_profile_field_music( $data, $name, $model ) {
	return wdfb__profile_sync_connections_process_connection( 'music', $model );
}

add_filter( 'wdfb-profile_sync-connection-music', 'wdfb__connection_complex_profile_field_music', 10, 3 );
/**
 * Movies connection complex field data mapping processor.
 */
function wdfb__connection_complex_profile_field_television( $data, $name, $model ) {
	return wdfb__profile_sync_connections_process_connection( 'television', $model );
}

add_filter( 'wdfb-profile_sync-connection-television', 'wdfb__connection_complex_profile_field_television', 10, 3 );
/**
 * Movies connection complex field data mapping processor.
 */
function wdfb__connection_complex_profile_field_interests( $data, $name, $model ) {
	return wdfb__profile_sync_connections_process_connection( 'interests', $model );
}

add_filter( 'wdfb-profile_sync-connection-interests', 'wdfb__connection_complex_profile_field_interests', 10, 3 );


// It's also pluggable
if ( ! function_exists( 'wdfb_notify_post_author' ) ) {
	function wdfb_notify_post_author( $post, $fb_response ) {
		$author = false;
		if ( ! empty( $post->post_author ) ) {
			$author = get_userdata( $post->post_author );
		}
		if ( empty( $author->user_email ) ) {
			return false;
		}

		$message = ! empty( $fb_response['message'] )
			? wp_specialchars_decode( wp_strip_all_tags( $fb_response['message'] ) )
			: '';
		$from    = ! empty( $fb_response['from']['name'] )
			? wp_specialchars_decode( wp_strip_all_tags( $fb_response['from']['name'] ) )
			: '';

		$blogname = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$subject  = sprintf( __( '[%1$s] Facebook Comment: "%2$s"', 'wdfb' ), $blogname, $post->post_title );
		$body     = '' .
		            sprintf( __( 'New Facebook comment on your post: "%s"', 'wdfb' ), $post->post_title ) .
		            "\r\n" .
		            ( ! empty( $message ) ? sprintf( __( 'Comment excerpt: %s', 'wdfb' ), $message ) . "\r\n" : '' ) .
		            ( ! empty( $from ) ? sprintf( __( 'From: %s', 'wdfb' ), $from ) . "\r\n" : '' ) .
		            get_permalink( $post->ID ) .
		            '';

		$subject = apply_filters( 'wdfb-comments-notify-subject', $subject, $post, $fb_response );
		$body    = apply_filters( 'wdfb-comments-notify-body', $body, $post, $fb_response );

		return wp_mail( $author->user_email, $subject, $body );
	}
}