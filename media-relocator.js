// TODO make it sensible
//   - better way of deciding when it's busy than counting number of ajax calls
//   - use wp_die, not die or exit. -- 
// - get rid of jQuery.bind -- use .on
// - don't use right-click
// - nicer buttons and icons

// -- put them in a namespace
var mocd = mocd || {};
mocd.ajax_count = 0;
mocd.right_click_menu = {};	// Right-click menu class object
mocd.input_text = {};	// Text-input form class object -- just one, shared between panes

mocd.mouse_x = 0; 
mocd.mouse_y = 0;
mocd.pane_right = {};	// Pane class objects
mocd.pane_left = {};


// function name: adjust_layout
// description : adjust layout when resized
// argument : (void)
mocd.adjust_layout = function () {
	var width_all = jQuery('#mocd_wrapper_all').width();
	var height_all = jQuery('#mocd_wrapper_all').height();
	var width_center =jQuery('#mocd_center_wrapper').width(); 
	var height_mocd_box = jQuery('.mocd_box1').height();

	var position = jQuery('#wpbody').offset();
	height_all = jQuery(window).height() - position.top - 100;

	var pane_w = (width_all - width_center)/2 - 16;
	jQuery('.mocd_wrapper_pane').width(pane_w);
	jQuery('.mocd_path').width(pane_w);
	jQuery('.mocd_pane').width(pane_w);
//	// TODO does this help? -- seems better without this line:  jQuery('.mocd_pane').height(height_all - height_mocd_box);	
	//jQuery('.mocd_filename').width(pane_w - 32);
}


// function name: mocd.ajax_count_in
// description : recognize entering ajax procedure to avoid user interrupt while data processing
// argument : (void)
mocd.ajax_count_in = function () {
	mocd.ajax_count++;
	document.body.style.cursor = "wait";
	if (mocd.ajax_count == 1) {
        jQuery(document).bind('click.mrl', function(e){
	    	e.cancelBubble = true;
		    if (e.stopPropagation) {
                e.stopPropagation();
            }
		    e.preventDefault();
	    });
    }
}
// function name: mocd.ajax_count_out
// description : recognize finishing ajax procedure
// argument : (void)
mocd.ajax_count_out = function () {
	mocd.ajax_count--;
	if (mocd.ajax_count == 0) {
		document.body.style.cursor = "default";
		jQuery(document).unbind('click.mrl');
	}
}

mocd.display_error = function display_error (msg) {
    console.log('display_error msg:', msg);
    alert(msg);
    // TODO don't (just) alert
}
// This is the new improved version
mocd.display_response = function (response) {
    // TODO something better than alert
    console.log('display_response: ', response);
    alert(response.message);
}

mocd.new_move_items = function nmi (pane_from, pane_to) {
    //// FIXME collect results and put up progress bar
    //var flist = [];  // list of filenames to return
    //var isdirs = []; // temp hack!!
    for (var i = 0; i < pane_from.dir_list.length; i++) {
        var id = '#' + pane_from.get_chkid(i);
        //console.log(i, id);
        if (jQuery(id).attr('checked')) {
            //flist.push(pane_from.dir_list[i].name);
            //isdirs.push(pane_from.dir_list[i].isdir);
            //
            // LOOP sending requests one at a time...
            //console.log(flist);
            // FIXME this is duplicated
            var data = {
                action: 'new_mocd_move',
                dir_from: pane_from.cur_dir,
                dir_to:   pane_to.cur_dir,
                item_from:  pane_from.dir_list[i].name,
                    // !! add item_to if renaming
                post_id:  pane_from.dir_list[i].post_id,
                isdir:    pane_from.dir_list[i].isdir 
            };
            //console.log('nmi sending data: ', data);
            // TODO do we need to do them in batches?
            mocd.ajax_count_in();
            // ... and dealing with a series of responses   FIXME work TODO here
            jQuery.post(ajaxurl, data, function (response) {
                // FIXME standard way of handling responses and errors
                //if (response.search(/Success/i) < 0) alert("mrloc_move(): "+response);
                mocd.display_response(response);
                //if (mocd.move_continue) {
                //	move_items(mocd.pane_from, mocd.pane_to, mocd.move_no+1);
                //} else {
                mocd.pane_left.refresh();
                mocd.pane_right.refresh();
                mocd.ajax_count_out();
                //}
            });
        } 
    }
}

