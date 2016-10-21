<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function mrl_adjpath($adr, $tailslash=false)
{
	$serverpathflag = false;
	if (strstr($adr,"\\\\")==$adr || strstr($adr,"//")==$adr) {$serverpathflag = true;}
	$adr = str_replace('\\','/',$adr);

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


if (!isset($_SERVER['DOCUMENT_ROOT'])) $_SERVER['DOCUMENT_ROOT'] = substr($_SERVER['SCRIPT_FILENAME'], 0, 0-strlen($_SERVER['SCRIPT_NAME']) );


?>
