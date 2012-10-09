<?php
/**
 * Ultimate Facebook post metabox handling classes.
 */

abstract class Wdfb_Metabox {

	protected $_types = array();
	protected $_context = 'normal';
	protected $_priority = 'default';
	protected $_id_string = 'wdfb_';

	protected $_fields = array();

	public function __construct () {
		$this->_add_hooks();
	}

	abstract public function get_box_title ();
	abstract public function render_box ();

	protected function _add_hooks () {
		if (is_admin()) {
			add_action('add_meta_boxes', array($this, 'register'));
			//add_action('init', array($this, 'enqueue_resources'));
			add_action('save_post', array($this, 'save_metabox_values'));
		}
		add_action('init', array($this, 'set_types'), 999);
	}
	
	public function save_metabox_values () {
		foreach ($this->_fields as $field) if (isset($_POST[$field])) {
			$this->_set_value($field, $_POST[$field]);
		}
	}

	public function set_types () {
		$defaults = get_post_types(array('public'=>true), 'names');
		$types = apply_filters('wdfb-metabox-filter_registered_types', $defaults, $this);
		$this->_types = is_array($types) ? $types : array();
	}

	public function register () {
		if (!is_admin()) return false;
		$this->_register();
	}

	protected function _register () {
		foreach ($this->_types as $type) {
			add_meta_box(
				$this->_id_string,
				$this->get_box_title(),
				array($this, 'render_box'),
				$type,
				$this->_context,
				$this->_priority
			);
		}

		// Enqueue dependencies
		global $post, $pagenow;
		if (!in_array($pagenow, array('post.php', 'post-new.php'))) return false; // Editor pages, double-check
		if (!is_object($post) || !isset($post->post_type) || !in_array($post->post_type, $this->_types)) return false; // Invalid post type
		wp_enqueue_script('wdfb_metabox', WDFB_PLUGIN_URL . '/js/wdfb_metabox.js', array('jquery'));
		wp_enqueue_style('wdfb_metabox', WDFB_PLUGIN_URL . '/css/wdfb_metabox.css');
	}

	protected function _set_value ($name, $value) {
		if (defined('DOING_AJAX')) return false; // autosave
		if (!$name) return false;
		global $post;
		if (!in_array($post->post_type, $this->_types)) return false;
		$id = wp_is_post_revision($post->ID);
		$id = $id ? $id : $post->ID;
		$value = is_array($value) ? stripslashes_deep($value) : stripslashes($value);
		return update_post_meta($id, $name, $value);
	}

	protected function _get_value ($name, $fallback=false) {
		if (!$name) return false;
		global $post;
		$id = wp_is_post_revision($post->ID);
		$id = $id ? $id : $post->ID;
		$val = get_post_meta($id, $name, true);
		return $val ? $val : $fallback;
	}

	protected function _add_text_field ($id, $title, $help=false, $is_box=false) {
		$help = $help ? "<small><em>{$help}</em></small>" : '';
		$value = esc_attr($this->_get_value($id));
		return $this->_add_field($title, (
			$is_box
				? "<textarea class='widefat' name='{$id}' id='{$id}'>{$value}</textarea>" 
				: "<input type='text' class='widefat' name='{$id}' id='{$id}' value='{$value}' />"
			) .
		"{$help}");
	}

	protected function _add_field ($title, $markup) {
		return "<div class='wdfb_metabox_container'><a href='#field' class='wdfb_metabox_field_trigger'>{$title}</a><div class='wdfb_metabox_field' style='display:none'>{$markup}</div></div>";
	}
}


/**
 * OpenGraph concrete implementation.
 */
class Wdfb_Metabox_OpenGraph extends Wdfb_Metabox {

	protected $_id_string = 'wdfb_opengraph_editor';
	protected $_context = 'side';

	protected $_fields = array(
		'wdfb_og_title',
		'wdfb_og_type',
		'wdfb_og_description',
		'wdfb_og_images',
		'wdfb_og_custom_name',
		'wdfb_og_custom_value',
	);

	protected function _add_hooks () {
		parent::_add_hooks();
		add_filter('wdfb-opengraph-title', array($this, 'apply_title_override'));
		add_filter('wdfb-opengraph-type', array($this, 'apply_type_override'));
		add_filter('wdfb-opengraph-description', array($this, 'apply_description_override'));

		add_action('wdfb-opengraph-after_extra_headers', array($this, 'apply_extra_headers_overrides'));
		add_action('wdfb-opengraph-after_extra_headers', array($this, 'apply_custom_images'));
	}

