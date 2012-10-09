<?php
/**
 * Handles all data - both Facebook requests and local WP database reuests.
 */
class Wdfb_Model {
	var $fb;
	var $db;
	var $data;
	var $log;

	function __construct () {
		global $wpdb;
		$this->data =& Wdfb_OptionsRegistry::get_instance();
		$this->db = $wpdb;
		$this->fb = new Facebook(array(
			'appId' => trim($this->data->get_option('wdfb_api', 'app_key')),
			'secret' => trim($this->data->get_option('wdfb_api', 'secret_key')),
			'cookie' => true,
		));
		$this->fb->getLoginUrl(); // Generate the CSRF sig stuff yay
		$this->log = new Wdfb_ErrorLog;
	}

	function Wdfb_Model () {
		$this->__construct();
	}

	/**
	 * Returns all blogs on the current site.
	 */
	function get_blog_ids () {
		global $current_blog;
		$site_id = 0;
		if ($current_blog) {
			$site_id = $current_blog->site_id;
		}
		$sql = "SELECT blog_id FROM " . $this->db->blogs . " WHERE site_id={$site_id} AND public='1' AND archived= '0' AND spam='0' AND deleted='0' ORDER BY registered DESC";
		return $this->db->get_results($sql, ARRAY_A);
	}

	/**
	 * Logs the user out of the site and Facebook.
	 */
	function wp_logout ($redirect=false) {
		setcookie('fbsr_' . $this->fb->getAppId(), '', time()-100, '/', COOKIE_DOMAIN); // Yay for retardness in FB SDK
		@session_unset();
		@session_destroy();
		unset($_SESSION);
		wp_logout();
		wp_set_current_user(0);
		if ($redirect) wp_redirect($redirect);
	}

	/**
	 * Lists registered BuddyPress profile fields.
	 */
	function get_bp_xprofile_fields () {
		if (!defined('BP_VERSION')) return true;
		$tbl_pfx = function_exists('bp_core_get_table_prefix') ? bp_core_get_table_prefix() : apply_filters('bp_core_get_table_prefix', $this->db->base_prefix);
		$sql = "SELECT id, name FROM {$tbl_pfx}bp_xprofile_fields WHERE parent_id=0";
		return $this->db->get_results($sql, ARRAY_A);
	}

	/**
	 * Create/update the BuddyPress profile field.
	 */
	function set_bp_xprofile_field ($field_id, $user_id, $data) {
		if (!defined('BP_VERSION')) return true;

		$field_id = (int)$field_id;
		$user_id = (int)$user_id;
		if (!$field_id || !$user_id) return false;

		if (is_array($data)) $data = $data['name']; // For complex FB fields that return JSON objects
		$data = apply_filters('wdfb-profile_sync-bp-field_value', $data, $field_id, $user_id);
		if (!$data) return false; // Don't waste cycles if we don't need to

		$tbl_pfx = function_exists('bp_core_get_table_prefix') ? bp_core_get_table_prefix() : apply_filters('bp_core_get_table_prefix', $this->db->base_prefix);
		$sql = "SELECT id FROM {$tbl_pfx}bp_xprofile_data WHERE field_id={$field_id} AND user_id={$user_id}";
		$id = $this->db->get_var($sql);

		if ($id) {
			$sql = "UPDATE {$tbl_pfx}bp_xprofile_data SET data='" . $data . "' WHERE id={$id}";
		} else {
			$sql = "INSERT INTO {$tbl_pfx}bp_xprofile_data (field_id, user_id, value, last_updated) VALUES (" .
				(int)$field_id . ', ' . (int)$user_id . ", '" . $data . "', '" . date('Y-m-d H:i:s') . "')";
		}
		return $this->db->query($sql);
	}