// FIXME?  this isn't in the class as claimed
// function name: MOCDPaneClass::move
// description : moving checked files/directories
// argument : (pane_from)pane object; (pane_to)pane object 
mocd.pane_from = "";
mocd.pane_to = "";
mocd.num_par_no = 0;
mocd.move_no = 0;
mocd.move_cnt = 0;
mocd.move_continue=0;

mocd.move_items = function move_items (pane_from, pane_to, no) {
	no = typeof no !== 'undefined' ? no : 0;
	var cnt = mocd.move_cnt;
	
	if (no==0) {
		mocd.pane_from = pane_from;
		mocd.pane_to = pane_to;
		mocd.move_cnt = 0;
		move_items(pane_from, pane_to, 1);
		return;
	}
	
	var num_par_no = 50;
	mocd.num_par_no = num_par_no;
	mocd.move_continue = 0;
	var no_from = (no-1)*num_par_no;
	var no_to = no_from + num_par_no-1;

	//alert(no+" "+mocd.pane_from.cur_dir+"-"+mocd.pane_to.cur_dir+"   "+no_from+"-"+no_to);

	var i,j;
	var flist="";

	if (pane_from.cur_dir == pane_to.cur_dir) return;

	var chk_no = -1;
	// make list of checked item
	for (i = 0; i < pane_from.dir_list.length; i++) {
		var attr = jQuery('#'+pane_from.get_chkid(i)).attr('checked');
		if (attr=='checked' || attr===true) {
			chk_no ++;
			if (chk_no<no_from) continue;
			if (chk_no>no_to) {
				mocd.move_continue = 1;
				continue;
			}
			cnt++;
			//flist += pane_from.dir_list[pane_from.dir_list[i]].name + "/";
			flist += pane_from.dir_list[i].name + "/";
			for (j=0; j<pane_from.dir_list.length; j++) {
				if (pane_from.dir_list[j].isthumb && pane_from.dir_list[j].parent == pane_from.dir_list[i]) {
					flist += pane_from.dir_list[j].name + "/";
				}
			}
		}
	}

	if (flist=="") {
		if (no == 1) {
			return;
		}
	}
	flist = flist.substr(0, flist.length-1);
	//alert(flist);

	var data = {
		action: 'mocd_move',
		dir_from: pane_from.cur_dir,
		dir_to: pane_to.cur_dir,
		items: flist
	};
	
	mocd.move_no = no;
	mocd.move_cnt = cnt;

	if (no == 1) {
		mocd.ajax_count_in();
	}
	jQuery.post(ajaxurl, data, function(response) {
		if (response.search(/Success/i) < 0) alert("mrloc_move(): "+response);
		if (mocd.move_continue) {
			move_items(mocd.pane_from, mocd.pane_to, mocd.move_no+1);
		} else {
			mocd.pane_left.refresh();
			mocd.pane_right.refresh();
			mocd.ajax_count_out();
		}
	});
}



