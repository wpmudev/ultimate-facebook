<?php

class Wdfb_PublicPages {

	var $data;
	var $replacer;
	var $fb;
	var $registration_success;

	function __construct() {
		$this->data                 = Wdfb_OptionsRegistry::get_instance();
		$this->model                = new Wdfb_Model;
		$this->replacer             = new Wdfb_MarkerReplacer;
		$this->registration_success = false;
	}

	/**
	 * Main entry point.
	 *
	 * @static
	 */
	public static function serve() {
		$me = new Wdfb_PublicPages;
		$me->add_hooks();
	}

	function js_load_scripts() {
		wp_enqueue_script( 'jquery' );
		$locale = wdfb_get_locale();
		/*
		if (defined('WDFB_LEGACY_SCRIPT_PLACEMENT') && WDFB_LEGACY_SCRIPT_PLACEMENT) {
			wp_enqueue_script('facebook-all',  WDFB_PROTOCOL  . 'connect.facebook.net/' . $locale . '/all.js');
		} else if (!(defined('WDFB_FB_ASYNC_INIT') && WDFB_FB_ASYNC_INIT)) {
			wp_enqueue_script('facebook-all',  WDFB_PROTOCOL  . 'connect.facebook.net/' . $locale . '/all.js', array('jquery'), null, true);
		}
		*/
		if ( defined( 'WDFB_INTERNAL_FLAG_FB_SCRIPT_INCLUDED' ) ) {
			return false;
		}
		echo '<script type="text/javascript" src="' . WDFB_PROTOCOL . 'connect.facebook.net/' . $locale . '/all.js"></script>';
		define( 'WDFB_INTERNAL_FLAG_FB_SCRIPT_INCLUDED', true );
	}

	function js_inject_fb_login_script() {
		echo '<script type="text/javascript" src="' . WDFB_PLUGIN_URL . '/js/wdfb_facebook_login.js?version='. WDFB_PLUGIN_VERSION . '"></script>';
	}

	function js_setup_ajaxurl() {
		printf(
			'<script type="text/javascript">var _wdfb_ajaxurl="%s";var _wdfb_root_url="%s";</script>',
			admin_url( 'admin-ajax.php' ),
			WDFB_PLUGIN_URL
		);
	}

	function css_load_styles() {
		wp_enqueue_style( 'wdfb_style', WDFB_PLUGIN_URL . '/css/wdfb.css' );
	}

