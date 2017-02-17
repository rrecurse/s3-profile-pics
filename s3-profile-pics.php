<?php
/*
Plugin Name: Amazon S3 Profile Images
Description: Replace Gravatar and BuddyPress User Profile images with images from your Amazon S3 account
Version: 1.0
Author: cdebellis
License: None
*/

// Prevent direct access to this file.
if( !defined( 'ABSPATH' ) ) {
        exit( 'You are not allowed to access this file directly.' );
}

// # Kill Gravatar Avatars!
add_filter( 'bp_core_fetch_avatar_no_grav', '__return_true' );

// # Replace BuddyPress Avatar with S3 profile images
add_filter('bp_core_fetch_avatar', 'replace_bp_avatar', 1, 9);

function replace_bp_avatar ($image, $params, $item_id, $avatar_dir, $css_id, $html_width, $html_height, $avatar_folder_url, $avatar_folder_dir) {

	if(!is_admin()) { // # do not run if in admin!
		$displayed_user = get_userdata(bp_displayed_user_id());
		$current_user = wp_get_current_user();
		$users_list =  get_userdata($item_id);

		if($displayed_user->user_login != $current_user->user_nicename) { 

			if($image && $params['width'] == 150) {

				$user = $displayed_user->user_login;

			} else if($image && $params['width'] == 100) {

				$user = $users_list->user_nicename;

			} else if($image && $params['width'] == 96) {

				$user = $current_user->user_nicename;
			} 

		} else {

			if($image) {
				$user = $users_list->user_nicename;
			}

		}
		// # Define the s# bucket name and location
		$bucketname = 'THE-BUCKET-NAME';
		$bucket = 'https://s3.amazonaws.com/'.$bucketname.'/';

		$headers = get_headers($bucket . $user . '.jpg');

		// # if requested profile image is not found, show a default avatar
		$default = ($headers[0] == 'HTTP/1.1 200 OK') ? $bucket . $user . '.jpg' : $bucket .'avatar-blank.png';

		return '<img src="' . $default . '" alt="avatar" class="avatar" ' . $html_width . $html_height . ' />';	
	}
}

// # Remove BuddyPress Activity tab
function bp_remove_nav_tabs() {
	global $bp;
	bp_core_remove_nav_item($bp->activity->slug);
	bp_core_remove_nav_item('activity');
}
add_action('bp_setup_nav', 'bp_remove_nav_tabs', 15);

// # Flush Activity from BuddyPress for user and hide activity block 
// # also added display:none to class class="activity" using 
// # css: #buddypress #item-header-content .activity {display:none;}
function bp_remove_activity() {
	bp_core_remove_data(bp_displayed_user_id());
}
add_action('bp_before_member_header_meta', 'bp_remove_activity', 15);

// # admin profile image / avatar override
function replace_admin_avatar($avatar, $id_or_email, $size, $default, $args) {

		// # Define the s# bucket name and location
		$bucketname = 'THE-BUCKET-NAME';
		$bucket = 'https://s3.amazonaws.com/'.$bucketname.'/';

		$current_user = false;

    	if(is_numeric( $id_or_email)) {

        	$id = (int) $id_or_email;
        	$current_user = get_user_by('id', $id);

        } elseif(is_object($id_or_email)) {

        	if (!empty($id_or_email->user_id ) ) {
            	$id = (int) $id_or_email->user_id;
            	$current_user = get_user_by('id', $id );
        	}
    	}

    	if ( $current_user && is_object( $current_user ) ) {

			$headers = get_headers($bucket . $current_user->data->user_login . '.jpg');

			// # if requested profile image is not found, show a default avatar
			$default = ($headers[0] == 'HTTP/1.1 200 OK') ? $bucket . $current_user->data->user_login . '.jpg' : $bucket .'avatar-blank.png';
			$avatar = '<img src="' . $default . '" alt="avataradmin" class="avatar" width="' . $size . '" />';
		}

		return $avatar;
}

add_filter( 'get_avatar', 'replace_admin_avatar', 1 , 5);

?>
