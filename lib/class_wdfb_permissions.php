<?php
class Wdfb_Permissions {

	const NEW_USER = 'user_about_me,user_birthday,user_education_history,user_events,user_hometown,user_location,user_relationships,user_religion_politics,user_birthday,user_likes,email';
	const NON_PUBLISHER = 'user_photos,create_event,rsvp_event,read_stream';
	const PUBLISHER = 'publish_stream,create_note,manage_pages,offline_access';

	private function __construct () {}

	public static function get_permissions () {
		$id = get_current_user_id();
		if (!$id) return self::get_new_user_permissions();
		if (!current_user_can('edit_theme_options')) return self::get_new_user_permissions();
		if (!current_user_can('publish_posts')) return self::get_non_publisher_permissions();
		else return self::get_publisher_permissions();
	}

	public static function get_new_user_permissions () {
		return rtrim(self::NEW_USER, ',');
	}

	public static function get_non_publisher_permissions () {
		return rtrim(join(',', array(
			self::get_new_user_permissions(),
			self::NON_PUBLISHER,
		)), ',');
	}

	public static function get_publisher_permissions () {
		return rtrim(join(',', array(
			self::get_new_user_permissions(),
			self::get_non_publisher_permissions(),
			self::PUBLISHER,
		)), ',');
	}
}