<?php
/**
 * Handles shortcodes.
 */
class Wdfb_MarkerReplacer {

	var $data;
	var $model;
	var $buttons = array (
		'like_button' => 'wdfb_like_button',
		'events' => 'wdfb_events',
		'connect' => 'wdfb_connect'
	);

	function __construct () {
		$this->model = new Wdfb_Model;
		$this->data =& Wdfb_OptionsRegistry::get_instance();
	}

	function Wdfb_MarkerReplacer () {
		$this->__construct();
	}

	function get_button_tag ($b) {
		if (!isset($this->buttons[$b])) return '';
		return '[' . $this->buttons[$b] . ']';
	}

	function process_connect_code ($atts, $content='') {
		if (!$this->data->get_option('wdfb_connect', 'allow_facebook_registration')) return $content;
		$content = $content ? $content : __('Log in with Facebook', 'wdfb');
		if (!class_exists('Wdfb_WidgetConnect')) {
			echo '<script type="text/javascript" src="' . WDFB_PLUGIN_URL . '/js/wdfb_facebook_login.js"></script>';
		}
		$user = wp_get_current_user();
		$html = '';
		if (!$user->ID) {
			$html = '<p class="wdfb_login_button"><fb:login-button perms="' . Wdfb_Permissions::get_permissions() . '" redirect-url="' . wdfb_get_login_redirect() . '">' . $content . '</fb:login-button></p>';
		} else {
			$logout = site_url('wp-login.php?action=logout&redirect_to=' . rawurlencode(home_url()));
			$html .= get_avatar($user->ID, 32);
			$html .= "<br /><a href='{$logout}'>" . __('Log out', 'wdfb') . "</a>";
		}
		return $html;
	}

	function process_like_button_code ($atts, $content='') {
		global $wp_current_filter;

		// Check allowed
		$allow = $this->data->get_option('wdfb_button', 'allow_facebook_button');
		if (!apply_filters('wdfb-show_facebook_button', $allow)) return '';

		// Check nesting (i.e. posts within post, reformatted with apply_filters)
		$filters = array_count_values($wp_current_filter);
		if ($filters['the_content'] > 1) return '';

		$atts = shortcode_atts(array(
			'forced' => false,
		), $atts);
		$forced = ($atts['forced'] && 'no' != $atts['forced']) ? true : false;

		$in_types = $this->data->get_option('wdfb_button', 'not_in_post_types');
		if (@in_array(get_post_type(), $in_types) && !$forced) return '';

		$send = $this->data->get_option('wdfb_button', 'show_send_button');
		$layout = $this->data->get_option('wdfb_button', 'button_appearance');
		$url = $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

		if (is_home() && $this->data->get_option('wdfb_button', 'show_on_front_page')) {
			$tmp_url = get_permalink();
			$url = $tmp_url ? $tmp_url : $url;
			$url = rawurlencode($url);
			$height = ("box_count" == $layout) ? 60 : 25;
			return "<div class='wdfb_like_button'><iframe src='http://www.facebook.com/plugins/like.php?&amp;href={$url}&amp;send=false&amp;layout={$layout}&amp;width=450&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font&amp;height={$height}' scrolling='no' frameborder='0' style='border:none; overflow:hidden; width:450px; height:{$height}px;' allowTransparency='true'></iframe></div>";
		}

		return '<div class="wdfb_like_button"><fb:like href="http://' . $url . '" send="' . ($send ? 'true' : 'false') . '" layout="' . $layout . '" width="450" show_faces="true" font=""></fb:like></div>';
	}

	function process_events_code ($atts, $content='') {
		$post_id = get_the_ID();
		if (!$post_id) return '';

		$atts = shortcode_atts(array(
			'for' => false,
			'starting_from' => false,
			'only_future' => false,
			'show_image' => "true",
			'show_location' => "true",
			'show_start_date' => "true",
			'show_end_date' => "true",
			'order' => false,
		), $atts);

		if (!$atts['for']) return ''; // We don't know whose events to show

		// Attempt to fetch the freshest events
		// Update cache if we can
		$new_events = $this->model->get_events_for($atts['for']);
		if(!empty($new_events['data'])) {
			$events = $new_events['data'];
			update_post_meta($post_id, 'wdfb_events', $events);
		} else {
			$events = get_post_meta($post_id, 'wdfb_events');
			$events = $events[0];
		}

		if (!is_array($events)) return $content;

		if ($atts['order']) {
			$events = $this->_sort_by_time($events, $atts['order']);
		}

		$show_image = ("true" == $atts['show_image']) ? true : false;
		$show_location = ("true" == $atts['show_location']) ? true : false;
		$show_start_date = ("true" == $atts['show_start_date']) ? true : false;
		$show_end_date = ("true" == $atts['show_end_date']) ? true : false;
		$timestamp_format = get_option('date_format') . ' ' . get_option('time_format');

		$date_threshold = $atts['starting_from'] ? strtotime($atts['starting_from']) : false;
		if ($atts['only_future'] && 'false' != $atts['only_future']) {
			$now = time();
			$date_threshold = ($date_threshold && $date_threshold > $now) ? $date_threshold : $now;
		}

		ob_start();
		foreach ($events as $event) {
			if ($date_threshold > strtotime($event['start_time'])) continue;
			include (WDFB_PLUGIN_BASE_DIR . '/lib/forms/event_item.php');
		}
		$ret = ob_get_contents();
		ob_end_clean();

		return "<div><ul>{$ret}</ul></div>";
	}

	/**
	 * Helper for sorting events by their start_time.
	 */
	function _sort_by_time ($events, $direction="ASC") {
		usort($events, create_function(
			'$a,$b',
			'if (strtotime($a["start_time"]) == strtotime($b["start_time"])) return 0;' .
			'return (strtotime($a["start_time"]) > strtotime($b["start_time"])) ? 1 : -1;'
		));
		return ("DESC" == $direction) ? array_reverse($events) : $events;
	}

	/**
	 * Registers shortcode handlers.
	 */
	function register () {
		foreach ($this->buttons as $key=>$shortcode) {
			//var_export("process_{$key}_code");
			add_shortcode($shortcode, array($this, "process_{$key}_code"));
		}
	}
}