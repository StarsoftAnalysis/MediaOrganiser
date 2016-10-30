// FIXME silly class
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
		$exp = explode(DIRECTORY_SEPARATOR, dirname(__FILE__));
		$this->pluginDirUrl = WP_PLUGIN_URL . '/' . array_pop($exp) . "/";

		// register handler
		if (is_admin())
		{
            // TODO PUT THESE IN TOP LEVEL
			// action
			add_action("admin_head_media_upload_mrlMS_form", array(&$this, "onMediaHead"     )    ); /* reading js */
			add_action("media_buttons",                         array(&$this, "onMediaButtons"  ), 20);
			add_action("media_upload_mrlMS",                 NS . "media_upload_mrlMS"                );

			// filter
			add_filter("admin_footer", array(&$this, "onAddShortCode"));
		}
	}

	/**
	 *  embed a script to insert a shortcoed.
	 */
	public function onAddShortCode()
	{
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
	public function onMediaButtons()
	{
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
		echo "<script type=\"text/javascript\"> var uploaddir = '".UPLOAD_DIR."' </script>\n";
		echo "<script type=\"text/javascript\"> var uploadurl = '".UPLOAD_URL."' </script>\n";
		echo "<script type=\"text/javascript\"> var pluginurl = '".PLUGIN_URL."' </script>\n";

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
		wp_enqueue_script("media-selector", plugins_url('media-selector.js', __FILE__));
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
		return array("mrlMS" => "Choose a media item");
	}
}


// FIXME Inline code
// create an instance of plugin
$MrlMediaSelector = new MrlMediaSelector();

/**
 * This function is called when opening a windows by pressing a media button.メディアボタンからダイアログが起動された時に呼び出されます。
 */
function media_upload_mrlMS() {
    wp_iframe(NS . "media_upload_mrlMS_form");
}

/**
 *  This function is called when showing contents in the dialog opened by pressing a media button.メディアボタンから起動されたダイアログの内容を出力する為に呼び出されます。
 */
function media_upload_mrlMS_form() {
    global $MrlMediaSelector;

    wp_enqueue_script('jquery');

    add_filter("media_upload_tabs", array(&$MrlMediaSelector, "onModifyMediaTab"));

    echo "<div id=\"media-upload-header\">\n";
    media_upload_header();
    echo "</div>\n";

    $MrlMediaSelector->onMediaButtonPage();
}
//add_action('admin_init', 'MrlMediaButtonInit');