	/**
	 * Gets FB profile image and sets it as BuddyPress avatar.
	 */
	function set_fb_image_as_bp_avatar ($user_id, $me) {
		if (!defined('BP_VERSION')) return true;
		if (!function_exists('bp_core_avatar_upload_path')) return true;
		if (!$me || !@$me['id']) return false;

		$fb_uid = $me['id'];

		if (function_exists('xprofile_avatar_upload_dir')) {
			$xpath = xprofile_avatar_upload_dir(false, $user_id);
			$path = $xpath['path'];
		} 
		if (!function_exists('xprofile_avatar_upload_dir') || empty($path)) {
			$object = 'user';
			$avatar_dir = apply_filters('bp_core_avatar_dir', 'avatars', $object);
			$path = bp_core_avatar_upload_path() . "/{$avatar_dir}/" . $user_id;
			$path = apply_filters('bp_core_avatar_folder_dir', $path, $user_id, $object, $avatar_dir);
			if (!realpath($path)) @wp_mkdir_p($path);
		}

		// Get FB picture
		//$fb_img = file_get_contents("http://graph.facebook.com/{$fb_uid}/picture?type=large");
		$page = wp_remote_get("http://graph.facebook.com/{$fb_uid}/picture?type=large", array(
			'method' 		=> 'GET',
			'timeout' 		=> '5',
			'redirection' 	=> '5',
			'user-agent' 	=> 'wdfb',
			'blocking'		=> true,
			'compress'		=> false,
			'decompress'	=> true,
			'sslverify'		=> false
		));
		if(is_wp_error($page)) return false; // Request fail
		if ((int)$page['response']['code'] != 200) return false; // Request fail
		$fb_img = $page['body'];
		
		$filename = md5($fb_uid);
		$filepath = "{$path}/{$filename}";
		file_put_contents($filepath, $fb_img);

		// Determine the right extension
		$info = getimagesize($filepath);
		$extension = false;

		if (function_exists('image_type_to_extension')) {
			$extension = image_type_to_extension($info[2], false);
		} else {
			switch ($info[2]) {
				case IMAGETYPE_GIF:
					$extension = 'gif';
					break;
				case IMAGETYPE_JPEG:
					$extension = 'jpg';
					break;
				case IMAGETYPE_PNG:
					$extension = 'png';
					break;
			}
		}
		// Unknown file type, clean up
		if (!$extension) {
			@unlink($filepath);
			return false;
		}
		$extension = 'jpeg' == strtolower($extension) ? 'jpg' : $extension; // Forcing .jpg extension for JPEGs

		// Clear old avatars
		$imgs = glob($path . '/*.{gif,png,jpg}', GLOB_BRACE);
		if (is_array($imgs)) foreach ($imgs as $old) {
			@unlink($old);
		}

		// Create and set new avatar
		if (defined('WDFB_BP_AVATAR_AUTO_CROP') && WDFB_BP_AVATAR_AUTO_CROP) {
			// Explicitly requested thumbnail processing
			// First, determine the centering position for cropping
			if ($info && isset($info[0]) && $info[0] && isset($info[1]) && $info[1]) {
				$full = apply_filters('wdfb-avatar-auto_crop', array(
					'x' => (int)(($info[0] - bp_core_avatar_full_width()) / 2),
					'y' => (int)(($info[1] - bp_core_avatar_full_height()) / 2),
					'width' => bp_core_avatar_full_width(),
					'height' => bp_core_avatar_full_height(),
				), $filepath, $info);
			}
			$crop = $full
				? wp_crop_image($filepath, $full['x'], $full['y'], $full['width'], $full['height'], bp_core_avatar_full_width(), bp_core_avatar_full_height(), false, "{$filepath}-bpfull.{$extension}")
				: false
			;
			if (!$crop) {
				@unlink($filepath);
				return false;
			}
			// Now, the thumbnail. First, try to resize the full avatar
			$thumb_file = wp_create_thumbnail("{$filepath}-bpfull.{$extension}", bp_core_avatar_thumb_width());
			if (!is_wp_error($thumb_file)) {
				// All good! We're done - clean up
				copy($thumb_file, "{$filepath}-bpthumb.{$extension}");
				@unlink($thumb_file);
			} else {
				// Sigh. Let's just fake it by using the original file then.
				copy("{$filepath}-bpfull.{$extension}", "{$filepath}-bpthumb.{$extension}");
			}
			@unlink($filepath);
			return true;
		} else {
			// No auto-crop, move on
			copy($filepath, "{$filepath}-bpfull.{$extension}");
			copy($filepath, "{$filepath}-bpthumb.{$extension}");
			@unlink($filepath);
			return true;
		}

		return false;
	}

	function get_all_user_tokens () {
		$sql = "SELECT * FROM " . $this->db->base_prefix . "usermeta WHERE meta_key='wdfb_api_accounts'";
		return $this->db->get_results($sql, ARRAY_A);
	}
	
	/**
	 * Gets an existing app token for the user.
	 */
	function get_user_api_token ($fb_uid) {
		global $current_user;
		$meta = get_user_meta($current_user->id, 'wdfb_api_accounts', true);
		$token = isset($meta['auth_tokens']) ? $meta['auth_tokens'] : array();
		if (!$token) return false;
		if (!isset($token[$fb_uid])) return false;
		return $token[$fb_uid];
	}

