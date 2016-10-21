<?php
/*
Plugin Name: Media File Manager
Plugin URI: http://tempspace.net/plugins/?page_id=111
Description: You can make sub-directories in the upload directory, and move files into them. At the same time, this plugin modifies the URLs/path names in the database. Also an alternative file-selector is added in the editing post/page screen, so you can pick up media files from the subfolders easily.
Version: 1.4.2
Author: Atsushi Ueda
Author URI: http://tempspace.net/plugins/
License: GPL2
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

_set_time_limit(600);

if (!is_admin()) {
	return;
}

define("MLOC_DEBUG", 0);

function dbg2($str){$fp=fopen("/tmp/log.txt","a");fwrite($fp,$str . "\n");fclose($fp);}

include 'set_document_root.php';
$mrelocator_plugin_URL = mrl_adjpath(plugins_url() . "/" . basename(dirname(__FILE__)));
$mrelocator_uploaddir_t = wp_upload_dir();
$mrelocator_uploaddir = mrl_adjpath($mrelocator_uploaddir_t['basedir'], true);
$mrelocator_uploadurl = mrl_adjpath($mrelocator_uploaddir_t['baseurl'], true);


function mrelocator_init() {
	wp_enqueue_script('jquery');
}
add_action('init', 'mrelocator_init');

function mrelocator_admin_register_head() {
	wp_enqueue_style( "mfm-style", plugins_url( 'style.css', __FILE__ ));
}
add_action('admin_head', 'mrelocator_admin_register_head');

// test permission for accessing media file manager
function test_mfm_permission()
{
	$current_user = wp_get_current_user();
	if ( !($current_user instanceof WP_User) ) return FALSE;
	$roles = $current_user->roles;
	$accepted_roles = get_option("mediafilemanager_accepted_roles", "administrator");
	$accepted = explode(",", $accepted_roles);

	for ($i=0; $i<count($accepted); $i++) {
		for ($j=0; $j<count($roles); $j++) {
			if ($accepted[$i] == $roles[$j]) {
				return $roles[$j];
			}
		}
	}
	return FALSE;
}

// add a setting menu
add_action('admin_menu', 'mrelocator_plugin_menu');
function mrelocator_plugin_menu()
{
	$role = test_mfm_permission();
	if ($role) {
		/*  add a configuration screen  */
		add_submenu_page('upload.php', 'Media File Manager', 'Media File Manager', $role, 'mrelocator-submenu-handle', 'mrelocator_magic_function'); 
	}
}


/*  show a configuration screen  */
function mrelocator_magic_function()
{
	global $mrelocator_plugin_URL;
	global $mrelocator_uploadurl;
	global $mrelocator_uploaddir_t;

	wp_enqueue_script( "media-relocator", plugins_url( 'media-relocator.js', __FILE__ ));
	?>
	<script type="text/javascript"> mrloc_url_root='<?php echo $mrelocator_uploadurl;?>';</script>

	<div class="wrap">
		<h2>Media File Manager</h2>

		<?php if ($mrelocator_uploaddir_t['error']!="") {echo "<div class=\"error\"><p>".$mrelocator_uploaddir_t['error']."</p></div>";die();}?>

		<div id="mrl_wrapper_all">
			<div class="mrl_wrapper_pane" id="mrl_left_wrapper">
				<div class="mrl_box1">
					<input type="textbox" class="mrl_path" id="mrl_left_path" s>
					<div style="clear:both;"></div>
					<div class="mrl_dir_up" id="mrl_left_dir_up"><img src="<?php echo $mrelocator_plugin_URL."/images/dir_up.png";?>"></div>
					<div class="mrl_dir_up" id="mrl_left_dir_new"><img src="<?php echo $mrelocator_plugin_URL."/images/dir_new.png";?>"></div>
					<div class="mrl_select_all" ><input class="mrl_select_all_button" id="mrl_left_select_all" type="button" value="Select All"></div>
					<div class="mrl_deselect_all"><input class="mrl_select_all_button" id="mrl_left_deselect_all"type="button" value="Deselect All"></div>
				</div>
				<div style="clear:both;"></div>
				<div class="mrl_pane" id="mrl_left_pane">	</div>
			</div>

			<div id="mrl_center_wrapper">
				<div id="mrl_btn_left2right"><img src="<?php echo $mrelocator_plugin_URL."/images/right.png";?>" /></div>
				<div id="mrl_btn_right2left"><img src="<?php echo $mrelocator_plugin_URL."/images/left.png";?>" /></div>
			</div>

			<div class="mrl_wrapper_pane" id="mrl_right_wrapper">
				<div class="mrl_box1">
					<input type="textbox" class="mrl_path" id="mrl_right_path" >
					<div style="clear:both;"></div>
					<div class="mrl_dir_up" id="mrl_right_dir_up"><img src="<?php echo $mrelocator_plugin_URL."/images/dir_up.png";?>"></div>
					<div class="mrl_dir_up" id="mrl_right_dir_new"><img src="<?php echo $mrelocator_plugin_URL."/images/dir_new.png";?>"></div>
					<div class="mrl_select_all" ><input class="mrl_select_all_button" id="mrl_right_select_all" type="button" value="Select All"></div>
					<div class="mrl_deselect_all"><input class="mrl_select_all_button" id="mrl_right_deselect_all"type="button" value="Deselect All"></div>
				</div>
				<div style="clear:both;"></div>
				<div class="mrl_pane" id="mrl_right_pane"></div>

			</div>
		</div>
<div id="debug">.<br></div>
<div id="mrl_test" style="display:none;">test<br></div>

	</div>



	<?php 
	if ( isset($_POST['updateEsAudioPlayerSetting'] ) ) {
		//echo '<script type="text/javascript">alert("Options Saved.");</script>';
	}
}