	/**
	 * Inject OpenGraph info in the HEAD
	 */
	function inject_opengraph_info() {
		$title = $url = $site_name = $description = $id = $image = false;
		if ( is_singular() ) {
			global $post;
			$id    = $post->ID;
			$title = $post->post_title;
			$url   = get_permalink( $id );
			if ( defined( 'BP_VERSION' ) && function_exists( 'bp_current_component' ) && bp_current_component() ) {
				global $wp, $bp;
				$url = function_exists( 'bp_is_user_profile_edit' ) && bp_is_user_profile_edit()
					? bp_core_get_user_domain( $bp->displayed_user->id )
					: site_url( $wp->request );
			}
			$site_name = get_option( 'blogname' );
			$text      = wdfb_get_singular_description();
			/*
			$content = $post->post_excerpt ? $post->post_excerpt : strip_shortcodes($post->post_content);
			$text = htmlspecialchars(wp_strip_all_tags($content), ENT_QUOTES);
			*/
			if ( strlen( $text ) > 250 ) {
				$description = preg_replace( '/(.{0,247}).*/um', '$1', preg_replace( '/\r|\n/', ' ', $text ) ) . '...';
			} else {
				$description = $text;
			}
		} else {
			$title       = get_option( 'blogname' );
			$url         = home_url( '/' );
			$site_name   = get_option( 'blogname' );
			$description = get_option( 'blogdescription' );
		}
		$image = $id ? wdfb_get_og_image( $id ) : '';

		// App ID
		if ( ! defined( 'WDFB_APP_ID_OG_SET' ) ) {
			$app_id = trim( $this->data->get_option( 'wdfb_api', 'app_key' ) );
			if ( $app_id ) {
				echo wdfb_get_opengraph_property( 'fb:app_id', $app_id, false );
				define( 'WDFB_APP_ID_OG_SET', true );
			}
		}

		// Type
		$type = false;
		if ( $this->data->get_option( 'wdfb_opengraph', 'og_custom_type' ) ) {
			if ( ! is_singular() ) {
				$type = $this->data->get_option( 'wdfb_opengraph', 'og_custom_type_not_singular' );
				$type = $type ? $type : 'website';
			} else {
				$type = $this->data->get_option( 'wdfb_opengraph', 'og_custom_type_singular' );
				$type = $type ? $type : 'article';
			}
			if ( is_home() || is_front_page() ) {
				$type = $this->data->get_option( 'wdfb_opengraph', 'og_custom_type_front_page' );
				$type = $type ? $type : 'website';
			}
		}
		$type = $type ? $type : ( is_singular() ? 'article' : 'website' );
		$type = apply_filters( 'wdfb-opengraph-type', $type );
		echo wdfb_get_opengraph_property( 'type', $type );

		// Defaults
		$title       = apply_filters( 'wdfb-opengraph-title', $title );
		$url         = apply_filters( 'wdfb-opengraph-url', $url );
		$site_name   = apply_filters( 'wdfb-opengraph-site_name', $site_name );
		$description = apply_filters( 'wdfb-opengraph-description', $description );

		if ( $title ) {
			echo wdfb_get_opengraph_property( 'title', $title );
		}
		if ( $url ) {
			echo wdfb_get_opengraph_property( 'url', $url );
		}
		if ( $site_name ) {
			echo wdfb_get_opengraph_property( 'site_name', $site_name );
		}
		if ( $description ) {
			echo wdfb_get_opengraph_property( 'description', $description );
		}
		if ( $image ) {
			echo wdfb_get_opengraph_property( 'image', $image );
		}

		$extras = $this->data->get_option( 'wdfb_opengraph', 'og_extra_headers' );
		$extras = $extras ? $extras : array();
		foreach ( $extras as $extra ) {
			$name  = apply_filters( 'wdfb-opengraph-extra_headers-name', @$extra['name'] );
			$value = apply_filters( 'wdfb-opengraph-extra_headers-value', @$extra['value'], @$extra['name'] );
			if ( ! $name || ! $value ) {
				continue;
			}
			echo wdfb_get_opengraph_property( $name, $value, false );
		}
		do_action( 'wdfb-opengraph-after_extra_headers' );
	}

	function inject_fb_init_js() {
		if ( defined( 'WDFB_FB_ASYNC_INIT' ) && WDFB_FB_ASYNC_INIT ) {
			$locale = wdfb_get_locale();
			echo '<script>
				window.fbAsyncInit = function() {
					FB.init({
						appId: "' . trim( $this->data->get_option( 'wdfb_api', 'app_key' ) ) . '",
						status: true,
						cookie: true,
						xfbml: true,
						oauth: true
					});
				};
				(function(d, debug){
					var js, id = "facebook-jssdk", ref = d.getElementsByTagName("script")[0];
					if (d.getElementById(id)) {return;}
					js = d.createElement("script"); js.id = id; js.async = true;
					js.src = "//connect.facebook.net/' . $locale . '/all" + (debug ? "/debug" : "") + ".js";
					ref.parentNode.insertBefore(js, ref);
				}(document, /*debug*/ false));
			</script>';
		} else {
			if ( ! defined( 'WDFB_INTERNAL_FLAG_FB_SCRIPT_INCLUDED' ) ) {
				$this->js_load_scripts();
			}
			echo "<script type='text/javascript'>
	         FB.init({
	            appId: '" . trim( $this->data->get_option( 'wdfb_api', 'app_key' ) ) . "',
	            status: true,
	            cookie: true,
	            xfbml: true,
	            oauth: true
	         });
	      </script>";
		}
	}

	/**
	 * Injects Facebook root div needed for XFBML near page footer.
	 */
	function inject_fb_root_div() {
		echo "<div id='fb-root'></div>";
	}

