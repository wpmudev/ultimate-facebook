<?php
/**
 * Shows Facebook Connect box.
 */
class Wdfb_WidgetConnect extends WP_Widget {

	function Wdfb_WidgetConnect () {
		$widget_ops = array('classname' => __CLASS__, 'description' => __('Shows Facebook Connect box.', 'wdfb'));

		add_action('wp_print_styles', array($this, 'css_load_styles'));
		add_action('wp_print_scripts', array($this, 'js_load_scripts'));

		parent::__construct(__CLASS__, 'Facebook Connect', $widget_ops);
	}

	function css_load_styles () {
		if (!is_admin()) wp_enqueue_style('wdfb_connect_widget_style', WDFB_PLUGIN_URL . '/css/wdfb_connect_widget.css', '', WDFB_PLUGIN_VERSION);
	}
	function js_load_scripts () {
		if (!is_admin()) wp_enqueue_script('wdfb_connect_widget', WDFB_PLUGIN_URL . '/js/wdfb_connect_widget.js', '', WDFB_PLUGIN_VERSION);
		if (!is_admin()) wp_enqueue_script('wdfb_facebook_login', WDFB_PLUGIN_URL . '/js/wdfb_facebook_login.js', '', WDFB_PLUGIN_VERSION );
	}

	function form($instance) {
		$title       = ! empty( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$width       = ! empty( $instance['width'] ) ? esc_attr( $instance['width'] ) : '';
		$avatar_size = ! empty( $instance['avatar_size'] ) ? esc_attr( $instance['avatar_size'] ) : '';
		$register    = ! empty( $instance['register'] ) ? esc_attr( $instance['register'] ) : '';

		// Set defaults
		// ...

		$html = '<p>';
		$html .= '<label for="' . $this->get_field_id('title') . '">' . __('Title:', 'wdfb') . '</label>';
		$html .= '<input type="text" name="' . $this->get_field_name('title') . '" id="' . $this->get_field_id('title') . '" class="widefat" value="' . $title . '"/>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('width') . '">' . __('Width:', 'wdfb') . '</label>';
		$html .= '<input type="text" name="' . $this->get_field_name('width') . '" id="' . $this->get_field_id('width') . '" size="3" value="' . $width . '"/>px';
		$html .= __('<p><small>For registration, the recommended width is greater then 240px</small></p>', 'wdfb');
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('avatar_size') . '">' . __('Avatar size:', 'wdfb') . '</label>';
		$html .= '<input type="text" name="' . $this->get_field_name('avatar_size') . '" id="' . $this->get_field_id('avatar_size') . '" size="3" value="' . $avatar_size . '"/>px';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('register') . '">' . __('Show register:', 'wdfb') . '</label>';
		$html .= '<input type="checkbox" name="' . $this->get_field_name('register') . '" id="' . $this->get_field_id('register') . '" value="1" ' . ($register ? 'checked="checked"' : '') . ' />';
		$html .= '</p>';

		echo $html;
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['width'] = strip_tags($new_instance['width']);
		$instance['avatar_size'] = strip_tags($new_instance['avatar_size']);
		$instance['register'] = strip_tags($new_instance['register']);

		return $instance;
	}

	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', $instance['title']);
		$register = (int)@$instance['register'];
		$width = $instance['width'];
		$width = $width ? $width : 250;
		$avatar_size = $instance['avatar_size'];
		$avatar_size = $avatar_size ? $avatar_size : 32;

		$opts = Wdfb_OptionsRegistry::get_instance();

		if ($opts->get_option('wdfb_connect', 'allow_facebook_registration')) {
			echo $before_widget;
			if ($title) echo $before_title . $title . $after_title;

			$user = wp_get_current_user();

			if ( ! $user->ID ) {
				echo '<p class="wdfb_login_button">' .
				     wdfb_get_fb_plugin_markup( 'login-button', array(
					     'scope'        => Wdfb_Permissions::get_permissions(),
					     'redirect-url' => wdfb_get_login_redirect(),
					     'content'      => __( "Login with Facebook", 'wdfb' ),
				     ) ) .
				     '</p>';
			} else {
				//$logout = site_url('wp-login.php?action=logout&redirect_to=' . rawurlencode(home_url()));
				$logout = wp_logout_url( home_url() ); // Props jmoore2026
				echo get_avatar( $user->ID, $avatar_size );
				echo "<p><a href='{$logout}'>" . __( 'Log out', 'wdfb' ) . "</a></p>";
			}

			echo $after_widget;
		}
	}
}