function mrelocator_getdir($dir, &$ret_arr)
{
	$dh = @opendir ( $dir );

	if ($dh === false) {
		die("error: cannot open directory (".$dir.")");
	}
	for ($i=0;;$i++) {
		$str = readdir($dh);
		if ($str=="." || $str=="..") {$i--;continue;}
		if ($str === FALSE) break;
		$ret_arr[$i] = $str;
	}
}

function mrelocator_isEmptyDir($dir)
{
	$dh = @opendir ( $dir );

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
}

function mrelocator_getdir_callback()
{
	if (!test_mfm_permission()) return 0;

	global $wpdb;
	global $mrelocator_plugin_URL;
	global $mrelocator_uploaddir;

	$local_post_dir = stripslashes($_POST['dir']);
	$errflg = false;

	$dir = mrl_adjpath($mrelocator_uploaddir . "/" . $local_post_dir, true);
	$dir0=array();
	mrelocator_getdir($dir, $dir0);
	if (!count($dir0)) die("[]");
	for ($i=0; $i<count($dir0); $i++) {
		$name = $dir0[$i];
		$dir1[$i]['ids'] = $i;
		$dir1[$i]['name'] = $name;
		$dir1[$i]['isdir'] = is_dir($dir."/".$name)?1:0;
		$dir1[$i]['isemptydir'] = 0;
		if ($dir1[$i]['isdir']) {
			$dir1[$i]['isemptydir'] = mrelocator_isEmptyDir($dir."/".$name)?1:0;
		}
		$dir1[$i]['isthumb'] = 0;
	}
	// set no-rename flag to prevent causing problem. 
	// (When "abc.jpg" and "abc.jpg.jpg" exist, and rename "abc.jpg", "abc.jpg.jpg" in posts will be affected.)
	usort($dir1, "mrelocator_dircmp");
	for ($i=0; $i<count($dir1); $i++) {
		$dir1[$i]['norename'] = 0;
	}
	for ($i=0; $i<count($dir1); $i++) {
		for ($j=$i+1; $j<count($dir1); $j++) {
			if (!$dir1[$i]['isdir'] && !$dir1[$i]['isdir']) {
				if (strpos($dir1[$j]['name'], $dir1[$i]['name'])===0) {
					$dir1[$i]['norename'] = 1;
					break;
				} else {
					break;
				}
			}
		}
	}
	usort($dir1, "mrelocator_dircmp_r");
	$sql = 	"select a.post_id, a.meta_value as meta_value_a, b.meta_value as meta_value_b, c.meta_value as meta_value_c  " . 
			"from $wpdb->postmeta a " .
			"left join $wpdb->postmeta b on a.post_id=b.post_id and b.meta_key='_wp_attachment_metadata' " .
			"left join $wpdb->postmeta c on a.post_id=b.post_id and c.meta_key='_wp_attachment_backup_sizes' " .
			"where a.meta_value in (";
	for ($i=count($dir1)-1; $i>=0; $i--) {
		$subdir_fn = mrelocator_get_subdir($dir) . $dir1[$i]['name'];
		$sql .= "'".$subdir_fn."'";
		if ($i>0) $sql .= ",";
	}
	$sql .= ")";
	$dbres_all = $wpdb->get_results($sql);

	$idx_subdir_fn = array(); 
	for ($i=0; $i<count($dbres_all); $i++) {
		$subdir_fn = $dbres_all[$i]->meta_value_a;
		$idx_subdir_fn[$subdir_fn] = $i;
	}

	$idx_dir1 = array(); 
	for ($i=0; $i<count($dir1); $i++) {
		$idx_dir1[$dir1[$i]['name']] = $i;
	}
	
	for ($i=count($dir1)-1; $i>=0; $i--) {
		$dir1[$i]['id'] = "";
		if ($dir1[$i]['isdir']) {
			$dir1[$i]['thumbnail_url'] = $mrelocator_plugin_URL . "/images/dir.png";
		}
		else if (!mrelocator_isimage($dir1[$i]['name'])) {
			if (mrelocator_isaudio($dir1[$i]['name'])) {
				$dir1[$i]['thumbnail_url'] = $mrelocator_plugin_URL . "/images/audio.png";
			} else if (mrelocator_isvideo($dir1[$i]['name'])) {
				$dir1[$i]['thumbnail_url'] = $mrelocator_plugin_URL . "/images/video.png";
			} else {
				$dir1[$i]['thumbnail_url'] = $mrelocator_plugin_URL . "/images/file.png";
			}
			continue;
		}
		if ($dir1[$i]['isthumb']==1 || $dir1[$i]['isdir']==1) {continue;}
		$subdir_fn = mrelocator_get_subdir($dir) . $dir1[$i]['name'];
		@$db_idx = $idx_subdir_fn[$subdir_fn]; //$wpdb->get_results("select post_id from $wpdb->postmeta where meta_value='".$subdir_fn."'");
		$dir1[$i]['parent'] = "";
		$dir1[$i]['thumbnail'] = "";
		$dir1[$i]['thumbnail_url'] = "";
		
		//if (count($dbres_all[$db_idx])) {
		if (!is_null($db_idx)) {
			$dir1[$i]['id'] = $dbres_all[$db_idx]->post_id;
			$res = unserialize($dbres_all[$db_idx]->meta_value_b);//wp_get_attachment_metadata( $dbres_all[$db_idx]->post_id );
			if (!is_array($res)) {
				//mrelocator_log(print_r($res,true));
				//echo "An error occured. Please download log file and send to the plugin author.";
				$errflg = true;
			} 
			if (is_array($res)) {
				if (array_key_exists('sizes', $res)) {
					$min_size = -1;
					$min_child = -1;
					foreach ($res['sizes'] as $key => $value) {
						$j = $idx_dir1[$res['sizes'][$key]['file']];
						if (!is_null($j)) {
							$dir1[$j]['parent'] = $i;
							$dir1[$j]['isthumb'] = 1;
							$size = $res['sizes'][$key]['width']*$res['sizes'][$key]['height'];
							if ($size < $min_size || $min_size==-1) {
								$min_size = $size;
								$min_child = $j;
							}
						}
					}
					$dir1[$i]['thumbnail'] = $min_child;
					if ($min_child >= 0) {
						$dir1[$i]['thumbnail_url'] = mrelocator_path2url($dir .  $dir1[$min_child]['name']);
					} else {
						$dir1[$i]['thumbnail_url'] = "";
					}
					$backup_sizes = unserialize($dbres_all[$db_idx]->meta_value_c); //get_post_meta( $dbres_all[$db_idx]->post_id, '_wp_attachment_backup_sizes', true );
					//$meta = wp_get_attachment_metadata( $dbres_all[$db_idx]->post_id );
					if ( is_array($backup_sizes) ) {
						foreach ( $backup_sizes as $size ) {
							$j = $idx_dir1[$size['file']];
							if (!is_null($j)) {
								$dir1[$j]['parent'] = $i;
								$dir1[$j]['isthumb'] = 1;
							}
						}
					}
				}
			}
		}
		if ($dir1[$i]['thumbnail_url']=="" && $dir1[$i]['isthumb']==0 || $dir1[$i]['thumbnail']==-1) {
			$fsize = filesize($dir . $dir1[$i]['name']);
			if ($fsize>1 && $fsize < 131072) {
				$dir1[$i]['thumbnail_url'] = mrelocator_path2url($dir .  $dir1[$i]['name']);
			} else {
				$dir1[$i]['thumbnail_url'] = $mrelocator_plugin_URL . "/images/no_thumb.png";
			}
		}
	}
//dbg2("mrelocator_getdir_callback end ".date("l jS \of F Y h:i:s A"));	
	echo json_encode($dir1);
	
	//if ($errflg) mrelocator_log(json_encode($dir1));

	die();
}

