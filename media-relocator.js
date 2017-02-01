
// namespace:
var mocd = mocd || {};

mocd.ajax_count = 0;    // no. of outstanding ajax calls

mocd.pane_right = {};	// Pane class objects
mocd.pane_left = {};

mocd.adjust_layout = function () {
	var width_all = jQuery('#mocd_wrapper_all').width();
	var width_center = jQuery('#mocd_center_wrapper').width(); 
	var height_mocd_box = jQuery('.mocd_box1').height();
	var pane_w = (width_all - width_center)/2 - 2;
	jQuery('.mocd_wrapper_pane').width(pane_w);
	jQuery('.mocd_path').width(pane_w);
	jQuery('.mocd_pane').width(pane_w);
}

// Encode and decode HTML (from http://stackoverflow.com/questions/1219860/html-encoding-in-javascript-jquery)
mocd.htmlEncode = function (str) {
    return str
        .replace(/&/g, '&amp;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#27;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;');
}
mocd.htmlDecode = function (str){
    return str
        .replace(/&amp;/g, '&')
        .replace(/&quot;/g, '"')
        .replace(/&#x27;/g, "'")
        .replace(/&lt;/g, '<')
        .replace(/&gt;/g, '>');
}

mocd.ajax_count_in = function () {
	mocd.ajax_count++;
	//document.body.style.cursor = "wait";
}

// function name: mocd.ajax_count_out
// description : recognize finishing ajax procedure
// argument : (void)
mocd.ajax_count_out = function () {
	mocd.ajax_count--;
	if (mocd.ajax_count <= 0) {
		mocd.ajax_count = 0;
		//document.body.style.cursor = "default";
	}
}

// TODO use this for the progress meter dialog
// Simple queue for dialog messages
// from http://stackoverflow.com/questions/7300040/jquery-modal-that-can-be-queued
mocd.message  = {};  // jQuery thing for the dialog, assigned when doc is ready  // FIXME rename this
mocd.messages = [];  // array of sets of details to display
mocd.message_dialog = {}; // jQuery dialog object, ditto
mocd.create_message_dialog = function () {
    mocd.message_dialog = mocd.message.dialog({
        autoOpen: false,
        appendTo: '#mocd_wrap',
        modal: true, //false,
        title: 'initial title', 
        buttons: {
            OK: function() {
                mocd.message_dialog.dialog("close");
            }
        },
        close: function () {
            if (mocd.messages.length > 0) {
                // Let this dialog close, then open the next one
                setTimeout(mocd.show_message_dialog, 0);
            }   
        }
    });
};
// Show dialog with the next message in the queue
mocd.show_message_dialog = function () {
    if (mocd.messages.length <= 0) {
        // Make sure dialog is closed
        mocd.message_dialog.dialog('close');
        return;
    }
    var details = mocd.messages.shift();
    mocd.message.html(details.message);
    mocd.message_dialog
        .dialog('option', 'title', details.title)
        .dialog('open');
    // Auto-close if it's just for information
    if (details.timeout > 0) {
        setTimeout(function () {
            mocd.message_dialog.dialog('close');
        }, details.timeout);
    }
};
mocd.add_message = function (title, message, timeout) {
    mocd.messages.push({title: title, message: message, timeout: timeout});
    if (!mocd.message_dialog.dialog("isOpen")) {
        mocd.show_message_dialog(); //(mocd.messages.shift());  // displayMessage();
    }
};

mocd.display_response = function (response) {
    console.log('display_response: ', response);
    //mocd.message_dialog(
    mocd.add_message(
        (response.success ? 'Success' : 'Failure'),
        response.message,
        (response.success ? 3000 : 0)
    );
}

mocd.progress = function progress() {
    var val = progressbar.progressbar('value') || 0;
    progressbar.progressbar('value', val + 1);
}

mocd.new_move_items = function nmi (pane_from, pane_to) {
    var checked_items = []; // list of indexes of checked items
    for (var i = 0; i < pane_from.dir_list.length; i++) {
        var id = '#' + pane_from.get_chkid(i);
        //console.log(i, id);
        if (jQuery(id).attr('checked')) {
            checked_items.push(i);
        }
    }
    var count = checked_items.length;
    if (count == 0) {
        return;
    }
    // Set up progress bar
    var progressbar = jQuery('#mocd_progressbar'),
        progresslabel = jQuery('#mocd_progresslabel');
    progressbar.progressbar({
        max: checked_items.length,
        change: function () {
            progresslabel.text('Moving: ' + progressbar.progressbar('value') + ' of ' + count);
        },
        complete: function () {
            progresslabel.text('Moving: Done');
        }
    });
    // Pop-up the progress bar as a dialog
    var progress_dialog = progressbar.dialog({
        appendTo: '#mocd_wrap',
        autoOpen: true,
        resizable: false,
        modal: true,
        dialogClass: 'mocd_dialog_no_close',
    });
    //progressbar.css('display', 'block');
    progresslabel.text('Moving: 0 of ' + count);
    progressbar.progressbar('enable');
    //for (var i = 0; i < pane_from.dir_list.length; i++) {
    //    var id = '#' + pane_from.get_chkid(i);
    //    //console.log(i, id);
    //    if (jQuery(id).attr('checked')) {
    var done_count = 0;
    var fail_count = 0;
    var err_msgs = [];
    for (var c = 0; c < count; c++) {
        var i = checked_items[c];
        //flist.push(pane_from.dir_list[i].name);
        //isdirs.push(pane_from.dir_list[i].isdir);
        //
        // LOOP sending requests one at a time...
        //console.log(flist);
        // FIXME this is duplicated
        var data = {
            action:    'mocd_move',
            dir_from:  pane_from.cur_dir,
            dir_to:    pane_to.cur_dir,
            item_from: pane_from.dir_list[i].name,
            post_id:   pane_from.dir_list[i].post_id,
            isdir:     pane_from.dir_list[i].isdir, 
            nonce:     mocd_array.nonce
        };
        //console.log('nmi sending data: ', data);
        mocd.ajax_count_in();
        // ... and dealing with a series of responses    work TODO here
        jQuery.post(ajaxurl, data, function (response) {
            mocd.ajax_count_out();
            done_count += 1;
            progressbar.progressbar('value', done_count);   // TODO should it say 'done' if there were errors?
            if (!response.success) {
                fail_count += 1;
                err_msgs.push(response.message);
                console.log(response);
            }
            if (done_count >= count) {
                var pause = 1000;
                if (fail_count > 0) {
                    // One or more failures
                    // Replace the progress bar with an error message
                    var msg = '<p>Error: ' + fail_count + ' items were not moved.';
                    for (var i = 0; i < err_msgs.length; i++) {
                        msg += '<p>' + err_msgs[i];
                    }
                    // TODO the list of errors could be very big -- just show a few of them??
                    //mocd.message_dialog('One or more items could not be moved', msg, 0);
                    mocd.add_message('Failure', msg, 0);

                    //mocd.message.html(msg);
                    //pause = 0;
                    //// TODO move this into a function
                    //var dialog = mocd.message.dialog({
                    //    autoOpen: true,
                    //    appendTo: '#mocd_wrap',
                    //    modal: false,
                    //    title: "One or more items could not be moved",
                    //    buttons: {
                    //        OK: function() {
                    //            dialog.dialog("close");
                    //        }
                    //    }
                    //});
                }
                setTimeout(function () {  // pause with the 'Done' message on the screen
                    progressbar.progressbar('destroy');
                    progress_dialog.dialog('close');
                }, pause);
                mocd.pane_left.refresh();
                mocd.pane_right.refresh();
            }
            //}
        });
    } 
}


// **** Pane class *******************************************************************
var MOCDPaneClass = function (id_root) {  // id_root is either 'mocd_left' or 'mocd_right'
	this.cur_dir = "";
	this.dir_list = new Array();
	//this.dir_disp_list = new Array();   // NOT NEEDED -- now they're all displayed
	this.id_root          = id_root;
	this.wrapper          = jQuery('#' + id_root + "_wrapper");  // TODO store the jQuery thing, not just the id (?)
	this.id_pane          = id_root + "_pane";
    this.pane             = jQuery('#' + this.id_pane);
	this.dir_name         = jQuery('#' + id_root + "_path");
	this.dir_new_btn      = jQuery('#' + id_root + "_dir_new");
	this.dir_up_btn       = jQuery('#' + id_root + "_dir_up");
    this.id_rename_dialog = id_root + '_rename_dialog';
    this.rename_field     = jQuery('#' + id_root + '_rename'); // input fields in the dialog
    this.rename_i_field   = jQuery('#' + id_root + '_rename_i');
    this.rename_error     = jQuery('#' + id_root + '_rename_error');
    this.id_newdir_dialog = id_root + '_newdir_dialog';
    this.newdir_field     = jQuery('#' + id_root + '_newdir');
    this.newdir_error     = jQuery('#' + id_root + '_newdir_error');
	this.opposite         = {};

	var thispane = this;

	this.dir_up_btn.click(function(ev) {
		if (mocd.ajax_count > 0     ||
		    thispane.cur_dir == '/'    ) {
            return;
        }
		thispane.chdir("..");
	});

    // 'Select All' box affects all boxes on this pane
    // (but only for files, not folders)
	jQuery('div.mocd_pane').on('click', '#' + this.id_pane + '_ck_all', function(ev) {
        var all_checked = this.checked;
        jQuery('.' + thispane.id_pane + '_ck').each(function () {
            var idx = thispane.get_idx_from_id(this.id);
            if (!thispane.dir_list[idx].isdir) {
                this.checked = all_checked;
            }
        });
	});

    // Set up rename dialog
    // (activated by [Rename] button on each item -- added later)
    this.rename_field.keypress(this.filter_item_name_characters);
    // TODO get rid of the [X] and cancel buttons
    // // -- no -- disable the cancel button once [Rename] has been pressed
    // TODO need a timeout in case it goes wrong...
    this.rename_dialog = jQuery("#" + this.id_rename_dialog).dialog({
        appendTo: '#mocd_wrap', //this.id_rename_dialog, // '#mocd_wrap',
        autoOpen: false,
        resizable: false,
        modal: true,
        buttons: [
            {
                text: 'Rename',
                click: thispane.rename_dialog_callback.bind(thispane),
                id: 'mocd_rename_rename_btn',
            },
            {
                text: 'Cancel',
                click: function() {
                    thispane.rename_dialog.dialog("close");
                },
                id: 'mocd_rename_cancel_btn',
            }
        ],
        open: function (event, ui) {
            //console.log('opening dialog');
        },
        close: function() {
            thispane.rename_field.removeClass("ui-state-error");
            thispane.rename_error.empty();
        }
    });
    
    // Set up new dir dialog
	this.dir_new_btn.click(function () {
        thispane.newdir_dialog.dialog("open");
    });
    this.newdir_field.keypress(this.filter_item_name_characters);
    this.newdir_dialog = jQuery("#" + this.id_newdir_dialog).dialog({
        appendTo: '#mocd_wrap',
        autoOpen: false,
        resizable: false,
        modal: true,
        buttons: {
            "Create": thispane.newdir_dialog_callback.bind(thispane, 'x42'),
            Cancel: function() {
                thispane.newdir_dialog.dialog("close");
            }
        },
        open: function (event, ui) {
            //console.log('opening dialog');
        },
        close: function() {
            thispane.newdir_field.removeClass("ui-state-error");
            thispane.newdir_error.empty();
        }
    });

}

MOCDPaneClass.prototype.filter_item_name_characters = function (e) {
    // Filter out invalid characters
    var key = String.fromCharCode(e.which);
    // TODO make this a constant in the main class
    // NOTE This needs to match the invalid_chars string in relocator_ajax.php
    // FIXME and therefore depends on the operating system of the server...
    //var invalid_chr = ["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&", "'", " ", "`"];
    //console.log('--- key=', key);
    // invalid_itemname_chars depends on server OS, so is supplied by backend
    if (mocd_array.invalid_itemname_chars.indexOf(key) >= 0) {
        return false;
    }
}

MOCDPaneClass.prototype.rename_dialog_callback = function () {
    // 'this' is the pane object, thanks to the bind on the call
    // (otherwise it would be the div containing the form element)
    console.log('rdc called');
    var newname = this.rename_field.val();
    var index = this.rename_i_field.val();
    var existing = false; //this.name_exists(newname);
    if (existing === false) {
        // disable the buttons  TODO need to disable the [X] button too
        jQuery('#mocd_rename_rename_btn').attr('disabled', true);
        jQuery('#mocd_rename_cancel_btn').attr('disabled', true);
        // send the request to the backend
        this.ajax_rename_item(index, newname);
        // wait for the reply before closing the dialog
    } else if (existing == index) {  // (not '===' 'cos one's a string, one's an integer)
        // name hasn't changed
        this.rename_dialog.dialog('close');
    } else {
        this.rename_error.html("There is already a file or folder called '" + newname + "'");
        this.rename_field.addClass('ui-state-error');
        // dialog stays open
    }
}

MOCDPaneClass.prototype.newdir_dialog_callback = function () {
    // 'this' is the pane object, thanks to the bind on the call
    // (otherwise it would be the div containing the form element)
    var newdir = this.newdir_field.val();
    if (this.name_exists(newdir) !== false) {       //  not class=error:
        jQuery('#' + this.id_newdir_error).html("<span class=error>There is already a file or folder called '" + newdir + "'</span>");
        jQuery(this.newdir_field).addClass('ui-state-error');
        return false;
    }
    this.newdir_dialog.dialog('close');
    this.ajax_newdir(newdir);
    return true;
}

// SORT THESE OUT
MOCDPaneClass.prototype.get_chkid = function (n) {
    return this.id_pane + '_ck_' + n;
}
MOCDPaneClass.prototype.get_renameid = function (n) {
    return this.id_pane + '_ren_' + n;
}
MOCDPaneClass.prototype.get_deleteid = function (n) {
    return this.id_pane + '_del_' + n;
}
// Get the trailing number from a CSS id, e.g. foo_n -> n
MOCDPaneClass.prototype.get_idx_from_id = function (id) {
    return id.substring(id.lastIndexOf('_') + 1);
}
// 'divid' is the div containing the whole item?? or just the thumbnail?
// // -- we'll try the latter, i.e. the <li>
MOCDPaneClass.prototype.get_divid = function (n) {
    return this.id_pane + '_' + n;
}
MOCDPaneClass.prototype.refresh = function () {
    this.setdir(this.cur_dir);
}

// function name: MOCDPaneClass::setdir
// description : move to the directory and display directory listing
// argument : (dir)absolute path name of the target directory
MOCDPaneClass.prototype.setdir = function(dir) {
	//? this.wrapper.css('cursor:wait'); // TODO move this into count_in
	var data = {
		action: 'mocd_getdir',
		dir:    dir,
        nonce:  mocd_array.nonce
	};
	var that = this;
	mocd.ajax_count_in();
	jQuery.post(ajaxurl, data, function(response) {
        if (response.success) {
            // Process the json directory from ajax,
            // create the html, and store the list
            that.dir_list = that.set_dir(data.dir, response.data);
            // Adjust layout here because a long list will cause the
            // window to get a vertical scrollbar, and become narrower
            mocd.adjust_layout();
        } else {
            mocd.display_response(response);
        }
		mocd.ajax_count_out();
	});
}

// TODO rename this -- too similar to setdir
// function name: MOCDPaneClass::set_dir
// description : display directory list sent from server
//               in response to mocd_getdir ajax request              
MOCDPaneClass.prototype.set_dir = function (target_dir, dir) {
	//var dir;
    var thispane = this;

	this.cur_dir = target_dir;
	this.dir_name.text('Folder: ' + mocd.htmlEncode(target_dir)); 
	this.disp_num = 0;

	var html = "";
    var thumb_url = '';

    html += '<ul class=mocd_pane_list>';

    var select_all_done = false; // first-time flag for adding the 'select all files' tick box

    // Display all items as a list
    // On each row, use CSS table properties to arrange the bits.
	for (i = 0; i < dir.length; i++) {
        var item = dir[i];

        // Add the 'select all' box before the first non-directory
        if (!item.isdir && !select_all_done) {
            html += '<li class=mocd_pane_item id="mocd_pane_select_all">';
            html += '<div class="mocd_pane_cell"></div>'; // just for spacing
            html += '<div class="mocd_pane_cell">';
            html += '<div class="mocd_pane_img"></div>'; // just as spacing
            html += '<div><input type="checkbox" id="' + this.id_pane + '_ck_all' + '"></div>';
            html += '<div>&nbsp;Select All Files</div>';
            html += '</div>';
            html += '</li>';
            select_all_done = true;
        }

        var divid = this.get_divid(i);
        html += '<li class="mocd_pane_item" id="' + divid + '">'; 
        // Thumbnail img URL should always be supplied by backend
        // (i.e. even for folders and non-images)
		if (item.thumbnail_url && item.thumbnail_url != "") {
            thumb_url = item.thumbnail_url;
		} else {
            thumb_url = 'notfound.jpg';  
        }
        var dirclass = item.isdir ? ' mocd_clickable' : '';
        // 1st cell contains the image
		html += '<div class="mocd_pane_cell">';
		html += '<img class="mocd_pane_img' + dirclass + '" src="' + thumb_url + '">';
		html += '</div>';
        // 2nd cell contains: text <br> box button button
        html += '<div class="mocd_pane_cell">'; // b
        html += '<div class="mocd_filename">' + mocd.htmlEncode(item.name) + '</div>';
        html += '<br><div><input type="checkbox" class="' + this.id_pane + '_ck' + '" id="' + 
            this.get_chkid(i) + '" title="Select to move this item to the opposite folder"></div>';
        if (item.exists) {
            html += ' <div><button type="button" class="mocd_pane_rename_btn" id="' + 
                this.get_renameid(i) + '">Rename</button></div>';
        } else {
            html += ' (file is missing)';
        }
        if (item.isemptydir) {
            html += ' <div><button type="button" class="mocd_pane_delete_btn" id="' + 
                this.get_deleteid(i) + '">Delete</button></div>';
        }
        html += '</div>'
        html += '</li>';
	}
    html += '</ul>';
    this.pane.html(html);

    // Now that the items are in the DOM,
    // go through the list again and set folders as clickable.
    // Note cunning use of bind to avoid having to re-extract
    // the index from the item's id or data.
    //
    // Plan B -- use delegation, getting the index from the CSS id
    // // TODO ? use data- -- WordPress seems to use HTML5 anyway
    
    // 'Rename' buttons
    this.pane.on('click', '.mocd_pane_rename_btn', function () {
        if (mocd.ajax_count > 0) {
            return;
        }
        //alert('rename ' + thispane.dir_list[i].name);
        //thispane.ajax_rename_item(i);
        // Set the current value before opening the form
        var i = thispane.get_idx_from_id(this.id);
        var name = thispane.dir_list[i].name;
        thispane.rename_field.val(name);
        thispane.rename_i_field.val(i);
        jQuery('#mocd_rename_rename_btn').attr('disabled', false);
        jQuery('#mocd_rename_cancel_btn').attr('disabled', false);
        thispane.rename_dialog.dialog("open");
    });
    
    // 'Delete' buttons (only exist on empty dirs)    // TODO allow files to be deleted??
    this.pane.on('click', '.mocd_pane_delete_btn', function () {
        if (mocd.ajax_count > 0) {
            return;
        }
        var i = thispane.get_idx_from_id(this.id);
        thispane.ajax_delete_empty_dir(thispane.dir_list[i].name);
    });

    // Folders are clickable ('up' and 'new' are done separately)
    this.pane.on('click', '.mocd_pane_img.mocd_clickable', function () {
        if (mocd.ajax_count > 0) {
            return;
        }
        var li = jQuery(this).closest('li');
        var i = thispane.get_idx_from_id(li.attr('id'));
        thispane.chdir(thispane.dir_list[i].name);
    });

    //
    //for (i = 0; i < dir.length; i++) {
    //    var name = dir[i].name;
    //    if (dir[i].isdir) {
    //        // Make folder clickable
    //        var divid = this.get_divid(i);  // e.g. 'mocd_left_pane_1'
    //        jQuery('#'+divid).on('click', 'img', function (newdir, e) {
    //            if (mocd.ajax_count > 0) {
    //                return;
    //            }
    //            thispane.chdir(newdir);
    //        }.bind(this, name));  // name gets passed in as newdir
    //    }
   // }

    //? this.wrapper.css('cursor:default');
    return dir;
}

// Send AJAX request to rename an item, given its index in the dir_list
// // This does both 'move' and 'rename'
MOCDPaneClass.prototype.ajax_rename_item = function (i, newname) {
    console.log('ari called');
    var thispane = this;
    if (!thispane.dir_list[i].name) {
        console.log('ajax_rename_item -- now item for i=', i);
        return;
    }
    var oldname = thispane.dir_list[i].name;

    var data = {
        action:    'mocd_move',
        dir_from:  thispane.cur_dir,
        dir_to:    thispane.cur_dir,
        item_from: oldname,
        item_to:   newname,
        post_id:   thispane.dir_list[i].post_id,
        isdir:     thispane.dir_list[i].isdir,
        nonce:     mocd_array.nonce
    };
    //console.log('nmi sending data: ', data);
    mocd.ajax_count_in();
    jQuery.post(ajaxurl, data, function (response) {
        // FIXME why do we never get here??
        console.log('rename reply: ', response);
        mocd.ajax_count_out();
        if (response.success) {
            //        mocd.display_response(response);
            thispane.refresh();
            // Refresh opposite pane if it's showing the same directory
            if (thispane.cur_dir == thispane.opposite.cur_dir) {
                thispane.opposite.refresh();
            }
            // Update opposite pane if it's showing the directory we've just renamed
            if (thispane.opposite.cur_dir == thispane.cur_dir + oldname + "/") {
                thispane.opposite.setdir(thispane.cur_dir + newname + '/'); // Linux only
            }
            thispane.rename_dialog.dialog('close');
        } else {
            // Keep the dialog open, display the message, re-enable the buttons
            thispane.rename_error.empty().text(response.message);
            jQuery('#mocd_rename_rename_btn').attr('disabled', false);
            jQuery('#mocd_rename_cancel_btn').attr('disabled', false);
        }
    });
} 

// Send AJAX request to create a new directory
MOCDPaneClass.prototype.ajax_newdir = function (newdir) {
    var thispane = this;
    var data = {
        action: 'mocd_mkdir',
        dir:    thispane.cur_dir,
        newdir: newdir,
        nonce:  mocd_array.nonce
    };
    mocd.ajax_count_in();
    jQuery.post(ajaxurl, data, function(response) {
        if (!response.success) {
            mocd.display_response(response);
        } else {
            thispane.refresh();
            if (thispane.cur_dir == thispane.opposite.cur_dir) {
                thispane.opposite.refresh();
            }
        }
        mocd.ajax_count_out();
    });
} 

// Send AJAX request to delete an empty directory, given its index in the dir_list
MOCDPaneClass.prototype.ajax_delete_empty_dir = function (name) {
    var thispane = this;
    var data = {
        action: 'mocd_delete_empty_dir',
        dir:    this.cur_dir,
        name:   name,
        nonce:  mocd_array.nonce
    };
    mocd.ajax_count_in();
    jQuery.post(ajaxurl, data, function(response) {
        mocd.display_response(response);
        if (response.success) {
            thispane.refresh();
            // Refresh opposite pane if it's showing the same directory
            if (thispane.cur_dir == thispane.opposite.cur_dir) {
                thispane.opposite.refresh();
            }
            // Update the opposite pane if it's showing the dir we've just deleted
            if (thispane.opposite.cur_dir == thispane.cur_dir + name + "/") {
                thispane.opposite.setdir(thispane.cur_dir);
            }
        }
        mocd.ajax_count_out();
    });
}

// TODO a new 'set_checkboxes' function that disables checkboxes
// for items that have items with the same name in the opposite pane
// -- if both sides are the same, then can't do any moving!!! TODO

// See if an item exists in the pane; return its index or false
MOCDPaneClass.prototype.name_exists = function (str) {
	for (var i = 0; i < this.dir_list.length; i++) {
		if (this.dir_list[i]['name'] === str) {
			return i;
		}
	}
	return false;
}

// Assumes 'dir' has no slashes; this.cur_dir has start and end slashes.
// If not changing directory, runs set_dir anyway to refresh the pane.
MOCDPaneClass.prototype.chdir = function (dir) {
	//var last_chr = this.cur_dir.substr(this.cur_dir.length-1,1);
	var new_dir = this.cur_dir;
    if (dir == '.') {
        // nothing to do
    } else if (dir == "..") {
        if (new_dir == '/') {
            // already at the top
        } else {
            // convert e.g. /photos/big/ to /photos/
            new_dir = new_dir.split('/').slice(0, -2).join('/') + '/'; // FIXME Linux only
		}
	} else {
        new_dir += dir + '/';
	}
	this.setdir(new_dir);
}

// Handle the Action drop-down -- rename, move, or delete.
//MOCDPaneClass.prototype.actions = function (action) {
//    console.log('action = ', action);
//}

// ----------- End of pane class definition 


jQuery(document).ready(function() {

    mocd.message = jQuery('#mocd_message');
    mocd.create_message_dialog();
    //setTimeout(function () {mocd.add_message('msg1', 'adlsakjalsdk', 3000); }, 2000);
    //setTimeout(function () {mocd.add_message('msg1', 'no timeout',  0); }, 2000);

	mocd.pane_left = new MOCDPaneClass('mocd_left');
	mocd.pane_right = new MOCDPaneClass('mocd_right');

	mocd.pane_left.opposite = mocd.pane_right;
	mocd.pane_right.opposite = mocd.pane_left;

    // Start in the top-level folders
	mocd.pane_left.setdir("/");
	mocd.pane_right.setdir("/");

	jQuery('#mocd_btn_left2right').click(function() {
		if (mocd.ajax_count > 0) {
            return;
        }
		mocd.new_move_items(mocd.pane_left, mocd.pane_right);
	});
	jQuery('#mocd_btn_right2left').click(function() {
		if (mocd.ajax_count > 0) {
            return;
        }
		mocd.new_move_items(mocd.pane_right, mocd.pane_left);
	});

    jQuery('#mocd_left_button_go').on('click', function () {
        var action = jQuery('#mocd_left_action select').val();
        mocd.pane_left.actions(action);
    });
    jQuery('#mocd_right_button_go').on('click', function () {
        var action = jQuery('#mocd_right_action select').val();
        mocd.pane_right.actions(action);
    });

	jQuery(window).resize(function() {
		//jQuery('#debug').html(jQuery('#wpbody').height());
		mocd.adjust_layout();
	});
    //mocd.adjust_layout();
});



