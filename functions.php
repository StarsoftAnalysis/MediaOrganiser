<?php
namespace media_organiser_cd;

function remove_prefix ($prefix, $text) {
    if (strpos($text, $prefix) === 0) {
        $text = substr($text, strlen($prefix));
    }
    return $text;
}

// --------------------------------------------------------------------------------------

// Set up globals
// Directories and URLs -- none of these will end in '/'
#    ABSPATH /var/www/rotarywp-dev/
#   __FILE__ /var/www/rotarywp-dev/wp-content/plugins/media-file-manager-cd/functions.php  
#       d(F) /var/www/rotarywp-dev/wp-content/plugins/media-file-manager-cd                    
#    b(d(F)) media-file-manager-cd                                                          
$plugin_url = plugins_url() . "/" . basename(dirname(__FILE__));  // used for URLs of icon images etc.
$upload = wp_upload_dir();
if ($upload['error']) {
    debug('failed to get WP upload directory: ' . $upload['error']);
    return;
}
$upload_dir = $upload['basedir'];
$upload_url = $upload['baseurl'];
// !! need separate UPLOAD_URL_REL and $upload_dir_REL because separator may not be
//    '/' in a dir, but always is in an URL.
// TODO _url_rel might not be needed after all
$upload_dir_rel = DIRECTORY_SEPARATOR . remove_prefix(ABSPATH, $upload_dir);
$upload_url_rel = '/' . remove_prefix(ABSPATH, $upload_dir);

// --------------------------------------------------------------------------------------

// test permission for accessing media file manager
// Returns one of the matching roles, or false
function test_mfm_permission () {
	$current_user = wp_get_current_user();
    if (empty($current_user)) {
        debug('failed to get current_user');
        return false;
    }
	$roles = $current_user->roles;
    $accepted_roles = get_option("mocd_relocator_roles", "administrator"); // 2nd arg is default, used if option not found
    debug('roles = ', $roles);
    debug('... accepted roles = ', $accepted_roles);
	$accepted = explode(",", $accepted_roles);
    // Return one of the matching roles    TODO just return true or false
    $matches = array_intersect($accepted, $roles);
    #debug('... matches = ', $matches);
    if ($matches) {
        return array_pop($matches);
        # $matches[0];  doesn't work 'cos first element might be $matches[6]
    }
    return FALSE;
}

// Return a list of files and subdirectories within the given directory.
// sorted alphabetically, and excluding '.' and '..'
function scandir_no_dots ($dir) {
    $listing = scandir($dir);
    if ($listing === false) {
        return [];
    }
    // Strip the dot directories
    return array_diff($listing, ['.', '..']);
}

// Return a list of subdirectories within the given directory
function subdirs ($dir) {
    $items = scandir_no_dots($dir);
    $listing = [];
    foreach ($items as $item) {
        if (is_dir($dir . '/' . $item)) {
            $listing[] = $item;
        }
    }
    return $listing;
}

# Return a request (get or post) data field, or '' if not set
function request_data ($field) {
    if (!isset($_REQUEST[$field])) {
        return '';
    }
    return $_REQUEST[$field];
}

// Provide icons for those without thumbnails
// TODO: pdfs?
function thumbnail_url ($fname, $mimetype = '', $id = null) {
    global $upload_url, $plugin_url;
    #debug("turl: '$fname' '$mimetype' '$id'");
    if (isimage($fname, $mimetype)) {
        if ($id && $url = wp_get_attachment_thumb_url($id)) {
            #debug('turl returning: ', $url);
            return $url;
        } 
        #debug('turl returning: ', UPLOAD_URL . '/' . $fname);
        return $upload_url . '/' . $fname;
    } elseif (isaudio($fname, $mimetype)) {
        return $plugin_url . "/images/audio.png";
    } elseif (isvideo($fname, $mimetype)) {
        return $plugin_url . "/images/video.png";
    }
    return $plugin_url . "/images/file.png";
}

function isimage ($fname, $mimetype = '') {
    if ($mimetype) {
        return strpos($mimetype, 'image/') === 0;
    }
    return preg_match('/\.(jpg|jpeg|gif|png|bmp|tif|tiff|dng|pef|cr2)$/i', $fname);
}

function isaudio ($fname, $mimetype = '') {
    if ($mimetype) {
        return strpos($mimetype, 'audio/') === 0;
    }
    return preg_match('/\.(wav|mp3|m3u|wma|ra|ram|aac|flac|ogg|opus)$/i', $fname);
}

function isvideo ($fname, $mimetype = '') {
    if ($mimetype) {
        return strpos($mimetype, 'video/') === 0;
    }
    return preg_match('/\.(mp4|wma|avi|flv|ogv|divx|mov|3gp)$/i', $fname);
}

function isEmptyDir($dir) {
    $list = scandir($dir);
    if ($list === false) {
        // It's not a directory, so it can't be an empty one
        return false;
    }
    return count($list) <= 2;  // ignore '.' and '..'
}

function last_error_msg () {
    $details = error_get_last();
    if ($details) {
        return $details['message'];
    }
    return '';
}

function get_post ($key) {
    $value = '';
    if (isset($_POST[$key])) {
        $value = $_POST[$key];
    }
    return stripslashes($value);
}

