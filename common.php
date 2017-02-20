<?php 
namespace media_organiser_cd;

// Things required on all admin pages

// Media Organiser sub-menu item within Media menu
function plugin_menu () {
    global $relocate_cap;
    add_submenu_page(
        'upload.php',
        'Media Organiser',
        'Media Organiser',
        $relocate_cap,
        'mocd_submenu', #'mrelocator-submenu-handle',
        NS . 'main_page');
}

// Add a link to the config page on the setting menu of wordpress
function settings_menu () {
    add_submenu_page('options-general.php',
        'Media Organiser Settings',
        'Media Organiser',
        'manage_options',
        'mocd_settings_submenu',
        NS . 'display_settings'
    );
}
add_action('admin_menu', NS . 'plugin_menu');
add_action('admin_menu', NS . 'settings_menu');

# No -- this is too general -- possibly belongs in a theme
#// Filters to make URLs relative
#// TODO need some more of these
#add_filter('wp_get_attachment_thumb_url', NS . 'mocd_relative_url', 10, 2);



?>
