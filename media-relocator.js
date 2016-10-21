//**** Global variables *******************************************************************
var mrl_shift_pressed = false;	// Flag indicates shift key is pressed or not
var mrl_ajax = 0;
var mrloc_right_menu;	// Right-click menu class object
var mrloc_input_text;	// Text-input form class object
var mrloc_mouse_x, mrloc_mouse_y;
var pane_left, pane_right;	// Pane class objects


// function name: (none)
// description :  initialization
// argument : (void)
jQuery(document).ready(function()
{
	mrloc_right_menu = new MrlRightMenuClass();
	mrloc_input_text = new MrlInputTextClass();

	pane_left = new MrlPaneClass('mrl_left', true);
	pane_right = new MrlPaneClass('mrl_right', true);

	pane_left.opposite = pane_right;
	pane_right.opposite = pane_left;

	//adjust_layout();

	pane_left.setdir("/");
	pane_right.setdir("/");

	jQuery(document).keydown(function (e) {
	  if(e.shiftKey) {
	    mrl_shift_pressed = true;
	  }
	});
   	jQuery(document).mousemove(function(e){
		mrloc_mouse_x = e.pageX;
		mrloc_mouse_y = e.pageY;
	}); 
	jQuery(document).keyup(function(event){
	   mrl_shift_pressed = false;
	});

	jQuery('#mrl_btn_left2right').click(function() {
		if (mrl_ajax) return;
		mrloc_move(pane_left, pane_right);
	});
	jQuery('#mrl_btn_right2left').click(function() {
		if (mrl_ajax) return;
		mrloc_move(pane_right, pane_left);
	});


	jQuery('#mrl_test').click(function() {
		var data = {
			action: 'mrelocator_test'
		};
		jQuery.post(ajaxurl, data, function(response) {
			alert("mrelocator_test: "+response);
		});
	});

	jQuery(window).resize(function() {
		//jQuery('#debug').html(jQuery('#wpbody').height());
		adjust_layout();
	});

	
	adjust_layout();
});




//**** Pane class *******************************************************************
var MrlPaneClass = function(id_root, flg_chkbox)
{
	this.cur_dir = "";
	this.dir_list = new Array();
	this.dir_disp_list = new Array();
	this.id_root = id_root;
	this.id_wrapper = id_root + "_wrapper";
	this.id_pane = id_root + "_pane";
	this.id_dir = id_root + "_path";
	this.id_dir_new = id_root + "_dir_new";
	this.id_dir_up = id_root + "_dir_up";
	this.id_select_all = id_root + "_select_all";
	this.id_deselect_all = id_root + "_deselect_all";
	this.flg_chkbox = flg_chkbox;
	this.checked_loc = -1;
	this.last_div_id = "";
	this.chk_prepare_id = -1;
	this.opposite=this;
	this.disp_num = 0;

	var that = this;

	jQuery('#'+this.id_dir_up).click(function(ev) {
		if (mrl_ajax) return;
		if ("/" == that.cur_dir) return;
		that.chdir("..");
	});

	jQuery('#'+this.id_dir_new).click(function(ev) {
		if (mrl_ajax) return;
		mrloc_input_text.make("Make Directory","",300, true);
		mrloc_input_text.set_callback(function(){
			var dir  =  mrloc_input_text.result;
			if (dir=="") return;
			if (that.check_same_name(dir)) {
				alert("The same name exists.");
				return;
			}
			var res = "";
			var data = {
				action: 'mrelocator_mkdir',
				dir: that.cur_dir,
				newdir: dir
			};
			mrl_ajax_in();
			jQuery.post(ajaxurl, data, function(response) {
				if (response.search(/Success/i) < 0) alert("mrelocator_mkdir: "+response);

				if (that.cur_dir == that.opposite.cur_dir) {
					that.refresh();
					that.opposite.refresh();
				} else {
					that.refresh();
				}

				mrl_ajax_out();
			});
		});
	});

	jQuery('#'+this.id_select_all).click(function(ev) {
		for (i=0; i<that.disp_num; i++) {
			jQuery('#'+that.get_chkid(i)).attr('checked',true);
		}
	});
	jQuery('#'+this.id_deselect_all).click(function(ev) {
		for (i=0; i<that.disp_num; i++) {
			jQuery('#'+that.get_chkid(i)).attr('checked',false);
		}
	});
}

