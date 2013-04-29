<?php

class Wdfb_UniversalWorker {

	private $_data;
	private $_model;
	private $_replacer;

	private function __construct () {
		$this->_data =& Wdfb_OptionsRegistry::get_instance();
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
	}

	/**
	 * Inject Facebook button into post content.
	 * This is triggered only for automatic injection.
	 * Adds shortcode in proper place, and lets replacer do its job later on.
	 */
	function inject_facebook_button ($body) {
		if (
			(is_home() && !$this->_data->get_option('wdfb_button', 'show_on_front_page'))
			||
			(!is_home() && !is_singular())
		) return $body;

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