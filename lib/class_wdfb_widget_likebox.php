<?php
/**
 * Shows Facebook Recommendations box.
 * See http://developers.facebook.com/docs/reference/plugins/recommendations/
 */
class Wdfb_WidgetLikebox extends WP_Widget {

	function Wdfb_WidgetLikebox () {
		$widget_ops = array('classname' => __CLASS__, 'description' => __('Shows Facebook Like box.', 'wdfb'));
		parent::WP_Widget(__CLASS__, 'Facebook Like Box', $widget_ops);
	}

	function form($instance) {
		$title                 = ! empty( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$url                   = ! empty( $instance['url'] ) ? esc_attr( $instance['url'] ) : '';
		$width                 = ! empty( $instance['width'] ) ? esc_attr( $instance['width'] ) : '';
		$height                = ! empty( $instance['height'] ) ? esc_attr( $instance['height'] ) : '';
		$show_timeline         = ! empty( $instance['show_timeline'] ) ? esc_attr( $instance['show_timeline'] ) : '';
		$show_events           = ! empty( $instance['show_events'] ) ? esc_attr( $instance['show_events'] ) : '';
		$show_messages         = ! empty( $instance['show_messages'] ) ? esc_attr( $instance['show_messages'] ) : '';
		$hide_cover            = ! empty( $instance['hide_cover'] ) ? esc_attr( $instance['hide_cover'] ) : '';
		$show_faces            = ! empty( $instance['show_faces'] ) ? esc_attr( $instance['show_faces'] ) : '';
		$small_header          = ! empty( $instance['small_header'] ) ? esc_attr( $instance['small_header'] ) : '';
		$adapt_container_width = ! empty( $instance['adapt_container_width'] ) ? esc_attr( $instance['adapt_container_width'] ) : '';
		$hide_if_logged_out    = ! empty( $instance['hide_if_logged_out'] ) ? esc_attr($instance['hide_if_logged_out']) : '';

		// Set defaults
		// ...

		$html = '<p>';
		$html .= '<label for="' . $this->get_field_id('title') . '">' . __('Title:', 'wdfb') . '</label> ';
		$html .= '<input type="text" name="' . $this->get_field_name('title') . '" id="' . $this->get_field_id('title') . '" class="widefat" value="' . $title . '"/>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('url') . '">' . __('Facebook page URL:', 'wdfb') . '</label> ';
		$html .= '<input type="text" name="' . $this->get_field_name('url') . '" id="' . $this->get_field_id('url') . '" class="widefat" value="' . $url . '"/>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('width') . '">' . __('Width:', 'wdfb') . '</label> ';
		$html .= '<input type="text" name="' . $this->get_field_name('width') . '" id="' . $this->get_field_id('width') . '" size="3" value="' . $width . '"/>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('height') . '">' . __('Height:', 'wdfb') . '</label> ';
		$html .= '<input type="text" name="' . $this->get_field_name('height') . '" id="' . $this->get_field_id('height') . '" size="3" value="' . $height . '"/>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('show_timeline') . '">' . __('Show Timeline tab:', 'wdfb') . '</label> ';
		$html .= '<input type="checkbox" name="' . $this->get_field_name('show_timeline') . '" id="' . $this->get_field_id('show_timeline') . '" value="1" ' . ($show_timeline ? 'checked="checked"' : '') . ' />';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('show_events') . '">' . __('Show Events tab:', 'wdfb') . '</label> ';
		$html .= '<input type="checkbox" name="' . $this->get_field_name('show_events') . '" id="' . $this->get_field_id('show_events') . '" value="1" ' . ($show_events ? 'checked="checked"' : '') . ' />';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('show_messages') . '">' . __('Show Messages tab:', 'wdfb') . '</label> ';
		$html .= '<input type="checkbox" name="' . $this->get_field_name('show_messages') . '" id="' . $this->get_field_id('show_messages') . '" value="1" ' . ($show_messages ? 'checked="checked"' : '') . ' />';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('hide_cover') . '">' . __('Hide Cover:', 'wdfb') . '</label> ';
		$html .= '<input type="checkbox" name="' . $this->get_field_name('hide_cover') . '" id="' . $this->get_field_id('hide_cover') . '" value="1" ' . ($hide_cover ? 'checked="checked"' : '') . ' />';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('show_faces') . '">' . __('Show faces:', 'wdfb') . '</label> ';
		$html .= '<input type="checkbox" name="' . $this->get_field_name('show_faces') . '" id="' . $this->get_field_id('show_faces') . '" value="1" ' . ($show_faces ? 'checked="checked"' : '') . ' />';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('small_header') . '">' . __('Small Header:', 'wdfb') . '</label> ';
		$html .= '<input type="checkbox" name="' . $this->get_field_name('small_header') . '" id="' . $this->get_field_id('small_header') . '" value="1" ' . ($small_header ? 'checked="checked"' : '') . ' />';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('adapt_container_width') . '">' . __('Adapt container width:', 'wdfb') . '</label> ';
		$html .= '<input type="checkbox" name="' . $this->get_field_name('adapt_container_width') . '" id="' . $this->get_field_id('adapt_container_width') . '" value="1" ' . ($adapt_container_width ? 'checked="checked"' : '') . ' />';
		$html .= '</p>';
	
		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id('hide_if_logged_out') . '">' . __('Hide widget if user is not logged into Facebook:', 'wdfb') . '</label> ';
		$html .= '<input type="checkbox" name="' . $this->get_field_name('hide_if_logged_out') . '" id="' . $this->get_field_id('hide_if_logged_out') . '" value="1" ' . ($hide_if_logged_out ? 'checked="checked"' : '') . ' />';
		$html .= '</p>';

		echo $html;
	}

	function update($new_instance, $old_instance) {
		$instance = $old_instance;
		$instance['title']                 = strip_tags($new_instance['title']);
		$instance['width']                 = strip_tags($new_instance['width']);
		$instance['height']                = strip_tags($new_instance['height']);
		$instance['url']                   = strip_tags($new_instance['url']);
		$instance['show_timeline']         = strip_tags($new_instance['show_timeline']);
		$instance['show_events']           = strip_tags($new_instance['show_events']);
		$instance['show_messages']         = strip_tags($new_instance['show_messages']);
		$instance['hide_cover']            = strip_tags($new_instance['hide_cover']);
		$instance['show_faces']            = strip_tags($new_instance['show_faces']);
		$instance['small_header']          = strip_tags($new_instance['small_header']);
		$instance['adapt_container_width'] = strip_tags($new_instance['adapt_container_width']);
		$instance['hide_if_logged_out']    = strip_tags($new_instance['hide_if_logged_out']);

		return $instance;
	}

	function widget($args, $instance) {
		extract($args);
		$title                 = apply_filters('widget_title', $instance['title']);
		$width                 = $instance['width'];
		$width                 = $width ? $width : 340;
		$height                = $instance['height'];
		$height                = $height ? $height : 500;
		// $url                   = rawurlencode($instance['url']);
		$url                   = $instance['url'];
		$show_timeline         = (int)@$instance['show_timeline'];
		$show_timeline         = $show_timeline ? 'timeline' : '';
		$show_events           = (int)@$instance['show_events'];
		$show_events           = $show_events ? 'events' : '';
		$show_messages         = (int)@$instance['show_messages'];
		$show_messages         = $show_messages ? 'messages' : '';
		$hide_cover            = (int)@$instance['hide_cover'];
		$hide_cover            = $hide_cover ? 'true' : 'false';
		$show_facepile         = (int)@$instance['show_faces'];
		$show_facepile         = $show_facepile ? 'true' : 'false';
		$small_header          = (int)@$instance['small_header'];
		$small_header          = $small_header ? 'true' : 'false';
		$adapt_container_width = (int)@$instance['adapt_container_width'];
		$adapt_container_width = $adapt_container_width ? 'true' : 'false';
		$hide_if_logged_out    = (int)@$instance['hide_if_logged_out'];

		$tabs = array(
			$show_timeline,
			$show_events,
			$show_messages,
		);

		$tabs = implode(',', $tabs);
		
		$locale = wdfb_get_locale();
		$id = "wdfb-likebox-" . md5(microtime());

		echo $before_widget;
		if ($title) echo $before_title . $title . $after_title;

		echo '<div class="fb-page" ' . 
				'id="' . $id . '" ' . 
				'data-href="' . $url . '" ' . 
				'data-width="' . $width . '" ' . 
				'data-height="' . $height . '" ' . 
				'data-tabs="' . $tabs . '" ' . 
				'data-hide-cover="' . $hide_cover . '" ' . 
				'data-show-facepile="' . $show_facepile . '" ' . 
				'data-small-header="' . $small_header . '" ' . 
				'data-adapt-container-width="' . $adapt_container_width . '" ' . 
			'></div>'
		;

		if ($hide_if_logged_out) {
			$hide_js = <<<EOWdfbLikeBoxHidingJs
<script type="text/javascript">
(function ($) {
$(function () {
FB.getLoginStatus(function(response) {
  if (response.status != 'connected') $('#{$id}').parents(".widget").remove();
});
});
})(jQuery);
</script>
EOWdfbLikeBoxHidingJs;
			echo apply_filters('wdfb-widgets-likebox-hide_js', $hide_js, $id);
		}

		echo $after_widget;
	}
}