<?php 
namespace media_organiser_cd;

// Handle the settings page

function get_roles () {
    global $wp_roles;
    debug('wp_roles: ', $wp_roles);
    $roles = [];
	foreach ($wp_roles->roles as $key => $value) {
		$roles[] = $key;
	}
    return $roles;
}

// Display settings page  
function display_settings () {
	$roles = get_roles();

	// Store setting information which POST has when this func is called by pressing [Save Change] btn 
	if (isset($_POST['update_setting'])) {
		echo '<div id="message" class="updated fade"><p><strong>Options saved.</strong></p></div>';
		//update_option('th_linklist_vnum', $_POST['th_linklist_vnum']);
		$roles_val = "";
		for ($i = 0; $i <count($roles); $i++) {
			if (!empty($_POST['roles_'.$roles[$i]])) {
				if ($roles_val != "") $roles_val .= ",";
				$roles_val .= $roles[$i];
			}
		}
		update_option('mediafilemanager_accepted_roles', $roles_val);

		$roles_val = "";
		for ($i = 0; $i < count($roles); $i++) {
			if (!empty($_POST['roles_sel_'.$roles[$i]])) {
				if ($roles_val != "") $roles_val .= ",";
				$roles_val .= $roles[$i];
			}
		}
		update_option('mediafilemanager_accepted_roles_selector', $roles_val);

		$disable_set_time_limit = (!(empty($_POST['disable_set_time_limit']))) ? 1 : 0;
		update_option('mediafilemanager_disable_set_time_limit', $disable_set_time_limit);

	}

    echo '<div class="wrap">';
	echo '<h2>Media Organiser Configuration</h2>';
    echo '<form method="post" action="', $_SERVER["REQUEST_URI"], '">';

    wp_nonce_field('update-options'); // echoes it too by default
	$accepted_roles = get_option("mediafilemanager_accepted_roles", "administrator");
    $accepted_roles_selector = get_option("mediafilemanager_accepted_roles_selector", 
        "administrator,editor,author,contributor,subscriber");
	#$disable_set_time_limit = get_option("mediafilemanager_disable_set_time_limit", 0);

    echo '<table class="form-table">';
	echo '<tr><th>Media Organiser can be used by </th>';
	echo '<td style="text-align: left;">';

	$accepted = explode(",", $accepted_roles);
	for ($i = 0; $i < count($roles); $i++) {
		$key = $roles[$i];
		$ck = "";
		for ($j = 0; $j < count($accepted); $j++) {
			if ($key == $accepted[$j]) {
				$ck = "checked";
				break;
			}
		}
        echo '<input type="checkbox" name="roles_' . $key . '" id="roles_' . 
            $key . '" ' . $ck . '>' . $key . '</input><br>' . "\n";
	}

    echo '</td></tr>';
	echo '<th>File Selector can be used by </th>';
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
    echo '</table>';
	echo '<input type="hidden" name="action" value="update">';
	echo '<p class="submit">'; // A WP-ism, apparently
	echo '<input type="submit" name="update_setting" class="button-primary" value="', _e('Save Changes'), '">';
	echo '</p>';
	echo '</form>';
    echo '</div>';
}

?>
