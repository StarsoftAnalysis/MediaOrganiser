<?php
namespace media_organiser_cd;

function get_media_list_callback() {
	global $wpdb;

	$res = $wpdb->get_results(
		"SELECT ".
		"post_title, ID, meta_value as file, post_mime_type, post_title, ".
		"substr(meta_value,1, (length(meta_value)-instr(reverse(meta_value),'/')+1)*(instr(meta_value,'/')>0)) as subfolder ".
		"FROM $wpdb->postmeta, $wpdb->posts ".
		"WHERE post_id=ID ".
		"AND meta_key='_wp_attached_file' ".
		"ORDER BY post_title ");
	for ($i=0; $i<count($res); $i++) {
		$meta = wp_get_attachment_metadata($res[$i]->ID);
		if (substr($res[$i]->post_mime_type,0,5)=='audio') {
			$res[$i]->thumbnail = PLUGIN_URL . "/images/audio.png";
		} else if (substr($res[$i]->post_mime_type,0,5)=='video') {
			$res[$i]->thumbnail = PLUGIN_URL . "/images/video.png";
		} else if (substr($res[$i]->post_mime_type,0,5)=='image') {
			if (isset($meta['sizes']['thumbnail'])) {
				$res[$i]->thumbnail = $res[$i]->subfolder . $meta['sizes']['thumbnail']['file'];
			} else {
				$res[$i]->thumbnail = $res[$i]->file;
			}
		} else {
			$res[$i]->thumbnail = PLUGIN_URL . "/images/file.png";
		}
	}
	echo json_encode($res);
	die();
}


function get_media_subdir_callback() {
	global $wpdb;
	$res = $wpdb->get_results(
		"SELECT  ".
		"DISTINCT LEFT(meta_value, CHAR_LENGTH(meta_value)-CHAR_LENGTH(SUBSTRING_INDEX(meta_value, '/', -1))) AS subdir ".
		"FROM $wpdb->postmeta ".
		"WHERE meta_key = '_wp_attached_file' ".
		"AND meta_value LIKE '%/%' ".
		"AND meta_value <> '.' AND meta_value <> '..' ".
		"ORDER BY subdir ");
	echo json_encode($res);
	die();
}

function get_image_info_callback() {
	global $wpdb;
	$id = $_POST['id'];
	if (!is_numeric($id)) {
		die("error");
	}

	$query = $wpdb->prepare(
		"SELECT * from $wpdb->posts ".
		"WHERE id='%d'",$id);
	$res = $wpdb->get_results($query);
	$ret->posts = $res[0];

	$meta = wp_get_attachment_metadata($id);
	$ret->meta = $meta;

	$query = $wpdb->prepare(
		"SELECT meta_value FROM $wpdb->postmeta WHERE post_id='%d' AND meta_key='_wp_attached_file'", $id);
	$file = $wpdb->get_results($query);	
	$ret->file = $file[0]->meta_value;

	$query = $wpdb->prepare(
		"SELECT meta_value FROM $wpdb->postmeta WHERE post_id='%d' AND meta_key='_wp_attachment_image_alt'", $id);
	$alt = $wpdb->get_results($query);
	if ($alt) {
		$ret->alt = $alt[0]->meta_value;
	} else {
		$ret->alt = "";
	}
//print_r($ret);
	echo json_encode($ret);
	die();
}



