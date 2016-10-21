<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function mrelocator_get_media_list_callback()
{
	global $wpdb;
	global $mrelocator_plugin_URL;

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
			$res[$i]->thumbnail = $mrelocator_plugin_URL . "/images/audio.png";
		} else if (substr($res[$i]->post_mime_type,0,5)=='video') {
			$res[$i]->thumbnail = $mrelocator_plugin_URL . "/images/video.png";
		} else if (substr($res[$i]->post_mime_type,0,5)=='image') {
			if (isset($meta['sizes']['thumbnail'])) {
				$res[$i]->thumbnail = $res[$i]->subfolder . $meta['sizes']['thumbnail']['file'];
			} else {
				$res[$i]->thumbnail = $res[$i]->file;
			}
		} else {
			$res[$i]->thumbnail = $mrelocator_plugin_URL . "/images/file.png";
		}
	}
	echo json_encode($res);
	die();
}
add_action('wp_ajax_mrelocator_get_media_list', 'mrelocator_get_media_list_callback');


function mrelocator_get_media_subdir_callback()
{
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
add_action('wp_ajax_mrelocator_get_media_subdir', 'mrelocator_get_media_subdir_callback');


function mrelocator_get_image_info_callback()
{
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
add_action('wp_ajax_mrelocator_get_image_info', 'mrelocator_get_image_info_callback');



function mrelocator_get_image_insert_screen_callback()
{
	global $wpdb;
	global $mrelocator_uploadurl;

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

	$urldir = $mrelocator_uploadurl . $file;
	$urldir = substr($urldir, 0, strrpos($urldir,"/")+1);
	$dat['urldir'] = $urldir;
	$url = $mrelocator_uploadurl . $file;

	if ($is_image) {
		$width=$meta['width'];
		$height=$meta['height'];

		if (isset($meta['sizes']['thumbnail'])) {
			$thumb = $urldir . $meta['sizes']['thumbnail']['file'];
		} else {
			$thumb = $mrelocator_uploadurl . $file;
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
?>
<div id="media-items">

<div class="media-item preloaded"><img class="pinkynail toggle" src="media-upload_data/aab-150x150.jpg" alt="" style="margin-top: 3px; display: none;"><div style="display: none;" class="progress"></div><div id="media-upload-error-4388"></div><div class="filename"></div>
	<div class="filename new"><span class="title"><?php echo $title;?></span></div>
	<table style="display: table;" class="slidetoggle describe">
		<thead class="media-item-info">
		<tr valign="top">
			<td class="A1B1">
			<p><a href="<?php echo bloginfo('url').'/?attachment_id='.$id;?>" target="_blank"><img class="thumbnail" src="<?php echo $thumb;?>" alt="" style="margin-top: 3px;"></a></p>
			<p><!--<input id="imgedit-open-btn-4388" onclick='imageEdit.open( 4388, "1f64e6952c" )' class="button" value="<?php _e("Edit Image");?>" type="button"> <img src="post.php_files/wpspin_light.gif" class="imgedit-wait-spin" alt="">--></p>
			</td>
			<td>
			<p><strong><?php _e('File name:');?></strong> <?php echo $file;?></p>
			<p><strong><?php _e('File type:');?></strong> <?php echo $mime_type;?></p>
			<p><strong><?php _e('Upload date:');?></strong> <?php echo $upload_date;?></p>
<?php if ($is_image): ?>
			<p><strong><?php _e('Dimensions:');?></strong> <span id="media-dims"><?php echo $width;?>&nbsp;×&nbsp;<?php echo $height;?></span> </p>
<?php endif; ?>
</td></tr>

		</thead>
		<tbody>
		<tr><td colspan="2" class="imgedit-response" id="imgedit-response-4388"></td></tr>
		<tr><td style="display: none;" colspan="2" class="image-editor" id="image-editor-4388"></td></tr>
		<tr class="post_title form-required">
			<th scope="row" class="label" valign="top"><label for="attachments[4388][post_title]"><span class="alignleft"><?php _e('Title');?></span><span class="alignright"><abbr title="required" class="required">*</abbr></span><br class="clear"></label></th>
			<td class="field"><input class="text" id="attachments_post_title" name="attachments_post_title" value="<?php echo $title;?>" aria-required="true" type="text"></td>
		</tr>
<?php if ($is_image): ?>
		<tr class="image_alt">
			<th scope="row" class="label" valign="top"><label for="attachments_image_alt"><span class="alignleft"><?php _e('Alternate Text');?></span><br class="clear"></label></th>
			<td class="field"><input class="text" id="attachments_image_alt" name="attachments_image_alt" value="<?php echo $alt;?>" type="text"><p class="help"><?php _e('Alt text for the image, e.g. “The Mona Lisa”');?></p></td>
		</tr>
<?php endif; ?>
		<tr class="post_excerpt">
			<th scope="row" class="label" valign="top"><label for="attachments_post_excerpt"><span class="alignleft"><?php _e('Caption');?></span><br class="clear"></label></th>
			<td class="field"><input class="text" id="attachments_post_excerpt" name="attachments_post_excerpt" value="<?php echo $caption;?>" type="text"></td>
		</tr>
		<tr class="post_content">
			<th scope="row" class="label" valign="top"><label for="attachments_post_content"><span class="alignleft"><?php _e('Description');?></span><br class="clear"></label></th>
			<td class="field"><textarea id="attachments_post_content" name="attachments_post_content"><?php echo $description;?></textarea></td>
		</tr>
		<tr class="url">
			<th scope="row" class="label" valign="top"><label for="attachments_url"><span class="alignleft"><?php _e('Link URL');?></span><br class="clear"></label></th>
			<td class="field">
	<input class="text urlfield" id="attachments_url" name="attachments_url" value="<?php echo $url;?>" type="text"><br>
	<button type="button" id="urlnone" class="button urlnone" data-link-url=""><?php _e('None');?></button>
	<button type="button" id="urlfile" class="button urlfile" data-link-url="<?php echo $url;?>"><?php _e('File URL');?></button>
	<button type="button" id="urlpost" class="button urlpost" data-link-url="<?php echo bloginfo('url').'/?attachment_id='.$id;?>"><?php _e('Attachment Post URL');?></button>
<p class="help"><?php _e('Enter a link URL or click above for presets.');?></p></td>
		</tr>
<?php if ($is_image): ?>
		<tr class="align">
			<th scope="row" class="label" valign="top"><label for="attachments_align"><span class="alignleft"><?php _e('Alignment');?></span><br class="clear"></label></th>
			<td class="field">
<input name="attachments_align" id="image-align-none" value="none" checked="checked" type="radio"><label for="image-align-none" class="align image-align-none-label"><?php _e('None');?></label>
<input name="attachments_align" id="image-align-left" value="left" type="radio"><label for="image-align-left" class="align image-align-left-label"><?php _e('Left');?></label>
<input name="attachments_align" id="image-align-center" value="center" type="radio"><label for="image-align-center" class="align image-align-center-label"><?php _e('Center');?></label>
<input name="attachments_align" id="image-align-right" value="right" type="radio"><label for="image-align-right" class="align image-align-right-label"><?php _e('Right');?></label></td>
		</tr>
		<tr class="image-size">
			<th scope="row" class="label" valign="top"><label for="attachments-image-size"><span class="alignleft"><?php _e('Size');?></span><br class="clear"></label></th>
			<td class="field">
<div class="image-size-item"><input <?php echo $disable_thumbnail;?> name="attachments-image-size" id="image-size-thumbnail" value="thumbnail" type="radio"><label for="image-size-thumbnail"><?php _e('Thumbnail');?></label> <label for="image-size-thumbnail" class="help"><?php echo $size_thumbnail;?></label></div>
<div class="image-size-item"><input <?php echo $disable_medium;?> name="attachments-image-size" id="image-size-medium" value="medium" type="radio"><label for="image-size-medium"><?php _e('Medium');?></label> <label for="image-size-medium" class="help"><?php echo $size_medium;?></label></div>
<div class="image-size-item"><input <?php echo $disable_large;?> name="attachments-image-size" id="image-size-large" value="large" type="radio"><label for="image-size-large"><?php _e('Large');?></label> <label for="image-size-large" class="help"><?php echo $size_large;?></label></div>
<div class="image-size-item"><input name="attachments-image-size" id="image-size-full" value="full" checked="checked" type="radio"><label for="image-size-full"><?php _e('Full Size');?></label> <label for="image-size-full" class="help"><?php echo $size_full;?></label></div></td>
		</tr>
<?php endif;?>
		<tr class="submit"><td></td><td class="savesend"><input name="send" id="send" class="button" value="<?php _e('Insert into Post');?>" type="submit">  
			<button type="button" id="mrl_cancel" class="button" ><?php _e('Cancel');?></button>
			
		</td></tr>
		</tbody>
	</table>
</div>
</div>
<div id="mrl_data" style="display:none;"><?php echo json_encode($dat);?></div>
<?php
	die();
}

add_action('wp_ajax_mrelocator_get_image_insert_screen', 'mrelocator_get_image_insert_screen_callback');

function mrelocator_update_media_information_callback()
{
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

	wp_update_post( $edit_post );
	die();
}
add_action('wp_ajax_mrelocator_update_media_information', 'mrelocator_update_media_information_callback');


/**
 *  processing plugin
 */
class MrlMediaSelector
{
	/**
	 *  The URL that points to the directory of this plugin.
	 */
	private $pluginDirUrl;

	/**
	 * Initialize instance
	 */
	public function __construct()
	{
		$exp = explode( DIRECTORY_SEPARATOR, dirname( __FILE__ ) );
		$this->pluginDirUrl = WP_PLUGIN_URL . '/' . array_pop( $exp ) . "/";

		// register handler
		if( is_admin() )
		{
			// action
			add_action( "admin_head_media_upload_mrlMS_form", array( &$this, "onMediaHead"      )     ); /* reading js */
			add_action( "media_buttons",                         array( &$this, "onMediaButtons"   ), 20 );
			add_action( "media_upload_mrlMS",                 "media_upload_mrlMS"                 );

			// filter
			add_filter( "admin_footer", array( &$this, "onAddShortCode" ) );
		}
	}

	/**
	 *  embed a script to insert a shortcoed.
	 */
	public function onAddShortCode()
	{
		//  only in the posting page 投稿の編集画面だけを対象とする
		if( strpos( $_SERVER[ "REQUEST_URI" ], "post.php"     ) ||
			strpos( $_SERVER[ "REQUEST_URI" ], "post-new.php" ) ||
			strpos( $_SERVER[ "REQUEST_URI" ], "page-new.php" ) ||
			strpos( $_SERVER[ "REQUEST_URI" ], "page.php"     ) ||
			strpos( $_SERVER[ "REQUEST_URI" ], "index.php"     ) )
		{
			echo <<<HTML
<script type="text/javascript">
//<![CDATA
function onMrlMediaSelector_ShortCode( text ) { send_to_editor( text ); }
//]]>
</script>
HTML;
		}
	}

	/**
	 *  This function is called when setting a media button. 
	 */
	public function onMediaButtons()
	{
		$cur_roles0 = get_option('mediafilemanager_accepted_roles_selector', 'administrator,editor,author,contributor,subscriber');
		$cur_roles = explode(',', $cur_roles0);
		if (!check_user_role($cur_roles)) return;

		global $post_ID, $temp_ID;

		$id     = (int)( 0 == $post_ID ? $temp_ID : $post_ID );
		$iframe = apply_filters( "media_upload_mrlMS_iframe_src", "media-upload.php?post_id={$id}&amp;type=mrlMS&amp;tab=mrlMS" );
		$option = "&amp;TB_iframe=true&amp;keepThis=true&amp;height=500&amp;width=640";
		$title  = "Media-selector";
		$button = "{$this->pluginDirUrl}images/media_folder.png";

//		echo '<a href="' . $iframe . $option . '" class="thickbox" title="' . $title . '"><img src="' . $button . '" alt="' . $title . '" /></a>';
		echo ' <a href="' . $iframe . $option . '" class="wp-media-buttons button add_media thickbox" title="' . $title . '">';
		echo '<span class="wp-media-buttons-icon" ></span><span  style="background-color:#ff0;"> &nbsp;&nbsp;'.$title.'&nbsp;&nbsp; </a> </span></span>';
	}

	/**
	 *  This function is called when showing contents in the dialog opened by pressing a media button.
	 */
	public function onMediaButtonPage()
	{
		global $mrelocator_uploaddir;
		global $mrelocator_uploadurl;
		global $mrelocator_plugin_URL;
		echo "<script type=\"text/javascript\"> var uploaddir = '".$mrelocator_uploaddir."' </script>\n";
		echo "<script type=\"text/javascript\"> var uploadurl = '".$mrelocator_uploadurl."' </script>\n";
		echo "<script type=\"text/javascript\"> var pluginurl = '".$mrelocator_plugin_URL."' </script>\n";

		echo '<p></p>';
		echo '<div id="mrl_control"> </div>';
		echo '<div id="mrl_selector"> </div>';
		echo '<div id="mrl_edit"> </div>';
	}

	/**
	 *  This function is called when generating header of a window opened by a media button.
	 */
	public function onMediaHead()
	{
		wp_enqueue_script( "media-selector", plugins_url( 'media-selector.js', __FILE__ ));
	}

	/**
	 * This function is called when setting tabs in the window opened by pressing a media button.
	 *
	 * @param	$tabs	規定のタブ情報コレクション。
	 *
	 * @return	実際に表示するタブ情報コレクション。
	 */
	function onModifyMediaTab( $tabs )
	{
		return array( "mrlMS" => "Choose a media" );
	}
}



// create an instance of plugin
if (1) 
{
	$MrlMediaSelector = new MrlMediaSelector();

	// The following functions are called only in the administration page.
	if( is_admin() )
	{
		/**
		 * This function is called when opening a windows by pressing a media button.メディアボタンからダイアログが起動された時に呼び出されます。
		 */
		function media_upload_mrlMS()
		{
			wp_iframe( "media_upload_mrlMS_form" );
		}

		/**
		 *  This function is called when showing contents in the dialog opened by pressing a media button.メディアボタンから起動されたダイアログの内容を出力する為に呼び出されます。
		 */
		function media_upload_mrlMS_form()
		{
			global $MrlMediaSelector;

	wp_enqueue_script('jquery');

			add_filter( "media_upload_tabs", array( &$MrlMediaSelector, "onModifyMediaTab" ) );

			echo "<div id=\"media-upload-header\">\n";
			media_upload_header();
			echo "</div>\n";

			$MrlMediaSelector->onMediaButtonPage();
		}
	}
}
//add_action( 'admin_init', 'MrlMediaButtonInit' );

function check_user_role($roles,$user_id=NULL) {
	// Get user by ID, else get current user
	if ($user_id)
		$user = get_userdata($user_id);
	else
		$user = wp_get_current_user();
 
	// No user found, return
	if (empty($user))
		return FALSE;
 
	// Append administrator to roles, if necessary
	if (!in_array('administrator',$roles))
		$roles[] = 'administrator';
 
	// Loop through user roles
	//echo "<pre>";print_r($roles);echo "</pre>";
	foreach ($user->roles as $role) {
	//echo $role;
		// Does user have role
		if (in_array($role,$roles)) {
			return TRUE;
		}
	}
 //echo "<br>false<br>";
	// User not in roles
	return FALSE;
}
?>