add_action('wp_ajax_mrelocator_getdir', 'mrelocator_getdir_callback');

function mrelocator_dircmp($a, $b)
{
	$ret = $b['isdir'] - $a['isdir'];
	if ($ret) return $ret;
	return strcasecmp($b['name'], $a['name']);
}

function mrelocator_dircmp_r($a, $b)
{
	$ret = $b['isdir'] - $a['isdir'];
	if ($ret) return $ret;
	return strcasecmp($a['name'], $b['name']);
}

function mrelocator_mkdir_callback()
{
	if (!test_mfm_permission()) return 0;

	global $wpdb;
	global $mrelocator_uploaddir;
	
	$local_post_dir = stripslashes($_POST['dir']);
	$local_post_newdir = stripslashes($_POST['newdir']);
	
	ini_set("track_errors",true);

	$dir = mrl_adjpath($mrelocator_uploaddir."/".$local_post_dir, true);
	$newdir = $local_post_newdir;
	
	$res = chdir($dir);
	if (!$res) die($php_errormsg);

	$res = @mkdir($newdir);
	if (!$res) {die($php_errormsg);}

	die('Success');
}
add_action('wp_ajax_mrelocator_mkdir', 'mrelocator_mkdir_callback');


function udate($format, $utimestamp = null)
{
    if (is_null($utimestamp))
        $utimestamp = microtime(true);

    $timestamp = floor($utimestamp);
    $milliseconds = round(($utimestamp - $timestamp) * 1000000);

    return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
}


