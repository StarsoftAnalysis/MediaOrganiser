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

if (is_admin()) {

    // FIXME what's this for?
    #if (!isset($_SERVER['DOCUMENT_ROOT'])) $_SERVER['DOCUMENT_ROOT'] = substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['SCRIPT_NAME']));

    require_once plugin_dir_path(__FILE__) . 'functions.php';
    debug('mocd running');
    if (defined('DOING_CRON')) { debug('.... DOING_CRON'); }
    if (defined('DOING_AJAX')) { debug('.... DOING_AJAX'); }
    debug('...         , SCRIPT_NAME=' . $_SERVER['SCRIPT_NAME']);
    debug('...         , REQUEST: ', $_REQUEST);
    #debug('__FILE__', __FILE__);
    #debug('__DIR__', __DIR__);
    #debug('prf(F)', plugin_dir_path(__FILE__));  // like __DIR__ but adds trailing slash

    define_constants();

    // Things needed on all admin pages
    require_once plugin_dir_path(__FILE__) . 'common.php';


    #_set_time_limit(600); // FIXME really needed? -- gets set to 1800 somewhere


    // Ajax -- don't to do anything if request is:
    // (
    //     [interval] => 60
    //     [_nonce] => eccd1e5923
    //     [action] => heartbeat
    //     [screen_id] => media_page_mocd_submenu
    //     [has_focus] => false
    // )
    if (defined('DOING_AJAX')) {
        debug('doing ajax, so add the actions... ');

        require_once plugin_dir_path(__FILE__) . 'relocator_ajax.php';
        // TODO split them up further?
        // TODO move the add_action into the other files
        add_action('wp_ajax_mocd_getdir',           NS . 'getdir_callback');
        add_action('wp_ajax_mocd_mkdir',            NS . 'mkdir_callback');
        add_action('wp_ajax_new_mocd_move',         NS . 'new_move_callback');
        add_action('wp_ajax_mocd_delete_empty_dir', NS . 'delete_empty_dir_callback');

    } else {

        // See which URL was used

        $script = $_SERVER['SCRIPT_NAME'];
        switch ($script) {
        case '/wp-admin/upload.php':
            // Media menu item -- relocator page
            // e.g. http://dev.fordingbridge-rotary.org.uk/wp-admin/upload.php?page=mocd_submenu
            // Only need this bit if called via wp-admin/upload.php?page=mocd_submenu
            require_once plugin_dir_path(__FILE__) . 'media-relocator.php';
            break;
        case '/wp-admin/options-general.php':
            // e.g. http://dev.fordingbridge-rotary.org.uk/wp-admin/options-general.php?page=mocd_settings_submenu
            require_once plugin_dir_path(__FILE__) . 'settings.php';
            break;
        }

    }

    // Later...
    #require_once plugin_dir_path(__FILE__) . 'media-selector.php';
}

?>