function get_image_insert_screen_callback() {
	global $wpdb;

	$id = $_POST['id'];
	if (!is_numeric($id)) {
		die("error");
	}

	$mime_type = "";
	$upload_date="";
	$width=0;
	$height=0;
	$file="";
	$title="";
	$alt="";
	$caption="";
	$thumb="";
	$description="";
	$url="";
	$dat =  array();

	$query = $wpdb->prepare(
		"SELECT * from $wpdb->posts ".
		"WHERE id='%d'", $id);
	$res = $wpdb->get_results($query);
	if (count($res)) {
		$mime_type = $res[0]->post_mime_type;
		$upload_date=$res[0]->post_date;
		$title=esc_html($res[0]->post_title);
		$caption=esc_html($res[0]->post_excerpt);
		$description=esc_html($res[0]->post_content);
		$dat['posts'] = $res[0];
	}

	$is_image = (substr($mime_type, 0, 5)=='image');

	$query = $wpdb->prepare(
		"SELECT meta_value FROM $wpdb->postmeta WHERE post_id='%d' AND meta_key='_wp_attached_file'", $id);
	$res = $wpdb->get_results($query);
	if (count($res)) {
		$file = $res[0]->meta_value;
	}

	$meta = wp_get_attachment_metadata($id);
	$dat['meta'] = $meta;
	$dat['is_image'] = $is_image;

	$urldir = UPLOAD_URL . $file;
	$urldir = substr($urldir, 0, strrpos($urldir,"/")+1);
	$dat['urldir'] = $urldir;
	$url = UPLOAD_URL . $file;

	if ($is_image) {
		$width=$meta['width'];
		$height=$meta['height'];

		if (isset($meta['sizes']['thumbnail'])) {
			$thumb = $urldir . $meta['sizes']['thumbnail']['file'];
		} else {
			$thumb = UPLOAD_URL . $file;
		}

		$size_thumbnail="";
		$size_medium="";
		$size_large="";
		$size_full="";
		$disable_thumbnail='disabled="disabled"';
		$disable_medium='disabled="disabled"';
		$disable_large='disabled="disabled"';

		if (isset($meta['sizes']['thumbnail'])) {
			$size_thumbnail='('.$meta['sizes']['thumbnail']['width']." x ".$meta['sizes']['thumbnail']['height'].')';
			$disable_thumbnail="";
		}
		if (isset($meta['sizes']['medium'])) {
			$size_medium='('.$meta['sizes']['medium']['width']." x ".$meta['sizes']['medium']['height'].')';
			$disable_medium="";
		}
		if (isset($meta['sizes']['large'])) {
			$size_large='('.$meta['sizes']['large']['width']." x ".$meta['sizes']['large']['height'].')';
			$disable_large="";
		}
		$size_full='('.$meta['width']." x ".$meta['height'].')';

		$query = $wpdb->prepare(
			"SELECT meta_value FROM $wpdb->postmeta WHERE post_id='%d' AND meta_key='_wp_attachment_image_alt'", $id);
		$res = $wpdb->get_results($query);
		if (count($res)) {
			$alt = esc_html($res[0]->meta_value);
		}
	}
    echo '<div id="media-items">';

    echo '<div class="media-item preloaded">';
    echo '<img class="pinkynail toggle" src="media-upload_data/aab-150x150.jpg" alt="" style="margin-top: 3px; display: none;">';
    echo '<div style="display: none;" class="progress">';
    echo '</div>';
    echo '<div id="media-upload-error-4388"></div>';
    echo '<div class="filename"></div>';
	echo '<div class="filename new"><span class="title"><?php echo $title;?></span></div>';
	echo '<table style="display: table;" class="slidetoggle describe">';
	echo '<thead class="media-item-info">';
	echo '<tr valign="top">';
	echo '<td class="A1B1">';
    echo '<p><a href="', bloginfo('url') , '/?attachment_id=' , $id , '" target="_blank">';
    echo '<img class="thumbnail" src="', $thumb, '" alt="" style="margin-top: 3px;"></a></p>';
    //echo '<p><!--<input id="imgedit-open-btn-4388" onclick='imageEdit.open(4388, "1f64e6952c")' class="button" value="', _e("Edit Image"), '" type="button"> <img src="post.php_files/wpspin_light.gif" class="imgedit-wait-spin" alt="">--></p>';
	echo '</td>';
	echo '<td>';
	echo '<p><strong>', _e('File name:'), '</strong> ', $file, '</p>';
	echo '<p><strong>', _e('File type:'), '</strong> ', $mime_type, '</p>';
	echo '<p><strong>', _e('Upload date:'), '</strong> ', $upload_date, '</p>';
    if ($is_image) {
        echo '<p><strong>', _e('Dimensions:'), '</strong> <span id="media-dims">', $width, '&nbsp;×&nbsp;', $height, '</span></p>';
    }
    echo '</td></tr>';
    echo '</thead>';
    echo '<tbody>';
	echo '<tr><td colspan="2" class="imgedit-response" id="imgedit-response-4388"></td></tr>';
	echo '<tr><td style="display: none;" colspan="2" class="image-editor" id="image-editor-4388"></td></tr>';
    echo '<tr class="post_title form-required">';
    // FIXME what's 4388?
    echo '<th scope="row" class="label" valign="top"><label for="attachments[4388][post_title]">';
    echo '<span class="alignleft">', _e('Title'), '</span><span class="alignright"><abbr title="required" class="required">*</abbr></span>';
    echo '<br class="clear"></label></th>';
    echo '<td class="field"><input class="text" id="attachments_post_title" name="attachments_post_title" value="', $title, '" aria-required="true" type="text"></td>';
    echo '</tr>';
    if ($is_image) {
		echo '<tr class="image_alt">';
        echo '<th scope="row" class="label" valign="top"><label for="attachments_image_alt">';
        echo '<span class="alignleft">', _e('Alternate Text'), '</span>';
        echo '<br class="clear"></label></th>';
        echo '<td class="field"><input class="text" id="attachments_image_alt" name="attachments_image_alt" value="', $alt, '" type="text">';
        echo '<p class="help">', _e('Alt text for the image, e.g. “The Mona Lisa”'), '</p></td>';
		echo '</tr>';
    }
    echo '<tr class="post_excerpt">';
	echo '<th scope="row" class="label" valign="top"><label for="attachments_post_excerpt"><span class="alignleft">', _e('Caption'), '</span><br class="clear"></label></th>';
	echo '<td class="field"><input class="text" id="attachments_post_excerpt" name="attachments_post_excerpt" value="', $caption, '" type="text"></td>';
	echo '</tr>';
	echo '<tr class="post_content">';
	echo '<th scope="row" class="label" valign="top"><label for="attachments_post_content"><span class="alignleft">', _e('Description'), '</span><br class="clear"></label></th>';
	echo '<td class="field"><textarea id="attachments_post_content" name="attachments_post_content">', $description, '</textarea></td>';
	echo '</tr>';
	echo '<tr class="url">';
	echo '<th scope="row" class="label" valign="top"><label for="attachments_url"><span class="alignleft">', _e('Link URL'), '</span><br class="clear"></label></th>';
	echo '<td class="field">';
	echo '<input class="text urlfield" id="attachments_url" name="attachments_url" value="', $url, '" type="text"><br>';
	echo '<button type="button" id="urlnone" class="button urlnone" data-link-url="">', _e('None'), '</button>';
	echo '<button type="button" id="urlfile" class="button urlfile" data-link-url="', url, '">', _e('File URL'), '</button>';
	echo '<button type="button" id="urlpost" class="button urlpost" data-link-url="', bloginfo('url'), '/?attachment_id=', $id, '">', _e('Attachment Post URL'), '</button>';
    echo '<p class="help">', _e('Enter a link URL or click above for presets.'), '</p></td>';
    echo '</tr>';
    if ($is_image) {
		echo '<tr class="align">';
		echo '<th scope="row" class="label" valign="top"><label for="attachments_align"><span class="alignleft">', _e('Alignment'), '</span><br class="clear"></label></th>';
		echo '<td class="field">';
        echo '<input name="attachments_align" id="image-align-none" value="none" checked="checked" type="radio"><label for="image-align-none" class="align image-align-none-label">', _e('None'), '</label>';
        echo '<input name="attachments_align" id="image-align-left" value="left" type="radio"><label for="image-align-left" class="align image-align-left-label">', _e('Left'), '</label>';
        echo '<input name="attachments_align" id="image-align-center" value="center" type="radio"><label for="image-align-center" class="align image-align-center-label">', _e('Center'), '</label>';
        echo '<input name="attachments_align" id="image-align-right" value="right" type="radio"><label for="image-align-right" class="align image-align-right-label">', _e('Right'), '</label></td>';
        echo '</tr>';
        echo '<tr class="image-size">';
        echo '<th scope="row" class="label" valign="top"><label for="attachments-image-size"><span class="alignleft">', _e('Size'), '</span><br class="clear"></label></th>';
        echo '<td class="field">';
        echo '<div class="image-size-item"><input ', $disable_thumbnail, ' name="attachments-image-size" id="image-size-thumbnail" value="thumbnail" type="radio"><label for="image-size-thumbnail">', _e('Thumbnail'), '</label> <label for="image-size-thumbnail" class="help">', $size_thumbnail, '</label></div>';
        echo '<div class="image-size-item"><input ', $disable_medium, ' name="attachments-image-size" id="image-size-medium" value="medium" type="radio"><label for="image-size-medium">', _e('Medium'), '</label> <label for="image-size-medium" class="help">', $size_medium, '</label></div>';
        echo '<div class="image-size-item"><input ', $disable_large, ' name="attachments-image-size" id="image-size-large" value="large" type="radio"><label for="image-size-large">', _e('Large'), '</label> <label for="image-size-large" class="help">', $size_large, '</label></div>';
        echo '<div class="image-size-item"><input name="attachments-image-size" id="image-size-full" value="full" checked="checked" type="radio"><label for="image-size-full">', _e('Full Size'), '</label> <label for="image-size-full" class="help">', $size_full, '</label></div></td>';
        echo '</tr>';
    }
    echo '<tr class="submit"><td></td><td class="savesend"><input name="send" id="send" class="button" value="', _e('Insert into Post'), '" type="submit">';
    echo '<button type="button" id="mocd_cancel" class="button" >', _e('Cancel'), '</button>';
	echo '</td></tr>';
    echo '</tbody>';
	echo '</table>';
    echo '</div>';
    echo '</div>';
    // FIXME really dump data in the html? -- better to do it the WP way?
    echo '<div id="mocd_data" style="display:none;">';
    echo json_encode($dat);
    echo '</div>';

	die();
}


