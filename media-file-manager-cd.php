<?php
namespace media_file_manager_cd;
define(__NAMESPACE__ . '\NS', __NAMESPACE__ . '\\'); // (need to escape \ before ')

/*
Plugin Name: Media File Manager CD
Plugin URI: https://example.com
Description: You can make sub-directories in the upload directory, and move files into them. At the same time, this plugin modifies the URLs/path names in the database. Also an alternative file-selector is added in the editing post/page screen, so you can pick up media files from the subfolders easily.  (CD's version)
Version: 1.4.2-CD
Author: Atsushi Ueda, Chris Dennis
Author URI:
License: GPL2
*/

if (is_admin()) {

    // FIXME what's this for?
    if (!isset($_SERVER['DOCUMENT_ROOT'])) $_SERVER['DOCUMENT_ROOT'] = substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['SCRIPT_NAME']));

    require_once plugin_dir_path(__FILE__) . 'functions.php';
    #debug('__FILE__', __FILE__);
    #debug('__DIR__', __DIR__);
    #debug('prf(F)', plugin_dir_path(__FILE__));  // like __DIR__ but adds trailing slash

    define_constants();

    require_once plugin_dir_path(__FILE__) . 'media-relocator.php';
    _set_time_limit(600); // FIXME really needed? -- gets set to 1800 somewhere
    add_action('init', NS . 'init');
    add_action('admin_head', NS . 'admin_register_head');
    add_action('admin_menu', NS . 'plugin_menu');
    add_action('admin_menu', NS . 'admin_plugin_menu');
    register_activation_hook(WP_PLUGIN_DIR . '/media-file-manager/media-relocator.php', 'media_file_manager_install');
    #add_action('wp_ajax_mocd_getdir',           NS . 'getdir_callback');
    add_action('wp_ajax_mocd_getdir',           NS . 'getdir_callback');
    add_action('wp_ajax_mocd_mkdir',            NS . 'mkdir_callback');
    add_action('wp_ajax_mocd_rename',           NS . 'rename_callback');
    add_action('wp_ajax_mocd_move',             NS . 'move_callback');
    add_action('wp_ajax_new_mocd_move',         NS . 'new_move_callback');
    add_action('wp_ajax_mocd_delete_empty_dir', NS . 'delete_empty_dir_callback');
    add_action('wp_ajax_mocd_download_log',     NS . 'download_log_callback');
    add_action('wp_ajax_mocd_delete_log',       NS . 'delete_log_callback');

    require_once plugin_dir_path(__FILE__) . 'media-selector.php';
    add_action('wp_ajax_mocd_get_media_list',           NS . 'get_media_list_callback');
    add_action('wp_ajax_mocd_get_media_subdir',         NS . 'get_media_subdir_callback');
    add_action('wp_ajax_mocd_get_image_info',           NS . 'get_image_info_callback');
    add_action('wp_ajax_mocd_get_image_insert_screen',  NS . 'get_image_insert_screen_callback');
    add_action('wp_ajax_mocd_update_media_information', NS . 'update_media_information_callback');
    add_action("admin_head_media_upload_mrlMS_form", NS . "onMediaHead"    ); /* reading js */
    add_action("media_buttons",                      NS . "onMediaButtons" , 20);
    add_action("media_upload_mrlMS",                 NS . "media_upload_mrlMS"                );
    add_filter("admin_footer", NS . "onAddShortCode");
}

?>
