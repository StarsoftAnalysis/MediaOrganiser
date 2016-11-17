<?php 
namespace media_organiser_cd;

// AJAX responders

// Send an AJAX response back to javascript, in the form
// [
//   success => true or false
//   message => 'blah',  // reason for the failure
//   data => an array of stuff, e.g....
// ]
// Could use wp_send_json_success
// or wp_send_json? no, I prefer mine
function ajax_response ($success = false, $message = '', $data = []) {
    debug('ajax_response');
    $response = [
        'success' => ($success ? true : false),  // convert truthy/falsy into proper booleans
        'message' => $message,
        'data'    => $data
    ];
    #debug('send ajax: ', $response);
    #sleep(5); // TEMP slow it down
    header('Content-Type: application/json;');
    echo json_encode($response);
    wp_die();
}

function mkdir_callback() {
    debug('mkdir_callback');
    if (!test_mfm_permission()) {
        ajax_response(false, 'no permission');
    }
	$dir    = get_post('dir');
    $newdir = get_post('newdir');
    $path = UPLOAD_DIR . $dir;
    $newpath = $path . $newdir;
    #debug('mkdir_c:', $path, $newpath);
    if (file_exists($newpath)) {
        ajax_response(false, "Can't create '$newdir' -- already exists");
    }
    if (!mkdir($newpath, 0777)) {
        ajax_response(false, "Failed to create '$newdir'");
    }
    ajax_response(true, "Created '$newdir' successfully");
}


// AJAX response ...
function getdir_callback () {
    debug('getdir_callback');
    global $wpdb;
    if (!test_mfm_permission()) {
        ajax_response(false, 'no permission');
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
    #debug("gc: post_dir = $post_dir   dir = $dir   attdir = $attdir");
    $dirlist = [];
    // Get the subdirectories first
    $sdirs = subdirs($dir);
    foreach ($sdirs as $sdir) {
        $dirlist[] = [
            #'id' => null,
            #'path' => $sdir, // FIXME either don't use it or make it different from name
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
    $sql = "select p.ID, p.post_mime_type, m.meta_value
              from wp_posts p
         left join wp_postmeta m on p.ID = m.post_id and m.meta_key = '_wp_attached_file'
             where post_type = 'attachment'
               and m.meta_value regexp '^{$attdir}[^/]+$'
          order by m.meta_value"; // FIXME better way to interpolate
    #debug('gc sql: ', $sql);
    $results = $wpdb->get_results($sql, ARRAY_A);
    #debug('gc results: ', $results);
    foreach ($results as $item) {
        $dirlist[] = [
            'post_id'  => $item['ID'],
            #'path'     => $item['meta_value'],
            'name'     => basename($item['meta_value']),
            'isdir'    => false,
            'isthumb'  => false, // always false now
            'norename' => false, // TODO
            'parent'   => false, // always false now
            // TODO if it's an image, get a nice small version of it (then it will be square)
            'thumbnail_url' => thumbnail_url($item['meta_value'], $item['post_mime_type'], $item['ID'])
            ];
    }
    #debug('gc dirlist: ', $dirlist);

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
    ajax_response(true, 'Got dir OK', $dirlist);
	#echo json_encode($dirlist);
    #wp_die(); // completes the AJAX thing
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
        #echo 'nnnxnnn: ', $nnnxnnn, "\n";
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

// FIXME this function is too big
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
    $dir_from  = get_post('dir_from');  // e.g. '/' or '/photos/'
    $dir_to    = get_post('dir_to');    //    ditto
    $item_from = get_post('item_from'); // e.g. 'foo.jpg' or 'images/foo.jpg'.  NOTE no leading '/'
    //                                      and it's relative to $dir_from
    $item_to   = get_post('item_to');   //    ditto
    if (!$item_to) {
        $item_to = $item_from;
    }
    $post_id   = get_post('post_id');
    $isdir     = get_post('isdir') == 'true';
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
    $item_from_path = UPLOAD_DIR     . $dir_from . $item_from;       // full file path, e.g. /var/www/website/wp_content/uploads/photos/foo.jpg
    $item_to_path   = UPLOAD_DIR     . $dir_to   . $item_to;
    $item_from_rel  = UPLOAD_DIR_REL . $dir_from . $item_from;   // relative to site root, e.g. /wp_content/uploads/photos/foo.jpg
    $item_to_rel    = UPLOAD_DIR_REL . $dir_to   . $item_to;
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
        ajax_response(false, "Unable to rename '" . $dir_from . $item_from . "' to '" . $dir_to . $item_to . "'");
    }
    $renamed[] = ['from' => $item_from_path, 'to' => $item_to_path];

    // Update the database:

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
                    // TODO last_error_msg is too wordy - just the bit after the last colon?
                    // TODO if secondary file doesn't exist, we might as well carry on regardless.
                    if (!rename($path_from, $path_to)) {  // puts a warning in the log on failure
                        $lem = last_error_msg();
                        if (preg_match('/no such file/i', $lem)) {
                            debug('Secondary file not found -- no worries');
                        } else {
                            throw new \Exception("Failed to rename '$path_from' to '$path_to'. Reason: $lem");
                        }
                    } else {
                        $renamed[] = ['from' => $path_from, 'to' => $path_to];
                        // Note the edit
                        #$edits[] = ['a' => 'metadata', 'from' => ltrim($dir_from . $oldsec, '/'), 'to' => ltrim($dir_to . $newsec, '/')];
                        $edits[] = ['a' => 'metadata', 'from' => UPLOAD_DIR_REL . $dir_from . $oldsec, 'to' => UPLOAD_DIR_REL . $dir_to . $newsec];
                    }
                    #'P1040025.jpg' to '/wp-content/uploadsP1040025.jpgP1040025-150x150.jpg'
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
                            //throw new \Exception("Failed to rename $path_from to $path_to");
                            // Not throwing the error, because backup files aren't so important, are they??
                            debug("Failed to rename $path_from to $path_to, but carrying on regardless");
                        } else {
                            $renamed[] = ['from' => $path_from, 'to' => $path_to];
                            // Note the edit (although in theory posts shouldn't refer to backup files)
                            #??$edits[] = ['a' => 'backup', 'from' => ltrim($dir_from . $oldsec, '/'), 'to' => ltrim($dir_to . $newsec, '/')];
                            $edits[] = ['a' => 'backup', 'from' => UPLOAD_DIR_REL . $dir_from . $oldsec, 'to' => UPLOAD_DIR_REL . $dir_to . $newsec];
                        }
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

        debug('Successful: committing and returning "true"');
        $wpdb->query("commit");
        ajax_response(true, 'Successful');

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
    // (open_basedir PHP setting will help)
    $full_dir = UPLOAD_DIR . $dir . $name;
	if (!rmdir($full_dir)) {
        ajax_response(false, 'Unable to delete \'' . $dir . $name . '\'.  Reason: ' . last_error_msg());
	}
    ajax_response(true, 'Deleted OK');
}

?>