	public function get_box_title () {
		return __('OpenGraph Settings', 'wdfb');
	}

	public function render_box () {
		echo $this->_add_text_field('wdfb_og_type', __('Type', 'wdfb'), __('Common values include "article", "book", "profile", etc.', 'wdfb'));
		echo $this->_add_text_field('wdfb_og_title', __('Title', 'wdfb'), __('Leave empty to use post title', 'wdfb'));
		echo $this->_add_text_field('wdfb_og_description', __('Description', 'wdfb'), __('Type in your OpenGraph description', 'wdfb'), true);
		echo $this->_add_images_field(__('Add a set of custom OpenGraph images which will be used in addition to default ones. Please, use the full image URL', 'wdfb'));
		echo $this->_add_custom_field(__('Add a set of custom OpenGraph properties', 'wdfb'));
	}

	public function apply_title_override ($val) { return $this->_has_field_value('title', $val); }
	public function apply_type_override ($val) { return $this->_has_field_value('type', $val); }
	public function apply_description_override ($val) { return $this->_has_field_value('description', $val); }

	public function apply_extra_headers_overrides () {
		$names = $this->_has_field_value('custom_name', array());
		$values = $this->_has_field_value('custom_value', array());

		if (!$names || !$values) return false;
		foreach ($names as $idx => $name) {
			echo wdfb_get_opengraph_property(
				apply_filters('wdfb-opengraph-extra_headers-name', $name), 
				apply_filters('wdfb-opengraph-extra_headers-value', @$values[$idx], $name),
				false
			);
		}
	}

	public function apply_custom_images () {
		$images = $this->_has_field_value('images', array());
		foreach ($images as $image) if ($image) echo wdfb_get_opengraph_property('image', $image);
	}

	private function _has_field_value ($field, $default=false) {
		global $post;
		$single = false;
		foreach ($this->_types as $type) {
			if (!is_singular($type)) continue;
			$single = true;
			break;
		}
		if (!$single) return $default;

		return $this->_get_value('wdfb_og_' . $field, $default);
	}

	private function _add_images_field ($help=false) {
		$help = $help ? "<small><em>{$help}</em></small>" : '';
		$images = $this->_get_value('wdfb_og_images');

		$images = $images ? $images : array('');
		$title = __('Custom OpenGraph images', 'wdfb');

		foreach ($images as $idx => $image) {
			$name = esc_attr($image);
			$markup .= '<div class="wdfb_repeatable wdfb_repeatable_image">' .
				"<label>" . __('Image URL', 'wdfb') . ":</label> <input type='text' class='widefat' name='wdfb_og_images[{$idx}]' value='{$image}' />" .
				'<a href="#remove-og-property" class="wdfb_repeatable_remove">' . __('Remove', 'wdfb') . '</a>' .
			'</div>';
		}
		$markup .= '<p><a href="#add-og-field" class="wdfb_repeatable_trigger" data-type="image">' . __('Add another', 'wdfb') . '</a></p>';
		return $this->_add_field($title, "{$markup}{$help}");
	}

	private function _add_custom_field ($help=false) {
		$help = $help ? "<small><em>{$help}</em></small>" : '';
		$names = $this->_get_value('wdfb_og_custom_name');
		$values = $this->_get_value('wdfb_og_custom_value');

		$names = $names ? $names : array('');
		$title = __('Custom OpenGraph properties', 'wdfb');

		foreach ($names as $idx => $name) {
			$name = esc_attr($name);
			$value = esc_attr(@$values[$idx]);
			$markup .= '<div class="wdfb_repeatable wdfb_repeatable_custom">' .
				"<label>" . __('Property name', 'wdfb') . ":</label> <input type='text' class='widefat' name='wdfb_og_custom_name[{$idx}]' value='{$name}' /><br />" .
				"<label>" . __('Property value', 'wdfb') . ":</label> <input type='text' class='widefat' name='wdfb_og_custom_value[{$idx}]' value='{$value}' />" .
				'<a href="#remove-og-property" class="wdfb_repeatable_remove">' . __('Remove', 'wdfb') . '</a>' .
			'</div>';
		}
		$markup .= '<p><a href="#add-og-field" class="wdfb_repeatable_trigger" data-type="custom">' . __('Add another', 'wdfb') . '</a></p>';
		return $this->_add_field($title, "{$markup}{$help}");
	}

}