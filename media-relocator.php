<?php
namespace media_organiser_cd;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

function enqueue_scripts() {
    debug('started');
	// (jquery is already loaded by WP for admin pages)

#    wp_enqueue_script('mocd_jqueryui', 
#        plugins_url('/lib/jquery-ui-1.12.1.custom/jquery-ui.js', __FILE__), // TODO use .min.js in prod
#        ['jquery']);
#    wp_enqueue_style('mocd_jqueryui', 
#        plugins_url('/lib/jquery-ui-1.12.1.custom/jquery-ui.css', __FILE__), // TODO use .min.css in prod
#        []);
    // These are already in WP! -- but won't have the context or default style of ours...
    wp_enqueue_script('jquery-ui-dialog');
    wp_enqueue_style('wp-jquery-ui-dialog');
    wp_enqueue_script('jquery-ui-progressbar');
    wp_enqueue_style('wp-jquery-ui-progressbar');

	wp_enqueue_style("mocd-style", plugins_url('style.css', __FILE__));
    wp_enqueue_script("mocd-relocator", plugins_url('media-relocator.js', __FILE__));
    global $invalid_itemname_chars, $mocd_plugin_images_url;
    wp_localize_script(
        "mocd-relocator", 
        'mocd_array', 
        [
            'nonce' => wp_create_nonce('mocd_relocator'),
            'invalid_itemname_chars' => $invalid_itemname_chars,
            'plugin_images_url' => $mocd_plugin_images_url,
        ]
    );
}

// Echo the html for either the left or right pane
function pane_html ($side) {
    global $mocd_plugin_images_url;
    #debug('pane_html');
	echo '<div class="mocd_wrapper_pane" id="mocd_', $side, '_wrapper">';
    echo '<div class="mocd_pane_header" id="mocd_', $side, '_header"></div><br>';
	echo '<div class="mocd_pane" id="mocd_', $side, '_pane"></div>';
	echo '</div>'; // mocd_wrapper_pane
    
    // HTML for renaming dialog -- initially hidden  TODO do we really need one for each side?
    echo '<div id="mocd_', $side, '_rename_dialog" title="Rename File or Folder" style="display: none;">';
    #echo '<p class="validateTips">Enter the new item name:</p>';
    echo '<form><fieldset>';
    echo '<label for="mocd_', $side, '_rename">New name: </label>';
    echo '<input type="text" name="mocd_', $side, '_rename" id="mocd_', $side, '_rename" value="">';
    echo '<div id="mocd_', $side, '_rename_error"></div>';
    echo '<input type="hidden" name="mocd_', $side, '_rename_i" id="mocd_', $side, '_rename_i" value="">';
    echo '</fieldset></form></div>';

    // HTML for new folder dialog -- initially hidden
    echo '<div id="mocd_', $side, '_newdir_dialog" title="Create a New Folder" style="display: none;">';
    echo '<form><fieldset>';
    echo '<label for="mocd_', $side, '_newdir">Name: </label>';
    echo '<input type="text" name="mocd_', $side, '_newdir" id="mocd_', $side, '_newdir" value="">';
    echo '<div id="mocd_', $side, '_newdir_error"></div>';
    echo '</fieldset></form></div>';
}

// Run the main Media Organiser admin page
function main_page () {
    global $mocd_plugin_images_url;

    #debug('>>>> mocd main_page()');
	echo '<div class="wrap" id="mocd_wrap">';
	echo '<h2>Media Organiser</h2>';
    echo '<ul class=mocd_blurb>';
    echo '<li>Click on a folder icon to open the folder.</li>';
    echo '<li>Click on the <img src="', $mocd_plugin_images_url, 'dir_up.png" class=mocd_inline_icon alt="up icon"> folder to go up a level. ';
    echo 'Click on the <img src="', $mocd_plugin_images_url, 'dir_new.png" class=mocd_inline_icon alt="new icon"> folder to create a new one.</li>';
    echo '<li>Select files and folders using the tick boxes, and then click one of the big arrows to move ',
        'them to the other side.</li>';
    echo '</ul>';

	echo '<div id="mocd_wrapper_all">';

    pane_html('left');

	echo '<div id="mocd_center_wrapper">';
    echo '<div id="mocd_left_pane_send" class="mocd_clickable" title="Move selected items to the folder on the right">';
    echo '<img src="', $mocd_plugin_images_url, 'right.png" alt="right arrow"></div>';
    echo '<div id="mocd_right_pane_send" class="mocd_clickable" title="Move selected items to the folder on the left">';
    echo '<img src="', $mocd_plugin_images_url, 'left.png" alt="left arrow"></div>';
    echo '</div>';
    // Progress bar -- initially hidden
    echo '<div id="mocd_progressbar" style="display: none;"><div id="mocd_progresslabel">Moving: </div></div>';

    pane_html('right');

    // General purpose message dialogue
    echo '<div id="mocd_message" style="display: none;"></div>';

	echo '</div>'; // div mocd_wrapper_all
	echo '</div>'; // div mocd_wrap
}

// Register stuff:
#debug('mocd adding actions');
// TODO only load JS if called via /wp-admin/upload.php?page=mocd_submenu
add_action('admin_enqueue_scripts', NS . 'enqueue_scripts');

#debug('mocd added all actions');

// vim: set tabstop=4 softtabstop=4 expandtab :
