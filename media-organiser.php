<?php
namespace media_organiser_cd;
define(__NAMESPACE__ . '\NS', __NAMESPACE__ . '\\'); // (need to escape \ before ')

/*
Plugin Name: Media Organiser
Plugin URI: https://wordpress.org/plugins/media-organiser/
Description: Allows media items (i.e. attachments) to be organised into folders.
Version: 0.1.5
Author:  ChrisDennis
Author URI: https://profiles.wordpress.org/chrisdennis#content-plugins
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

require_once plugin_dir_path(__FILE__) . 'debug.php';

// This is an admin-only plugin, with no need for cron
if (!is_admin() || defined('DOING_CRON')) {
    debug('mocd: !is_admin(), or doing cron -- exiting');
    return;  // NOT exit or die!
}

// Define globals and load functions
require_once plugin_dir_path(__FILE__) . 'functions.php';

// Things needed on all admin pages
require_once plugin_dir_path(__FILE__) . 'common.php';

// This seems to have to be in the main file.
// On activation, give administrators permission to use the features
function activate() {
    global $relocate_cap, $select_cap;
    $role = get_role('administrator');
    debug('adding capabilities for admin', $relocate_cap, $select_cap);
    $role->add_cap($relocate_cap);
    $role->add_cap($select_cap);
}
#debug('registering activation hook ', NS . 'activate');
register_activation_hook(__FILE__, NS . 'activate');

if (defined('DOING_AJAX')) {

    $action = $_REQUEST['action'];
    debug("doing ajax, action = '$action'");
    if (in_array($action, 
        ['mocd_getdir', 'mocd_mkdir', 'mocd_move', 'mocd_delete_empty_dir'])) {
        ob_start();  // Some errors (e.g. WP SQL errors) put text onto stdout,
        // which messes up AJAX, so use ob_start/ob_clean to clear such text.
        // (The ob_clean is in relocator_ajax.php)
        require_once plugin_dir_path(__FILE__) . 'relocator_ajax.php';
        // TODO split them up further?
    }

} else {

    // See which URL was used
    $script = $_SERVER['SCRIPT_NAME'];
    debug('script: ', $script);
    switch ($script) {
    case '/wp-admin/upload.php':
        // Media menu item -- relocator page
        // e.g. http://example.com/wp-admin/upload.php?page=mocd_submenu
        // Only need this bit if called via wp-admin/upload.php?page=mocd_submenu
        require_once plugin_dir_path(__FILE__) . 'media-relocator.php';
        break;
    case '/wp-admin/options-general.php':
        // e.g. http://example.com/wp-admin/options-general.php?page=mocd_settings_submenu
        require_once plugin_dir_path(__FILE__) . 'settings.php';
        break;
    }


    // Later...
    #require_once plugin_dir_path(__FILE__) . 'media-selector.php';
}

// vim: set tabstop=4 softtabstop=4 expandtab :
