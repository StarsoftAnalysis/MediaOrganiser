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

?>
