<?php 
namespace media_organiser_cd;

// Display settings page  
function display_settings () {

    echo '<div class="wrap">';
	echo '<h2>Media Organiser Configuration</h2>';

    if (!current_user_can('manage_options')) {
        echo '<div id="message" class="error"><p><strong>You do not have permission to view this page.</strong></p></div>';
        echo '</div>';
        wp_die();
    };

    global $wp_roles;   // object with details of all current roles
    global $relocate_cap;

    $nonce_action = 'mocd update settings';

    #debug($_REQUEST);
    if (isset($_REQUEST['update_setting'])) {
        check_admin_referer($nonce_action);
		$roles_ticked = [];
        foreach ($wp_roles->roles as $name => $role_array) {
            $role = get_role($name);  // why do we have to get it this way?
			if (!empty($_REQUEST['roles_' . $name])) {
				$role->add_cap($relocate_cap);
            } else {
                $role->remove_cap($relocate_cap);
            }
		}
		echo '<div id="message" class="updated fade"><p><strong>Options saved.</strong></p></div>';
	}

    echo '<form method="post" action="', $_SERVER["REQUEST_URI"], '">';
    wp_nonce_field($nonce_action); // echoes it too by default

    echo '<table class="form-table">';

	echo '<tr><th>Media Organiser can be used by </th>';
	echo '<td style="text-align: left;">';
	foreach ($wp_roles->roles as $name => $role_array) {
        // $name is slug-like name, $role_array['name'] is pretty name
        $role = get_role($name);  // why do we have to get it this way?
        $checked = ($role->has_cap($relocate_cap)) ? 'checked' : '';
        echo '<input type="checkbox" name="roles_', $name, '" id="roles_', $name,
           '" ', $checked, '>', $role_array['name'], '</input><br>', "\n";
	}
    echo '</td></tr>';

    // TODO (later) -- similar processing for 'mocd_selector' capability

    echo '</table>';
	echo '<p class="submit">'; // A WP-ism, apparently
	echo '<input type="submit" name="update_setting" class="button-primary" value="', _e('Save Changes'), '">';
	echo '</p>';
	echo '</form>';
    echo '</div>';
}

