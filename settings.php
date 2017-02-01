<?php 
namespace media_organiser_cd;

// Handle the settings page

function get_role_names () {
    global $wp_roles;   // object with details of all current roles
    $role_names = [];
	foreach ($wp_roles->roles as $key => $value) {
		$role_names[] = $key;
	}
    #debug('wp_roles: ', $wp_roles, $role_names);
    return $role_names;
}

// Display settings page  
function display_settings () {
	$role_names = get_role_names();

	// Store setting information which POST has when this func is called by pressing [Save Change] btn 

    debug($_POST);
    if (isset($_POST['update_setting'])) {

        debug('******************** updating settings');
		echo '<div id="message" class="updated fade"><p><strong>Options saved.</strong></p></div>';
		//update_option('th_linklist_vnum', $_POST['th_linklist_vnum']);
		$roles_ticked = [];
		foreach ($role_names as $role_name) {
			if (!empty($_POST['roles_'.$role_name])) {
				$roles_ticked[] = $role_name;
			}
		}
        debug('......... value ', $roles_ticked);
		update_option('mocd_relocator_roles', implode(',', $roles_ticked));

        /* no selector yet... 
		$roles_val = "";
		for ($i = 0; $i < count($roles); $i++) {
			if (!empty($_POST['roles_sel_'.$roles[$i]])) {
				if ($roles_val != "") $roles_val .= ",";
				$roles_val .= $roles[$i];
			}
		}
        update_option('mocd_selector_roles', $roles_val);
         */

	}

    echo '<div class="wrap">';
	echo '<h2>Media Organiser Configuration</h2>';
    echo '<form method="post" action="', $_SERVER["REQUEST_URI"], '">';

    wp_nonce_field('update-options'); // echoes it too by default
	$accepted_roles = get_option("mocd_relocator_roles", "administrator");
		$disable_set_time_limit = (!(empty($_POST['disable_set_time_limit']))) ? 1 : 0;
		update_option('mediafilemanager_disable_set_time_limit', $disable_set_time_limit);

    /* no selector yet...
    $accepted_roles_selector = get_option("mocd_selector_roles", 
        "administrator,editor,author,contributor,subscriber");
        #$disable_set_time_limit = get_option("mediafilemanager_disable_set_time_limit", 0);
     */

    echo '<table class="form-table">';

	echo '<tr><th>Media Organiser can be used by </th>';
	echo '<td style="text-align: left;">';
	$accepted = explode(",", $accepted_roles);
	foreach ($role_names as $role_name) {
		$ck = (in_array($role_name, $accepted)) ? 'checked' : '';
        echo '<input type="checkbox" name="roles_', $role_name, '" id="roles_', $role_name,
           '" ', $ck, '>', $role_name, '</input><br>', "\n";
	}
    echo '</td></tr>';

    /* no selector yet...
	echo '<tr><th>File Selector can be used by </th>';
	echo '<td style="text-align: left;">';
	$accepted = explode(",", $accepted_roles_selector);
	for ($i = 0; $i < count($roles); $i++) {
		$key = $roles[$i];
		$ck = "";
		for ($j = 0; $j < count($accepted); $j++) {
			if ($key == $accepted[$j]) {
				$ck = "checked";
				break;
			}
		}
        echo '<input type="checkbox" name="roles_sel_' . $key . '" id="roles_sel_' . 
            $key . '" ' . $ck . '>' . $key . '</input><br>' . "\n";
	}
    echo '</td></tr>';
    */ 

    echo '</table>';
	echo '<input type="hidden" name="action" value="update">';
	echo '<p class="submit">'; // A WP-ism, apparently
	echo '<input type="submit" name="update_setting" class="button-primary" value="', _e('Save Changes'), '">';
	echo '</p>';
	echo '</form>';
    echo '</div>';
}

