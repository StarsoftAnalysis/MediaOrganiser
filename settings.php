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

    // Handle press of 'Settings' button
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

    // Handle press of 'Tidy' button
    if (isset($_REQUEST['tidy_attachments'])) {
        check_admin_referer($nonce_action);
        // This bit of code from https://wordpress.stackexchange.com/questions/15467/remove-missing-image-attachments
        $posts = get_posts(['post_type' => 'attachment', 'numberposts' => -1]);
        $count = 0;
        foreach ($posts as $post) {
            $file = get_attached_file($post->ID);
            if (!file_exists($file)) {
                #debug("deleting post with ID", $file->ID);
                wp_delete_post($post->ID, false);
                $count += 1;
            }
        }
        echo '<div id="message" class="updated fade"><p><strong>' . $count . ' attachments tidied.</strong></p></div>';
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

    // Extra section for tidying attachments.  Here, because where else? -- ought to be in the Tools menu really

    // First, see if any such attachments exist.
    $posts = get_posts(['post_type' => 'attachment', 'numberposts' => -1]);
    $count = 0;
    $orphans = [];
    foreach ($posts as $post){
        $file = get_attached_file($post->ID);
        if (!file_exists($file)) {
            $orphans[] = $post->ID;
            $count += 1;
        }
    }

    echo '<hr><h2>Tidy File Attachments with Missing Files</h2>';
    echo '<p>Click the button to check for file attachments whose associated images or other files are missing from the file system.  This may be required if the files have been deleted directly rather than through WordPress.';
    if ($count == 0) {
        echo '<p>There are currently no attachments with missing files.';
    } else {
        echo "<p>There are currently $count attachments with missing files. Their IDs are: ";
        foreach ($orphans as $orphan) {
            echo ' ', $orphan;
        }
    }
    echo '<form method="post" action="', $_SERVER["REQUEST_URI"], '">';
    wp_nonce_field($nonce_action); // echoes it too by default
    echo '<p class="submit">'; // A WP-ism, apparently
    echo "<input type=submit name=tidy_attachments class=button-primary value='", _e('Tidy'), "' ", (($count == 0) ? 'disabled' : ''), ">";
    echo " (There won't be any confirmation -- it will just happen!)";
    echo '</form>';

    echo '</div>';
}

// vim: set tabstop=4 softtabstop=4 expandtab :
