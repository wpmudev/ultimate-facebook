<?php

class Wdfb_UniversalWorker {

	private $_data;
	private $_model;
	private $_replacer;

	private function __construct () {
		$this->_data = Wdfb_OptionsRegistry::get_instance();
		$this->_model = new Wdfb_Model;
		$this->_replacer = new Wdfb_MarkerReplacer;
	}

	public static function serve () {
		$me = new Wdfb_UniversalWorker;
		$me->_add_hooks();
	}

	private function _add_hooks () {
		// Automatic Facebook button
		if ('manual' != $this->_data->get_option('wdfb_button', 'button_position')) {
			add_filter('the_content', array($this, 'inject_facebook_button'), 10);
			if (defined('BP_VERSION')) add_filter('bp_get_activity_content_body', array($this, 'inject_facebook_button_bp'));
		}
		if ($this->_data->get_option('wdfb_connect', 'login_redirect_url')) {
			add_action('init', array($this, 'post_login_url_expansion'));
		}
		if ($this->_data->get_option('wdfb_grant', 'use_minimal_permissions')) {
			add_action('init', array($this, 'setup_minimal_permission_set'));
		}
	}

	/**
	 * Set up the required permission set to a minimum,
	 * unless otherwise already set up
	 */
	function setup_minimal_permission_set () {
		if (!defined('WDFB_CORE_MINIMAL_PERMISSIONS_SET')) define('WDFB_CORE_MINIMAL_PERMISSIONS_SET', true, true);
	}

	/**
	 * Expand user macros after the user has been logged in
	 * and returns from Facebook.
	 */
	function post_login_url_expansion () {
		if (empty($_GET['wdfb_expand'])) return false;
		if (preg_match('/\b(USER_ID|USER_LOGIN)\b/', $_SERVER['REQUEST_URI'])) {
			wp_safe_redirect(wdfb_get_login_redirect());
			die;
		}
	}

	/**
	 * Inject Facebook button into post content.
	 * This is triggered only for automatic injection.
	 * Adds shortcode in proper place, and lets replacer do its job later on.
	 */
	function inject_facebook_button ($body) {
		if (!is_singular()) {
			if (
				!(is_home() && $this->_data->get_option('wdfb_button', 'show_on_front_page'))
				&&
				!(is_archive() && $this->_data->get_option('wdfb_button', 'show_on_archive_page'))
			) return $body;
		}

		$position = $this->_data->get_option('wdfb_button', 'button_position');
		if ('top' == $position || 'both' == $position) {
			$body = $this->_replacer->get_button_tag('like_button') . " " . $body;
		}
		if ('bottom' == $position || 'both' == $position) {
			$body .= " " . $this->_replacer->get_button_tag('like_button');
		}
		return $body;
	}

	/**
	 * Activities don't use the_content filter, and doesn't understand shortcodes.
	 * Make sure we're ready.
	 */
	function inject_facebook_button_bp ($body) {
		// Disregard position
		// ...
		$body .= " " . do_shortcode($this->_replacer->get_button_tag('like_button'));
		if ($this->_data->get_option('wdfb_button', 'bp_activity_xfbml') && !defined('WDFB_FLAG_XFBML_REPARSING_QUEUED')) {
			add_action('wp_footer', array($this, 'inject_xfbml_reparsing_on_load'));
			define('WDFB_FLAG_XFBML_REPARSING_QUEUED', true, true);
		}
		return $body;
	}

	function inject_xfbml_reparsing_on_load () {
		if (defined('WDFB_FLAG_XFBML_REPARSING_INJECTED')) return false;
		echo '<script type="text/javascript">jQuery.ajaxSetup({"complete": function () { if ("undefined" == typeof FB) return false; FB.XFBML.parse(); }});</script>';
		define('WDFB_FLAG_XFBML_REPARSING_INJECTED', true, true);
	}
}