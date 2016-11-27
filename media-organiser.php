<?php
namespace media_organiser_cd;
define(__NAMESPACE__ . '\NS', __NAMESPACE__ . '\\'); // (need to escape \ before ')

/*
Plugin Name: Media Organiser
Plugin URI: https://github.com/StarsoftAnalysis/MediaOrganiser
Description: Allows media items (i.e. attachments) to be organised into folders.  This is ALPHA software -- not ready for serious use yet.
Version: 0.1.0
Author:  ChrisDennis
Author URI: https://github.com/StarsoftAnalysis
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
#debug('mocd running');
#if (defined('DOING_CRON')) { debug('.... DOING_CRON'); }
#if (defined('DOING_AJAX')) { debug('.... DOING_AJAX'); }
#debug('...         , SCRIPT_NAME=' . $_SERVER['SCRIPT_NAME']);
#debug('...         , REQUEST: ', $_REQUEST);
#debug('__FILE__', __FILE__);
#debug('__DIR__', __DIR__);
#debug('pdp(F)', plugin_dir_path(__FILE__));  // like __DIR__ but adds trailing slash

#define_constants();

// Things needed on all admin pages
require_once plugin_dir_path(__FILE__) . 'common.php';

if (defined('DOING_AJAX')) {

    $action = $_REQUEST['action'];
    debug("doing ajax, action = '$action'");
    if (in_array($action, 
        ['mocd_getdir', 'mocd_mkdir', 'mocd_move', 'mocd_delete_empty_dir'])) {
        ob_start();  // Some error (e.g. WP SQL errors) but text onto stdout,
                     // which messes up AJAX, so use ob_start/ob_clean to clear such text.
        require_once plugin_dir_path(__FILE__) . 'relocator_ajax.php';
        // TODO split them up further?
    }

} else {

    // See which URL was used
    $script = $_SERVER['SCRIPT_NAME'];
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