//**** Pane class *******************************************************************
var MOCDPaneClass = function (id_root) {
	this.cur_dir = "";
	this.dir_list = new Array();
	//this.dir_disp_list = new Array();   // NOT NEEDED -- now they're all displayed
	this.id_root      = id_root;
	this.wrapper   = jQuery('#' + id_root + "_wrapper");  // TODO store the jQuery thing, not just the id (?)
	this.id_pane      = id_root + "_pane";
    this.pane      = jQuery('#' + this.id_pane);
	this.id_dir       = id_root + "_path";
	this.id_dir_new   = id_root + "_dir_new";
	this.id_dir_up    = id_root + "_dir_up";
    this.id_rename_dialog  = id_root + '_rename_dialog';
    this.id_rename   = id_root + '_newname';
    this.id_rename_i = id_root + '_newname_i';
    this.id_newdir_dialog = id_root + '_newdir_dialog';
    this.id_newdir   = id_root + '_newdir';
    this.id_newdir_error   = id_root + '_newdir_error';
	this.checked_loc = -1;
	this.last_div_id = "";
	this.chk_prepare_id = -1;
	this.opposite  = this; // WTF
	//this.disp_num = 0;

	var thispane = this;

	jQuery('#'+this.id_dir_up).click(function(ev) {
		if (mocd.ajax_count) return;
		if ("/" == thispane.cur_dir) return;
		thispane.chdir("..");
	});

//	jQuery('#'+this.id_dir_new).click(function(ev) {
//		if (mocd.ajax_count) return;
//		mocd.input_text.make("Make Directory","",300, true);
//		mocd.input_text.set_callback(function(){
//			var dir  =  mocd.input_text.result;
//			if (dir=="") return;
//			if (thispane.check_same_name(dir)) {
//				alert("The same name exists.");
//				return;
//			}
//			var res = "";
//			var data = {
//				action: 'mocd_mkdir',
//				dir: thispane.cur_dir,
//				newdir: dir
//			};
//			mocd.ajax_count_in();
//            jQuery.post(ajaxurl, data, function(response) {
//                if (!response.success) {
//                    alert("mocd_mkdir: "+response.message);
//                } else {
//                    thispane.refresh();
//                    if (thispane.cur_dir == thispane.opposite.cur_dir) {
//                        thispane.opposite.refresh();
//                    }
//                }
//                mocd.ajax_count_out();
//            });
//		});
//    });

    // 'Select All' box affects all boxes on this pane
	jQuery('div.mocd_pane').on('click', '#' + this.id_pane + '_ck_all', function(ev) {
        jQuery('.' + thispane.id_pane + '_ck').attr('checked', this.checked);
	});

//	jQuery('#'+this.id_select_all).click(function(ev) {
//		//for (i=0; i<thispane.disp_num; i++) {
//		for (i = 0; i < thispane.dir_list.length; i++) {
//			jQuery('#'+thispane.get_chkid(i)).attr('checked',true);
//		}
//	});
//	jQuery('#'+this.id_deselect_all).click(function(ev) {
//		for (i=0; i<thispane.dir_list.length; i++) {
//			jQuery('#'+thispane.get_chkid(i)).attr('checked',false);
//		}
//	});

    // Set up rename dialog
    this.rename_field = jQuery('#' + this.id_rename);
    this.rename_dialog = jQuery("#" + this.id_rename_dialog).dialog({
        appendTo: '#mocd_wrap',
        autoOpen: false,
        //height: 400,
        //width: 350,
        resizable: false,

        modal: true,
        buttons: {
            "Rename": thispane.rename_dialog_callback.bind(thispane, 'x42'),
            Cancel: function() {
                thispane.rename_dialog.dialog("close");
            }
        },
        open: function (event, ui) {
            //console.log('opening dialog');
        },
        close: function() {
            // ??form[0].reset();
            //allFields.removeClass("ui-state-error");
        }
    });
    
    // TODO validation, either in dialog or callback
    // Set up new dir dialog
	jQuery('#' + this.id_dir_new).click(function () {
        thispane.newdir_dialog.dialog("open");
    });
    this.newdir_field = jQuery('#' + this.id_newdir);
    this.newdir_dialog = jQuery("#" + this.id_newdir_dialog).dialog({
        appendTo: '#mocd_wrap',
        autoOpen: false,
        //height: 400,
        //width: 350,
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
            // ??form[0].reset();
            //allFields.removeClass("ui-state-error");
        }
    });

//?    var form = this.dialog.find("form").on("submit", function(event) {
//        event.preventDefault();
//        thispane.rename_dialog_callback().bind(thispane, 'ff');
//    });
    // Button click is done later
    //jQuery("#create-user").button().on( "click", function() {
    //    thispane.rename_dialog.dialog("open");
    //});

}

// TEMP?
MOCDPaneClass.prototype.rename_dialog_callback = function (x) {
    // 'this' is the pane object, thanks to the bind on the call
    // (otherwise it would be the div containing the form element)
    var newname = this.rename_field.val();
    if (this.name_exists(newname)) {
        jQuery('#' + this.id_newname_error).html("<span class=error>There is already a file or folder called '" + newname + "'</span>");
        jQuery(this.newname_field).addClass('error');
        return false;
    }
    this.rename_dialog.dialog('close');
    //alert('newname: ' + newname);
    this.ajax_rename_item(jQuery('#' + this.id_rename_i).val(), newname);
    return true;
}