	function inject_fb_login() {
		if ( ! apply_filters( 'wdfb-login-show_wordpress_login_button', apply_filters( 'wdfb-login-show_login_button', true ) ) ) {
			return false;
		}
		if( is_user_logged_in() ) {
			return;
		}
		$args = array(
			'scope'        => Wdfb_Permissions::get_permissions(),
			'redirect-url' => wdfb_get_login_redirect( true ),
			'content'      => __( "Login with Facebook", 'wdfb' ),
		);
		echo '<p class="wdfb_login_button">' . wdfb_get_fb_plugin_markup( 'login-button', $args ) . '</p>';
	}

	function inject_fb_login_return( $content ) {
		if ( ! apply_filters( 'wdfb-login-show_wordpress_login_button', apply_filters( 'wdfb-login-show_login_button', true ) ) ) {
			return false;
		}
		$content .= '<script type="text/javascript" src="' . WDFB_PLUGIN_URL . '/js/wdfb_facebook_login.js?version='. WDFB_PLUGIN_VERSION . '"></script>';
		$content .= '<p class="wdfb_login_button">' .
		            wdfb_get_fb_plugin_markup( 'login-button', array(
			            'scope'        => Wdfb_Permissions::get_permissions(),
			            'redirect-url' => wdfb_get_login_redirect( true ),
			            'content'      => __( "Login with Facebook", 'wdfb' ),
		            ) ) .
		            '</p>';

		return $content;
	}


	function inject_fb_login_for_bp() {
		if ( ! apply_filters( 'wdfb-login-show_buddypress_login_button', apply_filters( 'wdfb-login-show_login_button', true ) ) ) {
			return false;
		}
		echo '<p class="wdfb_login_button">' .
		     wdfb_get_fb_plugin_markup( 'login-button', array(
			     'scope'        => Wdfb_Permissions::get_permissions(),
			     'redirect-url' => wdfb_get_login_redirect(),
			     'content'      => __( "Login with Facebook", 'wdfb' ),
		     ) ) .
		     '</p>';
	}

	function inject_fb_comments_admin_og() {
		if ( defined( 'WDFB_APP_ID_OG_SET' ) ) {
			return false;
		}
		$app_id = trim( $this->data->get_option( 'wdfb_api', 'app_key' ) );
		if ( ! $app_id ) {
			return false;
		}
		echo wdfb_get_opengraph_property( 'fb:app_id', $app_id, false );
		define( 'WDFB_APP_ID_OG_SET', true );
	}

	function inject_fb_comments( $defaults ) {
		if ( ! comments_open() && ! $this->data->get_option( 'wdfb_comments', 'override_wp_comments_settings' ) ) {
			return $defaults;
		}

		$post_id = get_the_ID();

		$link = get_permalink();
		$xid  = rawurlencode( $link );

		$width = (int) $this->data->get_option( 'wdfb_comments', 'fb_comments_width' );
		$width = $width ? $width : '550';

		$num_posts = (int) $this->data->get_option( 'wdfb_comments', 'fb_comments_number' );
		$reverse   = $this->data->get_option( 'wdfb_comments', 'fb_comments_reverse' ) ? 'true' : 'false';

		$scheme = $this->data->get_option( 'wdfb_comments', 'fb_color_scheme' );
		$scheme = $scheme ? $scheme : 'light';

		$args = array(
			'link',
			'xid',
			'num_posts',
			'width',
			'reverse',
			'scheme'
		);

		$fb_comment_form = wdfb_get_fb_plugin_markup( 'comments', compact( $args ) );

		/**
		 * Allows to optionally hide the facebook comment form
		 * @bool, true
		 * @int, $post_id
		 * @since Ultimate Facebook 2.7.3
		 */
		$show_comment_form = apply_filters( 'wdfb_show_comment_form', 'true', $post_id );

		if ( ! empty( $show_comment_form ) && $show_comment_form != 'false' ) {
			echo $fb_comment_form;
		}

		if ( $this->data->get_option( 'wdfb_comments', 'fbc_notify_authors' ) && ! empty( $post_id ) ) {
			$hash = esc_js( wp_hash( $link ) );
			echo <<<EOCOMJS
<script>
(function ($) {
$(window).load(function () {
	if (typeof FB != 'object') return false;
	FB.Event.subscribe('comment.create', function (c) {
		if (!c.commentID) return false;
		$.post(_wdfb_ajaxurl, {
			action: 'wdfb_notify_author',
			post_id: {$post_id},
			hash: '{$hash}',
			cid: c.commentID
		});
	});
});
})(jQuery);
</script>
EOCOMJS;
		}
		//Check if override settings has been enabled, close the wordpress comments and display only facebook comment form
		//delay helps us to display our content and then close WordPress comments
		if ( ! ( defined( 'WDFB_COMMENTS_RESPECT_WP_DISCUSSION_SETTINGS' ) && WDFB_COMMENTS_RESPECT_WP_DISCUSSION_SETTINGS ) ) {
			$data = Wdfb_OptionsRegistry::get_instance();
			if ( $data->get_option( 'wdfb_comments', 'override_wp_comments_settings' ) ) {
				add_filter( 'comments_open', '__return_false' );
			}
		}

		return $defaults;
	}

