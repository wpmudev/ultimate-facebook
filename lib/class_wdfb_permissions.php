<?php

class Wdfb_Permissions {

	const NEW_USER = 'email';
	const NON_PUBLISHER = '';
	const PUBLISHER = '';
	const EXTRAS = 'user_education_history,user_hometown,user_relationships,user_religion_politics'; // Deprecated

	const EXTRA_READ = 'user_posts';

	const EXTRA_PUBLISH_PAGES = 'manage_pages';
	const EXTRA_PUBLISH_ACTION = 'publish_actions,publish_pages';

	const EXTRA_USER_PHOTOS = 'user_photos';

	const EXTRA_ABOUT = 'user_about_me';
	const EXTRA_BIRTHDAY = 'user_birthday';
	const EXTRA_LOCATION = 'user_location';
	const EXTRA_HOMETOWN = 'user_hometown';
	const EXTRA_RELATIONSHIP = 'user_relationships';
	const EXTRA_RELIGION = 'user_religion_politics';
	const EXTRA_POLITICS = 'user_religion_politics';
	const EXTRA_GENDER_INTEREST = 'user_relationship_details';
	const EXTRA_INTERESTS = 'user_interests';
	const EXTRA_EDUCATION = 'user_education_history';
	const EXTRA_WORK = 'user_work_history';
	const EXTRA_EVENTS = 'user_events';

	private function __construct() {
	}

	public static function get_permissions() {
		$id = get_current_user_id();
		if ( ! $id ) {
			return self::get_new_user_permissions();
		}
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			return self::get_new_user_permissions();
		}
		if ( ! current_user_can( 'publish_posts' ) ) {
			return self::get_non_publisher_permissions();
		} else {
			return self::get_publisher_permissions();
		}
	}

	public static function get_new_user_permissions() {
		$data         = Wdfb_OptionsRegistry::get_instance();
		$extra_fields = array(
			'about'                      => self::EXTRA_ABOUT,
			'birthday'                   => self::EXTRA_BIRTHDAY,
			'location'                   => self::EXTRA_LOCATION,
			'hometown'                   => self::EXTRA_HOMETOWN,
			'relationship_status'        => self::EXTRA_RELATIONSHIP,
			'significant_other'          => self::EXTRA_RELATIONSHIP,
			'political'                  => self::EXTRA_POLITICS,
			'religion'                   => self::EXTRA_RELIGION,
			'favorite_teams'             => self::EXTRA_INTERESTS,
			'quotes'                     => self::EXTRA_INTERESTS,
			'interested_in'              => self::EXTRA_GENDER_INTEREST,
			'education/schools'          => self::EXTRA_EDUCATION,
			'education/graduation_dates' => self::EXTRA_EDUCATION,
			'education/subjects'         => self::EXTRA_EDUCATION,
			'work/employers'             => self::EXTRA_WORK,
			'work/position_history'      => self::EXTRA_WORK,
			'work/employer_history'      => self::EXTRA_WORK,
			'connection/books'           => self::EXTRA_INTERESTS,
			'connection/games'           => self::EXTRA_INTERESTS,
			'connection/movies'          => self::EXTRA_INTERESTS,
			'connection/music'           => self::EXTRA_INTERESTS,
			'connection/television'      => self::EXTRA_INTERESTS,
			'connection/interests'       => self::EXTRA_INTERESTS,
		);
		$import       = array();

		if ( ! defined( 'BP_VERSION' ) && $data->get_option( 'wdfb_connect', 'wordpress_registration_fields' ) ) {
			$wp_fields = $data->get_option( 'wdfb_connect', 'wordpress_registration_fields' );
			if ( is_array( $wp_fields ) ) {
				foreach ( $wp_fields as $map ) {
					if ( ! isset( $map['fb'] ) ) {
						continue;
					}
					if ( ! in_array( $map['fb'], array_keys( $extra_fields ) ) ) {
						continue;
					}
					$import[] = $extra_fields[ $map['fb'] ];
				}
			}
		} else if ( defined( 'BP_VERSION' ) ) {
			$model  = new Wdfb_Model;
			$fields = $model->get_bp_xprofile_fields();
			if ( is_array( $fields ) ) {
				foreach ( $fields as $field ) {
					$fb_value = $data->get_option( 'wdfb_connect', 'buddypress_registration_fields_' . $field['id'] );
					if ( ! in_array( $fb_value, array_keys( $extra_fields ) ) ) {
						continue;
					}
					$import[] = $extra_fields[ $fb_value ];
				}
			}
		}
		$permissions = ! empty( $import )
			? join( ',', array_values( array_unique( $import ) ) )
			: false;

		$perms = ! empty( $permissions ) ?
			rtrim( join( ',', array(
				$permissions,
				self::NEW_USER,
			) ), ',' )
			:
			rtrim( self::NEW_USER, ',' );;

		return apply_filters( 'wdfb-permissions-new_user', $perms );
	}

	public static function get_non_publisher_permissions() {
		$extras = explode( ',', self::NON_PUBLISHER );
		if ( ! ( defined( 'WDFB_CORE_MINIMAL_PERMISSIONS_SET' ) && WDFB_CORE_MINIMAL_PERMISSIONS_SET ) ) {
			$extras[] = self::EXTRA_READ;
		}
		$perms = array_merge(
			explode( ',', self::get_new_user_permissions() ),
			$extras
		);
		$perms = array_values( array_unique( array_filter( $perms ) ) );
		$perms = join( ',', $perms );

		return apply_filters( 'wdfb-permissions-non_publisher', $perms );
	}

	public static function get_publisher_permissions() {
		$data   = Wdfb_OptionsRegistry::get_instance();
		$extras = array();

		//Access User photos
		$extras[] = self::EXTRA_USER_PHOTOS;

		//Access User events
		$extras[] = self::EXTRA_EVENTS;

		//Manage pages and publish permission, if Autopost is selected or if single post is not disabled
		$include_posting = defined( 'WDFB_CORE_MINIMAL_PERMISSIONS_SET' ) && WDFB_CORE_MINIMAL_PERMISSIONS_SET
			? $data->get_option( 'wdfb_autopost', 'allow_autopost' ) || ! $data->get_option( 'wdfb_autopost', 'prevent_post_metabox' )
			: true;
		if ( $include_posting ) {
			//Publish link
			$extras[] = self::EXTRA_PUBLISH_ACTION;

			//Publish on page
			$extras[] = self::EXTRA_PUBLISH_PAGES;
		}

		$perms = array_merge(
			explode( ',', self::get_new_user_permissions() ),
			explode( ',', self::get_non_publisher_permissions() ),
			explode( ',', self::PUBLISHER ),
			$extras
		);
		$perms = array_values( array_unique( array_filter( $perms ) ) );
		$perms = join( ',', $perms );

		return apply_filters( 'wdfb-permissions-publisher', $perms );
	}
}