MOCDPaneClass.prototype.newdir_dialog_callback = function () {
    // 'this' is the pane object, thanks to the bind on the call
    // (otherwise it would be the div containing the form element)
    var newdir = this.newdir_field.val();
    if (this.name_exists(newdir)) {
        jQuery('#' + this.id_newdir_error).html("<span class=error>There is already a file or folder called '" + newdir + "'</span>");
        jQuery(this.newdir_field).addClass('error');
        return false;
    }
    this.newdir_dialog.dialog('close');
    this.ajax_newdir(newdir);
    return true;
}

MOCDPaneClass.prototype.get_chkid = function (n) {
    return this.id_pane + '_ck_' + n;
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
	this.wrapper.css('cursor:wait'); // TODO move this into count_in
	var data = {
		action: 'mocd_getdir',
		dir: dir
	};

	var that = this;
	mocd.ajax_count_in();
	jQuery.post(ajaxurl, data, function(response) {
        if (response.success) {
            // Process the json directory from ajax,
            // create the html, and store the list
            that.dir_list = that.set_dir(data.dir, response.data);
        } else {
            alert(response.message);
        }
		mocd.ajax_count_out();
	});
}

// function name: MOCDPaneClass::set_dir
// description : display directory list sent from server
//               in response to mocd_getdir ajax request              
MOCDPaneClass.prototype.set_dir = function (target_dir, dir) {
	//var dir;
    var thispane = this;

	this.cur_dir = target_dir;
	jQuery('#' + this.id_dir).text(target_dir);
	this.disp_num = 0;

    // it's already un-JSON'd    
    //try {
	//	dir = JSON.parse(data);
	//} catch (err) {
    //    mocd.display_error('Invalid response when parsing directory listing');
    //    return;
	//}

    //if (dir.error) {
    //    mocd.display_error(dir.error);
    //    return;
    //}
    
	var html = "";
    var thumb_url = '';
	this.last_chk_id = "";

    //html += '<h2><span class="dashicons dashicons-smiley"></span> A Cheerful Headline</h2>';
    //html += '<span class="dashicons dashicons-portfolio"></span>';

    html += '<ul class=mocd_pane_list>';

    // First item is the 'de/select all' box
    html += '<li class=mocd_pane_item>';
    html += '<div><input type="checkbox" id="' + this.id_pane + '_ck_all' + '"></div>';
    html += '<div>Select All</div>';
    html += '</li>';

    // Display all items as a list
	for (i = 0; i < dir.length; i++) {
        // ignore 'thumbnails' -- the flag is on for everything that isn't a parent!
        // i.e. everything except 'real' items 
		///if (dir[i].isthumb) continue;
        var item = dir[i];
		//this.dir_disp_list[this.disp_num] = i;
        var divid = this.get_divid(i);
		this.last_div_id = divid;
        html += '<li class="mocd_pane_item" id="' + divid + '">'; //style="vertical-align:middle;display:block;height:55px;clear:both; position:relative;">';
        html += '<div><input type="checkbox" class="' + this.id_pane + '_ck' + '" id="' + this.get_chkid(i) + '"></div>';
		html += '<div>'; // id="' + this.get_divid(i) + '">';
        // Thumbnail img URL should always be supplied by backend
		if (item.thumbnail_url && item.thumbnail_url != "") {
            thumb_url = item.thumbnail_url;
		} else {
            thumb_url = 'notfound.jpg';
        }
        var dirclass = item.isdir ? ' mocd_isdir' : '';
		html += '<img class="mocd_pane_img' + dirclass + '" src="' + thumb_url + '">';
		html += '</div><div class="mocd_filename">';
		html += item.name; 
        if (item.isdir) {
            html += ' <button type="button" class="mocd_pane_rename">Rename</button>';
            if (item.isemptydir) {
                html += ' <button type="button" class="mocd_pane_delete">Delete</button>';
            }
        }
		html += '</div>';
        //html += '</div>'

		//this.disp_num ++;
        html += '</li>';
	}
    html += '</ul>';
    this.id_pane).html(html);

    // Now that the items are in the DOM,
    // go through the list again and set folders as clickable.
    // Note cunning use of bind to avoid having to re-extract
    // the index from the item's id or data.
    for (i = 0; i < dir.length; i++) {
        if (dir[i].isdir) {
            // Make folder clickable
            var divid = this.get_divid(i);  // e.g. 'mocd_left_pane_1'
            var name = dir[i].name;
            jQuery('#'+divid).on('click', 'img', function (newdir, e) {
                if (mocd.ajax_count > 0) {
                    return;
                }
                thispane.chdir(newdir);
            }.bind(this, name));  // name gets passed in as newdir
            // Make folder's rename button clickable
            jQuery('#'+divid).on('click', 'button.mocd_pane_rename', function (i, e) {
                if (mocd.ajax_count > 0) {
                    return;
                }
                alert('rename ' + thispane.dir_list[i].name);
                //thispane.ajax_rename_item(i);
                // Set the current value before opening the form
                jQuery('#' + thispane.id_rename).val(thispane.dir_list[i].name);
                jQuery('#' + thispane.id_rename_i).val(i);
                thispane.rename_dialog.dialog("open");
            }.bind(this, i));  // index gets passed in as i
            if (dir[i].isemptydir) {
                // Make folder's delete button clickable 
                jQuery('#'+divid).on('click', 'button.mocd_pane_delete', function (i, e) {
                    if (mocd.ajax_count > 0) {
                        return;
                    }
                    alert('deleting ' + thispane.dir_list[i].name);
                    thispane.ajax_delete_empty_dir(i);
                }.bind(this, i));  // index gets passed in as i
            }
        }
    }

    // FIXME this looks very silly
    // // but it also sets up the directories as left-click buttons
    //    so we'll just call it once