function update_media_information_callback() {
    $id = (int)$_POST['id'];
    $alt = $_POST['alt'];
    if ($alt != "$none$") {
        update_post_meta($id, '_wp_attachment_image_alt', $alt);
    }
    $edit_post = array();
    $edit_post['ID'] = $id;
    $edit_post['post_title'] = $_POST['title'];
    $edit_post['post_excerpt'] = $_POST['caption'];
    $edit_post['post_content'] = $_POST['description'];

    wp_update_post($edit_post);
    die();
}



/**
 *  embed a script to insert a shortcoed.
 */
function onAddShortCode() {
    //  only in the posting page 投稿の編集画面だけを対象とする
    $request_uri = $_SERVER['REQUEST_URI'];
    if (strpos($request_uri, "post.php"   ) ||  // pos will never be 0
        strpos($request_uri, "post-new.php") ||
        strpos($request_uri, "page-new.php") ||
        strpos($request_uri, "page.php"   ) ||
        strpos($request_uri, "index.php"  )   )
    {
        echo '<script type="text/javascript">';
        echo 'function onMrlMediaSelector_ShortCode(text) { send_to_editor(text); }';
        echo '</script>';
    }
}

/**
 *  This function is called when setting a media button. 
 */
function onMediaButtons() {
    $cur_roles0 = get_option('mediafilemanager_accepted_roles_selector', 'administrator,editor,author,contributor,subscriber');
    $cur_roles = explode(',', $cur_roles0);
    if (!check_user_role($cur_roles)) {
        debug('onMediaButtons -- wrong role, cur_roles = ', cur_roles);
        return;
    }
    debug('onMediaButtons -- role OK');

    global $post_ID, $temp_ID;

    $id     = (int)(0 == $post_ID ? $temp_ID : $post_ID);
    $iframe = apply_filters("media_upload_mrlMS_iframe_src", "media-upload.php?post_id={$id}&amp;type=mrlMS&amp;tab=mrlMS");
    #$option = "&amp;TB_iframe=true&amp;keepThis=true&amp;height=500&amp;width=640";
    # Try without Thickbox
    $option = "&amp;TB_iframe=false&amp;keepThis=true&amp;height=500&amp;width=640";
    $title  = "Media Organiser Selector";
    $button = PLUGIN_URL . "images/media_folder.png";

    //		echo '<a href="' . $iframe . $option . '" class="thickbox" title="' . $title . '"><img src="' . $button . '" alt="' . $title . '" /></a>';
    echo ' <a href="' . $iframe . $option . '" class="wp-media-buttons button Xadd_media thickbox" title="' . $title . '">';
    echo '<span class="wp-media-buttons-icon" ></span><span  style="background-color:#ff0;"> &nbsp;&nbsp;'.$title.'&nbsp;&nbsp; </a> </span></span>';
}