function mrelocator_get_subdir($dir)
{
	global $mrelocator_uploaddir;
	$upload_dir = $mrelocator_uploaddir;
	$subdir = substr($dir,  strlen($upload_dir));
	if (substr($subdir,0,1)=="/" || substr($subdir,0,1)=="\\") {
		$subdir = substr($subdir, 1);
	}
	$subdir = mrl_adjpath($subdir, true);
	if ($subdir=="/") $subdir="";
	return $subdir;
}


function mrelocator_rename_callback()
{
	if (!test_mfm_permission()) return 0;

	global $wpdb;
	global $mrelocator_uploaddir;
	global $mrelocator_uploadurl;

	ignore_user_abort(true);
	_set_time_limit(1800);
	ini_set("track_errors",true);

	$wpdb->show_errors();
	
	$local_post_dir = stripslashes($_POST['dir']);
	$local_post_from = stripslashes($_POST['from']);
	$local_post_to = stripslashes($_POST['to']);

	$dir = mrl_adjpath($mrelocator_uploaddir."/".$local_post_dir, true);
	$subdir = substr($dir, strlen($mrelocator_uploaddir));

	$old[0] = $local_post_from;
	$new[0] = $local_post_to;
	if ($old[0] == $new[0]) die("Success");

	$old_url =  mrelocator_path2url($dir . $old[0]);
	$dbres = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_value = '" . $subdir . $old[0] . "'");

	$smallimgs = array();

	// $old or $new [greater than 0] : smaller images
	if (count($dbres)) { 
		$res = wp_get_attachment_metadata( $dbres[0]->post_id );
		if (array_key_exists('sizes', $res)) {
			foreach ($res['sizes'] as $key => $value) {
				$file = $res['sizes'][$key]['file'];
				$width = $res['sizes'][$key]['width'];
				$height = $res['sizes'][$key]['height'];
				$path_parts = pathinfo($new[0]);
				$old[count($old)] = $file;
				$new[count($new)] = $path_parts['filename']."-".$width."x".$height.".".$path_parts['extension'];
				$smallimgs[$key]['old'] = $file;
				$smallimgs[$key]['new'] = $new[count($new)-1];
			}
		}
	}

	for ($i=0; $i<count($old); $i++) {
		$res = @rename($dir.$old[$i], $dir.$new[$i]);
//echo $dir.$old[$i]." -> ". $dir.$new[$i]."\n";
		if (!$res) {
			for ($j=0; $j<$i; $j++) {
				$res = @rename($dir.$new[$i], $dir.$old[$i]);
			}
			die($php_errormsg);
		}
	}
//die("OK");

	$subdir = mrelocator_get_subdir($dir);

	try {
		if ($wpdb->query("START TRANSACTION")===false) {throw new Exception('1');}

		for ($i=0; $i<count($old); $i++) {
			$oldp = $dir . $old[$i];	//old path
			$newp = $dir . $new[$i];	//new path
			if (is_dir($newp)) {
				$oldp .= "/";
				$newp .= "/";
			}
			$oldu = $mrelocator_uploadurl . ltrim($local_post_dir,"/") . $old[$i].(is_dir($newp)?"/":"");	//old url
			$newu = $mrelocator_uploadurl . ltrim($local_post_dir,"/") . $new[$i].(is_dir($newp)?"/":"");	//new url
			$olda = $subdir.$old[$i];	//old attachment file name (subdir+basename)
			$newa = $subdir.$new[$i];	//new attachment file name (subdir+basename)

			if ($wpdb->query("update $wpdb->posts set post_content=replace(post_content, '" . $oldu . "','" . $newu . "') where post_content like '%".$oldu."%'")===FALSE) {throw new Exception('2');}
			if ($wpdb->query("update $wpdb->postmeta set meta_value=replace(meta_value, '" . $oldu . "','" . $newu . "') where meta_value like '%".$oldu."%'")===FALSE)  {throw new Exception('3');}

			if (is_dir($newp)) {
				if ($wpdb->query("update $wpdb->posts set guid=replace(guid, '" . $oldu . "','" . $newu . "') where guid like '".$oldu."%'")===FALSE)  {throw new Exception('4');}
				//$wpdb->query("update $wpdb->postmeta set meta_value=CONCAT('".$subdir.$new[$i]."/',substr(meta_value,".(strlen($subdir.$old[$i]."/")+1).")) where meta_value like '".$subdir.$old[$i]."/%'");

				$ids = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_value like '".$subdir.$old[$i]."/%'");
				for ($j=0; $j<count($ids); $j++) {
					$meta = wp_get_attachment_metadata($ids[$j]->post_id);
					//CONCAT('".$subdir.$new[$i]."/',substr(meta_value,".(strlen($subdir.$old[$i]."/")+1)."))
					$meta['file'] = $subdir.$new[$i]."/".substr($meta['file'], strlen($subdir.$old[$i]."/"));
					if (!wp_update_attachment_metadata($ids[$j]->post_id, $meta))  {throw new Exception('5');}
					$wpdb->query("update $wpdb->postmeta set meta_value='".$meta['file']."' where post_id=".$ids[$j]->post_id." and meta_key='_wp_attached_file'");
				}
			} else {
				if ($i==0) {
					$res = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_key='_wp_attached_file' and meta_value='".$olda."'");
					if (count($res)) {
						if ($wpdb->query("update $wpdb->postmeta set meta_value='" . $newa . "' where meta_value = '".$olda."'")===FALSE)  {throw new Exception('6');}
						$id = $res[0]->post_id;
						$pt=pathinfo($newa);
						if ($wpdb->query("update $wpdb->posts set guid='".$newu."', post_title='".$pt['filename']."' where ID = '".$id."'")===FALSE)  {throw new Exception('7');}
	
						$meta = wp_get_attachment_metadata($id);
						foreach ($smallimgs as $key => $value) {
							$meta['sizes'][$key]['file'] = $smallimgs[$key]['new'];
						}
						$meta['file'] = $subdir . $new[$i];
						if (wp_update_attachment_metadata($id, $meta)===FALSE)  {throw new Exception('8');}
					}
				}
			}
		}
	
		if ($rc=$wpdb->query("COMMIT") === FALSE) {throw new Exception('9');}

		die("Success");
	} catch (Exception $e) {
		$wpdb->query("ROLLBACK");
		for ($j=0; $j<count($new); $j++) {
			$res = @rename($dir.$new[$j], $dir.$old[$j]);
		}
		die("Error ".$e->getMessage());
	}
}
add_action('wp_ajax_mrelocator_rename', 'mrelocator_rename_callback');

