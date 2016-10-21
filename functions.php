<?php
namespace media_file_manager_cd;

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

// FIXME avoid using this
function mrl_adjpath($adr, $tailslash=false) {
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

?>
