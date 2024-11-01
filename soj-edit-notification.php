<?php
/*
Plugin Name: SoJ Edit Notification
Version: 1.0
Author: Jeff Johnson
Description: Sends an e-mail to everyone registered at a secified role level whenever the state of a page or post changes.
*/

/**
 * Generate admin panel for plugin
 */
function soj_notify_editor_subpanel()
{
	// Handle setting settings
	if(isset($_POST['action']))
	{
		switch($_POST['action'])
		{
			case 'setGroupSoJNotify':
				update_option('soj_notify_editors',$_POST['soj_notify_role']);
				$message = 'Role updated. All "'.$_POST['soj_notify_role'].'" users will receive edit/deletion notifications.';
				break;
		}
		if(isset($message))
		{
		?>
		<div id="message" class="updated fade">
			<p><?php _e($message); ?></p>
		</div>
		<?php
		}
	}
	?>
    
	<div class="wrap">
		<h2>Edit Notification</h2>
		
		<form method="post" action="" enctype="multipart/form-data">
		 <div><a id="cache"></a></div>
		 <fieldset class="options">
		 	<legend>Choose group to send notifications to:</legend>

			<select name="soj_notify_role">
			<?php wp_dropdown_roles(get_option('soj_notify_editors')); ?>
			</select>

			  <input type="hidden" name="action" value="setGroupSoJNotify" />
			  <input type="submit" name="info_update" value="<?php _e('Set Group', 'soj-ldap'); ?>" />
		 </fieldset>
		</form>
	</div>
	<?php
}

/**
 * Add admin panel for plugin
 */
function soj_notify_editor_panel()
{
    if (function_exists('add_options_page')) {
		add_options_page('SoJ Edit Notification', 'SoJ Edit Notification', 'edit_published_posts', __FILE__, 'soj_notify_editor_subpanel');
    }
 }
 add_action('admin_menu', 'soj_notify_editor_panel');


function get_user_emails($role='')
{
	if(empty($role)) return array();

	global $wpdb;
	$results = $wpdb->get_results("
		SELECT u.user_email
		FROM {$wpdb->usermeta} AS t, {$wpdb->users} AS u
		WHERE t.meta_value LIKE '%".$role."%' AND t.user_id=u.ID
	", ARRAY_N);

	$emails = array();
	if($results)
	{
		foreach($results as $key=>$value)
			foreach($value as $sub_key=>$sub_value)
				if(!empty($sub_value))
					$emails[] = $sub_value;
	}
	return $emails;
}


function soj_send_notification($emails,$subject,$body)
{
	$body = '"'.get_bloginfo('blogname').'" ('.get_bloginfo('siteurl').') has changed!
'.$body;
	foreach($emails as $key=>$value)
		mail($value, $subject, $body);
}


function soj_delete_post_notify($p)
{
	$editors = get_user_emails(get_option('soj_notify_editors'));
	$post = get_post($p);

	// Build e-mail
	$body = '
The '.$post->post_type.': "'.$post->post_title.'" has been deleted.

Manage site: '.get_bloginfo('siteurl').'/wp-admin/edit.php';

	// Send e-mail
	soj_send_notification($editors,'WP Deletion Notification',$body);
}

function soj_edit_post_notify($p)
{
	$editors = get_user_emails(get_option('soj_notify_editors'));
	$post = get_post($p);

	// Build e-mail
	$body = '
The '.$post->post_type.': "'.$post->post_title.'" has been edited.

View '.$post->post_type.': '.get_permalink($p).'
Edit '.$post->post_type.': '.get_bloginfo('siteurl').'/wp-admin/'.$post->post_type.'.php?action=edit&post='.$p;

	// Send e-mail
	soj_send_notification($editors,'WP Edit Notification',$body);
}

add_action('delete_post', 'soj_delete_post_notify');
add_action('edit_post', 'soj_edit_post_notify');

?>