function mrelocator_move_callback()
{
	if (!test_mfm_permission()) return 0;

	global $wpdb;
$wpdb->show_errors();

	ignore_user_abort(true);
	_set_time_limit(900);
	ini_set("track_errors",true);


	global $mrelocator_uploaddir;
	global $mrelocator_uploadurl;
	
	$local_post_dir_from = stripslashes($_POST['dir_from']);
	$local_post_dir_to = stripslashes($_POST['dir_to']);
	$local_post_items = stripslashes($_POST['items']);

	
	$dir_from = mrl_adjpath($mrelocator_uploaddir."/".$local_post_dir_from, true);
	$dir_to = mrl_adjpath($mrelocator_uploaddir."/".$local_post_dir_to, true);
	$dir_to_list = array();
	mrelocator_getdir($dir_to, $dir_to_list);
	
	$items0 = $local_post_items;
	$items = explode("/",$items0);
	
	$same = "";
	$samecnt=0;
	for ($i=0; $i<count($items); $i++) {
		for ($j=0; $j<count($dir_to_list); $j++) {
			if ($items[$i] == $dir_to_list[$j]) {
				if ($same != "") $same .= ", ";
				$same .= $items[$i];
				$samecnt++;
			}
		}
	}
	if ($samecnt) {
		$msg = (($samecnt==1)?"A same name exists ":"Same names exist ")."in the destination directory:\n";
		die($msg."\n".$same);
	}

	for ($i=0; $i<count($items); $i++) {
		$res = @rename($dir_from . $items[$i] , $dir_to . $items[$i]);
		if (!$res) {
			for ($j=0; $j<$i; $j++) {
				$res = @rename($dir_to . $items[$j] , $dir_from . $items[$j]);
			}
			die($php_errormsg);
		}
	}
//die("OK");

	try {
		if ($wpdb->query("START TRANSACTION") === FALSE) {throw new Exception('0');}

		$subdir_from = mrelocator_get_subdir($dir_from);
		$subdir_to = mrelocator_get_subdir($dir_to);

		for ($i=0; $i<count($items); $i++) {
			$old = $dir_from . $items[$i];
			$new = $dir_to . $items[$i];
			$isdir=false;
			if (is_dir($new)) {
				$old .= "/";
				$new .= "/";
				$isdir=true;
			}
			$oldu = mrl_adjpath( $mrelocator_uploadurl."/".$local_post_dir_from."/".$items[$i] );	//old url
			$newu = mrl_adjpath( $mrelocator_uploadurl."/".$local_post_dir_to."/".$items[$i] );	//new url

			if ($wpdb->query("update $wpdb->posts set post_content=replace(post_content, '" . $oldu . "','" . $newu . "') where post_content like '%".$oldu."%'")===FALSE) {throw new Exception('1');}
			if ($wpdb->query("update $wpdb->postmeta set meta_value=replace(meta_value, '" . $oldu . "','" . $newu . "') where meta_value like '%".$oldu."%'")===FALSE) {throw new Exception('2');}

			if ($isdir) {
				if ($wpdb->query("update $wpdb->posts set guid=replace(guid, '" . $oldu . "','" . $newu . "') where guid like '".$oldu."%'")===FALSE) {throw new Exception('3');}
				if ($wpdb->query("update $wpdb->postmeta set meta_value=replace(meta_value, '" . $oldu . "','" . $newu . "') where meta_value like '".$oldu."%'")===FALSE) {throw new Exception('4');}

				$ids = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_value like '".$subdir_from.$items[$i]."/%'");
				for ($j=0; $j<count($ids); $j++) {
					$meta = wp_get_attachment_metadata($ids[$j]->post_id);
					//$meta->file = CONCAT('".$subdir_to.$items[$i]."/',substr(meta_value,".(strlen($subdir_from.$items[$i]."/")+1)."))
					$meta['file'] = $subdir_to.$items[$i]."/" . substr($meta['file'], strlen($subdir_from.$items[$i]."/"));
					wp_update_attachment_metadata($ids[$j]->post_id, $meta);
					if ($wpdb->query("update $wpdb->postmeta set meta_value='".$meta['file']."' where post_id=".$ids[$j]->post_id." and meta_key='_wp_attached_file'")===FALSE) {throw new Exception('5');}
				}
				//$wpdb->query("update $wpdb->postmeta set meta_value=CONCAT('".$subdir_to.$items[$i]."/',substr(meta_value,".(strlen($subdir_from.$items[$i]."/")+1).")) where meta_value like '".$subdir_from.$items[$i]."/%'");
			} else {
				if ($wpdb->query("update $wpdb->posts set guid='" . $newu . "' where guid = '".$oldu."'")===FALSE) {throw new Exception('6');}
				$ids = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_value = '".$subdir_from.$items[$i]."'");
				for ($j=0; $j<count($ids); $j++) {
					$meta = wp_get_attachment_metadata($ids[$j]->post_id);
					$meta['file'] = $subdir_to.$items[$i]; 
					wp_update_attachment_metadata($ids[$j]->post_id, $meta);
				}
				if ($wpdb->query("update $wpdb->postmeta set meta_value='" . $subdir_to.$items[$i] . "'where meta_value = '".$subdir_from.$items[$i]."'")===FALSE) {throw new Exception('7');}
			}
		}

		if ($wpdb->query("COMMIT") === FALSE) {throw new Exception('8');}

		die("Success");
	} catch (Exception $e) {
		$wpdb->query("ROLLBACK");
		for ($j=0; $j<count($items); $j++) {
			$res = @rename($dir_to . $items[$j] , $dir_from . $items[$j]);
		}
		die("Error ".$e->getMessage());
	}
}
add_action('wp_ajax_mrelocator_move', 'mrelocator_move_callback');