	function comment_already_imported ($fb_cid) {
		if (!$fb_cid) return false;
		$key = '%s:13:"fb_comment_id";s:' . strlen($fb_cid) . ':"' . $fb_cid . '";%';
		$sql = "SELECT meta_id FROM " . $this->db->prefix . "commentmeta WHERE meta_value LIKE '{$key}'";
		return $this->db->get_var($sql);
	}

	function get_wp_user_from_fb () {
		$fb_user_id = $this->fb->getUser();

		$sql = "SELECT user_id FROM " . $this->db->base_prefix . "usermeta WHERE meta_key='wdfb_fb_uid' AND meta_value=%s";
		$res = $this->db->get_results($this->db->prepare($sql, $fb_user_id), ARRAY_A);
		if ($res) return $res[0]['user_id'];

		// User not yet linked. Try finding her by email.
		$me = false;
		try {
			$me = $this->fb->api('/me');
		} catch (Exception $e) {
			return false;
		}
		if (!$me || !isset($me['email'])) return false;

		$sql = "SELECT ID FROM " . $this->db->base_prefix . "users WHERE user_email=%s";
		$res = $this->db->get_results($this->db->prepare($sql, $me['email']), ARRAY_A);

		if (!$res) return false;

		return $this->map_fb_to_wp_user($res[0]['ID']);
	}

	function get_fb_user_from_wp ($wp_uid) {
		$fb_uid = get_user_meta($wp_uid, 'wdfb_fb_uid', true);
		return $fb_uid;
	}

	function map_fb_to_wp_user ($wp_uid) {
		if (!$wp_uid) return false;
		update_user_meta($wp_uid, 'wdfb_fb_uid', $this->fb->getUser());
		return $wp_uid;
	}

	function map_fb_to_current_wp_user () {
		$user = wp_get_current_user();
		$id = $user->ID;
		$this->map_fb_to_wp_user($id);

	}

	function register_fb_user () {
		$uid = $this->get_wp_user_from_fb();
		if ($uid) return $this->map_fb_to_wp_user($uid);

		return $this->create_new_wp_user_from_fb();
	}

	function delete_wp_user ($uid) {
		$uid = (int)$uid;
		if (!$uid) return false;
		$this->db->query("DELETE FROM {$this->db->users} WHERE ID={$uid}");
		$this->db->query("DELETE FROM {$this->db->usermeta} WHERE user_id={$uid}");
	}

	function create_new_wp_user_from_fb () {
		$send_email = false;
		$reg = (array)((isset($this->fb->registration) && $this->fb->registration) ? $this->fb : $this->fb->getSignedRequest());
		$registration = isset($reg['registration']) ? $reg['registration'] : array();
		try {
			$me = $this->fb->api('/me');
		} catch (Exception $e) {
			$me = $registration;
			$send_email = true; // we'll need to notify the user
			$me['id'] = $this->fb->user_id;
		}
		if (!$me) return false;

		$username = $this->_create_username_from_fb_response($me);
		$password = wp_generate_password(12, false);
		$user_id = wp_create_user($username, $password, $me['email']);

		if (is_wp_error($user_id)) {
			$this->log->error(__FUNCTION__, new Exception($user_id->get_error_message()));
			return false;
		} else if ($send_email) {
			wp_new_user_notification($user_id, $password);
			do_action('wdfb-registration_email_sent');
		}

		// Allow others to process the fields
		do_action('wdfb-user_registered', $user_id, $registration);

		// Allow other actions - e.g. posting to Facebook, upon registration
		do_action('wdfb-user_registered-postprocess', $user_id, $me, $registration, $this);

		if (defined('BP_VERSION')) $this->populate_bp_fields_from_fb($user_id, $me); // BuddyPress
		else $this->populate_wp_fields_from_fb($user_id, $me); // WordPress

		return $this->map_fb_to_wp_user($user_id);
	}

	function populate_bp_fields_from_fb ($user_id, $me=false) {
		if (!defined('BP_VERSION')) return true;
		if (!$me) {
			try {
				$me = $this->fb->api('/me');
			} catch (Exception $e) {
				return false;
			}
			if (!$me) return false;
		}

		$this->set_fb_image_as_bp_avatar($user_id, $me);

		$bp_fields = $this->get_bp_xprofile_fields();
		if (is_array($bp_fields)) foreach ($bp_fields as $bpf) {
			$fb_value = $this->data->get_option('wdfb_connect', 'buddypress_registration_fields_' . $bpf['id']);
			if ($fb_value && @$me[$fb_value]) $this->set_bp_xprofile_field($bpf['id'], $user_id, @$me[$fb_value]);
		}
		return true;
	}

