<?php

/**
 * Options registry.
 */
class Wdfb_OptionsRegistry {

	var $_store = array();

	/*
	function get_instance () {
		static $instance;
		if (! isset($instance)) {
			$instance = array(new Wdfb_OptionsRegistry);
		}
		return $instance[0];
	}
	*/

	private static $_instance;

	private function __construct() {
	}

	public static function get_instance() {
		if ( self::$_instance ) {
			return self::$_instance;
		}
		self::$_instance = new Wdfb_OptionsRegistry;

		return self::$_instance;
	}

	function get_key( $key, $default = false ) {
		if ( ! isset( $this->_store[ $key ] ) ) {
			return $default;
		}

		return $this->_store[ $key ];
	}

	function set_key( $key, $values = array() ) {
		$this->_store[ $key ] = $values;
	}

	function get_option( $key, $option, $default = false ) {
		if ( ! isset( $this->_store[ $key ] ) ) {
			return $default;
		}
		if ( ! isset( $this->_store[ $key ][ $option ] ) ) {
			return $default;
		}

		return $this->_store[ $key ][ $option ];
	}

	function get_network_option( $key, $option ) {
		$opts = is_multisite() ? get_site_option( $key ) : get_option( $key );

		return @$opts[ $option ];
	}

	function set_option( $key, $option, $value = false ) {
		if ( ! isset( $this->_store[ $key ] ) ) {
			$this->_store[ $key ] = array();
		}
		$this->_store[ $key ][ $option ] = $value;
	}

}