	function bp_inject_form_checkbox() {
		echo '<span id="wdfb_send_activity-container"><input type="checkbox" name="wdfb_send_activity" id="wdfb_send_activity" value="1" />&nbsp;<label for="wdfb_send_activity">' . __( 'Publish on your facebook wall', 'wdfb' ) . '</label></span>';
		echo <<<EOBpFormInjection
<script>
(function ($) {
$(function () {
	if (!$("#whats-new-post-in-box").length) return false;
	$("#whats-new-post-in-box").append($("#wdfb_send_activity-container"));

});
$.ajaxSetup({
	"beforeSend": function (jqxhr, settings) {
		if ($("#wdfb_send_activity").is(":checked")) settings.data += '&wdfb_send_activity=1';
		return settings;
	}
});
})(jQuery);
</script>
EOBpFormInjection;
	}

	function get_commenter_avatar( $old, $comment, $size ) {
		if ( ! is_object( $comment ) ) {
			return $old;
		}
		$meta = get_comment_meta( $comment->comment_ID, 'wdfb_comment', true );
		if ( ! $meta ) {
			return $old;
		}

		$fb_size_map = false;
		if ( $size <= 50 ) {
			$fb_size_map = 'square';
		}
		if ( $size > 50 && $size <= 100 ) {
			$fb_size_map = 'normal';
		}
		if ( $size > 100 ) {
			$fb_size_map = 'large';
		}
		$fb_size_map = $fb_size_map
			? '?type=' . apply_filters( 'wdfb-avatar-fb_size_map', $fb_size_map, $size )
			: false;

		return '<img src="' . WDFB_PROTOCOL . 'graph.facebook.com/' . $meta['fb_author_id'] . '/picture' . $fb_size_map . '" class="avatar avatar-' . $size . ' photo" height="' . $size . '" width="' . $size . '" />';
	}