	function populate_wp_fields_from_fb ($user_id, $me=false) {
		if (!$me) {
			try {
				$me = $this->fb->api('/me');
			} catch (Exception $e) {
				return false;
			}
			if (!$me) return false;
		}
		$wp_mappings = $this->data->get_option('wdfb_connect', 'wordpress_registration_fields');

		if (is_array($wp_mappings)) foreach($wp_mappings as $map) {
			if (!$map['wp'] || !$map['fb'] || !@$me[$map['fb']]) continue;
			if (is_array(@$me[$map['fb']]) && isset($me[$map['fb']]['name'])) $data = @$me[$map['fb']]['name'];
			else if (is_array(@$me[$map['fb']]) && isset($me[$map['fb']][0])) $data = join(', ', array_map(create_function('$m', 'return $m["name"];'), $me[$map['fb']]));
			else $data = @$me[$map['fb']];
			$data = apply_filters('wdfb-profile_sync-wp-field_value', $data, $map['wp'], $user_id);
			update_user_meta($user_id, $map['wp'], $data);
		}

		return true;
	}

	function get_user_data_for ($for=false) {
		if (!$for) return false;
		try {
			$data = $this->fb->api("/{$for}");
		} catch (Exception $e) {
			return false;
		}
		return $data;
	}

	function get_current_wp_user_data () {
		$user = wp_get_current_user();
		if (!$user || !$user->ID) return false; // User not logged into WP, skip
		$fb_uid = get_user_meta($user->ID, 'wdfb_fb_uid', true);
		return $this->get_user_data_for($fb_uid);
	}

	function get_current_fb_user_data () {
		return $this->get_user_data_for('me');
	}

	function get_current_user_fb_id () {
		$fb_uid = $this->fb->getUser();
		if ($fb_uid) return $fb_uid; // User is logged into FB, use that

		$user = wp_get_current_user();
		if (!$user || !$user->ID) return false; // User not logged into WP, skip

		$fb_uid = get_user_meta($user->ID, 'wdfb_fb_uid', true);
		return $fb_uid;
	}

	function get_pages_tokens ($token=false) {
		$fid = $this->get_current_user_fb_id();
		if (!$fid) return false;

		$token = $token ? $token : $this->get_user_api_token($fid);
		/*
		$token = $token ? "?access_token={$token}" : '';
		try {
			//$ret = $this->fb->api('/' . $fid . '/accounts/');
			$ret = $this->fb->api('/' . $fid . '/accounts/' . $token);
		} catch (Exception $e) {
			return false;
		}
		return $ret;
		*/
		$url = "https://graph.facebook.com/{$fid}/accounts?access_token={$token}";
		$page = wp_remote_get($url, array(
			'method' 		=> 'GET',
			'timeout' 		=> '5',
			'redirection' 	=> '5',
			'user-agent' 	=> 'wdfb',
			'blocking'		=> true,
			'compress'		=> false,
			'decompress'	=> true,
			'sslverify'		=> false
		));

		if(is_wp_error($page)) return false; // Request fail
		if ((int)$page['response']['code'] != 200) return false; // Request fail

		return (array)@json_decode($page['body']);
	}

	function post_on_facebook ($type, $fid, $post, $as_page=false) {
		$type = $type ? $type : 'feed';
		
		$fid = $fid ? $fid : $this->get_current_user_fb_id();
		$token = $this->get_user_api_token($fid);

		// Events sanity check
		if ('events' == $type && (!@$post['start_time'] || !@$post['end_time'])) return false;

		//$post['auth_token'] = $tokens[$fid];
		if (!$token) {
			$tokens = $this->data->get_option('wdfb_api', 'auth_tokens');
			$token = $tokens[$fid];
		}
		$post['access_token'] = $token;
		
		$title = ('feed' == $type) ? @$post['message'] : '';
		$_ap = $as_page ? 'as page' : '';
		$this->log->notice("Posting {$title} to Facebook [{$type}] - [{$fid}] {$_ap} [{$token}].");

		/*
		if ($as_page) {
			try {
				$resp = $this->fb->api($fid, array('auth_token' => $tokens[$fid]));
			} catch (Exception $e) {
				$this->log->notice("Unable to post to Facebook as page.");
			}
			
			// can_post checks perms for posting as user
			if (@$resp['can_post']) $post['access_token'] = $tokens[$fid];
			else $this->log->notice("Unable to post to Facebook as page.");
		}
		*/

		try {
			$ret = $this->fb->api('/' . $fid . '/' . $type . '/', 'POST', $post);
		} catch (Exception $e) {
			$this->log->error(__FUNCTION__, $e);
			return false;
		}
		$this->log->notice("Posting to Facebook finished.");
		return $ret;
	}