MrlPaneClass.prototype.get_chkid = function (n) {return this.id_pane+'_ck_'+n;}
MrlPaneClass.prototype.get_divid = function (n) {return this.id_pane+'_'+n;}
MrlPaneClass.prototype.refresh = function () {this.setdir(this.cur_dir);}

// function name: MrlPaneClass::setdir
// description : move to the directory and display directory listing
// argument : (dir)absolute path name of the target directory
MrlPaneClass.prototype.setdir = function(dir)
{
	jQuery('#'+this.id_wrapper).css('cursor:wait');
	var data = {
		action: 'mrelocator_getdir',
		dir: dir
	};

	var that = this;
	mrl_ajax_in();
	jQuery.post(ajaxurl, data, function(response) {
		that.dir_list = that.dir_ajax(data.dir, response);
		mrl_ajax_out();
	});
}

// function name: mrl_ins8203
// description : 
// argument : (str)
function mrl_ins8203(str)
{
	var ret="", i;
	for (i=0; i<str.length; i+=3) {
		ret += str.substr(i, 3);
		ret += '&#8203;'
	}
	return ret;
}

// function name: MrlPaneClass::dir_ajax
// description : display directory list retrieved from server
// argument : (dir)target_dir: target directory; (dirj):list(JSON); 
MrlPaneClass.prototype.dir_ajax = function(target_dir,dirj) 
{
	if (dirj.search(/error/i) == 0) {	
		alert(dirj);
		jQuery('#'+this.id_wrapper).css('cursor:default');
		return;
	}
	this.cur_dir = target_dir;
	jQuery('#'+this.id_dir).val(target_dir);
	this.disp_num = 0;

	dirj = jQuery.trim(dirj);
	if (dirj=="") {
		jQuery('#'+this.id_pane).html("");
		return new Array();
	}
	var dir;
	try {
		//dirj = dirj.substr(0, dirj.length-1);
		dir = JSON.parse(dirj);
	} catch (err) {
		alert(dirj+" : "+mrl_toHex(dirj));
		document.write('<table border="3"><tr><td width="200">');
		document.write("<prea>"+err+"\n"+dirj+"</pre>");
		document.write("</td></tr></table>");
	}
	var html = "";
	var that = this;
	this.last_chk_id = "";

	for (i=0; i<dir.length; i++) {
		if (dir[i].isthumb) continue;
		this.dir_disp_list[this.disp_num] = i;
		html = html+'<div style="vertical-align:middle;display:block;height:55px;clear:both; position:relative;">';
		if (this.flg_chkbox) {
			html = html + '<div style="float:left;"><input type="checkbox" id="'+this.get_chkid(this.disp_num)+'"></div>';
		}
		html = html + '<div style="float:left;" id="' + this.get_divid(this.disp_num)+'">';
		this.last_div_id = this.get_divid(this.disp_num);
		if (dir[i].thumbnail_url && dir[i].thumbnail_url!="") {
			html=html+'<img style="margin:0 5px 0 5px;" src="' + dir[i].thumbnail_url+'" width="50" />';
		}
		html=html+'</div><div class="mrl_filename">';
		html = html + mrl_ins8203(dir[i].name)/*+" --- " + dir[i].isdir+ (dir[i].id!=""?" "+dir[i].id:"")*/;
		html = html + '</div></div>';

		this.disp_num ++;
	}
	jQuery('#'+this.id_pane).html(html);

	//if (this.flg_chkbox) {
		function callMethod_chkprepare() {that.prepare_checkboxes();}
		if (this.chk_prepare_id == -1) {
			this.chk_prepare_id = setInterval(callMethod_chkprepare, 20);
		}
	//}
	jQuery('#'+this.id_wrapper).css('cursor:default');
	return dir;
}


