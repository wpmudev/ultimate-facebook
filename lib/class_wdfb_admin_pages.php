<?php

/**
 * Handles all admin side stuff.
 */
class Wdfb_AdminPages {

	/**
	 * Main entry point.
	 *
	 * @static
	 */
	public static function serve() {
		$me        = new Wdfb_AdminPages;
		$me->data  = Wdfb_OptionsRegistry::get_instance();
		$me->model = new Wdfb_Model;
		$me->add_hooks();
	}

	/**
	 * Registers settings and form handlers/elements for sitewide administration.
	 *
	 * @access private
	 */
	function register_site_settings() {
		$form = new Wdfb_AdminFormRenderer;

		/**
		 * Network Options
		 */
		register_setting( 'wdfb', 'wdfb_network' );
		add_settings_section( 'wdfb_network', __( 'Network options', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
		add_settings_field( 'wdfb_override_all', __( 'Override individual blog settings', 'wdfb' ), array(
			$form,
			'create_override_all_box'
		), 'wdfb_options_page', 'wdfb_network' );
		add_settings_field( 'wdfb_preserve_api', __( 'Preserve individual blog API settings', 'wdfb' ), array(
			$form,
			'create_preserve_api_box'
		), 'wdfb_options_page', 'wdfb_network' );
		add_settings_field( 'wdfb_prevent_blog_settings', __( 'Do not allow per-blog settings', 'wdfb' ), array(
			$form,
			'create_prevent_blog_settings_box'
		), 'wdfb_options_page', 'wdfb_network' );

		/**
		 * Facebook API
		 */
		register_setting( 'wdfb', 'wdfb_api' );
		add_settings_section( 'wdfb_api', __( 'Facebook API', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
		add_settings_field( 'wdfb_api_info', __( 'Before we begin', 'wdfb' ), array(
			$form,
			'api_info'
		), 'wdfb_options_page', 'wdfb_api' );
		add_settings_field( 'wdfb_app_key', __( 'App ID / API key', 'wdfb' ), array(
			$form,
			'create_app_key_box'
		), 'wdfb_options_page', 'wdfb_api' );
		add_settings_field( 'wdfb_secret_key', __( 'Secret key', 'wdfb' ), array(
			$form,
			'create_secret_key_box'
		), 'wdfb_options_page', 'wdfb_api' );
		add_settings_field( 'wdfb_locale', __( 'API Locale', 'wdfb' ), array(
			$form,
			'create_locale_box'
		), 'wdfb_options_page', 'wdfb_api' );
		add_settings_field( 'wdfb_prevent_access', __( 'Prevent access to my linked accounts', 'wdfb' ), array(
			$form,
			'create_prevent_access_box'
		), 'wdfb_options_page', 'wdfb_api' );
		add_settings_field( 'wdfb_allow_propagation', __( 'Allow sub-sites to use these credentials', 'wdfb' ), array(
			$form,
			'create_allow_propagation_box'
		), 'wdfb_options_page', 'wdfb_api' );
		add_settings_field( '', '', array( $form, 'next_step' ), 'wdfb_options_page', 'wdfb_api' );

		/**
		 * Permissions and Tokens
		 */
		add_settings_section( 'wdfb_grant', __( 'Permissions &amp; Tokens', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
		add_settings_field( 'wdfb_api_permissions', __( 'Allowing permissions', 'wdfb' ), array(
			$form,
			'api_permissions'
		), 'wdfb_options_page', 'wdfb_grant' );
		add_settings_field( 'wdfb_api_cache', __( 'Extra permissions', 'wdfb' ), array(
			$form,
			'extra_permissions'
		), 'wdfb_options_page', 'wdfb_grant' );
		add_settings_field( '', '', array( $form, 'next_step' ), 'wdfb_options_page', 'wdfb_grant' );

		/**
		 * Facebook Connect
		 */
		register_setting( 'wdfb', 'wdfb_connect' );
		add_settings_section( 'wdfb_connect', __( 'Facebook Connect', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
		add_settings_field( 'wdfb_allow_facebook_registration', __( 'Allow users to connect using Facebook', 'wdfb' ), array(
			$form,
			'create_allow_facebook_registration_box'
		), 'wdfb_options_page', 'wdfb_connect' );
		add_settings_field( 'wdfb_easy_facebook_registration', __( 'Allow single-click registration', 'wdfb' ), array(
			$form,
			'create_easy_facebook_registration_box'
		), 'wdfb_options_page', 'wdfb_connect' );
		if ( defined( 'BP_VERSION' ) && class_exists('BuddyPress') ) {
			add_settings_field( 'wdfb_update_bp_activity', __( 'Update Activity feed', 'wdfb' ), array(
				$form,
				'create_update_bp_activity_box'
			), 'wdfb_options_page', 'wdfb_connect' );
		}
		add_settings_field( 'wdfb_facebook_avatars', __( 'Do not use Facebook avatars as profile images', 'wdfb' ), array(
			$form,
			'create_facebook_avatars_box'
		), 'wdfb_options_page', 'wdfb_connect' );
		add_settings_field( 'wdfb_login_redirect', __( 'Redirect on login', 'wdfb' ), array(
			$form,
			'create_login_redirect_box'
		), 'wdfb_options_page', 'wdfb_connect' );
		add_settings_field( 'wdfb_autologin', __( 'Auto-login after registration', 'wdfb' ), array(
			$form,
			'create_autologin_box'
		), 'wdfb_options_page', 'wdfb_connect' );
		if ( defined( 'BP_VERSION' ) && class_exists('BuddyPress') ) { // BuddyPress
			add_settings_field( 'wdfb_buddypress_registration_fields', __( 'Map BuddyPress profile to Facebook', 'wdfb' ), array(
				$form,
				'create_buddypress_registration_fields_box'
			), 'wdfb_options_page', 'wdfb_connect' );
		} else {
			add_settings_field( 'wdfb_wordrpess_registration_fields', __( 'Map WordPress profile to Facebook', 'wdfb' ), array(
				$form,
				'create_wordpress_registration_fields_box'
			), 'wdfb_options_page', 'wdfb_connect' );
		}
		add_settings_field( 'wdfb_identity_renewal', __( 'Allow Facebook identity renewal', 'wdfb' ), array(
			$form,
			'create_identity_renewal_box'
		), 'wdfb_options_page', 'wdfb_connect' );
		add_settings_field( '', '', array( $form, 'next_step' ), 'wdfb_options_page', 'wdfb_connect' );

		/**
		 * Facebook Like and Send Button
		 */
		register_setting( 'wdfb', 'wdfb_button' );
		add_settings_section( 'wdfb_button', __( 'Facebook Like/Send Button', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
		add_settings_field( 'wdfb_show_button', __( 'Allow Facebook Like Button', 'wdfb' ), array(
			$form,
			'create_allow_facebook_button_box'
		), 'wdfb_options_page', 'wdfb_button' );
		add_settings_field( 'wdfb_show_send_position', __( 'Show "Send" button too', 'wdfb' ), array(
			$form,
			'create_show_send_button_box'
		), 'wdfb_options_page', 'wdfb_button' );
		add_settings_field( 'wdfb_show_front_page', __( 'Show on', 'wdfb' ), array(
			$form,
			'create_show_on_front_page_box'
		), 'wdfb_options_page', 'wdfb_button' );
		add_settings_field( 'wdfb_not_in_post_types', __( 'Do <strong>NOT</strong> show button in these types', 'wdfb' ), array(
			$form,
			'create_do_not_show_button_box'
		), 'wdfb_options_page', 'wdfb_button' );
		add_settings_field( 'wdfb_button_position', __( 'Button position', 'wdfb' ), array(
			$form,
			'create_button_position_box'
		), 'wdfb_options_page', 'wdfb_button' );
		add_settings_field( 'wdfb_button_appearance', __( 'Button appearance', 'wdfb' ), array(
			$form,
			'create_button_appearance_box'
		), 'wdfb_options_page', 'wdfb_button' );
		add_settings_field( 'wdfb_button_color_scheme', __( 'Color scheme', 'wdfb' ), array(
			$form,
			'create_button_color_scheme_box'
		), 'wdfb_options_page', 'wdfb_button' );
		add_settings_field( '', '', array( $form, 'next_step' ), 'wdfb_options_page', 'wdfb_button' );

		/**
		 * Facebook Open Graph
		 */
		register_setting( 'wdfb', 'wdfb_opengraph' );
		add_settings_section( 'wdfb_opengraph', __( 'Facebook OpenGraph', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
		add_settings_field( 'wdfb_use_opengraph', __( 'Use OpenGraph support', 'wdfb' ), array(
			$form,
			'create_use_opengraph_box'
		), 'wdfb_options_page', 'wdfb_opengraph' );
		add_settings_field( 'wdfb_always_use_image', __( 'Always use this image', 'wdfb' ), array(
			$form,
			'create_always_use_image_box'
		), 'wdfb_options_page', 'wdfb_opengraph' );
		add_settings_field( 'wdfb_fallback_image', __( 'Fallback image', 'wdfb' ), array(
			$form,
			'create_fallback_image_box'
		), 'wdfb_options_page', 'wdfb_opengraph' );
		add_settings_field( 'wdfb_og_type', __( 'OpenGraph type', 'wdfb' ), array(
			$form,
			'create_og_type_box'
		), 'wdfb_options_page', 'wdfb_opengraph' );
		add_settings_field( 'wdfb_og_extras', __( 'Additional OpenGraph headers', 'wdfb' ), array(
			$form,
			'create_og_extras_box'
		), 'wdfb_options_page', 'wdfb_opengraph' );
		add_settings_field( '', '', array( $form, 'next_step' ), 'wdfb_options_page', 'wdfb_opengraph' );

		if ( defined( 'BP_VERSION' ) && class_exists('BuddyPress') ) {
			register_setting( 'wdfb', 'wdfb_groups' );
			add_settings_section( 'wdfb_groups', __( 'Facebook Groups', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
			add_settings_field( 'wdfb_allow_bp_groups_sync', __( 'BuddyPress groups info sync', 'wdfb' ), array(
				$form,
				'create_allow_bp_groups_sync_box'
			), 'wdfb_options_page', 'wdfb_groups' );
		}

		/**
		 * Facebook Comments
		 */
		register_setting( 'wdfb', 'wdfb_comments' );
		add_settings_section( 'wdfb_comments', __( 'Facebook Comments', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
		add_settings_field( 'wdfb_import_fb_comments', __( 'Import Facebook comments', 'wdfb' ), array(
			$form,
			'create_import_fb_comments_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_skip_import_fb_comments', __( 'Skip importing comments for these accounts', 'wdfb' ), array(
			$form,
			'create_import_fb_comments_skip_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_comments_limit', __( 'Limit', 'wdfb' ), array(
			$form,
			'create_fb_comments_limit_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_notify_authors', __( 'Notify post authors', 'wdfb' ), array(
			$form,
			'create_notify_authors_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_use_fb_comments', __( 'Use Facebook for comments', 'wdfb' ), array(
			$form,
			'create_use_fb_comments_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		if ( ! defined( 'BP_VERSION' ) /*&& class_exists('BuddyPress')*/ ) {
			add_settings_field( 'wdfb_override_wp_comments_settings', __( 'Override WordPress discussion settings', 'wdfb' ), array(
				$form,
				'create_override_wp_comments_settings_box'
			), 'wdfb_options_page', 'wdfb_comments' );
		}
		add_settings_field( 'wdfb_fb_comments_width', __( 'Facebook Comments box width', 'wdfb' ), array(
			$form,
			'create_fb_comments_width_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_comments_reverse', __( 'Show Facebook Comments in reverse order?', 'wdfb' ), array(
			$form,
			'create_fb_comments_reverse_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_comments_number', __( 'Show this many Facebook Comments', 'wdfb' ), array(
			$form,
			'create_fb_comments_number_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_color_scheme', __( 'Color scheme', 'wdfb' ), array(
			$form,
			'create_fb_comments_color_scheme_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_notify_post_authors', __( 'Notify post authors', 'wdfb' ), array(
			$form,
			'create_fbc_notify_authors_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_comments_custom_hook', __( 'Use a custom hook <small>(advanced)</small>', 'wdfb' ), array(
			$form,
			'create_fb_comments_custom_hook_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( '', '', array( $form, 'next_step' ), 'wdfb_options_page', 'wdfb_comments' );

		/**
		 * Facebook Autopost
		 */
		register_setting( 'wdfb', 'wdfb_autopost' );
		add_settings_section( 'wdfb_autopost', __( 'Autopost to Facebook', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
		add_settings_field( 'wdfb_show_button', __( 'Allow autoposting new posts to Facebook', 'wdfb' ), array(
			$form,
			'create_allow_autopost_box'
		), 'wdfb_options_page', 'wdfb_autopost' );
		add_settings_field( 'wdfb_allow_skip_autopost', __( 'Allow users to skip autopost', 'wdfb' ), array(
			$form,
			'skip_autopost'
		), 'wdfb_options_page', 'wdfb_autopost' );
		add_settings_field( 'wdfb_allow_frontend_autopost', __( 'Allow frontend autoposting to Facebook', 'wdfb' ), array(
			$form,
			'create_allow_frontend_autopost_box'
		), 'wdfb_options_page', 'wdfb_autopost' );
		add_settings_field( 'wdfb_show_status_column', __( 'Show post Facebook status column', 'wdfb' ), array(
			$form,
			'create_show_status_column_box'
		), 'wdfb_options_page', 'wdfb_autopost' );
		add_settings_field( 'wdfb_autopost_types', __( 'Map WordPress types to Facebook locations', 'wdfb' ), array(
			$form,
			'create_autopost_map_box'
		), 'wdfb_options_page', 'wdfb_autopost' );
		add_settings_field( 'wdfb_allow_post_metabox', __( 'Do not allow individual posts to Facebook', 'wdfb' ), array(
			$form,
			'create_allow_post_metabox_box'
		), 'wdfb_options_page', 'wdfb_autopost' );
		add_settings_field( 'wdfb_image_size', __( 'Image Size for Autopost', 'wdfb' ), array(
			$form,
			'image_size_autopost_facebook'
		), 'wdfb_options_page', 'wdfb_autopost' );
		if ( defined( 'BP_VERSION' ) && class_exists('BuddyPress') ) {
			add_settings_field( 'wdfb_allow_bp_activity_switch', __( 'Do not allow individual Activity updates to Facebook', 'wdfb' ), array(
				$form,
				'create_allow_bp_activity_switch_box'
			), 'wdfb_options_page', 'wdfb_autopost' );
		}
		add_settings_field( '', '', array( $form, 'next_step' ), 'wdfb_options_page', 'wdfb_autopost' );

		/**
		 * Submenu: Widget Pack
		 */
		register_setting( 'wdfb', 'wdfb_widget_pack' );
		add_settings_section( 'wdfb_widget_pack', __( 'Widget pack', 'wdfb' ), create_function( '', '' ), 'wdfb_widget_options_page' );
		add_settings_field( 'wdfb_widget_connect', __( 'Use Facebook Connect widget', 'wdfb' ), array(
			$form,
			'create_widget_connect_box'
		), 'wdfb_widget_options_page', 'wdfb_widget_pack' );
		add_settings_field( 'wdfb_widget_albums', __( 'Use Facebook Albums widget', 'wdfb' ), array(
			$form,
			'create_widget_albums_box'
		), 'wdfb_widget_options_page', 'wdfb_widget_pack' );
		add_settings_field( 'wdfb_widget_events', __( 'Use Facebook Events widget', 'wdfb' ), array(
			$form,
			'create_widget_events_box'
		), 'wdfb_widget_options_page', 'wdfb_widget_pack' );
		add_settings_field( 'wdfb_widget_likebox', __( 'Use Facebook Like Box widget', 'wdfb' ), array(
			$form,
			'create_widget_likebox_box'
		), 'wdfb_widget_options_page', 'wdfb_widget_pack' );
		add_settings_field( 'wdfb_widget_recent_comments', __( 'Use Facebook Recent Comments widget', 'wdfb' ), array(
			$form,
			'create_widget_recent_comments_box'
		), 'wdfb_widget_options_page', 'wdfb_widget_pack' );
		add_settings_field( 'wdfb_dashboard_permissions', __( 'Use Facebook Dashboard widgets', 'wdfb' ), array(
			$form,
			'create_dashboard_permissions_box'
		), 'wdfb_widget_options_page', 'wdfb_widget_pack' );
	}

	/**
	 * Registers settings and form handlers/elements for per-blog administration.
	 *
	 * @access private
	 */
	function register_blog_settings() {
		$form = new Wdfb_AdminFormRenderer;

		/**
		 * Facebook API
		 */
		register_setting( 'wdfb', 'wdfb_api' );
		add_settings_section( 'wdfb_api', __( 'Facebook API', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
		add_settings_field( 'wdfb_api_info', __( 'Before we begin', 'wdfb' ), array(
			$form,
			'api_info'
		), 'wdfb_options_page', 'wdfb_api' );
		add_settings_field( 'wdfb_app_key', __( 'App ID / API key', 'wdfb' ), array(
			$form,
			'create_app_key_box'
		), 'wdfb_options_page', 'wdfb_api' );
		add_settings_field( 'wdfb_secret_key', __( 'Secret key', 'wdfb' ), array(
			$form,
			'create_secret_key_box'
		), 'wdfb_options_page', 'wdfb_api' );
		add_settings_field( 'wdfb_locale', __( 'API Locale', 'wdfb' ), array(
			$form,
			'create_locale_box'
		), 'wdfb_options_page', 'wdfb_api' );
		add_settings_field( 'wdfb_prevent_access', __( 'Prevent access to my linked accounts', 'wdfb' ), array(
			$form,
			'create_prevent_access_box'
		), 'wdfb_options_page', 'wdfb_api' );
		add_settings_field( '', '', array( $form, 'next_step' ), 'wdfb_options_page', 'wdfb_api' );

		/**
		 * Permission and Tokens
		 */
		add_settings_section( 'wdfb_grant', __( 'Permissions &amp; Tokens', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
		add_settings_field( 'wdfb_api_permissions', __( 'Allowing permissions', 'wdfb' ), array(
			$form,
			'api_permissions'
		), 'wdfb_options_page', 'wdfb_grant' );
		add_settings_field( 'wdfb_api_cache', __( 'Cache', 'wdfb' ), array(
			$form,
			'cache_operations'
		), 'wdfb_options_page', 'wdfb_grant' );
		add_settings_field( 'wdfb_api_cache', __( 'Extra permissions', 'wdfb' ), array(
			$form,
			'extra_permissions'
		), 'wdfb_options_page', 'wdfb_grant' );
		add_settings_field( '', '', array( $form, 'next_step' ), 'wdfb_options_page', 'wdfb_grant' );

		/**
		 * Check for Single site, Facebook Connect option
		 */
		if ( ! is_multisite() || current_user_can( 'manage_network_options' ) ) {
			register_setting( 'wdfb', 'wdfb_connect' );
			add_settings_section( 'wdfb_connect', __( 'Facebook Connect', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
			add_settings_field( 'wdfb_allow_facebook_registration', __( 'Allow users to login/register with Facebook', 'wdfb' ), array(
				$form,
				'create_allow_facebook_registration_box'
			), 'wdfb_options_page', 'wdfb_connect' );
			add_settings_field( 'wdfb_easy_facebook_registration', __( 'Allow single-click registration', 'wdfb' ), array(
				$form,
				'create_easy_facebook_registration_box'
			), 'wdfb_options_page', 'wdfb_connect' );
			if ( defined( 'BP_VERSION' ) && class_exists('BuddyPress') ) {
				add_settings_field( 'wdfb_update_bp_activity', __( 'Update Activity feed', 'wdfb' ), array(
					$form,
					'create_update_bp_activity_box'
				), 'wdfb_options_page', 'wdfb_connect' );
			}
			add_settings_field( 'wdfb_facebook_avatars', __( 'Do not use Facebook avatars as profile images', 'wdfb' ), array(
				$form,
				'create_facebook_avatars_box'
			), 'wdfb_options_page', 'wdfb_connect' );
			add_settings_field( 'wdfb_login_redirect', __( 'Redirect on login', 'wdfb' ), array(
				$form,
				'create_login_redirect_box'
			), 'wdfb_options_page', 'wdfb_connect' );
			add_settings_field( 'wdfb_autologin', __( 'Auto-login after registration', 'wdfb' ), array(
				$form,
				'create_autologin_box'
			), 'wdfb_options_page', 'wdfb_connect' );
			if ( defined( 'BP_VERSION' ) && class_exists('BuddyPress') ) { // BuddyPress
				add_settings_field( 'wdfb_buddypress_registration_fields', __( 'Map BuddyPress profile to Facebook', 'wdfb' ), array(
					$form,
					'create_buddypress_registration_fields_box'
				), 'wdfb_options_page', 'wdfb_connect' );
			} else {
				add_settings_field( 'wdfb_wordrpess_registration_fields', __( 'Map WordPress profile to Facebook', 'wdfb' ), array(
					$form,
					'create_wordpress_registration_fields_box'
				), 'wdfb_options_page', 'wdfb_connect' );
			}
			add_settings_field( 'wdfb_identity_renewal', __( 'Allow Facebook identity renewal', 'wdfb' ), array(
				$form,
				'create_identity_renewal_box'
			), 'wdfb_options_page', 'wdfb_connect' );
			add_settings_field( '', '', array( $form, 'next_step' ), 'wdfb_options_page', 'wdfb_connect' );
		}

		/**
		 * Facebook Like/Send Button
		 */
		register_setting( 'wdfb', 'wdfb_button' );
		add_settings_section( 'wdfb_button', __( 'Facebook Like/Send Button', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
		add_settings_field( 'wdfb_show_button', __( 'Allow Facebook Like Button', 'wdfb' ), array(
			$form,
			'create_allow_facebook_button_box'
		), 'wdfb_options_page', 'wdfb_button' );
		add_settings_field( 'wdfb_show_send_position', __( 'Show "Send" button too', 'wdfb' ), array(
			$form,
			'create_show_send_button_box'
		), 'wdfb_options_page', 'wdfb_button' );
		add_settings_field( 'wdfb_show_front_page', __( 'Show on', 'wdfb' ), array(
			$form,
			'create_show_on_front_page_box'
		), 'wdfb_options_page', 'wdfb_button' );
		add_settings_field( 'wdfb_not_in_post_types', __( 'Do <strong>NOT</strong> show button in these types', 'wdfb' ), array(
			$form,
			'create_do_not_show_button_box'
		), 'wdfb_options_page', 'wdfb_button' );
		add_settings_field( 'wdfb_button_position', __( 'Button position', 'wdfb' ), array(
			$form,
			'create_button_position_box'
		), 'wdfb_options_page', 'wdfb_button' );
		add_settings_field( 'wdfb_button_appearance', __( 'Button appearance', 'wdfb' ), array(
			$form,
			'create_button_appearance_box'
		), 'wdfb_options_page', 'wdfb_button' );
		add_settings_field( 'wdfb_button_color_scheme', __( 'Color scheme', 'wdfb' ), array(
			$form,
			'create_button_color_scheme_box'
		), 'wdfb_options_page', 'wdfb_button' );
		add_settings_field( '', '', array( $form, 'next_step' ), 'wdfb_options_page', 'wdfb_button' );

		/**
		 * Facebook Open graph
		 */
		register_setting( 'wdfb', 'wdfb_opengraph' );
		add_settings_section( 'wdfb_opengraph', __( 'Facebook OpenGraph', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
		add_settings_field( 'wdfb_use_opengraph', __( 'Use OpenGraph support', 'wdfb' ), array(
			$form,
			'create_use_opengraph_box'
		), 'wdfb_options_page', 'wdfb_opengraph' );
		add_settings_field( 'wdfb_always_use_image', __( 'Always use this image', 'wdfb' ), array(
			$form,
			'create_always_use_image_box'
		), 'wdfb_options_page', 'wdfb_opengraph' );
		add_settings_field( 'wdfb_fallback_image', __( 'Fallback image', 'wdfb' ), array(
			$form,
			'create_fallback_image_box'
		), 'wdfb_options_page', 'wdfb_opengraph' );
		add_settings_field( 'wdfb_og_type', __( 'OpenGraph type', 'wdfb' ), array(
			$form,
			'create_og_type_box'
		), 'wdfb_options_page', 'wdfb_opengraph' );
		add_settings_field( 'wdfb_og_extras', __( 'Additional OpenGraph headers', 'wdfb' ), array(
			$form,
			'create_og_extras_box'
		), 'wdfb_options_page', 'wdfb_opengraph' );
		add_settings_field( '', '', array( $form, 'next_step' ), 'wdfb_options_page', 'wdfb_opengraph' );

		if ( defined( 'BP_VERSION' ) ) {
			register_setting( 'wdfb', 'wdfb_groups' );
			add_settings_section( 'wdfb_groups', __( 'Facebook Groups', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
			add_settings_field( 'wdfb_allow_bp_groups_sync', __( 'BuddyPress groups info sync', 'wdfb' ), array(
				$form,
				'create_allow_bp_groups_sync_box'
			), 'wdfb_options_page', 'wdfb_groups' );
		}

		/**
		 * Facebook Comments
		 */
		register_setting( 'wdfb', 'wdfb_comments' );
		add_settings_section( 'wdfb_comments', __( 'Facebook Comments', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
		add_settings_field( 'wdfb_import_fb_comments', __( 'Import Facebook comments', 'wdfb' ), array(
			$form,
			'create_import_fb_comments_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_skip_import_fb_comments', __( 'Skip importing comments for these accounts', 'wdfb' ), array(
			$form,
			'create_import_fb_comments_skip_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_comments_limit', __( 'Limit', 'wdfb' ), array(
			$form,
			'create_fb_comments_limit_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_notify_authors', __( 'Notify post authors', 'wdfb' ), array(
			$form,
			'create_notify_authors_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_import_now', __( 'Import comments now', 'wdfb' ), array(
			$form,
			'create_import_now_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_use_fb_comments', __( 'Use Facebook for comments', 'wdfb' ), array(
			$form,
			'create_use_fb_comments_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		if ( ! defined( 'BP_VERSION' ) ) {
			add_settings_field( 'wdfb_override_wp_comments_settings', __( 'Override WordPress discussion settings', 'wdfb' ), array(
				$form,
				'create_override_wp_comments_settings_box'
			), 'wdfb_options_page', 'wdfb_comments' );
		}
		add_settings_field( 'wdfb_fb_comments_width', __( 'Facebook Comments box width', 'wdfb' ), array(
			$form,
			'create_fb_comments_width_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_comments_reverse', __( 'Show Facebook Comments in reverse order?', 'wdfb' ), array(
			$form,
			'create_fb_comments_reverse_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_comments_number', __( 'Show this many Facebook Comments', 'wdfb' ), array(
			$form,
			'create_fb_comments_number_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_color_scheme', __( 'Color scheme', 'wdfb' ), array(
			$form,
			'create_fb_comments_color_scheme_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_notify_post_authors', __( 'Notify post authors', 'wdfb' ), array(
			$form,
			'create_fbc_notify_authors_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( 'wdfb_fb_comments_custom_hook', __( 'Use a custom hook <small>(advanced)</small>', 'wdfb' ), array(
			$form,
			'create_fb_comments_custom_hook_box'
		), 'wdfb_options_page', 'wdfb_comments' );
		add_settings_field( '', '', array( $form, 'next_step' ), 'wdfb_options_page', 'wdfb_comments' );

		/**
		 * Facebook Autopost
		 */
		register_setting( 'wdfb', 'wdfb_autopost' );
		add_settings_section( 'wdfb_autopost', __( 'Autopost to Facebook', 'wdfb' ), create_function( '', '' ), 'wdfb_options_page' );
		add_settings_field( 'wdfb_allow_autopost', __( 'Allow autoposting new posts to Facebook', 'wdfb' ), array(
			$form,
			'create_allow_autopost_box'
		), 'wdfb_options_page', 'wdfb_autopost' );
		add_settings_field( 'wdfb_allow_skip_autopost', __( 'Allow users to skip autopost', 'wdfb' ), array(
			$form,
			'skip_autopost'
		), 'wdfb_options_page', 'wdfb_autopost' );
		add_settings_field( 'wdfb_image_size', __( 'Image Size for Autopost', 'wdfb' ), array(
			$form,
			'image_size_autopost_facebook'
		), 'wdfb_options_page', 'wdfb_autopost' );
		add_settings_field( 'wdfb_allow_frontend_autopost', __( 'Allow frontend autoposting to Facebook', 'wdfb' ), array(
			$form,
			'create_allow_frontend_autopost_box'
		), 'wdfb_options_page', 'wdfb_autopost' );
		add_settings_field( 'wdfb_show_status_column', __( 'Show post Facebook status column', 'wdfb' ), array(
			$form,
			'create_show_status_column_box'
		), 'wdfb_options_page', 'wdfb_autopost' );
		add_settings_field( 'wdfb_autopost_types', __( 'Map WordPress types to Facebook locations', 'wdfb' ), array(
			$form,
			'create_autopost_map_box'
		), 'wdfb_options_page', 'wdfb_autopost' );
		add_settings_field( 'wdfb_allow_post_metabox', __( 'Do not allow individual posts to Facebook', 'wdfb' ), array(
			$form,
			'create_allow_post_metabox_box'
		), 'wdfb_options_page', 'wdfb_autopost' );
		if ( defined( 'BP_VERSION' ) ) {
			add_settings_field( 'wdfb_allow_bp_activity_switch', __( 'Do not allow individual Activity updates to Facebook', 'wdfb' ), array(
				$form,
				'create_allow_bp_activity_switch_box'
			), 'wdfb_options_page', 'wdfb_autopost' );
		}

		/**
		 * Widget Pack
		 */
		register_setting( 'wdfb', 'wdfb_widget_pack' );
		add_settings_section( 'wdfb_widget_pack', __( 'Widget pack', 'wdfb' ), create_function( '', '' ), 'wdfb_widget_options_page' );
		add_settings_field( 'wdfb_widget_connect', __( 'Use Facebook Connect widget', 'wdfb' ), array(
			$form,
			'create_widget_connect_box'
		), 'wdfb_widget_options_page', 'wdfb_widget_pack' );
		add_settings_field( 'wdfb_widget_albums', __( 'Use Facebook Albums widget', 'wdfb' ), array(
			$form,
			'create_widget_albums_box'
		), 'wdfb_widget_options_page', 'wdfb_widget_pack' );
		add_settings_field( 'wdfb_widget_events', __( 'Use Facebook Events widget', 'wdfb' ), array(
			$form,
			'create_widget_events_box'
		), 'wdfb_widget_options_page', 'wdfb_widget_pack' );
		add_settings_field( 'wdfb_widget_likebox', __( 'Use Facebook Like Box widget', 'wdfb' ), array(
			$form,
			'create_widget_likebox_box'
		), 'wdfb_widget_options_page', 'wdfb_widget_pack' );
		add_settings_field( 'wdfb_widget_recent_comments', __( 'Use Facebook Recent Comments widget', 'wdfb' ), array(
			$form,
			'create_widget_recent_comments_box'
		), 'wdfb_widget_options_page', 'wdfb_widget_pack' );
		add_settings_field( 'wdfb_dashboard_permissions', __( 'Use Facebook Dashboard widgets', 'wdfb' ), array(
			$form,
			'create_dashboard_permissions_box'
		), 'wdfb_widget_options_page', 'wdfb_widget_pack' );
	}

	/**
	 * Creates per-blog Admin menu entry.
	 *
	 * @access private
	 */
	function create_blog_admin_menu_entry() {
		if ( current_user_can( 'manage_options' ) && ! empty( $_POST ) && isset( $_POST['option_page'] ) ) {
			if ( 'wdfb' == @$_POST['option_page'] ) {
				$keys = Wdfb_Installer::get_keys();
				unset( $keys['widget_pack'] );
			} else if ( 'wdfb_widgets' == @$_POST['option_page'] ) {
				$keys = array( 'widget_pack' );
			} else {
				$keys = false;
			}
			if ( $keys ) {
				foreach ( $keys as $key ) {
					if ( isset( $_POST["wdfb_{$key}"] ) ) {
						update_option( "wdfb_{$key}", $_POST["wdfb_{$key}"] ); //echo "<p>we have $key</p>";
					}
				}
				$goback = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
				wp_redirect( $goback );
				die;
			}
		}
		add_menu_page( 'Ultimate Facebook', 'Facebook', 'manage_options', 'wdfb', array(
			$this,
			'create_admin_page'
		), 'dashicons-facebook' );
		add_submenu_page( 'wdfb', 'Ultimate Facebook', 'Facebook Settings', 'manage_options', 'wdfb', array(
			$this,
			'create_admin_page'
		) );
		add_submenu_page( 'wdfb', 'Widget Pack', 'Widget Pack', 'manage_options', 'wdfb_widgets', array(
			$this,
			'create_admin_widgets_page'
		) );
		add_submenu_page( 'wdfb', 'Shortcodes', 'Shortcodes', 'manage_options', 'wdfb_shortcodes', array(
			$this,
			'create_admin_shortcodes_page'
		) );
		add_submenu_page( 'wdfb', 'Error Log', 'Error Log', 'manage_options', 'wdfb_error_log', array(
			$this,
			'create_admin_error_log_page'
		) );
	}

	/**
	 * Creates sitewide Admin menu entry.
	 * Also, process settings.
	 *
	 * @access private
	 */
	function create_site_admin_menu_entry() {
		if ( current_user_can( 'manage_network_options' ) && ! empty( $_POST ) && isset( $_POST['option_page'] ) ) {
			$override = false;
			if ( 'wdfb' == @$_POST['option_page'] ) {
				$keys = Wdfb_Installer::get_keys();
				unset( $keys['widget_pack'] );
				$override = (int) @$_POST['_override_all'];
			} else if ( 'wdfb_widgets' == @$_POST['option_page'] ) {
				$opt      = get_site_option( 'wdfb_network' );
				$override = @$opt['prevent_blog_settings'] ? true : false;
				$keys     = array( 'widget_pack' );
			} else {
				$keys = false;
			}
			if ( $keys ) {
				if ( $override ) {
					$blogs = $this->model->get_blog_ids();
				} // Get this list only once
				foreach ( $keys as $key ) {
					if ( isset( $_POST["wdfb_{$key}"] ) ) {
						update_site_option( "wdfb_{$key}", $_POST["wdfb_{$key}"] );
						if ( $override ) { // Override child settings
							if ( 'api' == $key && isset( $_POST['_preserve_api'] ) ) {
								continue;
							} // Preserve API
							if ( ! $blogs ) {
								continue;
							}
							foreach ( $blogs as $blog ) {
								update_blog_option( $blog['blog_id'], "wdfb_{$key}", $_POST["wdfb_{$key}"] );
							}
						}
					}
				}
				$goback = add_query_arg( 'settings-updated', 'true', wp_get_referer() );
				wp_redirect( $goback );
				die;
			}
		}
		add_menu_page( 'Ultimate Facebook', 'Facebook', 'manage_network_options', 'wdfb', array(
			$this,
			'create_admin_page'
		), 'dashicons-facebook' );
		add_submenu_page( 'wdfb', 'Ultimate Facebook', 'Facebook Settings', 'manage_network_options', 'wdfb', array(
			$this,
			'create_admin_page'
		) );
		add_submenu_page( 'wdfb', 'Widget Pack', 'Widget Pack', 'manage_network_options', 'wdfb_widgets', array(
			$this,
			'create_admin_widgets_page'
		) );
		add_submenu_page( 'wdfb', 'Shortcodes', 'Shortcodes', 'manage_network_options', 'wdfb_shortcodes', array(
			$this,
			'create_admin_shortcodes_page'
		) );
	}

	/**
	 * Creates Admin menu page.
	 *
	 * @access private
	 */
	function create_admin_page() {
		$this->handle_fb_auth_tokens();
		include( WDFB_PLUGIN_BASE_DIR . '/lib/forms/plugin_settings.php' );
	}

	/**
	 * Creates Admin Widgets pack page.
	 *
	 * @access private.
	 */
	function create_admin_widgets_page() {
		include( WDFB_PLUGIN_BASE_DIR . '/lib/forms/widget_pack_settings.php' );
	}

	/**
	 * Creates Admin Shortcodes info page.
	 *
	 * @access private.
	 */
	function create_admin_shortcodes_page() {
		include( WDFB_PLUGIN_BASE_DIR . '/lib/forms/shortcodes_info.php' );
	}

	/**
	 * Creates Admin Error Log info page.
	 *
	 * @access private.
	 */
	function create_admin_error_log_page() {
		$log = new Wdfb_ErrorLog;
		if ( 'purge' == @$_GET['action'] ) {
			$log->purge_errors();
			$log->purge_notices();
		}
		$errors  = $log->get_all_errors();
		$notices = $log->get_all_notices();
		include( WDFB_PLUGIN_BASE_DIR . '/lib/forms/error_log.php' );
	}

	/**
	 * API setup notice
	 */
	function notice_api_setup() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		} // This guy can't help us. Moving on.
		if ( $this->data->get_option( 'wdfb_api', 'app_key' ) ) {
			return false;
		} // We're set up. Moving on.
		if ( isset( $_GET['page'] ) && 'wdfb' == $_GET['page'] ) {
			return false;
		} // We're doing this. Stop nagging.
		if ( defined( 'WDFB_DO_NOT_SHOW_CONFIGURATION_NAG' ) && WDFB_DO_NOT_SHOW_CONFIGURATION_NAG ) {
			return false;
		}

		$opt = get_site_option( 'wdfb_network', array() );
		if ( @$opt['prevent_blog_settings'] ) {
			return false;
		} // Can't really do this, no point in whining.

		echo '<div class="error"><p>' .
		     sprintf(
			     __( '<b>Ultimate Facebook</b> plugin needs to be configured. <a href="%s">You can do so here.</a>', 'wdfb' ),
			     admin_url( 'admin.php?page=wdfb' )
		     ) .
		     '</p></div>';
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

	function js_load_scripts() {
		wp_enqueue_script( 'jquery' );
		$locale = wdfb_get_locale();
		if ( function_exists( 'get_current_screen' ) ) {
			$screen = get_current_screen();
			if ( is_object( $screen ) && isset ( $screen->id ) && in_array( $screen->id, apply_filters( 'wdfb-scripts-prevent_inclusion_ids', array() ) ) ) {
				return false;
			}
		}
		define( 'WDFB_ROOT_SCRIPT_ADDED', true, true );
		wp_enqueue_script( 'facebook-all', WDFB_PROTOCOL . 'connect.facebook.net/' . $locale . '/all.js' );
		//wp_enqueue_script('wdfb_post_as_page', WDFB_PLUGIN_URL . '/js/wdfb_post_as_page.js'); // Deprecated
	}

	function js_editors() {
		wp_enqueue_script( 'thickbox' );

		wp_enqueue_script( 'wdfb_editor_album', WDFB_PLUGIN_URL . '/js/editor_album.js' );
		wp_localize_script( 'wdfb_editor_album', 'l10nWdfbEditor', array(
			'add_fb_photo'        => __( 'Add FB Photo', 'wdfb' ),
			'insert_album'        => __( 'Insert album', 'wdfb' ),
			'insert_album_photos' => __( 'Insert album photos', 'wdfb' ),
			'insert'              => __( 'Insert', 'wdfb' ),
			'go_back'             => __( 'Go back', 'wdfb' ),
			'use_this_image'      => __( 'Use this image', 'wdfb' ),
			'please_wait'         => __( 'Please, wait...', 'wdfb' ),
		) );
	}

	function css_load_styles() {
		wp_enqueue_style( 'wdfb_album_editor', WDFB_PLUGIN_URL . '/css/wdfb_album_editor.css', '', WDFB_PLUGIN_VERSION );
	}

	/**
	 * Introduces plugins_url() as root variable (global).
	 */
	function js_plugin_url() {
		printf(
			'<script type="text/javascript">var _wdfb_root_url="%s";</script>',
			WDFB_PLUGIN_URL
		);
	}

	function inject_fb_init_js() {
		if ( ! ( defined( 'WDFB_ROOT_SCRIPT_ADDED' ) && WDFB_ROOT_SCRIPT_ADDED ) ) {
			return false;
		} // No point it doing this unless we're set up
		echo "<script type='text/javascript'>
         FB.init({
            appId: '" . trim( $this->data->get_option( 'wdfb_api', 'app_key' ) ) . "', cookie:true,
            status: true,
            cookie: true,
            xfbml: true,
            oauth: true
         });
      </script>";
	}

	/**
	 * Injects Facebook root div needed for XFBML near page footer.
	 */
	function inject_fb_root_div() {
		echo "<div id='fb-root'></div>";
	}

	/**
	 * This happens only if allow_facebook_registration is true.
	 */
	function handle_fb_session_state() {
		if ( wp_validate_auth_cookie( '' ) ) {
			return $this->handle_fb_auth_tokens();
		}
		$fb_user = $this->model->fb->getUser();

		if ( $fb_user ) {
			$user_id = $this->model->get_wp_user_from_fb();
			if ( ! $user_id ) {
				$user_id = $this->model->map_fb_to_current_wp_user();
			}
			if ( $user_id ) {
				$user = get_userdata( $user_id );
				/*
				if (is_multisite() && function_exists('is_user_member_of_blog')) {
					if (!is_user_member_of_blog($user_id)) return false; // Don't allow this
				}
				*/
				wp_set_current_user( $user->ID, $user->user_login );
				wp_set_auth_cookie( $user->ID ); // Logged in with Facebook, yay
				do_action( 'wp_login', $user->user_login );
				$this->handle_fb_auth_tokens();
				if ( ! (
					defined( 'DOING_AJAX' )
					&& isset( $_REQUEST['action'] )
					&& 'wdfb_perhaps_create_wp_user' == $_REQUEST['action']
				)
				) {
					wp_redirect( admin_url() );
					exit();
				}
			}
		}
	}

	function handle_fb_auth_tokens( $return = false ) {
		$log    = new Wdfb_ErrorLog;
		$tokens = $this->data->get_option( 'wdfb_api', 'auth_tokens' );

		$fb_uid     = $this->model->fb->getUser();
		$app_id     = trim( $this->data->get_option( 'wdfb_api', 'app_key' ) );
		$app_secret = trim( $this->data->get_option( 'wdfb_api', 'secret_key' ) );
		if ( ! $app_id || ! $app_secret ) {
			return false;
		} // Plugin not yet configured

		// Token is now long-term token
		$token = $this->model->get_user_api_token( $fb_uid );

		// Make sure it is
		$user_token = preg_match( '/^' . preg_quote( "{$app_id}|" ) . '/', $token ) ? false : $token;

		// Just force the token reset, for now
		$token = false;
		if ( ! $token ) {
			// Get temporary token
			$token = $this->model->fb->getAccessToken();

			$user_token = preg_match( '/^' . preg_quote( "{$app_id}|" ) . '/', $token ) ? $user_token : $token;

			if ( ! $token ) {
				return false;
			}

			// Exchange it for the actual long-term token
			$url  = "https://graph.facebook.com/oauth/access_token?client_id={$app_id}&client_secret={$app_secret}&grant_type=fb_exchange_token&fb_exchange_token={$token}&access_token={$user_token}";
			$args = array(
				'method'      => 'GET',
				'timeout'     => '5',
				'redirection' => '5',
				'user-agent'  => 'wdfb',
				'blocking'    => true,
				'compress'    => false,
				'decompress'  => true,
				'sslverify'   => false
			);
			$page = wp_remote_get( $url, $args );
			if ( is_wp_error( $page ) ) {
				return false;
			} // Request fail
			if ( (int) $page['response']['code'] != 200 ) {

				return false;
			} // Request fail

			parse_str( $page['body'], $response );
			$token = isset( $response['access_token'] ) ? $response['access_token'] : false;
			if ( ! $token ) {
				return false;
			}
		}
		if ( ! $this->data->get_option( 'wdfb_api', 'prevent_linked_accounts_access' ) ) {
			$page_tokens = $this->model->get_pages_tokens( $token );
			$page_tokens = isset( $page_tokens['data'] ) ? $page_tokens['data'] : array();
		} else {
			$page_tokens = array();
		}

		$api                             = array();
		$api['auth_tokens'][ $fb_uid ]   = $token;
		$api['auth_accounts'][ $fb_uid ] = sprintf( __( "Me (%s)", 'wdfb' ), $fb_uid );
		foreach ( $page_tokens as $ptk ) {
			$ptk = (array) $ptk;
			if ( ! isset( $ptk['id'] ) || ! isset( $ptk['access_token'] ) ) {
				continue;
			}
			if ( $this->data->get_option( 'wdfb_api', 'prevent_linked_accounts_access' ) ) {
				if ( $ptk['id'] != $app_id ) {
					continue;
				}
			}

			$api['auth_tokens'][ $ptk['id'] ]   = $ptk['access_token'];
			$api['auth_accounts'][ $ptk['id'] ] = $ptk['name'];
		}

		if( !$return ) {
			$user = wp_get_current_user();
			update_user_meta( $user->ID, 'wdfb_api_accounts', $api );
			$this->merge_api_tokens();
		}else{
			return $ptk;
		}

		return true;
	}

	function merge_api_tokens() {
		$user      = wp_get_current_user();
		$api       = $this->data->get_key( 'wdfb_api' );
		$auts_meta = $this->model->get_all_user_tokens();
		$this_guy  = false;
		foreach ( $auts_meta as $meta ) {
			if ( $meta['user_id'] == $user->ID ) {
				$this_guy = $meta;
				continue;
			}
			$data = unserialize( $meta['meta_value'] );
			if ( is_array( $data['auth_tokens'] ) ) {
				foreach ( $data['auth_tokens'] as $fb_uid => $token ) {
					$api['auth_tokens'][ $fb_uid ] = $token;
				}
			}
			if ( is_array( $data['auth_accounts'] ) ) {
				foreach ( $data['auth_accounts'] as $fb_uid => $acc ) {
					$api['auth_accounts'][ $fb_uid ] = $acc;
				}
			}
		}
		// Make sure the current user is processed last - trump other tokens
		$data = unserialize( $this_guy['meta_value'] );
		if ( is_array( $data['auth_tokens'] ) ) {
			foreach ( $data['auth_tokens'] as $fb_uid => $token ) {
				$api['auth_tokens'][ $fb_uid ] = $token;
			}
		}
		if ( is_array( $data['auth_accounts'] ) ) {
			foreach ( $data['auth_accounts'] as $fb_uid => $acc ) {
				$api['auth_accounts'][ $fb_uid ] = $acc;
			}
		}
		$this->data->set_key( 'wdfb_api', $api );
		update_option( 'wdfb_api', $api );
	}

	function add_facebook_publishing_metabox() {
		if ( $this->data->get_option( 'wdfb_autopost', 'prevent_post_metabox' ) ) {
			return false;
		}
		$types = get_post_types( array( 'public' => true ), 'names' );
		foreach ( $types as $type ) {
			if ( 'attachment' == $type ) {
				continue;
			}
			add_meta_box(
				'wdfb_facebook_publishing',
				__( 'Facebook Publishing', 'wdfb' ),
				array( $this, 'render_facebook_publishing_metabox' ),
				$type
			);
		}
	}

	function render_facebook_publishing_metabox() {
		$frm = new Wdfb_AdminFormRenderer;
		echo $frm->facebook_publishing_metabox();
	}

	/**
	 * Default catch-all publishing procedure.
	 */
	function publish_post_on_facebook( $id, $new = false, $old = false ) {
		if ( ! $id ) {
			return false;
		}

		$post_id = $id;
		if ( $rev = wp_is_post_revision( $post_id ) ) {
			$post_id = $rev;
		}

		// Should we even try? ( Autopost is off, and post to facebook is not checked, or if Autopost is on and
		//Skip posting is turned on
		if (
			( ! $this->data->get_option( 'wdfb_autopost', 'allow_autopost' ) && ! @$_POST['wdfb_metabox_publishing_publish'] ) ||

			( ! empty( $_POST['wdfb_metabox_publishing_skip_publish'] ) && $_POST['wdfb_metabox_publishing_skip_publish'] )
		) {
			$stored_publish_test = get_post_meta( $post_id, 'wdfb_scheduled_publish', true ); // Allow scheduled semi-auto publishing
			if ( empty( $stored_publish_test['wdfb_metabox_publishing_publish'] ) ) {
				return false;
			} // Okay, so this has a stored value
		}

		$post = get_post( $post_id );
		if ( 'publish' != $post->post_status ) {
			if ( ( 'future' == $post->post_status || 'draft' == $post->post_status ) &&
			     ( ! empty( $_POST['wdfb_metabox_publishing_publish'] ) || ! empty( $_POST['wdfb_metabox_publishing_title'] ) || ! empty( $_POST['wdfb_metabox_publishing_account'] ) )
			) {
				update_post_meta( $post_id, 'wdfb_scheduled_publish', array(
					'wdfb_metabox_publishing_publish' => $_POST['wdfb_metabox_publishing_publish'],
					'wdfb_metabox_publishing_title'   => $_POST['wdfb_metabox_publishing_title'],
					'wdfb_metabox_publishing_account' => $_POST['wdfb_metabox_publishing_account'],
				) );
			}

			return false; // Draft, auto-save or something else we don't want
		}

		$_POST = wp_parse_args(
			@$_POST,
			get_post_meta( $post_id, 'wdfb_scheduled_publish', true )
		);

		$is_published = get_post_meta( $post_id, 'wdfb_published_on_fb', true );
		if ( $is_published && ! @$_POST['wdfb_metabox_publishing_publish'] ) {
			return true;
		} // Already posted and no manual override, nothing to do
		if ( $old && 'publish' == $old->post_status && ! @$_POST['wdfb_metabox_publishing_publish'] ) {
			return false;
		} // Previously published, we don't want to override

		$post_type  = $post->post_type;
		$post_title = @$_POST['wdfb_metabox_publishing_title'] ? stripslashes( $_POST['wdfb_metabox_publishing_title'] ) : wp_strip_all_tags( html_entity_decode( $post->post_title ) );

		// If publishing semi-auto, always use wall
		$post_as = @$_POST['wdfb_metabox_publishing_publish'] ? 'feed' : $this->data->get_option( 'wdfb_autopost', "type_{$post_type}_fb_type" );
		$post_to = @$_POST['wdfb_metabox_publishing_account'] ? $_POST['wdfb_metabox_publishing_account'] : $this->data->get_option( 'wdfb_autopost', "type_{$post_type}_fb_user" );
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
		$post_content = wdfb_get_excerpt( $post );

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
				$url           = home_url( '/' );
				$find          = array( 'http://', 'https://' );
				$replace       = '';
				$url           = str_ireplace( $find, $replace, $url );
				$send          = array(
					'caption'     => $url,
					'message'     => $post_title,
					'link'        => $permalink,
					'name'        => wp_strip_all_tags( html_entity_decode( $post->post_title ) ),
					'description' => $description
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
			update_post_meta( $post_id, 'wdfb_scheduled_publish', array() );
			do_action( 'wdfb-autopost-posting_successful', $post_id );
		}
		add_filter( 'redirect_post_location', create_function( '$loc', 'return add_query_arg("wdfb_published", ' . (int) $res . ', $loc);' ) );
		do_action( 'wdfb-autopost-posting_complete', $res );
	}

	/**
	 * BuddyPress Activity publishing procedure.
	 */
	function publish_bp_activity( $content, $user_id, $activity_id ) {
		//If post on wall checkbox is checked
		$is_particular = (bool) ( isset( $_POST['wdfb_send_activity'] ) && $_POST['wdfb_send_activity'] );
		if ( ! $this->data->get_option( 'wdfb_autopost', 'type_bp_activity_fb_type' ) ) {
			if ( $this->data->get_option( 'wdfb_autopost', 'prevent_bp_activity_switch' ) ) {
				return false;
			}
			if ( ! $this->data->get_option( 'wdfb_autopost', 'prevent_bp_activity_switch' ) && ! $is_particular ) {
				return false;
			}
		}
		//If checkbox post on wall is checked ignore settings, post to User Facebook wall
		$fb_id = $is_particular
			? $this->model->get_fb_user_from_wp( $user_id )
			: $this->data->get_option( 'wdfb_autopost', 'type_bp_activity_fb_user' );
		//$fb_id = $fb_id ? $fb_id : $this->model->fb->getUser(); // No current user fallback - if not caught by WP mapping, no permissions are likely granted.
		if ( ! $fb_id ) {
			return false;
		}

		$post_as = $is_particular ? "feed" : $this->data->get_option( 'wdfb_autopost', "type_bp_activity_fb_type" );
		$post_to = $fb_id;

		$permalink = bp_activity_get_permalink( $activity_id );

		$send = array(
			'caption'     => substr( $content, 0, 999 ),
			'message'     => substr( $content, 0, 999 ),
			'link'        => $permalink,
			'name'        => __( 'Activity Update', 'wdfb' ),
			'description' => get_option( 'blogdescription' ),
			'actions'     => array(
				'name' => __( 'Share', 'wdfb' ),
				'link' => 'http://www.facebook.com/sharer.php?u=' . rawurlencode( $permalink ),
			),
		);
		$send = apply_filters( 'wdfb-autopost-bp_activity_update', $send, $activity_id, $user_id );
		$send = apply_filters( 'wdfb-autopost-send', $send, $post_as, $post_to );
		// Strip nulled out values
		foreach ( $send as $key => $val ) {
			if ( ! $val ) {
				unset( $send[ $key ] );
			}
		}

		$res = $this->model->post_on_facebook( $post_as, $post_to, $send, false );
		if ( $res ) {
			bp_activity_update_meta( $activity_id, 'wdfb_published_on_fb', 1 );
		}
	}

	function show_post_publish_error() {
		if ( ! isset( $_GET['wdfb_published'] ) ) {
			return false;
		}
		$done  = ( (int) $_GET['wdfb_published'] > 0 ) ? true : false;
		$class = $done ? 'updated' : 'error';
		$msg   = $done ? __( "Post published on Facebook", "wdfb" ) : __( "Publishing on Facebook failed", "wdfb" );
		echo "<div class='{$class}'><p>{$msg}</p></div>";
	}

	function insert_events_into_post_meta( $post ) {
		if ( ! $post['post_content'] ) {
			return $post;
		}

		$post_id = (int) $_POST['post_ID'];
		if ( ! $post_id ) {
			return $post;
		}

		// We need to have active FB session for this, else skip
		$fb_uid = $this->model->fb->getUser();
		if ( ! $fb_uid ) {
			return $post;
		}

		// Process the shortcode
		$txt = stripslashes( $post['post_content'] );
		if ( preg_match( '~\[wdfb_events\s+for\s*=~', $txt ) ) {
			preg_match_all( '~\[wdfb_events\s+for\s*=\s*(.+)\s*]~', $txt, $matches );
			$fors = $matches[1];
			if ( ! empty( $fors ) ) {
				foreach ( $fors as $for ) {
					$for    = trim( $for, '\'" ' );
					$events = $this->model->get_events_for( $for );
					if ( ! is_array( $events ) || empty( $events['data'] ) ) {
						continue;
					} // No events, skip to next
					update_post_meta( $post_id, 'wdfb_events', $events['data'] );
				}
			}
		}

		return $post;
	}

	function add_published_status_column( $cols ) {
		$cols['ufb_published'] = __( 'On Facebook', 'wdfb' );

		return $cols;
	}

	function update_published_status_column( $col_name, $post_id ) {
		if ( 'ufb_published' != $col_name ) {
			return false;
		}
		$meta = get_post_meta( $post_id, 'wdfb_published_on_fb', true );
		echo $meta ? __( 'Yes', 'wdfb' ) : __( 'No', 'wdfb' );
	}

	function json_list_fb_albums() {
		$privacy = ! empty( $_POST['privacy'] ) ? $_POST['privacy'] : '';
		$albums  = $this->model->get_current_albums( $privacy );
		//Get Album Details
		$albums        = ! empty( $albums['data'] ) ? $albums['data'] : '';
		$album_details = array();
		if ( ! empty( $albums ) ) {
			foreach ( $albums as $album ) {
				//Get album data
				$album_data = $this->model->get_album_details( $album['id'] );
				if ( $album_data ) {
					$album_details[ $album['id'] ] = $album_data;
				}
			}
		}
		$status = $albums ? 1 : 0;
		header( 'Content-type: application/json' );
		echo json_encode( array(
			'status' => $status,
			'albums' => $album_details,
		) );
		exit();
	}

	function json_list_fb_album_photos() {
		$album_id = $_POST['album_id'];
		$photos   = $this->model->get_album_photos( $album_id );
		$status   = $photos ? 1 : 0;
		header( 'Content-type: application/json' );
		echo json_encode( array(
			'status' => $status,
			'photos' => $photos,
		) );
		exit();
	}

	function json_import_comments() {
		Wdfb_CommentsImporter::serve();
		echo json_encode( array(
			'status' => 1,
		) );
		exit();
	}

	function json_populate_profile() {
		$user = wp_get_current_user();
		if ( defined( 'BP_VERSION' ) && class_exists('BuddyPress')) {
			$status = $this->model->populate_bp_fields_from_fb( $user->ID );
		} else {
			$status = $this->model->populate_wp_fields_from_fb( $user->ID );
		}
		echo json_encode( array(
			'status' => (int) $status,
		) );
		exit();
	}

	function json_perhaps_create_wp_user() {
		$user = wp_get_current_user();
		if ( $user->ID ) {
			wp_send_json_error( "User already logged in" );
			die();
		}

		$fb_user = $this->model->fb->getUser();
		if ( $fb_user ) {
			$user_id = $this->model->get_wp_user_from_fb();
			if ( $user_id ) {
				do_action( 'wdfb-login-facebook_login', $user_id, $fb_user );
			}

			if ( ! $user_id ) {
				$user_id = $this->model->map_fb_to_current_wp_user();
			}
			if ( $user_id ) {
				do_action( 'wdfb-login-facebook_mapping', $user_id, $fb_user );
			}

			if ( ! $user_id && $this->data->get_option( 'wdfb_connect', 'easy_facebook_registration' ) ) {
				$user_id = $this->model->register_fb_user();
				// Record activities, if told so
				if ( $user_id && defined( 'BP_VERSION' ) && $this->data->get_option( 'wdfb_connect', 'update_feed_on_easy_registration' ) ) {
					if ( function_exists( 'bp_core_new_user_activity' ) ) {
						bp_core_new_user_activity( $user_id );
					}
				}
				if ( $user_id ) {
					do_action( 'wdfb-registration-facebook_registration', $user_id );
					do_action( 'wdfb-registration-facebook_easy_registration', $user_id );
				}
			}
			$this->handle_fb_session_state();
			if ( empty( $user_id ) ) {
				$message = apply_filters( 'wdfb_warn_disabled_registration', __( "User registration is currently not allowed.", 'wdfb' ) );
				wp_send_json_error( $message );
			} else {
				wp_send_json_success( "Logging in" );
			}
		}
		exit();
	}

	function json_refresh_access_token() {
		$this->data->set_option( 'wdfb_api', 'auth_tokens', '' );
		$this->handle_fb_auth_tokens();
		die;
	}

	function json_remap_user() {
		$this->model->map_fb_to_current_wp_user();
		die;
	}

	function json_check_api_status() {
		$form = new Wdfb_AdminFormRenderer;
		header( "Content-type: application/json" );

		$wdfb_api = ! empty( $_POST['network'] ) && $_POST['network'] == 'true' ? get_site_option( 'wdfb_api' ) : get_option( 'wdfb_api' );
		$app_key  = ( ! empty( $wdfb_api ) && ! empty( $wdfb_api['app_key'] ) ) ? $wdfb_api['app_key'] : '';

		//get app secret
		$secret_key = ( ! empty( $wdfb_api ) && ! empty( $wdfb_api['secret_key'] ) ) ? $wdfb_api['secret_key'] : '';

		$app_key    = ! empty( $app_key ) ? $app_key : trim( $this->data->get_option( 'wdfb_api', 'app_key' ) );
		$secret_key = ! empty( $secret_key ) ? $secret_key : trim( $this->data->get_option( 'wdfb_api', 'secret_key' ) );

		$resp = wp_remote_get( "https://graph.facebook.com/{$app_key}?fields=name&access_token={$app_key}|{$secret_key}", array(
			'sslverify' => false,
			'timeout'   => 120, // Allow for extra long timeout here. Props @Dharmendra Vekariya
		) );
		if ( is_wp_error( $resp ) ) {
			wp_send_json_error();
		} // Request fail
		else if ( (int) $resp['response']['code'] != 200 ) {
			wp_send_json_error();
		} // Request fail
		else {
			$resp = wp_remote_retrieve_body( $resp );
			$resp = json_decode( $resp );
			wp_send_json_success( $resp->name );
		}
		die();

	}

	function json_get_facebook_groups_map() {
		$groups = $this->model->get_current_user_groups();
		if ( ! $groups ) {
			die;
		}

		$str = '<select id="wdfb-fb_groups_selection-selection">';
		foreach ( $groups as $group ) {
			$str .= '<option value="' . esc_attr( $group['id'] ) . '">' . $group['name'] . '</option>';
		}
		$str .= '</select>';

		$str .= '&nbsp;<input type="button" id="wdfb-fb_groups_selection-map" value="' . esc_attr( __( 'Sync', 'wdfb' ) ) . '" />';
		$str .= '&nbsp;<input type="button" id="wdfb-fb_groups_selection-map_cancel" value="' . esc_attr( __( 'Cancel', 'wdfb' ) ) . '" />';
		$str .= '&nbsp;';
		die( '<div id="wdfb-fb_groups_selection">' . $str . '</div>' );
	}

	function json_map_facebook_group() {
		$bp_group = ! empty( $_POST['bp_group'] ) ? $_POST['bp_group'] : false;
		$fb_group = ! empty( $_POST['fb_group'] ) ? $_POST['fb_group'] : false;
		$token    = ! empty( $_POST['token'] ) ? $_POST['token'] : false;
		if ( ! $bp_group || ! $fb_group || ! $token ) {
			die;
		}

		$result = $this->model->map_bp_group_info( $bp_group, $fb_group, $token );
		die( json_encode( array(
			"status" => $result,
		) ) );
	}

	function json_fbc_notify_author() {
		$data = stripslashes_deep( $_POST );
		if ( empty( $data['post_id'] ) || empty( $data['hash'] ) || empty( $data['cid'] ) ) {
			die;
		}
		if ( ! is_numeric( $data['post_id'] ) ) {
			die;
		}

		$post = get_post( $data['post_id'] );
		if ( empty( $post->ID ) ) {
			die;
		}

		// Level 1 check - post hash comparison
		$link = get_permalink( $post->ID );
		if ( $data['hash'] != wp_hash( $link ) ) {
			die;
		}

		// Level 2 check - comment ID comparison
		$resp = $this->model->get( '/' . $data['cid'] );
		if ( empty( $resp['id'] ) || empty( $resp['message'] ) ) {
			die;
		}
		if ( $resp['id'] != $data['cid'] ) {
			die;
		}

		if ( function_exists( 'wdfb_notify_post_author' ) ) {
			wdfb_notify_post_author( $post, $resp );
		}

		die;
	}

	function json_cache_purge() {
		$purge = ! empty( $_POST['purge'] ) ? trim( strtolower( $_POST['purge'] ) ) : false;
		if ( ! $purge ) {
			die;
		}
		if ( ! in_array( $purge, array( 'events', 'album_photos' ) ) ) {
			die;
		}

		global $wpdb;
		$sql = "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient%wdfb%{$purge}%'";
		$wpdb->query( $sql );
		die;
	}

	function json_partial_data_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			die;
		}
		$key      = @$_POST['part'];
		$old_data = get_option( $key, false );
		$old_data = is_array( $old_data ) ? $old_data : array();

		$data = array();
		parse_str( $_POST['data'], $data );

		$new_data = array_merge( $old_data, $data[ $key ] );
		update_option( $key, $new_data );
		die;
	}

	function json_network_partial_data_save() {
		if ( ! current_user_can( 'manage_network_options' ) ) {
			die;
		}
		$key      = @$_POST['part'];
		$old_data = get_site_option( $key, false );
		$old_data = is_array( $old_data ) ? $old_data : array();

		$data     = $keys = array();
		$override = $preserve_api = false;
		parse_str( $_POST['data'], $data );
		if ( 'wdfb_network' == $key ) {
			$keys = Wdfb_Installer::get_keys();
			unset( $keys['widget_pack'] );
			$override     = (int) @$data['wdfb_network']['_override_all'];
			$preserve_api = (int) @$data['wdfb_network']['_preserve_api'];
		}

		$new_data = array_merge( $old_data, $data[ $key ] );
		update_site_option( $key, $new_data );

		if ( $keys && $override ) {
			$page  = ! empty( $data['page'] ) ? (int) $data['page'] : 0;
			$blogs = $this->model->get_paged_blog_ids( $page ); // Get this list only once

			if ( empty( $blogs ) ) {
				wp_send_json_success( 'success' );
			} // We're done with paging
			foreach ( $keys as $key ) {
				if ( 'api' == $key && $preserve_api ) {
					continue;
				} // Preserve API
				$site_opt = get_site_option( "wdfb_{$key}" );

				//Update all the details for primary blog too
				update_blog_option( 1, "wdfb_{$key}", $site_opt );

				foreach ( $blogs as $blog ) {
					update_blog_option( $blog['blog_id'], "wdfb_{$key}", $site_opt );
				}
			}
			die( json_encode( array( "page" => $page + 1 ) ) ); // Paged resource, respond with next page
		}

		wp_send_json_success( 'success' );
	}

	function inject_identity_reset_link() {
		$allowed = $this->data->get_option( 'wdfb_connect', 'allow_identity_renewal' );
		if ( ! $allowed ) {
			return false;
		} // Not allowed, leave
		if ( ! apply_filters( 'wdfb-identity_renewal-limit_scope', $allowed ) ) {
			return false;
		} // For limiting scope to e.g. user roles

		if ( isset( $_GET['wdfb_reset'] ) ) {
			$this->model->map_fb_to_current_wp_user();
		}
		if ( $this->model->fb->getUser() ) {
			$mapped = $this->model->get_current_wp_user_data();
			$data   = $this->model->get_current_fb_user_data();
			if ( $mapped && isset( $mapped['name'] ) ) {
				echo '<p><i>' . sprintf( __( "Your currently mapped Facebook identity is %s", 'wdfb' ), '<b>' . $mapped['name'] . '</b>' ) . '</i></p>';
			}
			if ( $data && isset( $data['id'] ) && isset( $data['name'] ) && @$mapped['id'] != @$data['id'] ) {
				echo '<p><a href="' . esc_url( add_query_arg( 'wdfb_reset', 1 ) ) . '">' . sprintf( __( 'Renew my Facebook identity mapping to %s', 'wdfb' ), @$data['name'] ) . '</a></p>';
			}
		} else {
			echo '<p><em>' . sprintf( __( 'You need to be logged into Facebook to see and renew your mapped identity details. <a href="%s">Click here to log in</a>' ), $this->model->fb->getLoginUrl() ) . '</em></p>';
		}
	}

	function inject_profile_update_link() {
		$profile = apply_filters( 'wdfb-profile_name', '<em>' . get_bloginfo( 'name' ) . '</em>' ); //defined('BP_VERSION') ? "BuddyPress" : "WordPress";
		echo '<p><a href="#" class="wdfb_fill_profile">' . sprintf( __( 'Fill my %s profile with Facebook data', 'wdfb' ), $profile ) . '</a></p>';
		echo '<script type="text/javascript">(function ($) { $(function () { $(".wdfb_fill_profile").click(function () { var $me = $(this); var oldHtml = $me.html(); try {var url = _wdfb_ajaxurl;} catch (e) { var url = ajaxurl; } $me.html("Please, wait... <img src=\"' . WDFB_PLUGIN_URL . '/img/waiting.gif\">"); $.post(url, {"action": "wdfb_populate_profile"}, function (data) { window.location.reload(); }); return false; }); }); })(jQuery);</script>';
	}

	function inject_settings_link( $links ) {
		$settings = '<a href="' .
		            ( is_network_admin() ? network_admin_url( 'admin.php?page=wdfb' ) : admin_url( 'admin.php?page=wdfb' ) ) .
		            '">' . __( 'Settings', 'wdfb' ) .
		            '</a>';
		array_unshift( $links, $settings );

		return $links;
	}

	function allow_default_avatars_selection( $defaults ) {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return $defaults;
		}

		$screen = get_current_screen();
		if ( ! isset( $screen->id ) || 'options-discussion' != $screen->id ) {
			return $defaults;
		}

		remove_filter( 'get_avatar', array( $this, 'get_fb_avatar' ), 10, 3 );

		return $defaults;
	}

	/**
	 * Hooks to appropriate places and adds stuff as needed.
	 *
	 * @access private
	 */
	function add_hooks() {
		// Step0: Register options and menu
		if ( defined( 'WP_NETWORK_ADMIN' ) && WP_NETWORK_ADMIN ) {
			add_action( 'admin_init', array( $this, 'register_site_settings' ) );
			add_action( 'network_admin_menu', array( $this, 'create_site_admin_menu_entry' ) );

			add_filter( 'plugin_action_links_' . WDFB_PLUGIN_CORE_BASENAME, array( $this, 'inject_settings_link' ) );
			add_filter( 'network_admin_plugin_action_links_' . WDFB_PLUGIN_CORE_BASENAME, array(
				$this,
				'inject_settings_link'
			) );
		} else {
			$opt = get_site_option( 'wdfb_network', array() );
			if ( ! @$opt['prevent_blog_settings'] ) {
				add_filter( 'plugin_action_links_' . WDFB_PLUGIN_CORE_BASENAME, array(
					$this,
					'inject_settings_link'
				) );
				add_action( 'admin_init', array( $this, 'register_blog_settings' ) );
				add_action( 'admin_menu', array( $this, 'create_blog_admin_menu_entry' ) );
			}
		}

		// Step1a: Add plugin script core requirements and editor interface
		add_action( 'admin_print_scripts', array( $this, 'js_plugin_url' ) );

		add_action( 'admin_print_scripts', array( $this, 'js_load_scripts' ) );
		add_action( 'admin_print_styles', array( $this, 'css_load_styles' ) );

		add_action( 'admin_print_scripts-post.php', array( $this, 'js_editors' ) );
		add_action( 'admin_print_scripts-post-new.php', array( $this, 'js_editors' ) );

		add_action( 'admin_footer', array( $this, 'inject_fb_root_div' ) );
		add_action( 'admin_footer', array( $this, 'inject_fb_init_js' ) );

		// Show notices.
		add_action( 'admin_notices', array( $this, 'notice_api_setup' ) );

		// Step2: Add AJAX request handlers
		add_action( 'wp_ajax_wdfb_list_fb_albums', array( $this, 'json_list_fb_albums' ) );
		add_action( 'wp_ajax_wdfb_list_fb_album_photos', array( $this, 'json_list_fb_album_photos' ) );
		add_action( 'wp_ajax_wdfb_import_comments', array( $this, 'json_import_comments' ) );
		add_action( 'wp_ajax_wdfb_populate_profile', array( $this, 'json_populate_profile' ) );

		add_action( 'wp_ajax_wdfb_get_facebook_groups_map', array( $this, 'json_get_facebook_groups_map' ) );
		add_action( 'wp_ajax_wdfb_map_facebook_group', array( $this, 'json_map_facebook_group' ) );

		add_action( 'wp_ajax_wdfb_check_api_status', array( $this, 'json_check_api_status' ) );
		add_action( 'wp_ajax_wdfb_refresh_access_token', array( $this, 'json_refresh_access_token' ) );
		add_action( 'wp_ajax_wdfb_remap_user', array( $this, 'json_remap_user' ) );
		add_action( 'wp_ajax_wdfb_cache_purge', array( $this, 'json_cache_purge' ) );

		add_action( 'wp_ajax_wdfb_partial_data_save', array( $this, 'json_partial_data_save' ) );
		add_action( 'wp_ajax_wdfb_network_partial_data_save', array( $this, 'json_network_partial_data_save' ) );

		add_action( 'wp_ajax_wdfb_notify_author', array( $this, 'json_fbc_notify_author' ) );
		add_action( 'wp_ajax_nopriv_wdfb_notify_author', array( $this, 'json_fbc_notify_author' ) );

		// Step 3: Process conditional features:

		// Connect
		if ( $this->data->get_option( 'wdfb_connect', 'allow_facebook_registration' ) ) {
			// Add admin gravatars support
			if ( ! $this->data->get_option( 'wdfb_connect', 'skip_fb_avatars' ) ) {
				add_filter( 'get_avatar', array( $this, 'get_fb_avatar' ), 10, 3 );
			}
			// ... but allow avatars selection in the admin > Settings > Discussion
			add_filter( 'avatar_defaults', array( $this, 'allow_default_avatars_selection' ) );
			// Single-click registration enabled
			add_action( 'wp_ajax_wdfb_perhaps_create_wp_user', array( $this, 'json_perhaps_create_wp_user' ) );
			add_action( 'wp_ajax_nopriv_wdfb_perhaps_create_wp_user', array( $this, 'json_perhaps_create_wp_user' ) );

			// Allow users to map their user profile fields from profile page
			if ( ! defined( 'BP_VERSION' ) ) {
				add_action( 'profile_personal_options', array( $this, 'inject_profile_update_link' ) );
			}
		}
		// Identity renewal
		if ( $this->data->get_option( 'wdfb_connect', 'allow_identity_renewal' ) ) {
			add_action( 'profile_personal_options', array( $this, 'inject_identity_reset_link' ) );
		}

		// Autopost
		if ( $this->data->get_option( 'wdfb_autopost', 'allow_autopost' ) ) {
			// Personal status updates only
			if ( defined( 'BP_VERSION' ) ) {
				add_action( 'bp_activity_posted_update', array( $this, 'publish_bp_activity' ), 10, 3 );
			}
		}
		// Post columns
		if ( $this->data->get_option( 'wdfb_autopost', 'show_status_column' ) ) {
			add_filter( 'manage_posts_columns', array( $this, 'add_published_status_column' ) );
			add_filter( 'manage_posts_custom_column', array( $this, 'update_published_status_column' ), 10, 2 );
			add_filter( 'manage_pages_columns', array( $this, 'add_published_status_column' ) );
			add_filter( 'manage_pages_custom_column', array( $this, 'update_published_status_column' ), 10, 2 );
		}

		// Post metabox
		add_action( 'add_meta_boxes', array( $this, 'add_facebook_publishing_metabox' ) );
		if ( ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) ||
		     ( defined( 'DOING_CRON' ) && DOING_CRON ) ||
		     ( defined( 'DOING_AJAX' ) && DOING_AJAX )
		) {
			add_action( 'save_post', array( $this, 'publish_post_on_facebook' ) );
		} else {
			add_action( 'post_updated', array( $this, 'publish_post_on_facebook' ), 999, 3 );
		}
		add_action( 'admin_notices', array( $this, 'show_post_publish_error' ) );

		// Internal actions definitions
		add_action( 'wdfb-api-handle_fb_auth_tokens', array( $this, 'handle_fb_auth_tokens' ) );

		// Register the shortcodes, so Membership picks them up
		$rpl = new Wdfb_MarkerReplacer;
		$rpl->register();

		// Allow unhooking actions and post-init behavior.
		do_action( 'wdfb-core-hooks_added-admin', $this );
	}
}