	function get_events_for ($for) {
		if (!$for) return false;

		$tokens = $this->data->get_option('wdfb_api', 'auth_tokens');
		$token = $tokens[$for];

		try {
			$res = $this->fb->api('/' . $for . '/events/?auth_token=' . $token);
		} catch (Exception $e) {
			return false;
		}
		return $res;
	}

	function get_albums_for ($for) {
		if (!$for) return false;

		$tokens = $this->data->get_option('wdfb_api', 'auth_tokens');
		$token = $tokens[$for];

		try {
			$res = $this->fb->api('/' . $for . '/albums/?auth_token=' . $token);
		} catch (Exception $e) {
			return false;
		}
		return $res;
	}

	function get_current_albums () {
		$user = wp_get_current_user();
		$fb_accounts = get_user_meta($user->ID, 'wdfb_api_accounts', true);
		$fb_accounts = isset($fb_accounts['auth_accounts']) ? $fb_accounts['auth_accounts'] : false;
		if (!$fb_accounts) return false;
		$albums = array('data'=>array());
		foreach ($fb_accounts as $fid => $label) {
			$res = $this->get_albums_for($fid);
			if (!$res) continue;
			$albums['data'] = array_merge($albums['data'], $res['data']);
		}
		return $albums ? $albums : false;
	}

	function get_album_photos ($aid, $limit=false, $offset=false) {
		if (!$aid) return false;
		$page_size = 25;
		$max_limit = apply_filters('wdfb-albums-max_photos_limit', 
			(defined('WDFB_ALBUMS_MAX_PHOTOS_LIMIT') && WDFB_ALBUMS_MAX_PHOTOS_LIMIT ? WDFB_ALBUMS_MAX_PHOTOS_LIMIT : 200)
		);

		if ($limit && $limit > $page_size) {
			$limit = $limit > $max_limit ? $max_limit : $limit;
			$batch = array();
			for ($i=0; $i<$limit; $i+=$page_size) {
				$batch[] = json_encode(array(
					'method' => 'GET',
					'relative_url' => "/{$aid}/photos/?limit={$page_size}&offset={$i}&fields=id,name,picture,source,height,width,images,link,icon,created_time,updated_time"
				));
			}
			try {
				$res = $this->fb->api('/', 'POST', array('batch' => '[' . implode(',',$batch) . ']'));
			} catch (Exception $e) {
				$this->log->error(__FUNCTION__, $e);
				return false;
			}
			$return = array();
			foreach ($res as $key => $data) {
				if (!$data || !isset($data['body'])) continue;
				$data = json_decode($data['body'],true);
				$return = array_merge($return, $data['data']);
			}
			return array('data' => $return);
		} else {
			$limit = $limit ? '?limit=' . $limit : '';
			try {
				$res = $this->fb->api('/' . $aid . '/photos/' . $limit);
			} catch (Exception $e) {
				$this->log->error(__FUNCTION__, $e);
				return false;
			}
			return $res;
		}
	}

	function get_feed_for ($uid, $limit=false) {
		if (!$uid) return false;
		$limit = $limit ? '?limit=' . $limit : '';

		$tokens = $this->data->get_option('wdfb_api', 'auth_tokens');
		$token = $tokens[$uid];

		$req = $limit ? $limit . '&auth_token=' . $token : '?auth_token=' . $token;

		try {
			$res = $this->fb->api('/' . $uid . '/feed/' . $req);
		} catch (Exception $e) {
			return false;
		}
		return $res;
	}

	function get_item_comments ($for) {
		$uid = $this->get_current_user_fb_id();

		$tokens = $this->data->get_option('wdfb_api', 'auth_tokens');
		$token = $tokens[$uid];

		try {
			$res = $this->fb->api('/' . $for . '/comments/?auth_token=' . $token);
		} catch (Exception $e) {
			$this->log->error(__FUNCTION__, $e);
			return false;
		}
		return $res;
	}

	function _create_username_from_fb_response ($me) {
		if (@$me['first_name'] && @$me['last_name']) {
			$name = preg_replace('/[^a-zA-Z0-9_]+/', '', ucfirst($me['first_name']) . '_' . ucfirst($me['last_name']));
		} else if (isset($me['name'])) {
			$name = $me['name'];
		} else {
			list($name, $rest) = explode('@', $me['email']);
		}
		$username = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '', $name));
		while (username_exists($username)) {
			$username .= rand();
		}
		return apply_filters('wdfb-registration-username', $username, $me);
	}
}