// function name: MrlPaneClass::prepare_checkboxes
// description : prepare event for checkboxes and right-click events(mkdir, rename)
// argument : (void)
MrlPaneClass.prototype.prepare_checkboxes = function()
{
	var that = this;

	if (jQuery('#'+this.last_div_id).length>0) {
		clearInterval(this.chk_prepare_id);
		this.chk_prepare_id = -1;

		for (i=0; i<this.dir_disp_list.length; i++) {
			var idx = this.dir_disp_list[i];
			//if (this.dir_list[idx].isthumb) continue;

			jQuery('#'+this.get_divid(i)).data('order', i);
			jQuery('#'+this.get_divid(i)).data('data', idx);

			if (this.flg_chkbox) {
				jQuery('#'+this.get_chkid(i)).data('order', i);
				jQuery('#'+this.get_chkid(i)).data('data', idx);
				jQuery('#'+this.get_chkid(i)).change(function() {
					if (mrl_shift_pressed && that.checked_loc >= 0) {
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
			}

			jQuery(document).bind("contextmenu",function(e){
				return false;
			}); 
			jQuery('#'+this.get_divid(i)).mousedown(function(ev) {
				if (ev.which == 3) {
					ev.preventDefault();
					var isDir = that.dir_list[jQuery(this).data('data')]['isdir'];
					var arrMenu = new Array("Preview","Rename");
					if (isDir) {
						arrMenu.push("Delete");
					}
					mrloc_right_menu.make(arrMenu);
					var that2 = this;
					if (isDir) {
					jQuery('#'+mrloc_right_menu.get_item_id(2)).click(function(){ //delete
						var target = that.dir_list[jQuery(that2).data('data')];
						var isEmptyDir = target['isemptydir'];
						if (!isEmptyDir) {alert('Directory not empty.');return;}
						var target = that.dir_list[jQuery(that2).data('data')];
						var dirname = target['name'];
						var data = {
							action: 'mrelocator_delete_empty_dir',
							dir: that.cur_dir,
							name: dirname
						};
						mrl_ajax_in();
						jQuery.post(ajaxurl, data, function(response) {
							if (response.search(/Success/i) < 0) {alert("mrelocator_delete_empty_dir: "+response);}
							that.refresh();
							if (that.cur_dir == that.opposite.cur_dir) {
								that.opposite.refresh();
							}
							if (that.cur_dir+dirname+"/" == that.opposite.cur_dir) {
								that.opposite.setdir(that.cur_dir);
							}
							mrl_ajax_out();
						});
					});
					}
					jQuery('#'+mrloc_right_menu.get_item_id(0)).click(function(){ //preview
						var url = mrloc_url_root + (that.cur_dir+that.dir_list[jQuery(that2).data('data')]['name'])/*.substr(mrloc_document_root.length)*/;
						window.open(url, 'mrlocpreview', 'toolbar=0,location=0,menubar=0')
					});
					jQuery('#'+mrloc_right_menu.get_item_id(1)).click(function(){ //rename
						if (mrl_ajax) return;
						var target = that.dir_list[jQuery(that2).data('data')];
						if (target['norename']) {
							alert("Sorry, you cannot rename this item.");
							return;
						}
						var old_name = target['name'];
						mrloc_input_text.make("Rename ("+old_name+")",old_name,300, target['isdir'] );
						mrloc_input_text.set_callback(function(){
							if (old_name == mrloc_input_text.result || mrloc_input_text.result=="") {
								return;
							}
							if (that.check_same_name(mrloc_input_text.result)) {
								alert("The same name exists.");
								return;
							}
							var data = {
								action: 'mrelocator_rename',
								dir: that.cur_dir,
								from: old_name,
								to: mrloc_input_text.result
							};
							mrl_ajax_in();

							jQuery.post(ajaxurl, data, function(response) {
								if (response.search(/Success/i) < 0) alert("mrelocator_rename: "+response);
								if (that.opposite.cur_dir.indexOf(that.cur_dir+old_name+"/")===0) {
									that.opposite.setdir(that.cur_dir+mrloc_input_text.result+"/"+that.opposite.cur_dir.substr((that.cur_dir+old_name+"/").length));
								}
								if (that.cur_dir == that.opposite.cur_dir) {
									that.refresh();
									that.opposite.refresh();
								} else {
									that.refresh();
								}
								mrl_ajax_out();
							});
						});
					});
				}
				var dir = that.dir_list[jQuery(this).data('data')];
			});

			jQuery('#'+this.get_divid(i)).click(function() {
				if (mrl_ajax) return;
				var dir = that.dir_list[jQuery(this).data('data')];
				if (dir.isdir) {
					that.chdir(dir.name);
				}
			});
		}
	}
}

MrlPaneClass.prototype.check_same_name = function(str)
{
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
MrlPaneClass.prototype.chdir = function(dir)
{
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

// function name: MrlPaneClass::move
// description : moving checked files/directories
// argument : (pane_from)pane object; (pane_to)pane object 
var global_pane_from = "";
var global_pane_to = "";
var global_num_par_no = 0;
var global_move_no = 0;
var global_move_cnt = 0;
var global_move_continue=0;
function mrloc_move(pane_from, pane_to, no)
{
	no = typeof no !== 'undefined' ? no : 0;
	var cnt = global_move_cnt;
	
	if (no==0) {
		global_pane_from = pane_from;
		global_pane_to = pane_to;
		global_move_cnt = 0;
		mrloc_move(pane_from, pane_to, 1);
		return;
	}
	
	var num_par_no = 50;
	global_num_par_no = num_par_no;
	global_move_continue = 0;
	var no_from = (no-1)*num_par_no;
	var no_to = no_from + num_par_no-1;

	//alert(no+" "+global_pane_from.cur_dir+"-"+global_pane_to.cur_dir+"   "+no_from+"-"+no_to);

	
	var i,j;
	var flist="";

	if (pane_from.cur_dir == pane_to.cur_dir) return;

	var chk_no = -1;
	// make list of checked item
	for (i=0; i<pane_from.dir_disp_list.length; i++) {
		var attr = jQuery('#'+pane_from.get_chkid(i)).attr('checked');
		if (attr=='checked' || attr===true) {
			chk_no ++;
			if (chk_no<no_from) continue;
			if (chk_no>no_to) {
				global_move_continue = 1;
				continue;
			}
			cnt++;
			flist += pane_from.dir_list[pane_from.dir_disp_list[i]].name + "/";
			for (j=0; j<pane_from.dir_list.length; j++) {
				if (pane_from.dir_list[j].isthumb && pane_from.dir_list[j].parent == pane_from.dir_disp_list[i]) {
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
		action: 'mrelocator_move',
		dir_from: pane_from.cur_dir,
		dir_to: pane_to.cur_dir,
		items: flist
	};
	
	global_move_no = no;
	global_move_cnt = cnt;

	if (no == 1) {
		mrl_ajax_in();
	}
	jQuery.post(ajaxurl, data, function(response) {
		if (response.search(/Success/i) < 0) alert("mrloc_move(): "+response);
		if (global_move_continue) {
			mrloc_move(global_pane_from, global_pane_to, global_move_no+1);
		} else {
			pane_left.refresh();
			pane_right.refresh();
			mrl_ajax_out();
		}
	});
}


//**** right-menu class *******************************************************************
var MrlRightMenuClass = function()
{
	var num=0;
	var flgRegisterRemoveFunc = false;
	var pos_left = 0;
	var pos_right = 0;
}


// function name: MrlRightMenuClass::make
// description : make and display right-click menu
// argument : (items)array of menu items 
MrlRightMenuClass.prototype.make = function(items)
{
	var html="";
	var i;
	jQuery('body').append('<div id="mrl_right_menu"></div>');

	this.num = items.length;
	for (i=0; i<items.length; i++) {
		html += '<div class="mrl_right_menu_item" id="mrl_right_menu_item_' + i + '">';
		html += items[i];
		html += '</div>';
	}

	this.pos_left = mrloc_mouse_x;
	this.pos_top = mrloc_mouse_y;

	jQuery('#mrl_right_menu').html(html);
	jQuery('#mrl_right_menu').css('top',this.pos_top+"px");
	jQuery('#mrl_right_menu').css('left',this.pos_left+"px");

	for (i=0; i<items.length; i++) {
		var id = 'mrl_right_menu_item_' + i;
		jQuery('#'+id).hover(
			function(){this.removeClass('mrl_right_menu_item');this.addClass('mrl_right_menu_item_hover');},
			function(){this.removeClass('mrl_right_menu_item_hover');this.addClass('mrl_right_menu_item');}
		);
	}
	if (!this.flgRegisterRemoveFunc) {
		jQuery(document).click(function(){jQuery('#mrl_right_menu').remove();});
		this.flgRegisterRemoveFunc = true;
	}
}

// function name: MrlRightMenuClass::get_item_id
// description : get the id of the specified item
// argument : (n)index of item (starting from 0)
MrlRightMenuClass.prototype.get_item_id = function(n)
{
	return 'mrl_right_menu_item_' + n;
}



//**** Text input form class *******************************************************************
var MrlInputTextClass = function()
{
	var flgRegisterRemoveFunc = false;
	var pos_left = 0;
	var pos_right = 0;
	var result = "";
	var flgOK = false;
	var callback;
}

// function name: MrlInputTextClass::make
// description : make and display a text input form
// argument : (title)title; (init_text)initial text; (textbox_width)width of textbox
MrlInputTextClass.prototype.make = function(title, init_text, textbox_width, is_dirname)
{
	this.is_dirname = is_dirname;
	var html="";
	jQuery('body').append('<div id="mrl_input_text"></div>');
	html = '<div class="title">'+title+'</div>';
	html += '<input type="textbox" id="mrl_input_textbox" style="width:'+textbox_width+'px"/>';
	html += '<div class="mrl_input_text_button_wrapper">';
	html += '<div class="mrl_input_text_button" id="mrl_input_text_ok">&nbsp;OK&nbsp;</div>';
	html += '<div class="mrl_input_text_button" id="mrl_input_text_cancel">&nbsp;Cancel&nbsp;</div>';
	html += '</div>';

	this.pos_left = mrloc_mouse_x;
	this.pos_top = mrloc_mouse_y;

	jQuery('#mrl_input_text').html(html);
	jQuery('#mrl_input_text').css('top',this.pos_top+"px");
	jQuery('#mrl_input_text').css('left',this.pos_left+"px");
	jQuery('#mrl_input_textbox').val(init_text);

	var that = this;
	jQuery('#mrl_input_text_ok').click(function(){
		var result = jQuery('#mrl_input_textbox').val();
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
		jQuery('#mrl_input_text').remove();
		that.callback();
	});
	jQuery('#mrl_input_text_cancel').click(function(){
		jQuery('#mrl_input_text').remove();
		jQuery('body').unbind('click.mrlinput');
	});
	jQuery('body').bind('click.mrlinput', function(e){e.preventDefault();})
	jQuery('#mrl_input_textbox').focus();
}

// function name: MrlInputTextClass::set_callback
// description : register callback function called when OK is pressed
// argument : (c)callback function
MrlInputTextClass.prototype.set_callback = function(c)
{
	this.callback = c;
}

// function name: MrlInputTextClass::check_dotext
// description : check if '.+file extension' pattern exists in the name (ex)abc.jpgdef
// argument : (str: target string, isdir: the name is of a directory)
// return : true(exists), false(not exists)
MrlInputTextClass.prototype.check_dotext = function(str, isdir)
{
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


var invalid_chr = ["\\", "/", ":", "*", "?", "+", "\"", "<", ">", "|", "%", "&", "'", " ", "!", "#", "$", "(", ")", "{", "}"];

// function name: MrlInputTextClass::invalid_chr
// description : check if invalid character exists in the name.
// argument : (str: target string)
// return : true(exists), false(not exists)
MrlInputTextClass.prototype.check_invalid_chr = function(str)
{
	var i;
	for (i=0; i<invalid_chr.length; i++) {
		if (str.indexOf(invalid_chr[i]) >= 0) {
			return true;
		}
	}
	return false;
}

MrlInputTextClass.prototype.invalid_chr_msg = function()
{
	var msg = "";
	for (i=0; i<invalid_chr.length; i++) {
		msg += invalid_chr[i] + " ";
	}
	return msg;
}



//**** Global functions*********************************************************************************

// function name: adjust_layout
// description : adjust layout when resized
// argument : (void)
function adjust_layout()
{
	var width_all = jQuery('#mrl_wrapper_all').width();
	var height_all = jQuery('#mrl_wrapper_all').height();
	var width_center =jQuery('#mrl_center_wrapper').width(); 
	var height_mrl_box = jQuery('.mrl_box1').height();

	var position = jQuery('#wpbody').offset();
	height_all = jQuery(window).height() - position.top - 100;



	var pane_w = (width_all - width_center)/2-16;
	jQuery('.mrl_wrapper_pane').width(pane_w);
	jQuery('.mrl_path').width(pane_w);
	jQuery('.mrl_pane').width(pane_w);
	jQuery('.mrl_pane').height(height_all - height_mrl_box);	
	jQuery('.mrl_filename').width(pane_w-32);
}


// function name: mrl_ajax_in
// description : recognize entering ajax procedure to avoid user interrupt while data processing
// argument : (void)
function mrl_ajax_in()
{
	mrl_ajax ++;
	document.body.style.cursor = "wait";
	if (mrl_ajax==1) jQuery(document).bind('click.mrl', function(e){
		e.cancelBubble = true;
		if (e.stopPropagation) e.stopPropagation();
		e.preventDefault();
	});
}
// function name: mrl_ajax_out
// description : recognize finishing ajax procedure
// argument : (void)
function mrl_ajax_out()
{
	mrl_ajax --;
	if (	mrl_ajax == 0) {
		document.body.style.cursor = "default";
		jQuery(document).unbind('click.mrl');
	}
}

function mrl_toHex(str) {
    var hex = '';
    for(var i=0;i<str.length;i++) {
        hex += ''+str.charCodeAt(i).toString(16);
    }
    return hex;
}

