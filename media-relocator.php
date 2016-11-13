<?php
namespace media_file_manager_cd;

// TODO for my version

if (!defined('ABSPATH')) exit; // Exit if accessed directly

function init() {
	wp_enqueue_script('jquery');
}

function admin_register_head() {
	wp_enqueue_style("mocd-style", plugins_url('style.css', __FILE__));
	wp_enqueue_script("media-relocator", plugins_url('media-relocator.js', __FILE__));

    wp_enqueue_script('mocd_jqueryui', 
        plugins_url('/lib/jquery-ui-1.12.1.custom/jquery-ui.js', __FILE__), // TODO use .min.js in prod
        ['jquery']);
    wp_enqueue_style('mocd_jqueryui', 
        plugins_url('/lib/jquery-ui-1.12.1.custom/jquery-ui.css', __FILE__), // TODO use .min.css in prod
        []);
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
            'Media File Manager CD',
            'Media File Manager CD',
            $role,
            'mrelocator-submenu-handle',
            NS . 'display_config');
	}
}

// Echo the html for either the left or right pane
function pane_html ($side) {
	echo '<div class="mocd_wrapper_pane" id="mocd_', $side, '_wrapper">';
	echo '<div class="mocd_box1">';
    echo '<p class=mocd_path id=mocd_', $side, '_path>';
	echo '<div style="clear:both;"></div>';
    echo '<div class="mocd_action" id="mocd_', $side, '_action">Action: ';
    echo '<select name="mocd_', $side, '_select">';
    echo '<option value="rename">Rename</option>';
    echo '<option value="move">Move</option>';
    echo '<option value="delete">Delete</option>';
    echo '</select>';
    echo '<button id="mocd_', $side, '_button_go" type="button">Go</button>';
    echo '</div>';
    // FIXME class should just be mocd_dir
	echo '<div class="mocd_dir_up" id="mocd_', $side, '_dir_up"><img src="', PLUGIN_URL, '/images/dir_up.png"></div>';
    echo '<div class="mocd_dir_up" id="mocd_', $side, '_dir_new"><img src="', PLUGIN_URL, '/images/dir_new.png"></div>';
	echo '</div>';
	echo '<div style="clear:both;"></div>';
    // This is the div that gets filled in with the dir listing in JS
	echo '<div class="mocd_pane" id="mocd_', $side, '_pane"></div>';
	echo '</div>';
    
    // ... I think I've gone off the idea of jQuery dialogs.  Or notV
    // HTML for renaming dialog -- initially hidden
    echo '<div id="mocd_', $side, '_rename_dialog" title="Rename File or Folder" style="display: none;">';
    #echo '<p class="validateTips">Enter the new item name:</p>';
    echo '<form><fieldset>';
    echo '<label for="mocd_"', $side, '_rename">New name: </label>';
    echo '<input type="text" name="mocd_', $side, '_rename" id="mocd_', $side, '_rename" value="">';
    echo '<input type="hidden" name="mocd_', $side, '_rename_i" id="mocd_', $side, '_rename_i" value="">';
    echo '</fieldset></form></div>';

    // HTML for new folder dialog -- initially hidden
    echo '<div id="mocd_', $side, '_newdir_dialog" title="Create a New Folder" style="display: none;">';
    echo '<form><fieldset>';
    echo '<label for="mocd_', $side, '_newdir">Name: </label>';
    echo '<input type="text" name="mocd_', $side, '_newdir" id="mocd_', $side, '_newdir" value="">';
    echo '<div id="mocd_', $side, '_newdir_error"></div>';
    echo '</fieldset></form></div>';
}

/*  show a configuration screen  */
function display_config () {

	echo '<div class="wrap" id="mocd_wrap">';
	echo '<h2>Media Organizer</h2>';

	echo '<div id="mocd_wrapper_all">';

    pane_html('left');

	echo '<div id="mocd_center_wrapper">';
	echo '<div id="mocd_btn_left2right"><img src="', PLUGIN_URL, '/images/right.png"></div>';
	echo '<div id="mocd_btn_right2left"><img src="', PLUGIN_URL, '/images/left.png"></div>';
    echo '</div>';

    pane_html('right');

	echo '</div>'; // div mocd_wrapper_all
	echo '</div>'; // div mocd_wrap
}