/**
 *  This function is called when showing contents in the dialog opened by pressing a media button.
 */
function onMediaButtonPage() {
    // Now done via localise-script below
    #echo "<script type=\"text/javascript\"> var uploaddir = '".UPLOAD_DIR."' </script>\n";
    #echo "<script type=\"text/javascript\"> var uploadurl = '".UPLOAD_URL."' </script>\n";
    #echo "<script type=\"text/javascript\"> var pluginurl = '".PLUGIN_URL."' </script>\n";

    echo '<p>(HTML created by media-selector.php:onMediaButtonPage)</p>';
    echo '<div id="mocd_control"> </div>';
    echo '<div id="mocd_selector"> </div>';
    echo '<div id="mocd_edit"> </div>';
}

/**
 *  This function is called when generating header of a window opened by a media button.
 */
function onMediaHead() {
    // FIXME the frontend doesn't need to know about these.  Or does it?
    $constants = [
        'uploaddir' => UPLOAD_DIR,
        'uploadurl' => UPLOAD_URL,
        'pluginurl' => PLUGIN_URL
    ];
    wp_enqueue_script("media-selector", plugins_url('media-selector.js', __FILE__));
    wp_localize_script('media-selector', 'mocd_constants', $constants);
}

/**
 * This function is called when setting tabs in the window opened by pressing a media button.
 *
 * @param	$tabs	規定のタブ情報コレクション。
 *
 * @return	実際に表示するタブ情報コレクション。
 */