	function get_fb_avatar( $avatar, $id_or_email, $size = false ) {
		$fb_uid = false;
		$wp_uid = false;
		if ( is_object( $id_or_email ) ) {
			if ( isset( $id_or_email->comment_author_email ) ) {
				$id_or_email = $id_or_email->comment_author_email;
			} else {
				return $avatar;
			}
		}

		if ( is_numeric( $id_or_email ) ) {
			$wp_uid = (int) $id_or_email;
		} else if ( is_email( $id_or_email ) ) {
			$user = get_user_by( 'email', $id_or_email );
			if ( $user ) {
				$wp_uid = $user->ID;
			}
		} else {
			return $avatar;
		}
		if ( ! $wp_uid ) {
			return $avatar;
		}

		$fb_uid = $this->model->get_fb_user_from_wp( $wp_uid );
		if ( ! $fb_uid ) {
			return $avatar;
		}

		$img_size    = $size ? "width='{$size}px'" : '';
		$fb_size_map = false;
		if ( $size <= 50 ) {
			$fb_size_map = 'square';
		}
		if ( $size > 50 && $size <= 100 ) {
			$fb_size_map = 'normal';
		}
		if ( $size > 100 ) {
			$fb_size_map = 'large';
		}
		$fb_size_map = $fb_size_map
			? '?type=' . apply_filters( 'wdfb-avatar-fb_size_map', $fb_size_map, $size )
			: false;

		return "<img class='avatar' src='" . WDFB_PROTOCOL . "graph.facebook.com/{$fb_uid}/picture{$fb_size_map}' {$img_size} />";
	}
	function process_facebook_registration() {
		// Should we even be here?
		if ( $this->data->get_option( 'wdfb_connect', 'force_facebook_registration' ) ) {
			global $pagenow;
			if ( 'wp-signup.php' == $pagenow ) {
				$_GET['fb_registration_page'] = 1;
			}
			if ( 'wp-login.php' == $pagenow && isset( $_GET['action'] ) && 'register' == $_GET['action'] ) {
				$_GET['fb_registration_page'] = 1;
			}

			if ( defined( 'BP_VERSION' ) ) { // BuddyPress :/
				global $bp;
				if ( 'register' == $bp->current_component ) {
					$_GET['fb_registration_page'] = 1;
				}
			}
		}
		if ( ! isset( $_GET['fb_registration_page'] ) && ! isset( $_GET['fb_register'] ) ) {
			return false;
		}

		$wp_grant_blog = false;

		// Are registrations allowed?
		if ( ! $this->model->registration_allowed() ) {
			return false;
		}

		$wp_grant_blog = apply_filters( 'wdfb-registration-allow_blog_creation', $wp_grant_blog );

		$user_id = false;
		$errors  = array();
		// Process registration data
		if ( isset( $_GET['fb_register'] ) ) {
			list( $encoded_sig, $payload ) = explode( '.', $_REQUEST['signed_request'], 2 );
			$data = json_decode( base64_decode( strtr( $payload, '-_', '+/' ) ), true );

			// We're good here
			if ( $data['registration'] ) {
				$user_id = $this->model->register_fb_user();
				if ( $user_id && $wp_grant_blog ) {
					$new_blog_title = '';
					$new_blog_url   = '';
					remove_filter( 'wpmu_validate_blog_signup', 'signup_nonce_check' );

					// Set up proper blog name
					$blog_domain = apply_filters( 'wdfb-registration-blog_domain-sanitize_domain',
						preg_replace( '/[^a-z0-9]/', '', strtolower( $data['registration']['blog_domain'] ) ),
						$data['registration']['blog_domain']
					);
					// All numbers? Fix that
					if ( preg_match( '/^[0-9]$/', $blog_domain ) ) {
						$letters = shuffle( range( 'a', 'z' ) );
						$blog_domain .= $letters[0];
					}
					$blog_domain = apply_filters( 'wdfb-registration-blog_domain', $blog_domain, $data['registration']['blog_domain'] );

					// Set up proper title
					$blog_title = $data['registration']['blog_title'];
					$blog_title = $blog_title ? $blog_title : apply_filters( 'wdfb-registration-default_blog_title', __( "My new blog", 'wdfb' ) );
					$blog_title = apply_filters( 'wdfb-registration-blog_title', $blog_title, $data['registration']['blog_title'] );

					$result    = wpmu_validate_blog_signup( $blog_domain, $blog_title );
					$iteration = 0;
					// Blog domain failed, try making it unique
					while ( $result['errors']->get_error_code() ) {
						if ( $iteration > 10 ) {
							break;
						} // We should really gtfo
						$blog_domain .= rand();
						$result = wpmu_validate_blog_signup( $blog_domain, $blog_title );
						$iteration ++;
					}

					if ( ! $result['errors']->get_error_code() ) {
						global $current_site;
						$blog_meta                  = array( 'public' => 1 );
						$blog_id                    = wpmu_create_blog( $result['domain'], $result['path'], $result['blog_title'], $user_id, $blog_meta, $current_site->id );
						$new_blog_title             = $result['blog_title'];
						$new_blog_url               = get_blog_option( $blog_id, 'siteurl' );
						$this->registration_success = true;
					} else {
						// Remove user
						$this->model->delete_wp_user( $user_id );
						$errors = array_merge( $errors, array_values( $result['errors']->errors ) );
					}
				} else if ( $user_id ) {
					$this->registration_success = true;
				} else {
					$msg = Wdfb_ErrorRegistry::get_last_error_message();
					if ( $msg ) {
						$errors[] = $msg;
					}
					$errors[] = __( 'Could not register such user', 'wdfb' );
				}
			}
		}

		// Successful registration stuff
		if ( $this->registration_success ) {

			// Trigger actions
			if ( $user_id ) {
				do_action( 'wdfb-registration-facebook_registration', $user_id );
				do_action( 'wdfb-registration-facebook_regular_registration', $user_id );
			}

			// Record activities, if told so
			if ( $user_id && defined( 'BP_VERSION' ) && $this->data->get_option( 'wdfb_connect', 'update_feed_on_registration' ) ) {
				if ( function_exists( 'bp_core_new_user_activity' ) ) {
					bp_core_new_user_activity( $user_id );
				}
			}

			// Attempt to auto-login the user
			if ( $this->data->get_option( 'wdfb_connect', 'autologin_after_registration' ) && isset( $_GET['fb_register'] ) ) {
				$fb_user = $this->model->fb->getUser();
				if ( $fb_user && $user_id ) { // Don't try too hard
					$user = get_userdata( $user_id );
					wp_set_current_user( $user->ID, $user->user_login );
					wp_set_auth_cookie( $user->ID ); // Logged in with Facebook, yay
					do_action( 'wp_login', $user->user_login );
				}
			}
		}

		// Allow registration page templating
		// Thank you so much!
		$page = ( isset( $_GET['fb_register'] ) && $this->registration_success )
			? $this->get_template_page( 'registration_page_success.php' )
			: $this->get_template_page( 'registration_page.php' );
		require_once $page;
		exit();
	}