//    this.prepare_checkboxes();
    //function callMethod_chkprepare() {
    //    that.prepare_checkboxes();
   // }
    //if (this.chk_prepare_id == -1) {
    //    this.chk_prepare_id = setInterval(callMethod_chkprepare, 20);
    //}
    this.wrapper.css('cursor:default');
    return dir;
}

// Send AJAX request to rename an item, given its index in the dir_list
MOCDPaneClass.prototype.ajax_rename_item = function (i, newname) {
    var thispane = this;
    if (!thispane.dir_list[i].name) {
        console.log('ajax_rename_item -- now item for i=', i);
        return;
    }
    var oldname = thispane.dir_list[i].name;

    var data = {
        action:    'new_mocd_move',
        dir_from:  thispane.cur_dir,
        dir_to:    thispane.cur_dir,
        item_from: oldname,
        item_to:   newname,
        post_id:   thispane.dir_list[i].post_id,
        isdir:     thispane.dir_list[i].isdir 
    };
    //console.log('nmi sending data: ', data);
    mocd.ajax_count_in();
    jQuery.post(ajaxurl, data, function (response) {
        mocd.display_response(response);
        thispane.refresh();
        // Refresh opposite pane if it's showing the same directory
        if (thispane.cur_dir == thispane.opposite.cur_dir) {
            thispane.opposite.refresh();
        }
        // Update opposite pane if it's showing the directory we've just renamed
        if (thispane.opposite.cur_dir == thispane.cur_dir + oldname + "/") {
            thispane.opposite.setdir(thispane.cur_dir + newname + '/'); // Linux only
        }
        mocd.ajax_count_out();
        //}
    });
} 