function mrelocator_delete_empty_dir_callback()
{
	if (!test_mfm_permission()) return 0;

	global $mrelocator_uploaddir;
	
	$local_post_dir = stripslashes($_POST['dir']);
	$local_post_name = stripslashes($_POST['name']);

	$dir = mrl_adjpath($mrelocator_uploaddir."/".$local_post_dir."/".$local_post_name, true);
	
	if (strstr($local_post_name,"\\")) {
		$dir = substr($dir,0,strlen($dir)-strlen($local_post_name)-1).$local_post_name."/";
	}
	
	if (!@rmdir($dir)) {
		$error = error_get_last();
		die($error['message']);
	}
	die("Success");
}
add_action('wp_ajax_mrelocator_delete_empty_dir', 'mrelocator_delete_empty_dir_callback');



function mrelocator_url2path($url)
{
	$urlroot = mrelocator_get_urlroot();
	if (stripos($url, $urlroot) != 0) {
		return "";
	}
	return $_SERVER['DOCUMENT_ROOT'] . substr($url, strlen($urlroot));
}

function mrelocator_path2url($pathname)
{
	$wu = wp_upload_dir();
	$wp_content_dir = str_replace("\\","/", $wu['basedir']);
	$wp_content_dir = str_replace("//","/", $wp_content_dir);
	$path = str_replace("\\","/",$pathname);
	$path = str_replace("//","/",$path);

	$ret = str_replace($wp_content_dir, $wu['baseurl'], $path);
	return $ret;
}

