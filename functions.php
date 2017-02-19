<?php
namespace media_organiser_cd;

function remove_prefix ($prefix, $text) {
    if (strpos($text, $prefix) === 0) {
        $text = substr($text, strlen($prefix));
    }
    return $text;
}

// Make an URL relative
function relative_url ($absurl) {
    #debug('relative_url  abs=', $absurl);
    $relurl = remove_prefix(site_url(), $absurl);
    #debug('relative_url  rel=', $relurl);
    return $relurl;
}

// --------------------------------------------------------------------------------------

// Set up globals (within our namespace)
// Directories and URLs
#    ABSPATH /var/www/rotarywp-dev/
#   __FILE__ /var/www/rotarywp-dev/wp-content/plugins/media-organiser/functions.php  
#       d(F) /var/www/rotarywp-dev/wp-content/plugins/media-organiser
#    b(d(F)) media-organiser

# From https://codex.wordpress.org/Function_Reference/plugins_url
# home_url()        Home URL                                 http://www.example.com
# site_url()        Site directory URL                       http://www.example.com or http://www.example.com/wordpress
# admin_url()       Admin directory URL                      http://www.example.com/wp-admin
# includes_url()    Includes directory URL                   http://www.example.com/wp-includes
# content_url()     Content directory URL                    http://www.example.com/wp-content
# plugins_url()     Plugins directory URL                    http://www.example.com/wp-content/plugins
# theme_url()       Themes directory URL (#18302)            http://www.example.com/wp-content/themes
# wp_upload_dir()   Upload directory URL (returns an array)  http://www.example.com/wp-content/uploads

#debug('1', plugins_url());
#debug('2', plugins_url('functions.php'));
#debug('3', plugins_url('fred.jpg', __FILE__));
#debug('4', plugins_url('', __FILE__));
#debug('ABSPATH:', ABSPATH);

$plugin_dir = basename(dirname(__FILE__));  // e.g. 'media-organiser'   ?? NEEDED?
$plugin_url = remove_prefix(site_url(), plugins_url('', __FILE__)) . '/';  // e.g. '/wp-content/plugins/media-organiser/' -- relative!
$plugin_images_url = $plugin_url . 'images/';
#debug('plugin_url: ', $plugin_url);
#debug('plugin_images_url: ', $plugin_images_url);

$upload = wp_upload_dir();
if ($upload['error']) {
    debug('failed to get WP upload directory: ' . $upload['error']);
    # TODO message to the user?
    return;
}
$upload_dir = $upload['basedir'];
$upload_url = $upload['baseurl'];
// !! need separate UPLOAD_URL_REL and $upload_dir_REL because separator may not be
//    '/' in a dir, but always is in an URL.
// FIXME should be using relative urls!!
$upload_dir_rel = DIRECTORY_SEPARATOR . remove_prefix(ABSPATH, $upload_dir);
$upload_url_rel = '/' . remove_prefix(ABSPATH, $upload_url); // always '/' in an URL

// Check if 'name' contains any characters that are invalid
// for a file or folder (not path) name
// on the current operating system
// NOTE This is not complete -- we'll have to rely on trapping
//      errors for obscurely-invalid filenames
// SEE https://msdn.microsoft.com/en-us/library/aa365247
function invalid_itemname_regex () {
    $chars = "/\0";   // default is Linux-like OS
    $class = '[:cntrl:]';
    if (PHP_OS == 'Darwin') { // MacOS
        $chars = ':';
    } elseif (preg_match('/^win/i', PHP_OS)) { // Windows
        $chars = '\/<>:"\'|?*';
    }
    $regex = '/[' . preg_quote($chars, '/') . $class . ']/';
    return [$chars, $regex];
}
list($invalid_itemname_chars, $invalid_itemname_regex) = invalid_itemname_regex();

function reserved_filenames () {
    $names = ['.', '..'];
    if (preg_match('/^win/i', PHP_OS)) {
        $names = array_merge($names, ['CON', 'PRN', 'AUX', 'NUL', 
            'COM1', 'COM2', 'COM3', 'COM4', 'COM5', 'COM6', 'COM7', 'COM8', 'COM9', 
            'LPT1', 'LPT2', 'LPT3', 'LPT4', 'LPT5', 'LPT6', 'LPT7', 'LPT8', 'LPT9']);
    }
    return $names;
}
$reserved_filenames = reserved_filenames();

// Names for plugin-specific capabilities
$relocate_cap = 'mocd_relocate';
$select_cap   = 'mocd_select';

// --------------------------------------------------------------------------------------

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
    global $upload_url, $plugin_images_url;
    #debug("turl: '$fname' '$mimetype' '$id'");
    if (isimage($fname, $mimetype)) {
        if ($id && $url = wp_get_attachment_thumb_url($id)) {
            // that returns a proper thumbnail if there is one,
            // otherwise a bigger image.
            // And the squareness of the thumbnail depends on site settings.
            $url = relative_url($url);
            #debug('turl 1 returning: ', $url);
            return $url;
        } 
        #debug('turl 2 returning: ', $upload_url_rel . '/' . $fname);
        return $upload_url_rel . '/' . $fname;
    } elseif (isaudio($fname, $mimetype)) {
        return $plugin_images_url . "audio.png";
    } elseif (isvideo($fname, $mimetype)) {
        return $plugin_images_url . "video.png";
    }
    return $plugin_images_url . "file.png";
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

// Get a request value, no sanitization
function get_request ($key) {
    return empty($_REQUEST[$key]) ? '' : $_REQUEST[$key];
}

// Get a file or directory base name from the request and sanitize it.
// Nasty filenames, such as with leading or trailing blanks, can exist
// in Linux, but they really shouldn't.  This will prevent e.g.
// a directory called 'fred   ' from being deleted.  C'ést la vie.
function get_basename ($key) {
    $filename = empty($_REQUEST[$key]) ? '' : $_REQUEST[$key];
    $sanitized = sanitize_file_name($filename);
    #debug("filename='$filename'  sanitized='$sanitized'");
    return $sanitized;
}

// Why do I have to invent this?
function sanitize_dir_name ($dir) {
    #debug('checking', $dir);
    $filenames = explode('/', $dir);
    $newfilenames = [];
    foreach ($filenames as $fn) {
        $newfilenames[] = sanitize_file_name($fn);
    }
    #debug("filenames ", $filenames, ' becomes ', $newfilenames);
    return implode('/', $newfilenames);
}

// Get a dir name and sanitize it
function get_dirname ($key) {
    $dirname = empty($_REQUEST[$key]) ? '' : $_REQUEST[$key];
    $sanitized = sanitize_dir_name($dirname);
    #debug("dirname '$dirname' becomes '$sanitized'");
    return $sanitized;
}

function invalid_item_name ($name) {
    global $reserved_filenames, $invalid_itemname_regex;
    $name = trim($name);
    if (in_array(strtoupper($name), $reserved_filenames)) {
        return true;
    }
    $regex = $invalid_itemname_regex;
    #debug('checking name ' . $name . ' against regex ' . $regex);
    return preg_match($regex, $name);
}

