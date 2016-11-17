<?php
namespace media_organiser_cd;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

function init() {
    debug('init called');
	wp_enqueue_script('jquery');
}

function admin_register_head() {
    debug('started');
	wp_enqueue_style("mocd-style", plugins_url('style.css', __FILE__));
	wp_enqueue_script("media-relocator", plugins_url('media-relocator.js', __FILE__));

    wp_enqueue_script('mocd_jqueryui', 
        plugins_url('/lib/jquery-ui-1.12.1.custom/jquery-ui.js', __FILE__), // TODO use .min.js in prod
        ['jquery']);
    wp_enqueue_style('mocd_jqueryui', 
        plugins_url('/lib/jquery-ui-1.12.1.custom/jquery-ui.css', __FILE__), // TODO use .min.css in prod
        []);
}

// Echo the html for either the left or right pane
function pane_html ($side) {
    debug('pane_html');
	echo '<div class="mocd_wrapper_pane" id="mocd_', $side, '_wrapper">';
	echo '<div class="mocd_box1">';
    echo '<p class=mocd_path id=mocd_', $side, '_path>';
	#echo '<div style="clear:both;"></div>';
    #echo '<div class="mocd_action" id="mocd_', $side, '_action">Action: ';
    #echo '<select name="mocd_', $side, '_select">';
    #echo '<option value="rename">Rename</option>';
    #echo '<option value="move">Move</option>';
    #echo '<option value="delete">Delete</option>';
    #echo '</select>';
    #echo '<button id="mocd_', $side, '_button_go" type="button">Go</button>';
    #echo '</div>';
    echo '<div class="mocd_dir_up  mocd_clickable" id="mocd_', $side, 
        '_dir_up"  title="Show parent folder"><img src="', PLUGIN_URL, '/images/dir_up.png"></div>';
    echo '<div class="mocd_dir_new mocd_clickable" id="mocd_', $side, 
        '_dir_new" title="Create new folder" ><img src="', PLUGIN_URL, '/images/dir_new.png"></div>';
	echo '</div>';
	echo '<div style="clear:both;"></div>';
    // This is the div that gets filled in with the dir listing in JS
	echo '<div class="mocd_pane" id="mocd_', $side, '_pane"></div>';
	echo '</div>';
    
    // HTML for renaming dialog -- initially hidden
    echo '<div id="mocd_', $side, '_rename_dialog" title="Rename File or Folder" style="display: none;">';
    #echo '<p class="validateTips">Enter the new item name:</p>';
    echo '<form><fieldset>';
    echo '<label for="mocd_"', $side, '_rename">New name: </label>';
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

    debug('>>>> mocd main_page()');
	echo '<div class="wrap" id="mocd_wrap">';
	echo '<h2>Media Organiser</h2>';

	echo '<div id="mocd_wrapper_all">';

    pane_html('left');

	echo '<div id="mocd_center_wrapper">';
    echo '<div id="mocd_btn_left2right" class="mocd_clickable" title="Move selected items to the folder on the right">';
    echo '<img src="', PLUGIN_URL, '/images/right.png"></div>';
    echo '<div id="mocd_btn_right2left" class="mocd_clickable" title="Move selected items to the folder on the left">';
    echo '<img src="', PLUGIN_URL, '/images/left.png"></div>';
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
debug('mocd adding actions');
add_action('init', NS . 'init');
// TODO only load JS if called via /wp-admin/upload.php?page=mocd_submenu
#add_action('admin_head', NS . 'admin_register_head');

debug('mocd added all actions');

?>