	/**
	 * Allows registration page templating.
	 *
	 */
	function get_template_page( $template ) {

		$theme_file = locate_template( array( $template ) );
		if ( $theme_file ) {
			// Look for the template file in the theme directory
			// Anyone who wants to theme the registration page can copy
			// the template file to their theme directory while keeping
			// the file name intact
			$file = $theme_file;
		} else {
			// If none was found in the current theme, use the default plugin template
			$file = WDFB_PLUGIN_BASE_DIR . '/lib/forms/' . $template;
		}

		return $file;
	}

	function publish_post_on_facebook( $id ) {
		if ( ! $id ) {
			return false;
		}

		$post_id = $id;
		if ( $rev = wp_is_post_revision( $post_id ) ) {
			$post_id = $rev;
		}

//		// Should we even try?
		if ( ! $this->data->get_option( 'wdfb_autopost', 'allow_autopost' ) ) {
			return false;
		}
		if ( ! $this->data->get_option( 'wdfb_autopost', 'allow_frontend_autopost' ) ) {
			return false;
		}

		$post = get_post( $post_id );
		if ( 'publish' != $post->post_status ) {
			return false;
		} // Draft, auto-save or something else we don't want

		$is_published = get_post_meta( $post_id, 'wdfb_published_on_fb', true );
		if ( $is_published ) {
			return true;
		} // Already posted and no manual override, nothing to do

		$post_type    = $post->post_type;
		$post_title   = $post->post_title;
		$post_content = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );

		$post_as = $this->data->get_option( 'wdfb_autopost', "type_{$post_type}_fb_type" );
		$post_to = $this->data->get_option( 'wdfb_autopost', "type_{$post_type}_fb_user" );
		if ( ! $post_to ) {
			return false;
		} // Don't know where to post, bail

		$as_page = false;
		if ( $post_to != $this->model->get_current_user_fb_id() ) {
			$as_page = isset( $_POST['wdfb_post_as_page'] ) ? $_POST['wdfb_post_as_page'] : $this->data->get_option( 'wdfb_autopost', 'post_as_page' );
		}

		if ( ! $post_as ) {
			return true;
		} // Skip this type