// Send AJAX request to create a new directory
MOCDPaneClass.prototype.ajax_newdir = function (newdir) {
    var thispane = this;
    var data = {
        action: 'mocd_mkdir',
        dir: thispane.cur_dir,
        newdir: newdir
    };
    mocd.ajax_count_in();
    jQuery.post(ajaxurl, data, function(response) {
        if (!response.success) {
            alert("mocd_mkdir: " + response.message);
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
MOCDPaneClass.prototype.ajax_delete_empty_dir = function (i) {
    var thispane = this;
    var dirname = thispane.dir_list[i].name;
    var data = {
        action: 'mocd_delete_empty_dir',
        dir:    this.cur_dir,
        name:   this.dir_list[i].name
    };
    mocd.ajax_count_in();
    jQuery.post(ajaxurl, data, function(response) {
        mocd.display_response(response);
        thispane.refresh();
        // Refresh opposite pane if it's showing the same directory
        if (thispane.cur_dir == thispane.opposite.cur_dir) {
            thispane.opposite.refresh();
        }
        // Update the opposite pane if it's showing the dir we've just deleted
        if (thispane.opposite.cur_dir == thispane.cur_dir + dirname + "/") {
            thispane.opposite.setdir(thispane.cur_dir);
        }
        mocd.ajax_count_out();
    });
}

// TODO a new 'set_checkboxes' function that disables checkboxes
// for items that have items with the same name in the opposite pane

MOCDPaneClass.prototype.name_exists = function (str) {
	for (var i = 0; i < this.dir_list.length; i++) {
		if (this.dir_list[i]['name'] === str) {
			return true;
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
            new_dir = new_folder.split('/').slice(0, -2).join('/') + '/'; // FIXME Linux only
		}
	} else {
        new_dir += dir + '/';
	}
	this.setdir(new_dir);
}

// Handle the Action drop-down -- rename, move, or delete.
MOCDPaneClass.prototype.actions = function (action) {
    console.log('action = ', action);
}


// ----------- End of class definition 

//**** Text input form class *******************************************************************
var MOCDInputTextClass = function() {
    // wtf?
	//var flgRegisterRemoveFunc = false;
	this.flgRegisterRemoveFunc = false;
	//var pos_left = 0;
	this.pos_left = 0;
	//var pos_right = 0;
	this.pos_right = 0;
	//var result = "";
	this.result = "";
	//var flgOK = false;
	this.flgOK = false;
	//var callback;
	this.callback;
    //var invalid_chr = ["\\", "/", ":", "*", "?", "+", "\"", "<", ">", "|", "%", "&", "'", " ", "!", "#", "$", "(", ")", "{", "}"];
    this.invalid_chr = ["\\", "/", ":", "*", "?", "\"", "<", ">", "|", "&", "'", " ", "`"];

}

// function name: MOCDInputTextClass::make
// description : make and display a text input form
// argument : (title)title; (init_text)initial text; (textbox_width)width of textbox
MOCDInputTextClass.prototype.make = function(title, init_text, textbox_width, is_dirname) {
	this.is_dirname = is_dirname;
	var html="";
	jQuery('body').append('<div id="mocd_input_text"></div>');
	html = '<div class="title">'+title+'</div>';
	html += '<input type="textbox" id="mocd_input_textbox" style="width:'+textbox_width+'px"/>';
	html += '<div class="mocd_input_text_button_wrapper">';
	//html += '<div class="mocd_input_text_button" id="mocd_input_text_ok">&nbsp;OK&nbsp;</div>';
	//html += '<div class="mocd_input_text_button" id="mocd_input_text_cancel">&nbsp;Cancel&nbsp;</div>';
	html += '<button type=button class="mocd_input_text_button" id="mocd_input_text_ok">OK</button> ';
	html += '<button type=button class="mocd_input_text_button" id="mocd_input_text_cancel">Cancel</button>';
	html += '</div>';

	this.pos_left = mocd.mouse_x;
	this.pos_top = mocd.mouse_y;

	jQuery('#mocd_input_text').html(html);
	jQuery('#mocd_input_text').css('margin', '0 auto'); //'top',this.pos_top+"px");
	//jQuery('#mocd_input_text').css('left',this.pos_left+"px");
	jQuery('#mocd_input_textbox').val(init_text);

	var that = this;

    // CD Hack
    jQuery('#mocd_input_text').keypress(function (e) {
        // Filter out invalid characters
        var key = String.fromCharCode(e.which);
        console.log('--- key=', key);
        if (that.invalid_chr.indexOf(key) >= 0) {
            return false;
        }
        //return 'a'; //?? 
    });

	jQuery('#mocd_input_text_ok').click(function(){
		var result = jQuery('#mocd_input_textbox').val();
		if (that.check_dotext(result, that.is_dirname)) {
			alert("Please do not use 'dot + file extension' pattern in the directory name because that can cause problems.");
			return;
		}
		if (that.check_invalid_chr(result)) {
			alert("You cannot use the following characters and whitespace:  " + that.invalid_chr_msg());
			return;
		}
		jQuery('body').unbind('click.mrlinput');
		that.result = result;
		jQuery('#mocd_input_text').remove();
		that.callback();
	});
	jQuery('#mocd_input_text_cancel').click(function(){
		jQuery('#mocd_input_text').remove();
		jQuery('body').unbind('click.mrlinput');
	});
	jQuery('body').bind('click.mrlinput', function(e){e.preventDefault();})
	jQuery('#mocd_input_textbox').focus();
}

// function name: MOCDInputTextClass::set_callback
// description : register callback function called when OK is pressed
// argument : (c)callback function
MOCDInputTextClass.prototype.set_callback = function(c) {
	this.callback = c;
}

// function name: MOCDInputTextClass::check_dotext
// description : check if '.+file extension' pattern exists in the name (ex)abc.jpgdef
// argument : (str: target string, isdir: the name is of a directory)
// return : true(exists), false(not exists)
MOCDInputTextClass.prototype.check_dotext = function(str, isdir) {
	var ext = 
		['.jpg', '.jpeg', '.gif', '.png', '.mp3','.m4a','.ogg','.wav',
		 '.mp4v', '.mp4', '.mov', '.wmv', '.avi', '.mpg', '.ogv', '.3gp', '.3g2',  
		 '.pdf', '.docx', '.doc', '.pptx', 'ppt', '.ppsx', '.pps', '.odt', '.xlsx', '.xls'];
	var i;
	for (i=0; i<ext.length; i++) {
		if (str.toLowerCase().indexOf(ext[i]) >= 0) {
			if (isdir) return true;
		}
	}
	return false;
}



// function name: MOCDInputTextClass::invalid_chr
// description : check if invalid character exists in the name.
// argument : (str: target string)
// return : true(exists), false(not exists)
MOCDInputTextClass.prototype.check_invalid_chr = function(str) {
	var i;
	for (i = 0; i < this.invalid_chr.length; i++) {
		if (str.indexOf(this.invalid_chr[i]) >= 0) {
			return true;
		}
	}
	return false;
}

MOCDInputTextClass.prototype.invalid_chr_msg = function() {
	var msg = "";
	for (i = 0; i < this.invalid_chr.length; i++) {
		msg += this.invalid_chr[i] + " ";
	}
	return msg;
}



// function name: (none)
// description :  initialization
// argument : (void)
jQuery(document).ready(function() {
	mocd.input_text = new MOCDInputTextClass();

	mocd.pane_left = new MOCDPaneClass('mocd_left');
	mocd.pane_right = new MOCDPaneClass('mocd_right');

	mocd.pane_left.opposite = mocd.pane_right;
	mocd.pane_right.opposite = mocd.pane_left;

	//adjust_layout();

	mocd.pane_left.setdir("/");
	mocd.pane_right.setdir("/");

    // Track mouse position for dialogue boxes
   	jQuery(document).mousemove(function(e){
		mocd.mouse_x = e.pageX;
		mocd.mouse_y = e.pageY;
	}); 

	jQuery('#mocd_btn_left2right').click(function() {
		if (mocd.ajax_count) return;
		mocd.new_move_items(mocd.pane_left, mocd.pane_right);
	});
	jQuery('#mocd_btn_right2left').click(function() {
		if (mocd.ajax_count) return;
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

	jQuery('#mocd_test').click(function() {
		var data = {
			action: 'mocd_test'
		};
		jQuery.post(ajaxurl, data, function(response) {
			alert("mocd_test: "+response);
		});
	});

	jQuery(window).resize(function() {
		//jQuery('#debug').html(jQuery('#wpbody').height());
		mocd.adjust_layout();
	});

	
	mocd.adjust_layout();
});



