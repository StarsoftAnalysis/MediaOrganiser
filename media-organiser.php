<?php
namespace media_organiser_cd;
define(__NAMESPACE__ . '\NS', __NAMESPACE__ . '\\'); // (need to escape \ before ')

/*
Plugin Name: Media Organiser
Plugin URI: https://example.com
Description: You can make sub-directories in the upload directory, and move files into them. At the same time, this plugin modifies the URLs/path names in the database. Also an alternative file-selector is added in the editing post/page screen, so you can pick up media files from the subfolders easily.  (CD's version)
Version: 1.4.2-mocd
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

    // Later...
    #require_once plugin_dir_path(__FILE__) . 'media-selector.php';
}

?>
