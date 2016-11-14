<?php
namespace media_organiser_cd;

function define_constants () {
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
    debug('ABSPATH:', ABSPATH);       // e.g. /var/www/website/
    debug('PLUGIN_URL:', PLUGIN_URL); // e.g. http://example.com/wp-content/plugins/media-organizer-cd
    debug('UPLOAD_DIR:', UPLOAD_DIR); // e.g. /var/www/website/wp-content/uploads
    debug('UPLOAD_URL:', UPLOAD_URL); // e.g. http://example.com/wp-content/uploads
    // !! need separate UPLOAD_URL_REL and UPLOAD_DIR_REL because separator may not be
    //    '/' in a dir, but always is in an URL.
    debug('UPLOAD_DIR_REL:', UPLOAD_DIR_REL); // e.g. /wp-content/uploads
    debug('UPLOAD_URL_REL:', UPLOAD_URL_REL); // e.g. /wp-content/uploads
}

// Debug to /wp-content/debug.log (see settings in wp-config.php)
function debug (...$args) {
    // FIXME caller keeps being 'include_once'
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

function remove_prefix ($prefix, $text) {
    if (strpos($text, $prefix) === 0) {
        $text = substr($text, strlen($prefix));
    }
    return $text;
}

// FIXME avoid using this
function mrl_adjpath($adr, $tailslash=false) {
    return $adr;
    // the rest is nonsense
	$serverpathflag = false;
    if (strstr($adr, "\\\\") == $adr || 
        strstr($adr, "//"  ) == $adr    ) {
        $serverpathflag = true;
    }
	$adr = str_replace('\\', '/', $adr);

    // WTF?  FIXME
	for ($i=0; $i<999; $i++) {
		if (strstr($adr, "//") === FALSE) {
			break;
		}		
		$adr = str_replace('//','/',$adr);
	}
	$adr = str_replace('http:/','http://',$adr);	
	$adr = str_replace('https:/','https://',$adr);
	$adr = str_replace('ftp:/','ftp://',$adr);
	if ($serverpathflag) {
		$adr = "/" . $adr;
	}
	$adr = rtrim($adr,"/");
	if ($tailslash) {
		$adr .= "/";
	}
	return $adr;
}

// Return a list of files and subdirectories within the given directory.
// sorted alphabetically, and excluding '.' and '..'
function scandir_no_dots ($dir) {
    // scandir gives a warning AND returns false on error, so use '@'
    $listing = @scandir($dir);
    if ($listing === false) {
        return [];
    }
    // Strip the dot directories
    return array_diff ($listing, array('.', '..'));
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
    // FIXME perhaps this doesn't work
    // FIXME deal with scandir returning false and use @
    return count(scandir($dir)) <= 2;  // ignore '.' and '..'
    /*
	$dh = @opendir ($dir);

	if ($dh === false) {
		return true;
	}
	for ($i=0;;$i++) {
		$str = readdir($dh);
		if ($str=="." || $str=="..") {$i--;continue;}
		if ($str === FALSE) break;
		return false;
	}
    return true;
     */
}

/* Just use scandir_no_dots */
// Get a directory listing as an array
function getdir ($dir, &$ret_arr) {
    // FIXME can we avoid @ ? 
	$dh = @opendir($dir);
	if ($dh === false) {
		die("error: cannot open directory (".$dir.")");
    }
    $ret_arr = array();
	for ($i = 0; ; $i++) {
		$str = readdir($dh);
		if ($str == "." || $str == "..") {$i--;continue;}
		if ($str === FALSE) break;
		# changed from this $ret_arr[$i] = $str;
		$ret_arr[] = $str;
    }
    return $ret_arr;
}

// This could be where to fix it  FIXME what?
function get_subdir($dir) {
	$subdir = substr($dir,  strlen(UPLOAD_DIR));
	if (substr($subdir,0,1)=="/" || substr($subdir,0,1)=="\\") {
		$subdir = substr($subdir, 1);
	}
	$subdir = mrl_adjpath($subdir, true);
	if ($subdir=="/") $subdir="";
	return $subdir;
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