function onModifyMediaTab($tabs)
{
    return array("mrlMS" => "Choose a media item (ms.php~372)" );
}


// FIXME Inline code
// create an instance of plugin
#$MrlMediaSelector = new MrlMediaSelector();

/**
 * This function is called when opening a windows by pressing a media button.メディアボタンからダイアログが起動された時に呼び出されます。
   CD   because type=tab=mrlMS when calling media-upload.php from onMediaButtons above/
 */
function media_upload_mrlMS() {
    wp_iframe(NS . "media_upload_mrlMS_form");
}

/**
 *  This function is called when showing contents in the dialog opened by pressing a media button.メディアボタンから起動されたダイアログの内容を出力する為に呼び出されます。
 */
function media_upload_mrlMS_form() {
    #global $MrlMediaSelector;

    #wp_enqueue_script('jquery');

    // add a 'tab'
    add_filter("media_upload_tabs", NS . "onModifyMediaTab");

    echo "<div id=\"media-upload-header\">\n";
    media_upload_header();
    echo "</div>\n";

    onMediaButtonPage();
}
//add_action('admin_init', 'MrlMediaButtonInit');

function check_user_role ($roles, $user_id = NULL) {
    // Get user by ID, else get current user
    if ($user_id) {
        $user = get_userdata($user_id);
    } else {
        $user = wp_get_current_user();
    }

    // No user found, return
    if (empty($user)) {
        return FALSE;
    }

    // Append administrator to roles, if necessary
    if (!in_array('administrator', $roles)) {
        $roles[] = 'administrator';
    }

    // Loop through user roles
    //echo "<pre>";print_r($roles);echo "</pre>";
    foreach ($user->roles as $role) {
        //echo $role;
        // Does user have role
        if (in_array($role, $roles)) {
            return TRUE;
        }
    }
    // User not in roles
    return FALSE;
}


    add_action('wp_ajax_mocd_get_media_list',           NS . 'get_media_list_callback');
    add_action('wp_ajax_mocd_get_media_subdir',         NS . 'get_media_subdir_callback');
    add_action('wp_ajax_mocd_get_image_info',           NS . 'get_image_info_callback');
    add_action('wp_ajax_mocd_get_image_insert_screen',  NS . 'get_image_insert_screen_callback');
    add_action('wp_ajax_mocd_update_media_information', NS . 'update_media_information_callback');
    add_action("admin_head_media_upload_mrlMS_form", NS . "onMediaHead"    ); /* reading js */  /* what's that all about FIXME */
    add_action("admin_head", NS . "onMediaHead"    ); /* reading js */
    add_action("media_buttons",                      NS . "onMediaButtons" , 20);
    // !! see wp-admin/media-upload.php -- media_upload... action seems to relate to
    //    old media system.
    #add_action("media_upload_mrlMS",                 NS . "media_upload_mrlMS"                );
    add_filter("admin_footer", NS . "onAddShortCode");
?>
