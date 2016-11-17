<?php
namespace media_organiser_cd;

// Debug to /wp-content/debug.log (see https://codex.wordpress.org/WP_DEBUG
// and settings in wp-config.php)
function debug (...$args) {
    if (!WP_DEBUG) {
        return;
    }
    list(, $caller) = debug_backtrace(false);
    #error_log('debug caller: ' . print_r($caller, true));
    $function = isset($caller['function']) ? $caller['function'] : '<unknown function>';
    $line = isset($caller['line']) ? ':'.$caller['line'] : '';
    $text = '';
    foreach ($args as $arg) {
        $text .= ' ' . print_r($arg, true);
    }
    error_log($function . $line . $text);
}

function define_constants () {
    debug('define_constants');
    // Directories and URLs -- none of these will end in '/'
    #    ABSPATH /var/www/rotarywp-dev/
    #   __FILE__ /var/www/rotarywp-dev/wp-content/plugins/media-file-manager-cd/functions.php  
    #       d(F) /var/www/rotarywp-dev/wp-content/plugins/media-file-manager-cd                    
    #    b(d(F)) media-file-manager-cd                                                          
    define('PLUGIN_URL', plugins_url() . "/" . basename(dirname(__FILE__)));  // used for URLs of icon images etc.
    $upload = wp_upload_dir();
    if ($upload['error']) {
        debug('failed to get WP upload directory: ' . $upload['error']);
        // guess:
        define('UPLOAD_DIR', '/tmp/'); // ought to be e.g. '/var/www/website/wp-content/uploads');
        define('UPLOAD_URL', '/wp-content/uploads'); 
        define('UPLOAD_REL', '/wp-content/uploads');
    } else {
        define('UPLOAD_DIR', $upload['basedir']);
        define('UPLOAD_URL', $upload['baseurl']);
        #define('UPLOAD_REL', _wp_relative_upload_path(UPLOAD_DIR));
        #define('UPLOAD_REL', '/' . str_replace(ABSPATH, '', UPLOAD_DIR));
        define('UPLOAD_DIR_REL', DIRECTORY_SEPARATOR . remove_prefix(ABSPATH, UPLOAD_DIR));
        define('UPLOAD_URL_REL', '/' . remove_prefix(ABSPATH, UPLOAD_DIR));
    }
    #debug('ABSPATH:', ABSPATH);       // e.g. /var/www/website/
    #debug('PLUGIN_URL:', PLUGIN_URL); // e.g. http://example.com/wp-content/plugins/media-organizer-cd
    #debug('UPLOAD_DIR:', UPLOAD_DIR); // e.g. /var/www/website/wp-content/uploads
    #debug('UPLOAD_URL:', UPLOAD_URL); // e.g. http://example.com/wp-content/uploads
    // !! need separate UPLOAD_URL_REL and UPLOAD_DIR_REL because separator may not be
    //    '/' in a dir, but always is in an URL.
    #debug('UPLOAD_DIR_REL:', UPLOAD_DIR_REL); // e.g. /wp-content/uploads
    #debug('UPLOAD_URL_REL:', UPLOAD_URL_REL); // e.g. /wp-content/uploads
}

function remove_prefix ($prefix, $text) {
    if (strpos($text, $prefix) === 0) {
        $text = substr($text, strlen($prefix));
    }
    return $text;
}

// test permission for accessing media file manager
// Returns one of the matching roles, or false
function test_mfm_permission () {
	$current_user = wp_get_current_user();
    #debug('test_mfm_permission, cu=', $current_user);
# FIXME why test this? why does it fail for webmaster?
#    if (!($current_user instanceof WP_User)) {
#        debug('... not a WP_User');
#        return FALSE;
#    }
	$roles = $current_user->roles;
    $accepted_roles = get_option("mediafilemanager_accepted_roles", "administrator"); // 2nd arg is default, used if option not found
    #debug('... accepted roles = ', $accepted_roles);
	$accepted = explode(",", $accepted_roles);
    // Return one of the matching roles
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
    #debug("turl: '$fname' '$mimetype' '$id'");
    if (isimage($fname, $mimetype)) {
        if ($id && $url = wp_get_attachment_thumb_url($id)) {
            #debug('turl returning: ', $url);
            return $url;
        } 
        #debug('turl returning: ', UPLOAD_URL . '/' . $fname);
        return UPLOAD_URL . '/' . $fname;
    } elseif (isaudio($fname, $mimetype)) {
        return PLUGIN_URL . "/images/audio.png";
    } elseif (isvideo($fname, $mimetype)) {
        return PLUGIN_URL . "/images/video.png";
    }
    return PLUGIN_URL . "/images/file.png";
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
?>
