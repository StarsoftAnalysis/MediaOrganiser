<?php
namespace media_file_manager_cd;

// TODO for my version
// * get rid of the 'preview' feature (?)

if (!defined('ABSPATH')) exit; // Exit if accessed directly



function init() {
	wp_enqueue_script('jquery');
}

function admin_register_head() {
	wp_enqueue_style("mocd-style", plugins_url('style.css', __FILE__));
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

// add a setting menu
function plugin_menu () {
	$role = test_mfm_permission();
    #debug('adding plugin menu, role=', $role);
	if ($role) {
		/*  add a configuration screen  */
        add_submenu_page(
            'upload.php',
            'Media File Manager',
            'Media File Manager',
            $role,
            'mrelocator-submenu-handle',
            NS . 'display_config');
	}
}


/*  show a configuration screen  */
function display_config () {

	wp_enqueue_script("media-relocator", plugins_url('media-relocator.js', __FILE__));
	# FIXME do the proper thing for this:
	echo "<script type='text/javascript'>mrloc_url_root='", UPLOAD_URL, "'</script>";

	echo '<div class="wrap">';
	echo '<h2>Media Organizer</h2>';

    // a bit late to test this for an error!
    /*
    if (UPLOAD_DIR_t['error'] != "") {
        echo "<div class=\"error\"><p>", UPLOAD_DIR_t['error'], "</p></div>";
        die();
    }
     */

	echo '<div id="mocd_wrapper_all">';
	echo '<div class="mocd_wrapper_pane" id="mocd_left_wrapper">';
	echo '<div class="mocd_box1">';
    // Don't need an input box for the path
    #echo '<input type="textbox" class="mocd_path" id="mocd_left_path">';
    echo '<p class=mocd_path id=mocd_left_path>';
	echo '<div style="clear:both;"></div>';
	echo '<div class="mocd_dir_up" id="mocd_left_dir_up"><img src="', PLUGIN_URL, '/images/dir_up.png"></div>';
    echo '<div class="mocd_dir_up" id="mocd_left_dir_new"><img src="', PLUGIN_URL, '/images/dir_new.png"></div>';
	echo '<div class="mocd_select_all"><input class="mocd_select_all_button" id="mocd_left_select_all" type="button" value="Select All"></div>';
	echo '<div class="mocd_deselect_all"><input class="mocd_select_all_button" id="mocd_left_deselect_all"type="button" value="Deselect All"></div>';
	echo '</div>';
	echo '<div style="clear:both;"></div>';
    // This is the div that gets filled in with the dir listing in JS
	echo '<div class="mocd_pane" id="mocd_left_pane"></div>';
	echo '</div>';

	echo '<div id="mocd_center_wrapper">';
	echo '<div id="mocd_btn_left2right"><img src="', PLUGIN_URL, '/images/right.png"></div>';
	echo '<div id="mocd_btn_right2left"><img src="', PLUGIN_URL, '/images/left.png"></div>';
	echo '</div>';

    echo '<div class="mocd_wrapper_pane" id="mocd_right_wrapper">';
	echo '<div class="mocd_box1">';
	#echo '<input type="textbox" class="mocd_path" id="mocd_right_path">';
    echo '<p class=mocd_path id=mocd_right_path>';
	echo '<div style="clear:both;"></div>';
    echo '<div class="mocd_dir_up" id="mocd_right_dir_up"><img src="', PLUGIN_URL, '/images/dir_up.png"></div>';
	echo '<div class="mocd_dir_up" id="mocd_right_dir_new"><img src="', PLUGIN_URL, '/images/dir_new.png"></div>';
	echo '<div class="mocd_select_all"><input class="mocd_select_all_button" id="mocd_right_select_all" type="button" value="Select All"></div>';
	echo '<div class="mocd_deselect_all"><input class="mocd_select_all_button" id="mocd_right_deselect_all" type="button" value="Deselect All"></div>';
	echo '</div>';
	echo '<div style="clear:both;"></div>';
    // This is the div that gets filled in with the dir listing in JS
	echo '<div class="mocd_pane" id="mocd_right_pane"></div>';

	echo '</div>';
	echo '</div>';
    #echo '<div id="debug">.<br></div>';
    #echo '<div id="mocd_test" style="display:none;">test<br></div>'; # FIXME what?
	echo '</div>';

	#if (isset($_POST['updateEsAudioPlayerSetting'])) {
	#	//echo '<script type="text/javascript">alert("Options Saved.");</script>';
	#}
}
 

// AJAX response ...
function getdir_callback () {
    global $wpdb;
    // CD's version -- hopefully simpler.
    if (!test_mfm_permission()) {
        // Send back an empty list
        // BETTER: frontend looks for 'error'
        $result = ['error' => 'Not permitted'];
        echo json_encode($result);
        exit;
    }
    // Get the directory to display, relative to...
	//$dir = stripslashes(request_data('dir');
    // Not sure which filter thingy to use -
    // might depend if it's a Linux or Windows or Mac directory name...  TODO
    $opts = [
        'options' => ['default' => '']
        //?? 'flags' => FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_HIGH,
    ];
    $post_dir = filter_input(INPUT_POST, 'dir', FILTER_DEFAULT, $opts);
    // $dir is relative to the uploads dir, e.g. '/' or 'photos'
	$dir = UPLOAD_DIR . $post_dir;
    $reldir = UPLOAD_REL . $post_dir; // relative to ..  .  not used
    $attdir = ltrim($post_dir, '/');  // remove leading /
    debug("gc: post_dir = $post_dir   dir = $dir   attdir = $attdir");
    // FIXME how to prevent going up to the root??
    $dirlist = [];
    // Get the subdirectories first
    $sdirs = subdirs($dir);
    foreach ($sdirs as $sdir) {
        $dirlist[] = [
            'name' => $sdir,
            'isdir' => true,
			'isemptydir' => isEmptyDir($dir . "/" . $sdir),
            'norename' => false, // FIXME ??
            // FIXME derive the following properly
            'thumbnail_url' => PLUGIN_URL . '/images/dir.png'
        ];
    }
    // Then get the attachments in this directory
    // But can't get them via posts, cos there may be no post using this image
    // Posts with post_type = 'attachment' have the full URL in the guid field (!, yes, really)
    // e.g. http://test.fordingbridge-rotary.org.uk/wp-content/uploads/Rotary-Activity-Sheet-2015.pdf
    // So, need to:
    //  * ignore the first bit; ...
    //  * but wait -- that URL is out of date, so not reliable.
    // so get wp_postmeta where meta_key is _wp_attached_file and meta_value is like 'photos/thingy.jpg'
    // Can't just say: like 'photos/%' because that would include 'photos/otherphotos/foo.jpg'
    // This seems to work:
    $sql = "select p.ID, p.post_mime_type, m.meta_value 
              from wp_posts p 
         left join wp_postmeta m on p.ID = m.post_id and m.meta_key = '_wp_attached_file'
             where post_type = 'attachment' 
               and m.meta_value regexp '^{$attdir}[^/]+$'
          order by m.meta_value"; // FIXME better way to interpolate
    debug('gc sql: ', $sql);
    $results = $wpdb->get_results($sql, ARRAY_A);
    debug('gc results: ', $results);
    foreach ($results as $item) {
        $dirlist[] = [
            'id' => $item['ID'], // ??needed?
            'name' => $item['meta_value'],
            'isdir' => false,
            'isthumb' => false, // always false now
            'norename' => false, // TODO
            'parent' => false, // always false now
            // TODO thumbnail is different depending on file type
            'thumbnail_url' => thumbnail_url($item['meta_value'], $item['post_mime_type'])
            ];
    }
    debug('gc dirlist: ', $dirlist);

    /*
    // Dummy data:
    $dirlist[] = [
        //'ids' => 16, // used? no
        'name' => 'Testdir',
        'isdir' => 1,
        'isemptydir' => 0,
        'isthumb' => 0, // will always be false now
        'norename' => 0,
        //'id' => 505,
        'parent' => 0, // meaningless for dir? 
        // Have to provide the icon:
        'thumbnail_url' => 'http://dev.fordingbridge-rotary.org.uk/wp-content/plugins/media-file-manager-cd/images/dir.png'
      ];
    $dirlist[] = [
        //'ids' => 16, // used?
        'name' => 'Test.jpg',
        'isdir' => 0,
        'isemptydir' => 0,
        'isthumb' => 0, // will always be false now
        'norename' => 0,
        //'id' => 505,
        'parent' => 1, // will always be true now
        //'thumbnail' => 14,
        'thumbnail_url' => 'http://dev.fordingbridge-rotary.org.uk/wp-content/uploads/AussieCricket04-125x125.jpg'
    ];
     */
    // Send the list back to the JS
	echo json_encode($dirlist);
    wp_die(); // completes the AJAX thing
}

/*
function old_getdir_callback() {
	if (!test_mfm_permission()) return 0;

	global $wpdb;

	$local_post_dir = stripslashes($_POST['dir']);
	$errflg = false;

	$dir = mocd_adjpath(UPLOAD_DIR . "/" . $local_post_dir, true);
    debug("mgc: local_post_dir = $local_post_dir   dir = $dir");
	#$dir0=array();
	#getdir($dir, $dir0);
    $dir0 = scandir_no_dots($dir);
    // $dir0 is all the files including size variations
    debug('mgc dir0=', $dir0);
    $dir1 = array();
    if (!count($dir0)) die("[]"); # FIXME really die?
    // NOTE: don't assume items start at [0]
    #for ($i = 0; $i < count($dir0); $i++) {
    $i = 0;
    while ($dir0) {
		$name = array_shift($dir0); //$dir0[$i];
		$dir1[$i]['ids'] = $i;
		$dir1[$i]['name'] = $name;
		$dir1[$i]['isdir'] = is_dir($dir . "/" . $name) ? 1 : 0;
		$dir1[$i]['isemptydir'] = 0;
		if ($dir1[$i]['isdir']) {
			$dir1[$i]['isemptydir'] = isEmptyDir($dir . "/" . $name) ? 1 : 0;
		}
        $dir1[$i]['isthumb'] = 0;
		$dir1[$i]['norename'] = 0;
        $i += 1;
	}
    // Sort in reverse order with directories at the top:
	usort($dir1, NS . "dircmp");
    debug('mgc dir1 after first sort: ', $dir1);
	// set no-rename flag to prevent causing problem.
    // (When "abc.jpg" and "abc.jpg.jpg" exist, and rename "abc.jpg", "abc.jpg.jpg" in posts will be affected.)
    // (now done above)
	#for ($i = 0; $i < count($dir1); $i++) {
    #	$dir1[$i]['norename'] = 0;
    #}
    // FIXME this doesn't seem to make sense
	for ($i = 0; $i < count($dir1); $i++) {
        for ($j = $i + 1; $j < count($dir1); $j++) {

			if (!$dir1[$i]['isdir'] && !$dir1[$i]['isdir']) {
				if (strpos($dir1[$j]['name'], $dir1[$i]['name']) === 0) {
					$dir1[$i]['norename'] = 1;
					break;
				} else {
					break;
				}
			}
		}
	}
    // ? not sure:  why sort backwards and then iterate backwards?
    // Seems to sort alpha with dirs at top
	usort($dir1, NS . "dircmp_r");
    debug('mgc dir1 after second sort: ', $dir1);
    // FIXME sort is irrelevant for use in sql
    // Clues:
    //  images get name in meta_value_a, meta stuff in meta_value_b
    //  pdfs just get name in _a
    //  _c is used when an image has been edited in WP -- it gets a name 
    //   suffix as '-e<timestamp>' and shows up as such in
    //   MFM listing (the original is then hidden)o/
    // when an edited image is restored to original, both lots are still
    //  in the directory.
    //  (and currently MFM seems to lose both lots).
    // TODO would it be better to get stuff for each item separately,
    //   perhaps using wp_get_attachment_metadata for b,
    //     (i.e. width, height, file, array of sizes, each with 
    //   and 
    // because dir1 already has the full list, including different sizes.

    // Plan: filter out the ones ending in '-ennnnnnnnnnnn' 
    //   -- no, 
    //  display edited names without -ennnnnnnnnnn

    // A single image can show up in the directory as
    //  AussieCricket05-125x125.jpg               
    //  AussieCricket05-150x150.jpg               
    //  AussieCricket05-200x100.jpg               
    //  AussieCricket05-200x150.jpg               
    //  AussieCricket05-e1477218030495-125x125.jpg
    //  AussieCricket05-e1477218030495-150x100.jpg
    //  AussieCricket05-e1477218030495-150x150.jpg
    //  AussieCricket05-e1477218030495.jpg        
    //  AussieCricket05.jpg                       
    // (possibly with many sets of -e numbers)

    // Surprisingly, all sizes seem to get sent in the json

    // Another plan.
    // Rewrite this to get all attachments from db where  file name starts with 
    // the given directory (i.e. the whole path equals the dir)
    // rather than getting all the files in the directory including the variants.
    // And then display the proper name without any -ennnnn
    // But first, check what the JS needs in the array.

// select p.ID, p.post_title, p.post_type, m1.meta_value as attachment, m2.meta_value as metadata, m3.meta_value as backup_sizes from wp_posts p, wp_postmeta m1, wp_postmeta m2, wp_postmeta m3  where p.ID = 506 and p.ID = m1.post_id and p.ID = m2.post_id and p.ID = m3.post_id and m1.meta_key = '_wp_attached_file' and m2.meta_key = '_wp_attachment_metadata' and m3.meta_key = '_wp_attachment_backup_sizes'
    // THAT DOESN'T WORK e.g. if there is no backup sizes meta -- need explicit left join:
    // select p.ID, p.post_title, p.post_type, m1.meta_value as attachment, m2.meta_value as metadata, m3.meta_value as backup_sizes from wp_posts p left join wp_postmeta m1 on p.ID = m1.post_id and m1.meta_key = '_wp_attached_file' left join wp_postmeta m2 on p.ID = m2.post_id and m2.meta_key = '_wp_attachment_metadata' left join  wp_postmeta m3 on p.ID = m3.post_id and m3.meta_key = '_wp_attachment_backup_sizes' where p.ID = 580 

    // THIS LOOKS WRONG -- see my sql above -- this isn't specific on _a
    $sql = "select a.post_id, 
                   a.meta_value as meta_value_a, 
                   b.meta_value as meta_value_b, 
                   c.meta_value as meta_value_c \n " .
	         "from $wpdb->postmeta a \n" .
		"left join $wpdb->postmeta b on a.post_id=b.post_id and b.meta_key='_wp_attachment_metadata' \n" .
		"left join $wpdb->postmeta c on a.post_id=b.post_id and c.meta_key='_wp_attachment_backup_sizes' \n" .
			"where a.meta_value in (\n";
	for ($i = count($dir1) - 1; $i >= 0; $i--) {
		$subdir_fn = get_subdir($dir) . $dir1[$i]['name'];
		$sql .= "'" . $subdir_fn . "'";
		if ($i>0) $sql .= ",\n";
	}
	$sql .= ")";
    debug('mgc: sql: ', $sql);
    $dbres_all = $wpdb->get_results($sql);
    debug('mgc: dbres_all: ', $dbres_all);

	$idx_subdir_fn = array();
	for ($i=0; $i<count($dbres_all); $i++) {
		$subdir_fn = $dbres_all[$i]->meta_value_a;
		$idx_subdir_fn[$subdir_fn] = $i;
	}
    debug('mgc: idx_subdir_fn:', $idx_subdir_fn);

	$idx_dir1 = array();
	for ($i=0; $i<count($dir1); $i++) {
		$idx_dir1[$dir1[$i]['name']] = $i;
	}

    // TODO use a foreach loop?  (but need $i in places)
	for ($i=count($dir1)-1; $i>=0; $i--) {
		$dir1[$i]['id'] = "";
        // Set up the icon images for directories etc.
		if ($dir1[$i]['isdir']) {
			$dir1[$i]['thumbnail_url'] = PLUGIN_URL . "/images/dir.png";
		}
		else if (!isimage($dir1[$i]['name'])) {
			if (isaudio($dir1[$i]['name'])) {
				$dir1[$i]['thumbnail_url'] = PLUGIN_URL . "/images/audio.png";
			} else if (isvideo($dir1[$i]['name'])) {
				$dir1[$i]['thumbnail_url'] = PLUGIN_URL . "/images/video.png";
			} else {
				$dir1[$i]['thumbnail_url'] = PLUGIN_URL . "/images/file.png";
			}
			continue;
		}
		if ($dir1[$i]['isthumb']==1 || $dir1[$i]['isdir']==1) {continue;}
		$subdir_fn = get_subdir($dir) . $dir1[$i]['name'];
        // FIXME @ was at the beginning of the next line
        $db_idx = $idx_subdir_fn[$subdir_fn]; //$wpdb->get_results("select post_id from $wpdb->postmeta where meta_value='".$subdir_fn."'");
		$dir1[$i]['parent'] = "";
		$dir1[$i]['thumbnail'] = "";
		$dir1[$i]['thumbnail_url'] = "";

		//if (count($dbres_all[$db_idx])) {
		if (!is_null($db_idx)) {
			$dir1[$i]['id'] = $dbres_all[$db_idx]->post_id;
			$res = unserialize($dbres_all[$db_idx]->meta_value_b);//wp_get_attachment_metadata($dbres_all[$db_idx]->post_id);
			if (!is_array($res)) {
				//log(print_r($res,true));
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
						$dir1[$i]['thumbnail_url'] = path2url($dir .  $dir1[$min_child]['name']);
					} else {
						$dir1[$i]['thumbnail_url'] = "";
					}
					$backup_sizes = unserialize($dbres_all[$db_idx]->meta_value_c); //get_post_meta($dbres_all[$db_idx]->post_id, '_wp_attachment_backup_sizes', true);
					//$meta = wp_get_attachment_metadata($dbres_all[$db_idx]->post_id);
					if (is_array($backup_sizes)) {
						foreach ($backup_sizes as $size) {
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
				$dir1[$i]['thumbnail_url'] = path2url($dir .  $dir1[$i]['name']);
			} else {
				$dir1[$i]['thumbnail_url'] = PLUGIN_URL . "/images/no_thumb.png";
			}
		}
	}
    debug('mgc: returning this to js: ', $dir1);
    // Return an array of things like this (some fields are sometimes missing):
    //  (
    //    [ids] => 16
    //    [name] => AussieCricket04.jpg
    //    [isdir] => 0
    //    [isemptydir] => 0
    //    [isthumb] => 0
    //    [norename] => 0
    //    [id] => 505
    //    [parent] =>
    //    [thumbnail] => 14
    //    [thumbnail_url] => http://dev.fordingbridge-rotary.org.uk/wp-content/uploads/AussieCricket04-125x125.jpg
    //  )
    / $dir1 = array(  array(
        'ids' => 16,
        'name' => 'AussieCricket04.jpg',
        'isdir' => 0,
        'isemptydir' => 0,
        'isthumb' => 0,
        'norename' => 0,
        'id' => 505,
        'parent' => FALSE,
        'thumbnail' => 14,
        'thumbnail_url' => 'http://dev.fordingbridge-rotary.org.uk/wp-content/uploads/AussieCricket04-125x125.jpg'
    )); /
	echo json_encode($dir1);

	//if ($errflg) log(json_encode($dir1));

	die();
}
 */
/* not used

function dircmp($a, $b) {
	$ret = $b['isdir'] - $a['isdir'];
	if ($ret) return $ret;
	return strcasecmp($b['name'], $a['name']);
}

function dircmp_r($a, $b) {
	$ret = $b['isdir'] - $a['isdir'];
	if ($ret) return $ret;
	return strcasecmp($a['name'], $b['name']);
}

function mkdir_callback() {
	if (!test_mfm_permission()) return 0;

	global $wpdb;

	$local_post_dir = stripslashes($_POST['dir']);
	$local_post_newdir = stripslashes($_POST['newdir']);

	ini_set("track_errors",true);

	$dir = mrl_adjpath(UPLOAD_DIR."/".$local_post_dir, true);
	$newdir = $local_post_newdir;

	$res = chdir($dir);
	if (!$res) die($php_errormsg);

    // FIXME @
	$res = @mkdir($newdir);
	if (!$res) {die($php_errormsg);}

	die('Success');
}

function udate($format, $utimestamp = null)
{
    if (is_null($utimestamp))
        $utimestamp = microtime(true);

    $timestamp = floor($utimestamp);
    $milliseconds = round(($utimestamp - $timestamp) * 1000000);

    return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
}
 */

// RENAME is not MOVE!!
function rename_callback() {
	if (!test_mfm_permission()) return 0;

	global $wpdb;

	ignore_user_abort(true);    // Eek!
	_set_time_limit(1800);
	ini_set("track_errors",true);

	$wpdb->show_errors();

	$local_post_dir = stripslashes($_POST['dir']);
	$local_post_from = stripslashes($_POST['from']);
	$local_post_to = stripslashes($_POST['to']);

	$dir = mrl_adjpath(UPLOAD_DIR."/".$local_post_dir, true);
	$subdir = substr($dir, strlen(UPLOAD_DIR));

	$old[0] = $local_post_from;
	$new[0] = $local_post_to;
	if ($old[0] == $new[0]) die("Success");

	$old_url =  path2url($dir . $old[0]);
	$dbres = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_value = '" . $subdir . $old[0] . "'");

	$smallimgs = array();

	// $old or $new [greater than 0] : smaller images
	if (count($dbres)) {
		$res = wp_get_attachment_metadata($dbres[0]->post_id);
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
                // FIXME @
				$res = @rename($dir.$new[$i], $dir.$old[$i]);
			}
			die($php_errormsg);
		}
	}
