<?php

/**
 * Shows Facebook Events box.
 */
class Wdfb_WidgetEvents extends WP_Widget {
	var $model;

	function Wdfb_WidgetEvents() {
		$this->model = new Wdfb_Model();
		$widget_ops  = array( 'classname' => __CLASS__, 'description' => __( 'Shows Facebook Events', 'wdfb' ) );

		add_action( 'wp_print_styles', array( $this, 'css_load_styles' ) );
		//add_action('wp_print_scripts', array($this, 'js_load_scripts'));
		add_action( 'admin_print_scripts-widgets.php', array( $this, 'js_load_scripts' ) );
		add_action( 'admin_print_styles-widgets.php', array( $this, 'css_load_admin_styles' ) );

		parent::__construct( __CLASS__, 'Facebook Events', $widget_ops );
	}

	function css_load_styles() {
		wp_enqueue_style( 'wdfb_widget_events', WDFB_PLUGIN_URL . '/css/wdfb_widget_events.css' );
	}

	function css_load_admin_styles() {
		wp_enqueue_style( 'wdfb_jquery_ui_style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.2/themes/ui-lightness/jquery-ui.css' );
	}

	function js_load_scripts() {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'wdfb_widget_events', WDFB_PLUGIN_URL . '/js/wdfb_widget_events.js', array(
			'jquery',
			'jquery-ui-datepicker'
		) );
		wp_localize_script( 'wdfb_widget_events', 'l10nWdfbEventsEditor', array(
			'insuficient_perms' => __( "Your app doesn't have enough permissions to access your events", 'wdfb' ),
			'grant_perms'       => __( "Grant needed permissions now", 'wdfb' ),
		) );
	}

	function form( $instance ) {
		$title           = isset( $instance['title'] ) ? esc_attr( $instance['title'] ) : '';
		$for             = isset( $instance['for'] ) ? esc_attr( $instance['for'] ) : '';
		$limit           = isset( $instance['limit'] ) ? esc_attr( $instance['limit'] ) : '';
		$show_image      = isset( $instance['show_image'] ) ? esc_attr( $instance['show_image'] ) : '';
		$show_location   = isset( $instance['show_location'] ) ? esc_attr( $instance['show_location'] ) : '';
		$show_start_date = isset( $instance['show_start_date'] ) ? esc_attr( $instance['show_start_date'] ) : '';
		$show_end_date   = isset( $instance['show_end_date'] ) ? esc_attr( $instance['show_end_date'] ) : '';
		$date_threshold  = isset( $instance['date_threshold'] ) ? esc_attr( $instance['date_threshold'] ) : '';
		$only_future     = isset( $instance['only_future'] ) ? esc_attr( $instance['only_future'] ) : true;
		$reverse_order   = isset( $instance['reverse_order'] ) ? esc_attr( $instance['reverse_order'] ) : '';

		$html = '';

		$fb_user = $this->model->fb->getUser();
		if ( ! $fb_user ) {
			$html .= '<div class="wdfb_admin_message message">';
			$html .= sprintf( __( 'You should be logged into your Facebook account when adding this widget. <a href="%s">Click here to do so now</a>, then refresh this page.' ), $this->model->fb->getLoginUrl() );
			$html .= '</div>';
		} else {
			$html .= '<div class="wdfb_admin_message message">Facebook user ID: ' . $fb_user . '</div>';
		}

		$html .= '<div class="wdfb_widget_events_home">';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'title' ) . '">' . __( 'Title:', 'wdfb' ) . '</label>';
		$html .= '<input type="text" name="' . $this->get_field_name( 'title' ) . '" id="' . $this->get_field_id( 'title' ) . '" class="widefat" value="' . $title . '"/>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'for' ) . '">' . __( 'Show events for:', 'wdfb' ) . '</label>';
		$html .= '<input type="text" name="' . $this->get_field_name( 'for' ) . '" id="' . $this->get_field_id( 'for' ) . '" value="' . $for . '"/>';
		$html .= '<div>Leave this box empty to display your own events.</div>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'limit' ) . '">' . __( 'Limit:', 'wdfb' ) . '</label>';
		$html .= ' <select name="' . $this->get_field_name( 'limit' ) . '" id="' . $this->get_field_id( 'limit' ) . '">';
		foreach ( range( 5, 50, 5 ) as $i ) {
			$selected = selected( $i, $limit, false );
			$html .= "<option value='{$i}' {$selected}>{$i}</option>";
		}
		$html .= '</select>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'show_image' ) . '">' . __( 'Show image:', 'wdfb' ) . '</label>';
		$html .= ' <input type="checkbox" name="' . $this->get_field_name( 'show_image' ) . '" id="' . $this->get_field_id( 'show_image' ) . '" value="1" ' . ( $show_image ? 'checked="checked"' : '' ) . '/>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'show_location' ) . '">' . __( 'Show location:', 'wdfb' ) . '</label>';
		$html .= ' <input type="checkbox" name="' . $this->get_field_name( 'show_location' ) . '" id="' . $this->get_field_id( 'show_location' ) . '" value="1" ' . ( $show_location ? 'checked="checked"' : '' ) . '/>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'show_start_date' ) . '">' . __( 'Show event start:', 'wdfb' ) . '</label>';
		$html .= ' <input type="checkbox" name="' . $this->get_field_name( 'show_start_date' ) . '" id="' . $this->get_field_id( 'show_start_date' ) . '" value="1" ' . ( $show_start_date ? 'checked="checked"' : '' ) . '/>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'show_end_date' ) . '">' . __( 'Show event end:', 'wdfb' ) . '</label>';
		$html .= ' <input type="checkbox" name="' . $this->get_field_name( 'show_end_date' ) . '" id="' . $this->get_field_id( 'show_end_date' ) . '" value="1" ' . ( $show_end_date ? 'checked="checked"' : '' ) . '/>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'only_future' ) . '">' . __( 'Show only future events:', 'wdfb' ) . '</label>';
		$html .= ' <input type="checkbox" name="' . $this->get_field_name( 'only_future' ) . '" id="' . $this->get_field_id( 'only_future' ) . '" value="1" ' . ( $only_future ? 'checked="checked"' : '' ) . '/>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'reverse_order' ) . '">' . __( 'Reverse order:', 'wdfb' ) . '</label>';
		$html .= ' <input type="checkbox" name="' . $this->get_field_name( 'reverse_order' ) . '" id="' . $this->get_field_id( 'reverse_order' ) . '" value="1" ' . ( $reverse_order ? 'checked="checked"' : '' ) . '/>';
		$html .= '</p>';

		$html .= '<p>';
		$html .= '<label for="' . $this->get_field_id( 'date_threshold' ) . '">' . __( 'Show events starting from this date:', 'wdfb' ) . '</label>';
		$html .= ' <input type="text" class="widefat wdfb_date_threshold" name="' . $this->get_field_name( 'date_threshold' ) . '" id="' . $this->get_field_id( 'date_threshold' ) . '" value="' . $date_threshold . '"/>';
		$html .= '<br /><small>(YYYY-mm-dd, e.g. 2011-06-09)</small>';
		$html .= '</p>';

		$html .= '</div>';

		echo $html;
	}

	function update( $new_instance, $old_instance ) {
		$instance                    = $old_instance;
		$instance['title']           = isset ( $new_instance['title'] ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['for']             = isset ( $new_instance['for'] ) ? strip_tags( $new_instance['for'] ) : $this->model->fb->getUser();
		$instance['limit']           = isset ( $new_instance['limit'] ) ? (int) $new_instance['limit'] : '';
		$instance['show_image']      = isset ( $new_instance['show_image'] ) ? strip_tags( $new_instance['show_image'] ) : '';
		$instance['show_location']   = isset ( $new_instance['show_location'] ) ? strip_tags( $new_instance['show_location'] ) : '';
		$instance['show_start_date'] = isset ( $new_instance['show_start_date'] ) ? strip_tags( $new_instance['show_start_date'] ) : '';
		$instance['show_end_date']   = isset ( $new_instance['show_end_date'] ) ? strip_tags( $new_instance['show_end_date'] ) : '';
		$instance['date_threshold']  = isset ( $new_instance['date_threshold'] ) ? strip_tags( $new_instance['date_threshold'] ) : '';
		$instance['only_future']     = isset ( $new_instance['only_future'] ) ? strip_tags( $new_instance['only_future'] ) : '';
		$instance['reverse_order']   = isset ( $new_instance['reverse_order'] ) ? strip_tags( $new_instance['reverse_order'] ) : '';

		//$instance['events'] = empty($instance['events']) ? $this->model->get_events_for($instance['for']) : $instance['events'];

		return $instance;
	}

	function widget( $args, $instance ) {
		extract( $args );
		$title           = isset ( $instance['title'] ) ? apply_filters( 'widget_title', $instance['title'] ) : '';
		$for             = isset ( $instance['for'] ) ? $instance['for'] : '';
		$limit           = isset ( $instance['limit'] ) ? (int) $instance['limit'] : '';
		$show_image      = isset ( $instance['show_image'] ) ? (int) $instance['show_image'] : '';
		$show_location   = isset ( $instance['show_location'] ) ? (int) $instance['show_location'] : '';
		$show_start_date = isset ( $instance['show_start_date'] ) ? (int) $instance['show_start_date'] : '';
		$show_end_date   = isset ( $instance['show_end_date'] ) ? (int) $instance['show_end_date'] : '';
		$date_threshold  = isset ( $instance['date_threshold'] ) ? $instance['date_threshold'] : '';
		$only_future     = isset ( $instance['only_future'] ) ? $instance['only_future'] : '';
		$reverse_order   = isset ( $instance['reverse_order'] ) ? $instance['reverse_order'] : '';

		$date_threshold = $date_threshold ? strtotime( $date_threshold ) : false;
		$now            = time();
		if ( $only_future ) {
			$date_threshold = ( $date_threshold && $date_threshold > $now ) ? $date_threshold : $now;
		}

		$api    = new Wdfb_EventsBuffer;
		$events = $api->get_for( $for, $limit );
		$events = is_array( $events ) ? $events : array();
		usort( $events, array( $this, 'sort_events_by_start_time' ) );
		if ( $reverse_order ) {
			$events = array_reverse( $events );
		}

		$timestamp_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		echo $before_widget;
		if ( $title ) {
			echo $before_title . $title . $after_title;
		}

		if ( is_array( $events ) && ! empty( $events ) ) {
			$current_tz = function_exists( 'date_default_timezone_get' ) ? @date_default_timezone_get() : 'UTC';
			echo '<ul class="wdfb_widget_events">';
			foreach ( $events as $idx => $event ) {
				if ( function_exists( 'date_default_timezone_set' ) && ! empty( $event['timezone'] ) ) {
					$start_time = isset ( $event['start_time'] ) ? strtotime( $event['start_time'] ) : '';
					$end_time   = isset ( $event['end_time'] ) ? strtotime( $event['end_time'] ) : '';
					date_default_timezone_set( $event['timezone'] );
					$event['start_time'] = $start_time ? date( 'Y-m-d H:i:s', $start_time ) : '';
					$event['end_time']   = $end_time ? date( 'Y-m-d H:i:s', $end_time ) : '';
					date_default_timezone_set( $current_tz );
				}
				if ( $date_threshold > strtotime( $event['start_time'] ) ) {
					continue;
				}
				if ( $idx >= $limit ) {
					break;
				}
				include( WDFB_PLUGIN_BASE_DIR . '/lib/forms/event_item.php' );
			}
			echo '</ul>';
		} else {
			$no_events = __( 'No upcoming events', 'psts' );
			echo '<p>' . apply_filters( 'psts_no_events', $no_events ) . '</p>';
		}

		echo $after_widget;
	}

	function sort_events_by_start_time( $a, $b ) {
		$a_time = strtotime( $a['start_time'] );
		$b_time = strtotime( $b['start_time'] );
		if ( $a_time == $b_time ) {
			return 0;
		}

		return $a_time > $b_time ? - 1 : 1;
	}
}