		switch ( $post_as ) {
			case "feed":
			default:
				$use_shortlink = $this->data->get_option( 'wdfb_autopost', "type_{$post_type}_use_shortlink" );
				$permalink     = $use_shortlink ? wp_get_shortlink( $post_id ) : get_permalink( $post_id );
				$permalink     = $permalink ? $permalink : get_permalink( $post_id );
				$picture       = wdfb_get_og_image( $post_id );
				$description   = $post->post_excerpt ? $post->post_excerpt : strip_shortcodes( $post->post_content );
				$description   = htmlspecialchars( wp_strip_all_tags( $description ), ENT_QUOTES );
				$description   = apply_filters( 'wdfb_fb_post_description', $description );
				$send          = array(
					'caption'     => home_url( '/' ),
					'message'     => $post_title,
					'link'        => $permalink,
					'name'        => $post->post_title,
					'description' => $description,
					'actions'     => array(
						'name' => __( 'Share', 'wdfb' ),
						'link' => 'http://www.facebook.com/sharer.php?u=' . rawurlencode( $permalink ),
					),
				);
				if ( $picture ) {
					$send['picture'] = $picture;
				}
				break;
		}
		$send = apply_filters( 'wdfb-autopost-post_update', $send, $post_id );
		$send = apply_filters( 'wdfb-autopost-send', $send, $post_as, $post_to );
		$res  = $this->model->post_on_facebook( $post_as, $post_to, $send, $as_page );
		if ( $res ) {
			update_post_meta( $post_id, 'wdfb_published_on_fb', 1 );
			do_action( 'wdfb-autopost-posting_successful', $post_id );
		}
		do_action( 'wdfb-autopost-posting_complete', $res );
	}

	function inject_bp_groups_sync() {
		if ( ! function_exists( 'bp_get_group_id' ) ) {
			return false;
		}
		echo '<p><a href="#bp-fb-group_sync" id="wdfb_sync_group" data-wdfb-bp_group_id="' . bp_get_group_id() . '">' .
		     __( 'Sync this group info with a Facebook group', 'wdfb' ) .
		     '</a></p>';
		wp_enqueue_script( 'wdfb_groups_sync', WDFB_PLUGIN_URL . '/js/wdfb_groups_sync.js', array( 'jquery' ) );
	}

	/**
	 * Hooks to appropriate places and adds stuff as needed.
	 *
	 * @access private
	 */
	function add_hooks() {
		// Step1a: Add script and style dependencies
		//add_action('wp_print_scripts', array($this, 'js_load_scripts'));
		$footer_hook = wdfb_get_footer_hook();
		if ( ! ( defined( 'WDFB_FB_ASYNC_INIT' ) && WDFB_FB_ASYNC_INIT ) ) {
			$hook = defined( 'WDFB_LEGACY_SCRIPT_PLACEMENT' ) && WDFB_LEGACY_SCRIPT_PLACEMENT
				? 'wp_head'
				: $footer_hook;
			add_action( $hook, array( $this, 'js_load_scripts' ) );
		}
		add_action( 'wp_print_styles', array( $this, 'css_load_styles' ) );
		add_action( 'wp_head', array( $this, 'js_setup_ajaxurl' ) );

		add_action( $footer_hook, array( $this, 'inject_fb_root_div' ), 99 );
		add_action( $footer_hook, array( $this, 'inject_fb_init_js' ), 99 );

		// OpenGraph
		if ( $this->data->get_option( 'wdfb_opengraph', 'use_opengraph' ) ) {
			add_action( 'wp_head', array( $this, 'inject_opengraph_info' ) );
		}

		// Connect
		if ( $this->data->get_option( 'wdfb_connect', 'allow_facebook_registration' ) ) {
			if ( ! $this->data->get_option( 'wdfb_connect', 'skip_fb_avatars' ) ) {
				add_filter( 'get_avatar', array( $this, 'get_fb_avatar' ), 10, 3 );
			}

			add_action( 'login_enqueue_scripts', create_function( '', 'wp_enqueue_script("jquery");' ) );
			add_action( 'login_head', array( $this, 'js_inject_fb_login_script' ) );
			add_action( 'login_head', array( $this, 'js_setup_ajaxurl' ) );
			add_action( 'login_form', array( $this, 'inject_fb_login' ) );
			add_filter( 'login_form_bottom', array( $this, 'inject_fb_login_return' ) );
			add_action( 'login_footer', array( $this, 'inject_fb_root_div' ) );

			add_action( 'login_footer', array(
				$this,
				'inject_fb_init_js'
			), 999 ); // Bind very late, so footer script can execute.

			// BuddyPress
			if ( defined( 'BP_VERSION' ) ) {
				add_action( 'bp_before_profile_edit_content', 'wdfb_dashboard_profile_widget' );
				add_action( 'bp_before_sidebar_login_form', array( $this, 'inject_fb_login_for_bp' ) );
				add_action( 'wp_head', array( $this, 'js_inject_fb_login_script' ) );

				// Have to kill BuddyPress redirection, or our registration doesn't work
				remove_action( 'wp', 'bp_core_wpsignup_redirect' );
				remove_action( 'init', 'bp_core_wpsignup_redirect' );
				add_action( 'bp_include', create_function( '', "remove_action('bp_init', 'bp_core_wpsignup_redirect');" ), 99 ); // Untangle for BP 1.5
				remove_action( 'bp_init', 'bp_core_wpsignup_redirect' ); // Die already, will you? Pl0x?
			}

			if ( is_multisite() ) {
				add_action( 'signup_hidden_fields', array( $this, 'inject_fb_login' ) );
			}else{
				add_action('register_form', array( $this, 'inject_fb_login' ) );
			}
			// Cole's changeset
			if ( WDFB_MEMBERSHIP_INSTALLED ) {
				add_action( 'signup_extra_fields', array( $this, 'inject_fb_login' ) );
				add_action( 'membership_popover_extend_login_form', array( $this, 'inject_fb_login' ) );
			}else {
				// BuddyPress
				add_action( 'bp_before_account_details_fields', array( $this, 'inject_fb_login' ) ); // BuddyPress
			}

			// Jack the signup
			add_action( 'init', array( $this, 'process_facebook_registration' ), 20 );
		}

		// Comments
		if ( $this->data->get_option( 'wdfb_comments', 'use_fb_comments' ) ) {
			$hook = $this->data->get_option( 'wdfb_comments', 'fb_comments_custom_hook' );
			add_action( 'wp_head', array( $this, 'inject_fb_comments_admin_og' ) );

			if ( ! $hook ) {
				add_filter( 'comment_form_defaults', array( $this, 'inject_fb_comments' ) );
				add_filter( 'bp_before_blog_comment_list', array( $this, 'inject_fb_comments' ) ); // BuddyPress :/
			} else {
				add_action( $hook, array( $this, 'inject_fb_comments' ) );
			}
		}
		if ( ! $this->data->get_option( 'wdfb_connect', 'skip_fb_avatars' ) ) {
			add_filter( 'get_avatar', array( $this, 'get_commenter_avatar' ), 10, 3 );
		}

		// Autopost for front pages
		if ( $this->data->get_option( 'wdfb_autopost', 'allow_autopost' ) && $this->data->get_option( 'wdfb_autopost', 'allow_frontend_autopost' ) ) {
			add_action( 'save_post', array( $this, 'publish_post_on_facebook' ) );
			if ( defined( 'BP_VERSION' ) ) {
				if ( ! $this->data->get_option( 'wdfb_autopost', "prevent_bp_activity_switch" ) ) {
					// Semi-auto-publish switch for BuddyPress Activities
					add_filter( 'bp_activity_post_form_options', array( $this, 'bp_inject_form_checkbox' ) );
				}
			}
		}

		// Groups
		if ( $this->data->get_option( 'wdfb_groups', 'allow_bp_groups_sync' ) ) {
			if ( defined( 'BP_VERSION' ) ) {
				add_action( 'bp_before_group_admin_content', array( $this, 'inject_bp_groups_sync' ) );
			}
		}

		$rpl = $this->replacer->register();

		// Allow unhooking actions and post-init behavior.
		do_action( 'wdfb-core-hooks_added-public', $this );
	}
}