//die("OK");

	$subdir = get_subdir($dir);

	try {
		if ($wpdb->query("START TRANSACTION")===false) {throw new Exception('1');}

		for ($i=0; $i<count($old); $i++) {
			$oldp = $dir . $old[$i];	//old path
			$newp = $dir . $new[$i];	//new path
			if (is_dir($newp)) {
				$oldp .= "/";
				$newp .= "/";
			}
            echo "oldp=$oldp newp=$newp";
			$oldu = UPLOAD_URL . ltrim($local_post_dir,"/") . $old[$i].(is_dir($newp)?"/":"");	//old url
			$newu = UPLOAD_URL . ltrim($local_post_dir,"/") . $new[$i].(is_dir($newp)?"/":"");	//new url
			$olda = $subdir.$old[$i];	//old attachment file name (subdir+basename)
			$newa = $subdir.$new[$i];	//new attachment file name (subdir+basename)
            #debug("oldp=$oldp newp=$newp");
            #debug("oldu=$oldu newu=$newu");
            #debug("olda=$olda newa=$newa");

            // This is where posts are updated with the new URL
            if ($wpdb->query("update $wpdb->posts
                              set post_content = replace(post_content, '" . $oldu . "','" . $newu . "')
                              where post_content like '%".$oldu."%'")
                === FALSE) {
                throw new Exception('2');
            }
            if ($wpdb->query("update $wpdb->postmeta
                             set meta_value=replace(meta_value, '" . $oldu . "','" . $newu . "')
                             where meta_value like '%".$oldu."%'")
                === FALSE) {
                throw new Exception('3');
            }

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
            // FIXME @
			$res = @rename($dir.$new[$j], $dir.$old[$j]);
		}
		die("Error ".$e->getMessage());
	}
}

// This gets called when a move arrow is clicked, with data:
// action:    "move"
// dir_from:  "/"
// dir_to:    "/photos/"
// items:     "AussieCricket03.jpg/AussieCricket03-125x125.jpg/AussieCricket03-150x150.jpg/AussieCricket03-200x100.jpg/AussieCricket03-200x150.jpg"
// FIXME this doesn't seem to return anything to the front end
function move_callback() {
    #debug('-- called move');
	if (!test_mfm_permission()) return 0;

	global $wpdb;
    $wpdb->show_errors();

	ignore_user_abort(true);
	_set_time_limit(900);
	ini_set("track_errors",true);

	$local_post_dir_from = stripslashes($_POST['dir_from']);
	$local_post_dir_to = stripslashes($_POST['dir_to']);
	$local_post_items = stripslashes($_POST['items']);
    // These are relative to upload_dir e.g. '/' or '/photos'
    #debug('local_post_dir_from:', $local_post_dir_from);
    #debug('local_post_dir_to:', $local_post_dir_to);
    #debug('local_post_items:', $local_post_items);

	$dir_from = mrl_adjpath(UPLOAD_DIR."/".$local_post_dir_from, true);
	$dir_to = mrl_adjpath(UPLOAD_DIR."/".$local_post_dir_to, true);
	$dir_to_list = array();
	getdir($dir_to, $dir_to_list);

	$items = explode("/", $local_post_items);

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
        $msg = (($samecnt==1) ?
            "A file with the same name is" :
            "Files with the same names are already"
        ) . " in the destination directory:\n";
		die($msg . "\n" . $same);
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

		$subdir_from = get_subdir($dir_from);
		$subdir_to = get_subdir($dir_to);
        #debug('items: ', $items);  // no slashes

		for ($i=0; $i<count($items); $i++) {
			$old = $dir_from . $items[$i];  // FIXME are these used?
			$new = $dir_to . $items[$i];
			$isdir=false;
			if (is_dir($new)) {
				$old .= "/";
				$new .= "/";
				$isdir=true;
			}
			#$oldu = mrl_adjpath(UPLOAD_URL."/".$local_post_dir_from."/".$items[$i]);	//old url
			#$newu = mrl_adjpath(UPLOAD_URL."/".$local_post_dir_to."/".$items[$i]);	//new url
            // CD -- make the URLS relative
            $upload = wp_upload_dir(null, false, false);
            $upload_dir_rel = _wp_relative_upload_path($upload['basedir']);
            #debug('upload', $upload);
            #debug('ldr', $upload_dir_rel);
            $siteurl = get_site_url();
            #debug('siteurl', $siteurl);
            // FIXME calc this outside the loop
            $rel_uploads = str_replace($siteurl, '', UPLOAD_URL);  // !! this might make a path that matches in too many places
            // rel_uploads ends in a slash, so do the local_post_dirs
            // $local_post_dir... start with a slash.
            $rel_uploads = rtrim($rel_uploads, '/');  // remove all trailins slashes
            #debug('rel_uploads', $rel_uploads);
            $oldu = $rel_uploads . $local_post_dir_from . $items[$i];
            $newu = $rel_uploads . $local_post_dir_to   . $items[$i];
            #debug("old=$old    new=$new");
            #debug("oldu=$oldu    newu=$newu");
            // e.g. old=/var/www/rotarywp-dev/wp-content/uploads/AussieCricket04.jpg new=/var/www/rotarywp-dev/wp-content/uploads/photos/AussieCricket04.jpg
            //      oldu=http://dev.fordingbridge-rotary.org.uk/wp-content/uploads/AussieCricket04.jpg newu=http://dev.fordingbridge-rotary.org.uk/wp-content/uploads/photos/AussieCricket04.jpg

            // FIXME use wpdb->update
			#if ($wpdb->query("update $wpdb->posts set post_content=replace(post_content, '" . $oldu . "','" . $newu . "') where post_content like '%".$oldu."%'")===FALSE) {throw new Exception('1');}
            $rc = $wpdb->query("
                update $wpdb->posts
                   set post_content=replace(post_content, '" . $oldu . "','" . $newu . "')
                 where post_content like '%".$oldu."%'
                ");
            if ($rc === FALSE) {
                throw new Exception('1');
            }
            #debug("update posts affected $rc rows");
            if ($wpdb->query("
                    update $wpdb->postmeta
                       set meta_value = replace(meta_value, '" . $oldu . "','" . $newu . "')
                     where meta_value like '%".$oldu."%'
                ") === FALSE) {
                throw new Exception('2');
            }

			if ($isdir) {
                if ($wpdb->query("
                        update $wpdb->posts
                           set guid=replace(guid, '" . $oldu . "','" . $newu . "')
                         where guid like '".$oldu."%'
                    ")
                    === FALSE) {
                    throw new Exception('3');
                }
                if ($wpdb->query("
                        update $wpdb->postmeta
                           set meta_value=replace(meta_value, '" . $oldu . "','" . $newu . "')
                        where meta_value like '".$oldu."%'
                    ") === FALSE) {
                    throw new Exception('4');
                }

                $ids = $wpdb->get_results("
                    select post_id
                      from $wpdb->postmeta
                     where meta_value like '".$subdir_from.$items[$i]."/%'
                ");
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



function delete_empty_dir_callback() {
	if (!test_mfm_permission()) return 0;


	$local_post_dir = stripslashes($_POST['dir']);
	$local_post_name = stripslashes($_POST['name']);

	$dir = mrl_adjpath(UPLOAD_DIR."/".$local_post_dir."/".$local_post_name, true);

	if (strstr($local_post_name,"\\")) {
		$dir = substr($dir,0,strlen($dir)-strlen($local_post_name)-1).$local_post_name."/";
	}

	if (!@rmdir($dir)) {
		$error = error_get_last();
		die($error['message']);
	}
	die("Success");
}



function url2path($url) {
	$urlroot = get_urlroot();
	if (stripos($url, $urlroot) != 0) {
		return "";
	}
	return $_SERVER['DOCUMENT_ROOT'] . substr($url, strlen($urlroot));
}

function path2url($pathname) {
	$wu = wp_upload_dir();
	$wp_content_dir = str_replace("\\","/", $wu['basedir']);
	$wp_content_dir = str_replace("//","/", $wp_content_dir);
	$path = str_replace("\\","/",$pathname);
	$path = str_replace("//","/",$path);

	$ret = str_replace($wp_content_dir, $wu['baseurl'], $path);
	return $ret;
}

function get_urlroot() {
	$urlroot = get_bloginfo('url');
	$pos = strpos($urlroot, "//");
	if (!$pos) return "";
	$pos = strpos($urlroot, "/", $pos+2);
	if ($pos) {
		$urlroot = substr($urlroot, 0, $pos);
	}
	return $urlroot;
}

// Add a link to the config page on the setting menu of wordpress
function admin_plugin_menu() {
	/*  Add a setting page  */
	add_submenu_page('options-general.php',
		'Media File Manager plugin Configuration',
		'Media File Manager',
		'manage_options',
		'submenu-handle',
		NS . 'admin_display_config'
	);
}

function get_roles(&$ret)
{
	global $wp_roles;
	$i=0;
	foreach($wp_roles->roles as $key=> $value1) {
		$ret[$i++] = $key;
	}
}

/*  Display config page  */
function admin_display_config() {
	$roles = Array();
	get_roles($roles);

	/*  Store setting information which POST has when this func is called by pressing [Save Change] btn  */
	if (isset($_POST['update_setting'])) {
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
		<th>File Selector can be used by </th>
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

<?php /*
		<th>Others</th>
		<td style="text-align: left;">
		<input type="checkbox" name="disable_set_time_limit" id="disable_set_time_limit" <?php echo $disable_set_time_limit?"checked":"";?>>Disable set_time_limit() (not recommended)</input><br>
		</td>
        </tr>
    */ ?>

		</table>
		<input type="hidden" name="action" value="update" />
		<p class="submit">
			<input type="submit" name="update_setting" class="button-primary" value="<?php _e('Save Changes')?>" onclick="" />
		</p>
		</form>


	</div>
<?php /* no log nonsense
	<a href="#" onclick="download_log()">Download Log</a>
	<script type="text/javascript">
	function download_log() {
		var data = {
			action: 'download_log'
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
			action: 'delete_log'
		};
		jQuery.post(ajaxurl, data, function(response) {
			alert(response);
		});
	}
	</script>

	<?php
	if (isset($_POST['update_th_linklist_Setting'])) {
		//echo '<script type="text/javascript">alert("Options Saved.");</script>';
    }
 */
}


function media_file_manager_install() {

	global $wpdb;
	$mfm_db_version = "1.00";
	$table_name = $wpdb->prefix . "media_file_manager_log";
	$installed_ver = get_option("mfm_db_version" ,"0");

	if ($installed_ver != $mfm_db_version) {
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		$sql = "CREATE TABLE " . $table_name . " (
			date_time datetime,
			log_data text
		) DEFAULT CHARSET utf8 COLLATE utf8_unicode_ci;";
		dbDelta($sql);

		update_option("mfm_db_version", $mfm_db_version);
	}
}

function log ($str) {
	global $wpdb;
	$str = mysql_real_escape_string($str);
	$sql = "INSERT INTO ". $wpdb->prefix."media_file_manager_log (date_time,log_data) VALUES ('" . date("Y-m-d H:i:s") . "', '" . $str . "');";
	$wpdb->query($sql);
}

function download_log_callback() {
	if (!test_mfm_permission()) return 0;

	global $wpdb;

	$sql = "select * from " . $wpdb->prefix . "media_file_manager_log order by date_time desc";
	$res = $wpdb->get_results($sql);

	for ($i=0; $i<count($res); $i++) {
		echo $res[$i]->date_time . "\t" . $res[$i]->log_data . "\n";
	}
	die("");
}

function delete_log_callback() {
	if (!test_mfm_permission()) return 0;

	global $wpdb;
	$ret = $wpdb->query("TRUNCATE TABLE ".$wpdb->prefix."media_file_manager_log");
	die ($ret===FALSE ? "failure":"success");
}


function _set_time_limit($t) {
	if (!get_option("mediafilemanager_disable_set_time_limit", 0)) {
		set_time_limit($t);
	}
}




?>