function mrelocator_get_urlroot()
{
	$urlroot = get_bloginfo('url');
	$pos = strpos($urlroot, "//");
	if (!$pos) return "";
	$pos = strpos($urlroot, "/", $pos+2);
	if ($pos) {
		$urlroot = substr($urlroot, 0, $pos);
	}
	return $urlroot;
}

function mrelocator_isimage($fname)
{
	$ext = array(".jpg", ".jpeg", ".gif", ".png", ".bmp", ".tif", ".dng", ".pef", ".cr2");
	for ($i=0; $i<count($ext); $i++) {
		if (strcasecmp(substr($fname, strlen($fname)-strlen($ext[$i])) , $ext[$i]) == 0) {
			return true;
		}
	}
	return false;
}

function mrelocator_isaudio($fname)
{
	$ext = array(".mp3", ".m3u", ".wma", ".ra", ".ram", ".aac", ".flac", ".ogg");
	for ($i=0; $i<count($ext); $i++) {
		if (strcasecmp(substr($fname, strlen($fname)-strlen($ext[$i])) , $ext[$i]) == 0) {
			return true;
		}
	}
	return false;
}

function mrelocator_isvideo($fname)
{
	$ext = array(".mp4", ".wav", ".wma", ".avi", ".flv", ".ogv", ".divx", ".mov", "3gp");
	for ($i=0; $i<count($ext); $i++) {
		if (strcasecmp(substr($fname, strlen($fname)-strlen($ext[$i])) , $ext[$i]) == 0) {
			return true;
		}
	}
	return false;
}




// Add a link to the config page on the setting menu of wordpress 
add_action('admin_menu', 'mrelocator_admin_plugin_menu');
function mrelocator_admin_plugin_menu()
{
	/*  Add a setting page  */
	add_submenu_page('options-general.php', 
		'Media File Manager plugin Configuration', 
		'Media File Manager', 
		'manage_options', 
		'mrelocator_submenu-handle', 
		'mrelocator_admin_magic_function'
	); 
}

function mrelocator_get_roles(&$ret)
{
	global $wp_roles;
	$i=0;
	foreach($wp_roles->roles as $key=> $value1) { 
		$ret[$i++] = $key;
	}
}

