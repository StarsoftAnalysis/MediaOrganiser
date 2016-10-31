// TODO make it sensible
//   - better way of deciding when it's busy than counting number of ajax calls
//   - use wp_die, not die or exit. -- 
// - get rid of jQuery.bind -- use .on
// - don't use right-click
// - nicer buttons and icons

// -- put them in a namespace
var mocd = mocd || {};
mocd.shift_pressed = false;	// Flag indicates shift key is pressed or not
mocd.ajax_count = 0;
mocd.right_click_menu = {};	// Right-click menu class object
mocd.input_text = {};	// Text-input form class object
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
	jQuery('.mocd_filename').width(pane_w - 32);
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

// TODO Get rid of right-click stuff
//
// function name: (none)
// description :  initialization
// argument : (void)
jQuery(document).ready(function() {
	mocd.right_click_menu = new MrlRightMenuClass();
	mocd.input_text = new MrlInputTextClass();

	mocd.pane_left = new MrlPaneClass('mocd_left');
	mocd.pane_right = new MrlPaneClass('mocd_right');

	mocd.pane_left.opposite = mocd.pane_right;
	mocd.pane_right.opposite = mocd.pane_left;

	//adjust_layout();

	mocd.pane_left.setdir("/");
	mocd.pane_right.setdir("/");

	jQuery(document).keydown(function (e) {
	  if(e.shiftKey) {
	    mocd.shift_pressed = true;
	  }
	});
   	jQuery(document).mousemove(function(e){
		mocd.mouse_x = e.pageX;
		mocd.mouse_y = e.pageY;
	}); 
	jQuery(document).keyup(function(event){
	   mocd.shift_pressed = false;
	});

	jQuery('#mocd_btn_left2right').click(function() {
		if (mocd.ajax_count) return;
		mocd.new_move_items(mocd.pane_left, mocd.pane_right);
	});
	jQuery('#mocd_btn_right2left').click(function() {
		if (mocd.ajax_count) return;
		mocd.new_move_items(mocd.pane_right, mocd.pane_left);
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




//**** Pane class *******************************************************************
var MrlPaneClass = function(id_root) {
	this.cur_dir = "";
	this.dir_list = new Array();
	//this.dir_disp_list = new Array();   // NOT NEEDED -- now they're all displayed
	this.id_root = id_root;
	this.id_wrapper = id_root + "_wrapper";
	this.id_pane = id_root + "_pane";
	this.id_dir = id_root + "_path";
	this.id_dir_new = id_root + "_dir_new";
	this.id_dir_up = id_root + "_dir_up";
	this.checked_loc = -1;
	this.last_div_id = "";
	this.chk_prepare_id = -1;
	this.opposite  = this; // WTF
	//this.disp_num = 0;

	var that = this;

	jQuery('#'+this.id_dir_up).click(function(ev) {
		if (mocd.ajax_count) return;
		if ("/" == that.cur_dir) return;
		that.chdir("..");
	});

	jQuery('#'+this.id_dir_new).click(function(ev) {
		if (mocd.ajax_count) return;
		mocd.input_text.make("Make Directory","",300, true);
		mocd.input_text.set_callback(function(){
			var dir  =  mocd.input_text.result;
			if (dir=="") return;
			if (that.check_same_name(dir)) {
				alert("The same name exists.");
				return;
			}
			var res = "";
			var data = {
				action: 'mocd_mkdir',
				dir: that.cur_dir,
				newdir: dir
			};
			mocd.ajax_count_in();
			jQuery.post(ajaxurl, data, function(response) {
				if (response.search(/Success/i) < 0) alert("mocd_mkdir: "+response);

				if (that.cur_dir == that.opposite.cur_dir) {
					that.refresh();
					that.opposite.refresh();
				} else {
					that.refresh();
				}

				mocd.ajax_count_out();
			});
		});
	});

    // 'Select All' box affects all boxes on this pane
	jQuery('div.mocd_pane').on('click', '#' + this.id_pane + '_ck_all', function(ev) {
        jQuery('.' + that.id_pane + '_ck').attr('checked', this.checked);
	});

//	jQuery('#'+this.id_select_all).click(function(ev) {
//		//for (i=0; i<that.disp_num; i++) {
//		for (i = 0; i < that.dir_list.length; i++) {
//			jQuery('#'+that.get_chkid(i)).attr('checked',true);
//		}
//	});
//	jQuery('#'+this.id_deselect_all).click(function(ev) {
//		for (i=0; i<that.dir_list.length; i++) {
//			jQuery('#'+that.get_chkid(i)).attr('checked',false);
//		}
//	});
}

MrlPaneClass.prototype.get_chkid = function (n) {return this.id_pane+'_ck_'+n;}
MrlPaneClass.prototype.get_divid = function (n) {return this.id_pane+'_'+n;}
MrlPaneClass.prototype.refresh = function () {this.setdir(this.cur_dir);}

// function name: MrlPaneClass::setdir
// description : move to the directory and display directory listing
// argument : (dir)absolute path name of the target directory
MrlPaneClass.prototype.setdir = function(dir) {
	jQuery('#'+this.id_wrapper).css('cursor:wait');
	var data = {
		action: 'mocd_getdir',
		dir: dir
	};

	var that = this;
	mocd.ajax_count_in();
	jQuery.post(ajaxurl, data, function(response) {
        // Process the json directory from ajax,
        // create the html, and store the list
		that.dir_list = that.dir_ajax(data.dir, response);
		mocd.ajax_count_out();
	});
}

// function name: MrlPaneClass::dir_ajax
// description : display directory list sent from server
//               in response to mocd_getdir ajax request              
// argument : (dir)target_dir: target directory; (response):list(JSON); 
// response is the array of directory items
//  -- elements used: name, isthumb, thumbnail_url
//      (isdir and id no longer used here, but are passed back)
// returns the dir array
MrlPaneClass.prototype.dir_ajax = function (target_dir, response) {
//    // Looking at response as a string is silly  
//	if (response.search(/error/i) == 0) {	
//		alert(response);
//		jQuery('#'+this.id_wrapper).css('cursor:default');  // FIXME need a 'reset cursor' function'
//		return;
//	}
//
//	response = jQuery.trim(response);
//	if (response=="") {
//		jQuery('#'+this.id_pane).html("");
//		return new Array();
//	}
	var dir;
    var thispane = this;

	this.cur_dir = target_dir;
	jQuery('#'+this.id_dir).text(target_dir);
	this.disp_num = 0;
	
    try {
		//response = response.substr(0, response.length-1);
		dir = JSON.parse(response);
	} catch (err) {
        display_error('Invalid response when getting directory listing');
        return;
		//alert(response + " : " + mocd_toHex(response));
		//document.write('<table border="3"><tr><td width="200">');
		//document.write("<pre>"+err+"\n"+response+"</pre>");
		//document.write("</td></tr></table>");
        // FIXME should skip the rest of the html-building stuff here.
        // as done for response.search test above.
	}

    if (dir.error) {
        display_error(dir.error);
        return;
    }
    
	var html = "";
	var that = this;
    var thumb_url = '';
	this.last_chk_id = "";

    html += '<ul class=mocd_pane_list>';

    // First item is the 'de/select all' box
    html += '<li class=mocd_pane_item>';
    html += '<div><input type="checkbox" id="' + this.id_pane + '_ck_all' + '"></div>';
    html += '<div>Select All</div>';
    html += '</li>';

    // Display all items as a list
	for (i = 0; i < dir.length; i++) {
//        html += '<li>';
        // ignore 'thumbnails' -- the flag is on for everything that isn't a parent!
        // i.e. everything except 'real' items 
		///if (dir[i].isthumb) continue;
        var item = dir[i];
		//this.dir_disp_list[this.disp_num] = i;
        html += '<li class="mocd_pane_item">'; //style="vertical-align:middle;display:block;height:55px;clear:both; position:relative;">';
        html += '<div><input type="checkbox" class="' + this.id_pane + '_ck' + '" id="' + this.get_chkid(i) + '"></div>';
		html += '<div id="' + this.get_divid(i) + '">';
		this.last_div_id = this.get_divid(i);
        // Thumbnail img URL should always be supplied by backend
		if (item.thumbnail_url && item.thumbnail_url != "") {
            thumb_url = item.thumbnail_url;
		} else {
            thumb_url = 'notfound.jpg';
        }
		html += '<img class=mocd_pane_img src="' + thumb_url + '">';
		html += '</div><div class="mocd_filename">';
		html += item.name; //mocd_ins8203(dir[i].name)/*+" --- " + dir[i].isdir+ (dir[i].id!=""?" "+dir[i].id:"")*/;
		html += '</div>';
        //html += '</div>'

		//this.disp_num ++;
        html += '</li>';
	}
    html += '</ul>';
    jQuery('#'+this.id_pane).html(html);

    // Now that the items are in the DOM,
    // go through the list again and set folders as clickable.
    // Note cunning use of bind to avoid having to re-extract
    // the index from the item's id or data.
    // TODO set cursor shape -- maybe set a class on the folder ones
    for (i = 0; i < dir.length; i++) {
        if (dir[i].isdir) {
            jQuery('#'+this.get_divid(i)).on('click', function (newdir, e) {
                if (mocd.ajax_count > 0) {
                    return;
                }
                thispane.chdir(newdir);
            }.bind(this, dir[i].name));  // dir[i].name gets passed in as newdir
        }
    }

    // FIXME this looks very silly
    // // but it also sets up the directories as left-click buttons
    //    so we'll just call it once
    this.prepare_checkboxes();
    //function callMethod_chkprepare() {
    //    that.prepare_checkboxes();
   // }
    //if (this.chk_prepare_id == -1) {
    //    this.chk_prepare_id = setInterval(callMethod_chkprepare, 20);
    //}
    jQuery('#'+this.id_wrapper).css('cursor:default');
    return dir;
}


// function name: MrlPaneClass::prepare_checkboxes
// description : prepare event for checkboxes and right-click events(mkdir, rename)
// argument : (void)
// OH FIXME -- this does the right click stuff as well as checkboxes
MrlPaneClass.prototype.prepare_checkboxes = function() {
    var that = this; // FIXME ?? needed!?

    if (jQuery('#'+this.last_div_id).length>0) {
        clearInterval(this.chk_prepare_id);
        this.chk_prepare_id = -1;

        //for (i=0; i<this.dir_disp_list.length; i++) {
        //	var idx = this.dir_disp_list[i];
        for (i = 0; i < this.dir_list.length; i++) {
            var idx = i; //this.dir_disp_list[i];
            //if (this.dir_list[idx].isthumb) continue; hmm this ref to isthumb was already commented out

            jQuery('#'+this.get_divid(i)).data('order', i);
            jQuery('#'+this.get_divid(i)).data('data', idx);

            jQuery('#'+this.get_chkid(i)).data('order', i);
            jQuery('#'+this.get_chkid(i)).data('data', idx);
            jQuery('#'+this.get_chkid(i)).change(function() {
                if (mocd.shift_pressed && that.checked_loc >= 0) {
                    var loc1 = jQuery(this).data('order');
                    var loc2 = that.checked_loc;
                    var checked = jQuery('#'+that.get_chkid(loc1)).attr('checked');
                    for (n=Math.min(loc1,loc2); n<=Math.max(loc1,loc2); n++) {
                        if (checked == 'checked') {
                            jQuery('#'+that.get_chkid(n)).attr('checked','checked');
                        } else if (checked === true) {
                            jQuery('#'+that.get_chkid(n)).attr('checked',true);
                        } else if (checked === false) { 
                            jQuery('#'+that.get_chkid(n)).attr('checked',false);
                        } else {
                            jQuery('#'+that.get_chkid(n)).removeAttr('checked');
                        }
                    }
                }
                that.checked_loc = jQuery(this).data('order');
            });
            /* Don't steal r-click
               jQuery(document).bind("contextmenu",function(e){
               return false;
               }); 
               */
            // NOTE we only reference things in dir_list that are already on the 
            // screen, so all the isthumb ones are still not needed.
            jQuery('#'+this.get_divid(i)).mousedown(function(ev) {
                if (ev.which == 3) {
                    ev.preventDefault();
                    var isDir = that.dir_list[jQuery(this).data('data')]['isdir'];
                    var arrMenu = new Array("Preview","Rename");
                    if (isDir) {
                        arrMenu.push("Delete");
                    }
                    mocd.right_click_menu.make(arrMenu);
                    var that2 = this;
                    if (isDir) {
                        jQuery('#'+mocd.right_click_menu.get_item_id(2)).click(function(){ //delete
                            var target = that.dir_list[jQuery(that2).data('data')];
                            var isEmptyDir = target['isemptydir'];
                            if (!isEmptyDir) {alert('Directory not empty.');return;}
                            var target = that.dir_list[jQuery(that2).data('data')];
                            var dirname = target['name'];
                            var data = {
                                action: 'mocd_delete_empty_dir',
                                dir: that.cur_dir,
                                name: dirname
                            };
                            mocd.ajax_count_in();
                            jQuery.post(ajaxurl, data, function(response) {
                                if (response.search(/Success/i) < 0) {alert("mocd_delete_empty_dir: "+response);}
                                that.refresh();
                                if (that.cur_dir == that.opposite.cur_dir) {
                                    that.opposite.refresh();
                                }
                                if (that.cur_dir+dirname+"/" == that.opposite.cur_dir) {
                                    that.opposite.setdir(that.cur_dir);
                                }
                                mocd.ajax_count_out();
                            });
                        });
                    }
                    jQuery('#'+mocd.right_click_menu.get_item_id(0)).click(function(){ //preview
                        var url = mrloc_url_root + (that.cur_dir+that.dir_list[jQuery(that2).data('data')]['name'])/*.substr(mrloc_document_root.length)*/;
                        window.open(url, 'mrlocpreview', 'toolbar=0,location=0,menubar=0')
                    });
                    jQuery('#'+mocd.right_click_menu.get_item_id(1)).click(function(){ //rename
                        if (mocd.ajax_count) return;
                        var target = that.dir_list[jQuery(that2).data('data')];
                        if (target['norename']) {
                            alert("Sorry, you cannot rename this item.");
                            return;
                        }
                        var old_name = target['name'];
                        mocd.input_text.make("Rename ("+old_name+")",old_name,300, target['isdir'] );
                        mocd.input_text.set_callback(function(){
                            if (old_name == mocd.input_text.result || mocd.input_text.result=="") {
                                return;
                            }
                            if (that.check_same_name(mocd.input_text.result)) {
                                alert("The same name exists.");
                                return;
                            }
                            var data = {
                                action: 'mocd_rename',
                                dir: that.cur_dir,
                                from: old_name,
                                to: mocd.input_text.result
                            };
                            mocd.ajax_count_in();

                            jQuery.post(ajaxurl, data, function(response) {
                                if (response.search(/Success/i) < 0) alert("mocd_rename: "+response);
                                if (that.opposite.cur_dir.indexOf(that.cur_dir+old_name+"/")===0) {
                                    that.opposite.setdir(that.cur_dir+mocd.input_text.result+"/"+that.opposite.cur_dir.substr((that.cur_dir+old_name+"/").length));
                                }
                                if (that.cur_dir == that.opposite.cur_dir) {
                                    that.refresh();
                                    that.opposite.refresh();
                                } else {
                                    that.refresh();
                                }
                                mocd.ajax_count_out();
                            });
                        });
                    });
                }
                // WHAT? why a new var here!?
                var dir = that.dir_list[jQuery(this).data('data')];
            });

            jQuery('#'+this.get_divid(i)).click(function() {
                if (mocd.ajax_count) return;
                var dir = that.dir_list[jQuery(this).data('data')];
                if (dir.isdir) {
                    that.chdir(dir.path);
                }
            });
        }
    }
    }

MrlPaneClass.prototype.check_same_name = function(str) {
	for (var i=0; i<this.dir_list.length; i++) {
		if (this.dir_list[i]['name'] == str) {
			return true;
		}
	}
	return false;
}

// function name: MrlPaneClass::chdir
// description : move directory and display its list
// argument : (dir)target directory
MrlPaneClass.prototype.chdir = function(dir) {
	var last_chr = this.cur_dir.substr(this.cur_dir.length-1,1);
	var new_dir = this.cur_dir;

	if (dir == "..") {
		if (last_chr == "/") {
			new_dir = new_dir.substr(0, new_dir.length-1);
		}
		var i=0;
		for (i=new_dir.length-1; i>=0; i--) {
			if (new_dir.substr(i, 1)=="/") {
				new_dir = new_dir.substr(0, i+1);
				break;
			}
		}
	} else {
		if (last_chr != "/") new_dir += "/";
		new_dir += dir;
		if (last_chr == "/") new_dir += "/";
	}
	this.setdir(new_dir);
}


// ----------- End of class definition 

// function name: mocd_ins8203
// description : 
// argument : (str)
function mocd_ins8203 (str) {
    return str;  // FIXME what's this for??   8203 is a zero-width space
	var ret = "", i, str = str || '';
	for (i = 0; i < str.length; i += 3) {
		ret += str.substr(i, 3);
		ret += '&#8203;'
	}
	return ret;
}

// FIXME where should this go?
function display_error (text) {
    // TODO don't (just) alert
    alert(text);
}

mocd.new_move_items = function nmi (pane_from, pane_to) {
    var flist = [];  // list of filenames to return
    for (var i = 0; i < pane_from.dir_list.length; i++) {
        if (jQuery('#' + pane_from.get_divid(i)).attr('checked')) {
           flist.push(pane_from.dir_list[i].name);
        } 
    }
	var data = {
		action: 'new_mocd_move',
		dir_from: pane_from.cur_dir,
		dir_to: pane_to.cur_dir,
		items: flist
	};
    console.log('nmi sending data: ', data);
    // TODO do we need to do them in batches?
    mocd.ajax_count_in();
	jQuery.post(ajaxurl, data, function (response) {
		if (response.search(/Success/i) < 0) alert("mrloc_move(): "+response);
		//if (mocd.move_continue) {
		//	move_items(mocd.pane_from, mocd.pane_to, mocd.move_no+1);
		//} else {
			mocd.pane_left.refresh();
			mocd.pane_right.refresh();
			mocd.ajax_count_out();
		//}
	});
}

// FIXME?  this isn't in the class as claimed
// function name: MrlPaneClass::move
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


//**** right-click menu class *******************************************************************
var MrlRightMenuClass = function() {
	var num=0;
	var flgRegisterRemoveFunc = false;
	var pos_left = 0;
	var pos_right = 0;
}


// function name: MrlRightMenuClass::make
// description : make and display right-click menu
// argument : (items)array of menu items 
MrlRightMenuClass.prototype.make = function(items) {
	var html="";
	var i;
	jQuery('body').append('<div id="mocd_right_menu"></div>');

	this.num = items.length;
	for (i=0; i<items.length; i++) {
		html += '<div class="mocd_right_menu_item" id="mocd_right_menu_item_' + i + '">';
		html += items[i];
		html += '</div>';
	}

	this.pos_left = mocd.mouse_x;
	this.pos_top = mocd.mouse_y;

	jQuery('#mocd_right_menu').html(html);
	jQuery('#mocd_right_menu').css('top',this.pos_top+"px");
	jQuery('#mocd_right_menu').css('left',this.pos_left+"px");

	for (i=0; i<items.length; i++) {
		var id = 'mocd_right_menu_item_' + i;
		jQuery('#'+id).hover(
                // FIXME this.removeClass does not exist!!
			//function(){this.removeClass('mocd_right_menu_item');this.addClass('mocd_right_menu_item_hover');},
			//function(){this.removeClass('mocd_right_menu_item_hover');this.addClass('mocd_right_menu_item');}
		);
	}
	if (!this.flgRegisterRemoveFunc) {
		jQuery(document).click(function(){jQuery('#mocd_right_menu').remove();});
		this.flgRegisterRemoveFunc = true;
	}
}

// function name: MrlRightMenuClass::get_item_id
// description : get the id of the specified item
// argument : (n)index of item (starting from 0)
MrlRightMenuClass.prototype.get_item_id = function(n) {
	return 'mocd_right_menu_item_' + n;
}



//**** Text input form class *******************************************************************
var MrlInputTextClass = function() {
	var flgRegisterRemoveFunc = false;
	var pos_left = 0;
	var pos_right = 0;
	var result = "";
	var flgOK = false;
	var callback;
    var invalid_chr = ["\\", "/", ":", "*", "?", "+", "\"", "<", ">", "|", "%", "&", "'", " ", "!", "#", "$", "(", ")", "{", "}"];
}

// function name: MrlInputTextClass::make
// description : make and display a text input form
// argument : (title)title; (init_text)initial text; (textbox_width)width of textbox
MrlInputTextClass.prototype.make = function(title, init_text, textbox_width, is_dirname) {
	this.is_dirname = is_dirname;
	var html="";
	jQuery('body').append('<div id="mocd_input_text"></div>');
	html = '<div class="title">'+title+'</div>';
	html += '<input type="textbox" id="mocd_input_textbox" style="width:'+textbox_width+'px"/>';
	html += '<div class="mocd_input_text_button_wrapper">';
	html += '<div class="mocd_input_text_button" id="mocd_input_text_ok">&nbsp;OK&nbsp;</div>';
	html += '<div class="mocd_input_text_button" id="mocd_input_text_cancel">&nbsp;Cancel&nbsp;</div>';
	html += '</div>';

	this.pos_left = mocd.mouse_x;
	this.pos_top = mocd.mouse_y;

	jQuery('#mocd_input_text').html(html);
	jQuery('#mocd_input_text').css('top',this.pos_top+"px");
	jQuery('#mocd_input_text').css('left',this.pos_left+"px");
	jQuery('#mocd_input_textbox').val(init_text);

	var that = this;
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

// function name: MrlInputTextClass::set_callback
// description : register callback function called when OK is pressed
// argument : (c)callback function
MrlInputTextClass.prototype.set_callback = function(c) {
	this.callback = c;
}

// function name: MrlInputTextClass::check_dotext
// description : check if '.+file extension' pattern exists in the name (ex)abc.jpgdef
// argument : (str: target string, isdir: the name is of a directory)
// return : true(exists), false(not exists)
MrlInputTextClass.prototype.check_dotext = function(str, isdir) {
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



// function name: MrlInputTextClass::invalid_chr
// description : check if invalid character exists in the name.
// argument : (str: target string)
// return : true(exists), false(not exists)
MrlInputTextClass.prototype.check_invalid_chr = function(str) {
	var i;
	for (i = 0; i < this.invalid_chr.length; i++) {
		if (str.indexOf(this.invalid_chr[i]) >= 0) {
			return true;
		}
	}
	return false;
}

MrlInputTextClass.prototype.invalid_chr_msg = function() {
	var msg = "";
	for (i = 0; i < this.invalid_chr.length; i++) {
		msg += this.invalid_chr[i] + " ";
	}
	return msg;
}