function mkdir_callback() {
    if (!test_mfm_permission()) {
        ajax_response(0, 'no permission');
    }
	$dir    = get_post('dir');
    $newdir = get_post('newdir');
    $path = UPLOAD_DIR . $dir;
    $newpath = $path . $newdir;
    #debug('mkdir_c:', $path, $newpath);
    if (file_exists($newpath)) {
        ajax_response(0, "Can't create '$newdir' -- already exists");
    }
    if (!mkdir($newpath, 0777)) {
        ajax_response(0, "Failed to create '$newdir'");
    }
    ajax_response(1, "Created '$newdir' successfully");
}


// AJAX response ...
function getdir_callback () {
    global $wpdb;
    // CD's version -- hopefully simpler.
    if (!test_mfm_permission()) {
        ajax_response(0, 'no permission');
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
    $reldir = UPLOAD_DIR_REL . $post_dir; // relative to ..  .  not used
    $attdir = ltrim($post_dir, '/');  // remove leading /
    debug("gc: post_dir = $post_dir   dir = $dir   attdir = $attdir");
    // FIXME how to prevent going up to the root??
    $dirlist = [];
    // Get the subdirectories first
    $sdirs = subdirs($dir);
    foreach ($sdirs as $sdir) {
        $dirlist[] = [
            #'id' => null,
            'path' => $sdir, // FIXME either don't use it or make it different from name
            'name' => $sdir,
            'post_id' => null,  // needed when updating metadata
            'isdir' => true,
			'isemptydir' => isEmptyDir($dir . "/" . $sdir),
            'norename' => false, // FIXME ??
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


    // FIXME !"!!!!!! can't always rely on attachments to be correct
    // -- e.g. when testing and we've moved the file but not updated the database!!


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
            'post_id'  => $item['ID'],
            'path'     => $item['meta_value'],
            'name'     => basename($item['meta_value']),
            'isdir'    => false,
            'isthumb'  => false, // always false now
            'norename' => false, // TODO
            'parent'   => false, // always false now
            // TODO if it's an image, get a nice small version of it (then it will be square)
            'thumbnail_url' => thumbnail_url($item['meta_value'], $item['post_mime_type'], $item['ID'])
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
    ajax_response(1, 'Got dir OK', $dirlist);
	#echo json_encode($dirlist);
    #wp_die(); // completes the AJAX thing
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

function udate($format, $utimestamp = null)
{
    if (is_null($utimestamp))
        $utimestamp = microtime(true);

    $timestamp = floor($utimestamp);
    $milliseconds = round(($utimestamp - $timestamp) * 1000000);

    return date(preg_replace('`(?<!\\\\)u`', $milliseconds, $format), $timestamp);
}
 */

/*
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
		if ($wpdb->query("START TRANSACTION")===false) {throw new \Exception('1');}

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
                throw new \Exception('2');
            }
            if ($wpdb->query("update $wpdb->postmeta
                             set meta_value=replace(meta_value, '" . $oldu . "','" . $newu . "')
                             where meta_value like '%".$oldu."%'")
                === FALSE) {
                throw new \Exception('3');
            }

			if (is_dir($newp)) {
				if ($wpdb->query("update $wpdb->posts set guid=replace(guid, '" . $oldu . "','" . $newu . "') where guid like '".$oldu."%'")===FALSE)  {throw new \Exception('4');}
				//$wpdb->query("update $wpdb->postmeta set meta_value=CONCAT('".$subdir.$new[$i]."/',substr(meta_value,".(strlen($subdir.$old[$i]."/")+1).")) where meta_value like '".$subdir.$old[$i]."/%'");

				$ids = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_value like '".$subdir.$old[$i]."/%'");
				for ($j=0; $j<count($ids); $j++) {
					$meta = wp_get_attachment_metadata($ids[$j]->post_id);
					//CONCAT('".$subdir.$new[$i]."/',substr(meta_value,".(strlen($subdir.$old[$i]."/")+1)."))
					$meta['file'] = $subdir.$new[$i]."/".substr($meta['file'], strlen($subdir.$old[$i]."/"));
					if (!wp_update_attachment_metadata($ids[$j]->post_id, $meta))  {throw new \Exception('5');}
					$wpdb->query("update $wpdb->postmeta set meta_value='".$meta['file']."' where post_id=".$ids[$j]->post_id." and meta_key='_wp_attached_file'");
				}
			} else {
				if ($i==0) {
					$res = $wpdb->get_results("select post_id from $wpdb->postmeta where meta_key='_wp_attached_file' and meta_value='".$olda."'");
					if (count($res)) {
						if ($wpdb->query("update $wpdb->postmeta set meta_value='" . $newa . "' where meta_value = '".$olda."'")===FALSE)  {throw new \Exception('6');}
						$id = $res[0]->post_id;
						$pt=pathinfo($newa);
						if ($wpdb->query("update $wpdb->posts set guid='".$newu."', post_title='".$pt['filename']."' where ID = '".$id."'")===FALSE)  {throw new \Exception('7');}

						$meta = wp_get_attachment_metadata($id);
						foreach ($smallimgs as $key => $value) {
							$meta['sizes'][$key]['file'] = $smallimgs[$key]['new'];
						}
						$meta['file'] = $subdir . $new[$i];
						if (wp_update_attachment_metadata($id, $meta)===FALSE)  {throw new \Exception('8');}
					}
				}
			}
		}

		if ($rc=$wpdb->query("COMMIT") === FALSE) {throw new \Exception('9');}

		die("Success");
	} catch (\Exception $e) {
		$wpdb->query("ROLLBACK");
		for ($j=0; $j<count($new); $j++) {
            // FIXME @
			$res = @rename($dir.$new[$j], $dir.$old[$j]);
		}
		die("Error ".$e->getMessage());
	}
}
 */

// TODO put this in functions.php
// Send an AJAX response back to javascript, in the form
// [
//   success => true or false
//   message => 'blah',  // reason for the failure
//   data => an array of stuff, e.g....
// ]
// TODO Or could use wp_send_json_success
// or wp_send_json? no, I prefer mine
function ajax_response ($success = false, $message = '', $data = []) {
    $response = [
        'success' => ($success ? true : false),  // convert truthy/falsy into proper booleans
        'message' => $message,
        'data'    => $data
    ];
    header('Content-Type: application/json;');
    echo json_encode($response);
    wp_die();
}

// Create a new secondary filename, given the old
// and new names.
// e.g. changing main filename from foo.jpg to bar.png
// when the old secondary name is foo-123x456.jpg,
// the new secondary name will be bar-123x456.png
function new_secondary_name ($new, $oldsec) {
    $newparts = pathinfo($new);
    print_r($newparts);
    $newsec = $oldsec;
    if (preg_match('/-\d+x\d+\./', $oldsec, $matches)) {
        $nnnxnnn = $matches[0];
        echo 'nnnxnnn: ', $nnnxnnn, "\n";
        $newsec = $newparts['filename'] . $nnnxnnn . $newparts['extension'];
    }
    return $newsec;
}

// Update the content of all posts with replacement text
// Throws on error.
// Returns the number of updates
function update_posts_content ($old, $new, $source = '') {
    if ($old == $new) {
        debug('old = new, doing nothing: ', $old);
        return 0;
    }
    debug("updating posts from '$old' to '$new' $source");
    global $wpdb;
    $sql = "update $wpdb->posts
               set post_content = replace(post_content, '$old', '$new')
             where post_content like '%$old%'";
    $rc = $wpdb->query($sql);
    if ($rc === false) {
        throw new \Exception('Failed to update post content');
    }
    return $rc;
}

// New plan -- do them one at a time (JS does the loop).
// Why?
// - JS can show a progress bar
// - reduces chances of PHP timeouts
// - makes it more atomic  -- just rename one thing and do the associated db updates

// FIXME moving e.g. /test/ into /private/ fails to update posts 
//  with /test/img.jpg to /private/test/img.jpg
//
// This gets called when a move arrow is clicked, with data:
// action:    "move"
// dir_from:  "/"
// dir_to:    "/photos/"
// item:     single item e.g. "AussieCricket03.jpg"
// Returns an array: 0 for failure, 1 for success, !! perhaps need a reason too, e.g. permissions, already exists, db failed etc.
//  !!! Does renaming too.


function new_move_callback () {
    global $wpdb;
    // Keep a list of renamed files in case we need to rollback
    $renamed = [];

    if (!test_mfm_permission()) {
        ajax_response(false, 'no permission');
    }

    // TODO these don't work for some reason
    // -- because filter_input only sees the original contents of $_GET,
    //    not the results of $_GET['foo']='bar' -- which is what the AJAX code does
    #$dir_from = filter_input(FILTER_POST, 'dir_from', FILTER_SANITIZE_STRING); //stripslashes($_POST['dir_from']);
    #$dir_to   = filter_input(FILTER_POST, 'dir_to',   FILTER_SANITIZE_STRING); //stripslashes($_POST['dir_to']);
    #$item     = filter_input(FILTER_POST, 'item',     FILTER_SANITIZE_STRING); //stripslashes($_POST['item']);
    // ...so use
    $dir_from  = get_post('dir_from'); #stripslashes($_POST['dir_from']);  // e.g. '/' or '/photos/'
    $dir_to    = get_post('dir_to');   #stripslashes($_POST['dir_to']);    //    ditto
    $item_from = get_post('item_from');  #stripslashes($_POST['item_to']);  // e.g. 'foo.jpg' or 'images/foo.jpg'.  NOTE no leading '/'
    //   and it's relative to $dir_from
    $item_to   = get_post('item_to'); #stripslashes($_POST['item_from']);
    if (!$item_to) {
        $item_to = $item_from;
    }
    $post_id   = get_post('post_id'); #stripslashes($_POST['post_id']);
    $isdir     = get_post('isdir') == 'true';  #stripslashes($_POST['isdir']);
    debug("nmc: dir_from='$dir_from' dir_to='$dir_to' item_from='$item_from' item_to='$item_to' post_id=$post_id isdir='$isdir'");

    // TODO check if the expected inputs are present
    if ($dir_from == $dir_to and $item_from == $item_to) {
        // TODO is this success or fail?
        ajax_response(false, 'same dir and item');
    }
    // dirs are e.g. '/' or '/private/' or '2015/10' relative to UPLOAD_DIR
    $path_from = UPLOAD_DIR . $dir_from;
    if (!file_exists($path_from)) {
        ajax_response(false, "Folder '" . $dir_from . "' does not exist");
    }
    $path_to = UPLOAD_DIR . $dir_to;
    if (!file_exists($path_to)) {
        ajax_response(false, "Folder '" . $dir_to . "' does not exist");
    }
    // TODO ? need to check if item_from_path exists? == yes
    $item_from_path     = UPLOAD_DIR . $dir_from . $item_from;       // full file path, e.g. /var/www/website/wp_content/uploads/photos/foo.jpg
    $item_to_path       = UPLOAD_DIR . $dir_to   . $item_to;
    $item_from_rel = UPLOAD_DIR_REL . $dir_from . $item_from;   // relative to site root, e.g. /wp_content/uploads/photos/foo.jpg
    $item_to_rel   = UPLOAD_DIR_REL . $dir_to   . $item_to;
    debug('item paths: ', $item_from_path, $item_to_path);
    debug('item rels: ', $item_from_rel, $item_to_rel);
    // PHP rename will overwrite, so check first if it exists
    if (file_exists($item_to_path)) {
        debug('...exists');
        ajax_response(false, 'exists');
    }

    // Keep a list of renamed files in case we need to rollback
    $renamed = [];

    // FIXME need a sanity check on what we're trying to rename!!!!  !!!!!!!!
    // -- i.e. check that UPLOAD_DIR etc. are sensible

    debug("renaming $item_from_path to $item_to_path");
    if (!rename($item_from_path, $item_to_path)) {  // puts a warning in the log on failure
        debug('...rename failed');
        ajax_response(false, 'rename failed');
    }
    $renamed[] = ['from' => $item_from_path, 'to' => $item_to_path];

    // Update the databaseo
    // ?? wp_check_post_lock(id) to see if any post is locked
    // -- probably too much work.  maintenance mode?

    try {

        // The codex at https://codex.wordpress.org/Changing_The_Site_URL#Important_GUID_Note says:
        //    Never, ever, change the contents of the GUID column, under any circumstances.
        //    If the default uploads folder needs to be changed to a different location,
        //    then any media URLs will need to be changed in the post_content column of
        //    the posts table. For example, if the default uploads folder is changing from wp-content/uploads to images:
        //   UPDATE wp_posts SET post_content = REPLACE(post_content,'www.domain.com/wp-content/uploads','www.domain.com/images');

        // Regex for matching an attachment url in a post's content...
        // It will usually look like:
        //   src="http://dev.fordingbridge-rotary.org.uk/wp-content/uploads/photos/otherphotos/AussieCricket04.jpg"
        // or
        //   src="/wp-content/uploads/photos/otherphotos/AussieCricket04.jpg"
        //
        // The '/wp-content/uploads'  part is in UPLOAD_URL_REL
        // If we're changing a directory, we'll be changing the bit between UPLOAD_URL_REL and the basename

        // Match the basename with [^/]+\w*["'] (any chars not a slash, optional space, quote)
        // (these are URLs, so the separator is always /)
        // $item_from is something like /private/foo.jpg
        // Match the bit befiore the item with src\w*=\w*["']\w* . UPLOAD_URL_REL
        // ... no, that won't work for things using <source> etc.
        // Hmmm, only MariaDB has regexp-replace , so manage withddout
        #$prefix_reg = 'src\w*=\w*["\']\w*'; # . UPLOAD_URL_REL;
        #$item_reg = $prefix_reg . $item_from;
        #debug('prefix_reg', $prefix_reg);
        #debug('item_reg', $item_reg);

        $post_count = 0;
        if ($wpdb->query("start transaction") === false) {
            throw new \Exception('Failed to start transaction');
        }

        // Accumulate list of edits...
        $edits = [];

        if ($isdir) {

            // Directory -- just rename it in every post it appears in
            $edits[] = ['a' => 'dir', 'from' => $item_from_rel, 'to' => $item_to_rel];

        } else {

            // Not a directory

            // Update the attachment itself -- the wp_posts entry is of type 'attachment',
            // but does not have the filename, that's in wp_postmeta with key '_wp_attached_file'
            // ('replace' replaces all occurrences in the string)
            // The value is the file name relative to UPLOAD_DIR_REL (or is it an URL?? FIXME)
            // with NO leading '/', e.g. 'foo.jpg' or 'photos/foo.jpg'
            // e.g. from / to

            $file_from_rel = ltrim($dir_from, '/') . $item_from;   // relative to upload dir e.g. photos/thing.jpg
            $file_to_rel   = ltrim($dir_to,   '/') . $item_to;
            debug("updating attachment with $file_to_rel");
            $sql = "update $wpdb->postmeta
                set meta_value = '$file_to_rel'  -- replace(meta_value, '$file_from_rel', '$file_to_rel')
                where post_id = $post_id
                and meta_key = '_wp_attached_file'";
            debug('>>> sql:', $sql);
            $rc = $wpdb->query($sql);
            debug('>>> rc', $rc);
            if ($rc === false) {
                throw new \Exception('Failed to replace name in attachment');
            }
            if ($rc != 1) {
                debug('!!!!!! unexpected number of attachments renamed: ', $rc);
            }
            // Note the edit
            #??$edits[] = ['a' => 'main', 'from' => $file_from_rel, 'to' => $file_to_rel];
            $edits[] = ['a' => 'main', 'from' => $item_from_rel, 'to' => $item_to_rel];


            // RENAME if we use the same code, renaming will need to update
            // the _wp_attachment_backup_sizes record to change foo.jpg to bar.jpg,
            //  and foo-nnnxnnn.jpg to bar-nnnxnnn.jpg
            // -- that will need to item names passed in.

            // attachment metadata has serialized data -- if changing strings,
            // need to change the length!! so have to get it, unpack it, change it, pack it, update it
            debug("updating attachment metadata...");
            $metadata = wp_get_attachment_metadata($post_id, true); // true for no filtering
            /*
            $sql = "select meta_id, post_id, meta_key, meta_value
                from $wpdb->postmeta
                where post_id = $post_id
                and meta_key = '_wp_attachment_metadata'";
            $row = $wpdb->get_row($sql, ARRAY_A);
             
            if (is_null($row)) {
             */
            if ($metadata === false) {
                debug('nmc: theres no metadata, thats ok');
                #throw new \Exception('Failed to get attachment with post_id ' . $post_id);
            } else {
                #debug('results: ', $row);
                #$metadata = unserialize($row['meta_value']);
                debug('metadata: ', $metadata);
                // Metadata is like:
                //    [width] => 400
                //    [height] => 600
                //    [file] => photos/avonway-plate.jpg
                //    [sizes] => Array
                //    (
                //        [thumbnail] => Array
                //        (
                //            [file] => avonway-plate-150x150.jpg
                //            [width] => 150
                //            [height] => 150
                //            [mime-type] => image/jpeg
                //        )
                //   )
                $metadata['file'] = $file_to_rel;

                // Move secondary files, renaming if required
                foreach ($metadata['sizes'] as $sizename => $size) {
                    $oldsec = $size['file'];
                    $newsec = '';
                    if ($item_to == $item_from) {
                        $newsec = $oldsec;
                    } else {
                        $newsec = new_secondary_name($item_to, $oldsec);
                        // Change it in the metadata
                        $metadata['sizes'][$sizename]['file'] = $newsec;
                    }
                    $path_from = UPLOAD_DIR . $dir_from . $oldsec;
                    $path_to   = UPLOAD_DIR . $dir_to   . $newsec;
                    debug("renaming $path_from to $path_to");
                    if (!rename($path_from, $path_to)) {  // puts a warning in the log on failure
                        throw new \Exception("Failed to rename $path_from to $path_to");
                    }
                    $renamed[] = ['from' => $path_from, 'to' => $path_to];
                    // Note the edit
                    #$edits[] = ['a' => 'metadata', 'from' => ltrim($dir_from . $oldsec, '/'), 'to' => ltrim($dir_to . $newsec, '/')];
                    $edits[] = ['a' => 'metadata', 'from' => UPLOAD_DIR_REL . $dir_from . $oldsec, 'to' => UPLOAD_DIR_REL . $dir_to . $newsec];
                    #'P1040025.jpg' to '/wp-content/uploadsP1040025.jpgP1040025-150x150.jpg'
                    // TODO edit posts containing the secondary name too!
                    // TODO ?? optimization -- posts often have e.g. the thumbnail and a link to the full size one,
                    //      so need two edits.
                }

                debug('changed metadata: ', $metadata);
                /*
                $serialized = serialize($metadata);
                $rc = $wpdb->update($wpdb->postmeta,
                    ['meta_value' => $serialized],
                    ['post_id' => $post_id, 'meta_value' => '_wp_attachment_metadata']);
                if ($rc === false) {
                */
                if (wp_update_attachment_metadata($post_id, $metadata) === false) {
                    throw new \Exception('Failed to update attachment metadata');
                }
                #debug('nmc: update metadata got rc ', $rc);

                // Then do much the same for backup sizes (created by WP when the image is edited)

                // TODO _wp_attachment_backup_sizes
                // Typical data:
                // (
                //    [full-orig] => Array
                //    (
                //        [width] => 2160
                //        [height] => 1440
                //        [file] => P1040028.jpg
                //    )
                //    [thumbnail-orig] => Array
                //    (
                //        [file] => P1040028-150x150.jpg
                //        [width] => 150
                //        [height] => 150
                //        [mime-type] => image/jpeg
                //    )
                //    [medium-orig] => Array
                //    (
                //        [file] => P1040028-300x200.jpg
                //        [width] => 300
                //        [height] => 200
                //        [mime-type] => image/jpeg
                //    )
                //    [medium_large-orig] => Array (
                //        [file] => P1040028-768x512.jpg
                //        [width] => 768
                //        [height] => 512
                //        [mime-type] => image/jpeg
                //    )
                //    [post-thumbnail-orig] => Array
                //    (
                //        [file] => P1040028-125x125.jpg
                //        [width] => 125
                //        [height] => 125
                //        [mime-type] => image/jpeg
                //    )
                // ... and if there are subsequent edits, entries like
                //    [full-1478377069693] => Array
                // (
                //    [width] => 1000
                //    [height] => 667
                //    [file] => P1040028-e1478376924462.jpg
                // )
                //)
                //  but it's all in the one metadata record.
                //TODO ?? will there be backup_sizes if no metadata?
                debug("updating attachment backup sizes...");
                $sql = "select meta_id, post_id, meta_key, meta_value
                    from $wpdb->postmeta
                    where post_id = $post_id
                    and meta_key = '_wp_attachment_backup_sizes'";
                $row = $wpdb->get_row($sql, ARRAY_A);
                if (is_null($row)) {
                    debug('nmc: therere no backup sizes, thats ok');
                    #throw new \Exception('Failed to get attachment with post_id ' . $post_id);
                } else {
                    #debug('results: ', $row);
                    $metadata = unserialize($row['meta_value']);
                    debug('backup sizes: ', $metadata);

                    // Move backup files, renaming if required
                    foreach ($metadata as $sizename => $size) {
                        $oldsec = $size['file'];
                        $newsec = '';
                        if ($item_to == $item_from) {
                            $newsec = $oldsec;
                        } else {
                            $newsec = new_secondary_name($item_to, $oldsec);
                            // Change it in the metadata
                            $metadata[$sizename]['file'] = $newsec;
                        }
                        $path_from = UPLOAD_DIR . $dir_from . $oldsec;
                        $path_to   = UPLOAD_DIR . $dir_to   . $newsec;
                        debug("renaming $path_from to $path_to");
                        if (!rename($path_from, $path_to)) {  // puts a warning in the log on failure
                            #throw new \Exception("Failed to rename $path_from to $path_to");
                            // TODO should we throw here?
                            debug("Failed to rename $path_from to $path_to, but carrying on regardless");
                        } else {
                            $renamed[] = ['from' => $path_from, 'to' => $path_to];
                        }
                        // Note the edit (although in theory posts shouldn't refer to backup files)
                        #??$edits[] = ['a' => 'backup', 'from' => ltrim($dir_from . $oldsec, '/'), 'to' => ltrim($dir_to . $newsec, '/')];
                        $edits[] = ['a' => 'backup', 'from' => UPLOAD_DIR_REL . $dir_from . $oldsec, 'to' => UPLOAD_DIR_REL . $dir_to . $newsec];
                    }

                    debug('changed backup metadata: ', $metadata);
                    // TODO consider using wp_update_attachment_metadata as below
                    $serialized = serialize($metadata);
                    $rc = $wpdb->update($wpdb->postmeta,
                        ['meta_value' => $serialized],
                        ['post_id' => $post_id, 'meta_value' => '_wp_attachment_backup_sizes']);
                    if ($rc === false) {
                        throw new \Exception('Failed to update attachment backups');
                    }
                    debug('nmc: update backup metadata got rc ', $rc);
                }

            } // else, no metadata
        }

        // TODO ?? need to catch moving a dir into its child? -- rename will fail anyway
        // TODO can we catch the rename failure message to display?
        // TODO posts seem to have alt="foo.jpg"

        // Update the text of posts and pages -- for moving folders as well as files
        // ?? Could this be optimised by doing all the edits on a post at once? -- not easily
        debug('edits: ', $edits);
        #$sql = "select * from $wpdb->posts
        foreach ($edits as $edit) {
            $post_count += update_posts_content($edit['from'], $edit['to'], $edit['a']);
            /*debug("updating posts from {$edit['from']} to {$edit['to']}");
            $sql = "update $wpdb->posts
                       set post_content = replace(post_content, '{$edit['from']}', '{$edit['to']}')
                     where post_content like '%{$edit['from']}%'";
                     #where post_type in ('post', 'page',
                #... what about post_status?

            $rc = $wpdb->query($sql);
            debug('>>> rc', $rc);
            if ($rc === false) {
                throw new \Exception('Failed to replace name in post content');
            }
            $post_count += $rc; */
        }

        // TEMP -- rollback for testing
        # throw new \Exception('test error');

        $wpdb->query("commit");
        ajax_response('true', 'Successful');

    } catch (\Exception $e) {
        // if that fails, rename it back...
        debug('nmc: caught exception: ', $e);
        debug('... rolling back and re-renaming');
        $wpdb->query("rollback");
        // FIXME duplicated code
        foreach ($renamed as $r) {
            debug("nmc: unrenaming " . $r['to'] . ' back to' . $r['from']);
            rename($r['to'], $r['from']);
        }
        rename($item_to_path, $item_from_path);   // puts a warning in the log on failure
        #die("Error ".$e->getMessage());
        ajax_response(false, $e->getMessage());
    }

    ajax_response(false, 'went too far');
}

function delete_empty_dir_callback() {

    if (!test_mfm_permission()) {
        ajax_response(false, 'no permission');
    }

    $dir  = get_post('dir');   // e.g. '/' or '/photos/'
	$name = get_post('name');  // e.g. 'dir_to_be_deleted'

    // FIXME need a sanity check on what we're trying to delete!!!!  !!!!!!!!
    $full_dir = UPLOAD_DIR . $dir . $name;

	if (!rmdir($full_dir)) {
        ajax_response(false, 'Unable to delete ' . $dir . $name);
	}
    ajax_response(true, 'Deleted OK');
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
		'Media File Manager CD plugin Configuration',
		'Media File Manager CD',
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
		<h2>Media File Manager CD plugin configurations</h2>

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
