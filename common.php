<?php 
namespace media_organiser_cd;

// Things required on all admin pages

// Media Organiser sub-menu item within Media menu
function plugin_menu () {
	$role = test_mfm_permission();  // FIXME check this
    debug('adding plugin menu, role=', $role);
	if ($role) {
        add_submenu_page(
            'upload.php',
            'Media Organiser',
            'Media Organiser',
            $role,
            'mocd_submenu', #'mrelocator-submenu-handle',
            NS . 'main_page');
	}
}

// Add a link to the config page on the setting menu of wordpress
function settings_menu () {
    debug('started');
	$role = test_mfm_permission();  // FIXME check this
    if ($role) {
        add_submenu_page('options-general.php',
            'Media Organiser Settings',
            'Media Organiser',
            'manage_options',
            'mocd_settings_submenu',
            NS . 'display_settings'
        );
    }
}

add_action('admin_menu', NS . 'plugin_menu');
add_action('admin_menu', NS . 'settings_menu');

?>