/*  Display config page  */
function mrelocator_admin_magic_function()
{
	$roles = Array();
	mrelocator_get_roles($roles);

	/*  Store setting information which POST has when this func is called by pressing [Save Change] btn  */
	if ( isset($_POST['update_mrelocator_setting'] ) ) {
		echo '<div id="message" class="updated fade"><p><strong>Options saved.</strong></p></div>';
		//update_option('th_linklist_vnum', $_POST['th_linklist_vnum']);
		$roles_val = "";
		for ($i=0; $i<count($roles); $i++) {
			if (!empty($_POST['roles_'.$roles[$i]])) {
				if ($roles_val != "") $roles_val .= ",";
				$roles_val .= $roles[$i];
			}
		}
		update_option('mediafilemanager_accepted_roles', $roles_val);

		$roles_val = "";
		for ($i=0; $i<count($roles); $i++) {
			if (!empty($_POST['roles_sel_'.$roles[$i]])) {
				if ($roles_val != "") $roles_val .= ",";
				$roles_val .= $roles[$i];
			}
		}
		update_option('mediafilemanager_accepted_roles_selector', $roles_val);

		$disable_set_time_limit = (!(empty($_POST['disable_set_time_limit']))) ? 1 : 0;
		update_option('mediafilemanager_disable_set_time_limit', $disable_set_time_limit);
		
	}

	?>
	<div class="wrap">
		<h2>Media File Manager plugin configurations</h2>

		<form method="post" action="<?php echo $_SERVER["REQUEST_URI"]; ?>">
		<?php 
		wp_nonce_field('update-options');
		$accepted_roles = get_option("mediafilemanager_accepted_roles", "administrator");
		$accepted_roles_selector = get_option("mediafilemanager_accepted_roles_selector", "administrator,editor,author,contributor,subscriber");
		$disable_set_time_limit = get_option("mediafilemanager_disable_set_time_limit", 0);
		?>
		<table class="form-table">
		<tr>
		<th>File Manager can be used by </th>
		<td style="text-align: left;">
<?php
	$accepted = explode(",", $accepted_roles);
	for($i=0; $i<count($roles); $i++) { 
		$key = $roles[$i];

		$ck = "";
		for ($j=0; $j<count($accepted); $j++) {
			if ($key == $accepted[$j]) {
				$ck = "checked";
				break;
			}
		}

		echo '<input type="checkbox" name="roles_'.$key.'" id="roles_'.$key.'" '.$ck.'>'.$key.'</input><br>'."\n";
	}
?>

		</td>
		</tr>
		<th>File Selector can bu used by </th>
		<td style="text-align: left;">
<?php
	$accepted = explode(",", $accepted_roles_selector);
	for($i=0; $i<count($roles); $i++) { 
		$key = $roles[$i];

		$ck = "";
		for ($j=0; $j<count($accepted); $j++) {
			if ($key == $accepted[$j]) {
				$ck = "checked";
				break;
			}
		}

		echo '<input type="checkbox" name="roles_sel_'.$key.'" id="roles_sel_'.$key.'" '.$ck.'>'.$key.'</input><br>'."\n";
	}
?>

		</td>
		</tr>
		
		<th>Others</th>
		<td style="text-align: left;">
		<input type="checkbox" name="disable_set_time_limit" id="disable_set_time_limit" <?php echo $disable_set_time_limit?"checked":"";?>>Disable set_time_limit() (not recommended)</input><br>
		</td>
		</tr>

		</table>
		<input type="hidden" name="action" value="update" />
		<p class="submit">
			<input type="submit" name="update_mrelocator_setting" class="button-primary" value="<?php _e('Save  Changes')?>" onclick="" />
		</p>
		</form>


	</div>
	<a href="#" onclick="download_log()">Download Log</a>
	<script type="text/javascript">
	function download_log() {
		var data = {
			action: 'mrelocator_download_log'
		};
		jQuery.post(ajaxurl, data, function(response) {
			download("mfm_log.txt",response);
		});
	}

	function download(filename, text) {
  		var element = document.createElement('a');
		element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
		element.setAttribute('download', filename);

		element.style.display = 'none';
		document.body.appendChild(element);

		element.click();

		document.body.removeChild(element);
	}
	</script>

	&nbsp;&nbsp;<a href="#" onclick="delete_log()">Delete log</a>
	<script type="text/javascript">
	function delete_log() {
		var data = {
			action: 'mrelocator_delete_log'
		};
		jQuery.post(ajaxurl, data, function(response) {
			alert(response);
		});
	}
	</script>

	<?php 
	if ( isset($_POST['update_th_linklist_Setting'] ) ) {
		//echo '<script type="text/javascript">alert("Options Saved.");</script>';
	}
}


function media_file_manager_install() {

	global $wpdb;
	$mfm_db_version = "1.00";
	$table_name = $wpdb->prefix . "media_file_manager_log";
	$installed_ver = get_option( "mfm_db_version" ,"0" );

	if( $installed_ver != $mfm_db_version ) {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');	
		$sql = "CREATE TABLE " . $table_name . " (
			date_time datetime,
			log_data text
		) DEFAULT CHARSET utf8 COLLATE utf8_unicode_ci;";
		dbDelta($sql);

		update_option("mfm_db_version", $mfm_db_version);
	}
}
register_activation_hook(WP_PLUGIN_DIR . '/media-file-manager/media-relocator.php', 'media_file_manager_install');

function mrelocator_log($str)
{
	global $wpdb;
	$str = mysql_real_escape_string($str);
	$sql = "INSERT INTO ". $wpdb->prefix."media_file_manager_log (date_time,log_data) VALUES ('" . date("Y-m-d H:i:s") . "', '" . $str . "');";
	$wpdb->query($sql);
}

function mrelocator_download_log_callback()
{
	if (!test_mfm_permission()) return 0;

	global $wpdb;

	$sql = "select * from " . $wpdb->prefix . "media_file_manager_log order by date_time desc";
	$res = $wpdb->get_results($sql);

	for ($i=0; $i<count($res); $i++) {
		echo $res[$i]->date_time . "\t" . $res[$i]->log_data . "\n";
	}
	die("");
}
add_action('wp_ajax_mrelocator_download_log', 'mrelocator_download_log_callback');

function mrelocator_delete_log_callback()
{
	if (!test_mfm_permission()) return 0;

	global $wpdb;
	$ret = $wpdb->query("TRUNCATE TABLE ".$wpdb->prefix."media_file_manager_log");
	die ($ret===FALSE ? "failure":"success");
}
add_action('wp_ajax_mrelocator_delete_log', 'mrelocator_delete_log_callback');


function _set_time_limit($t)
{
	if (get_option("mediafilemanager_disable_set_time_limit", 0)) {
	} else {
		set_time_limit($t);
	}
}

include 'media-selector.php';



?>
