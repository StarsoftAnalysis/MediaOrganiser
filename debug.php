<?php
namespace media_organiser_cd;

// Debug to /wp-content/debug.log (see https://codex.wordpress.org/WP_DEBUG
// and settings in wp-config.php)
if (!function_exists(NS . 'debug')) {       

    function debug (...$args) {
        if (!WP_DEBUG) {
            return;
        }
        $text = '';
        foreach ($args as $arg) {
            $text .= ' ' . print_r($arg, true);
        }
        $trace = debug_backtrace(false);
        $file = $trace[0]['file'];
        $p = strrpos($file, '/wp-content/');
        if ($p !== False) {
            $file = substr($file, $p + 12);
        }
        $line = $trace[0]['line'];
        $func = $trace[1]['function'];
        if ($func == 'include'      or
            $func == 'include_once' or
            $func == 'require'      or
            $func == 'require_once'    ) {
            $func = '';
        } else {
            $func = '(' . $func . ')'; 
        }
        error_log($file . $func . ':' . $line . $text);
    }

}

?>
