/**
 */

var MOCDMediaSelector = function() {

	// this function is called when the insert button is pressed.
	this.onClickSubmitButton = function(html) {
		self.parent.onMOCDMediaSelector_ShortCode( html );
	};
};

//唯一の MOCDMediaSelector インスタンスを生成
var MOCDMediaSelector = new MOCDMediaSelector();

window.onload = function() {
	//MOCDMediaSelector.initialize();
};


// namespace 'mocd' already exists

//var mocds = mocds || {};
//mocd.files_data = [];
//mocd.subdir_data = [];
//mocd.get_data_count = 0;
//mocd.get_data_id = -1;

// function name: (none)
// description :  initialization
// argument : (void)
jQuery(document).ready(function() {

    // !! Don't do anything until the button is pressed
//	var data = {
//		action: 'mocd_get_media_subdir'
//	};
//	jQuery.post(ajaxurl, data, function(response) {
//        // response is e.g. "[{"subdir":"private\/"},{"subdir":"private2\/"},{"subdir":"test\/"}]"
//		mocd.subdir_data = JSON.parse(response);
//		mocd.get_data_count++;
//	});
//
//
//	var data = {
//		action: 'mocd_get_media_list'
//	};
//	jQuery.post(ajaxurl, data, function(response) {
 //       // response is e.g. "[{"post_title":"a-and-e-connock-logo","ID":"746","file":"a-and-e-connock-logo.jpg","post_mime_type":"image\/jpeg","subfolder":"","thumbnail":"a-and-e-connock-logo-150x78.jpg"},...]"
//        // Yes, it's ALL the files in all the folders!!
//		mocd.files_data = JSON.parse(response);
//		mocd.get_data_count++;
//	});
//
//    // NNOOOOO !!!
//	mocd.get_data_id = setInterval(mocd.prepare, 20);

});

//mocd.prepare = function () 
//{
//	if (mocd.get_data_count < 2) return;
//	if (mocd.get_data_id>0) {
//		clearInterval(mocd.get_data_id);
//		mocd.get_data_id = -1;
//	}
//
//	mocd.make_selector_control()
//	mocd.make_file_list();
//}

mocd.prev_disp = 'list';


// function name: mocd.make_selector_control
// description :  display pull-down menus and prepare events for menu changes.
// argument : (void)
mocd.make_selector_control = function () {
	var html, i;
	html = '&nbsp; Directory:<select id="sel_subdir" name="sel_subdir">';
	html += '<option value="all">(all)&nbsp;</option>';
	html += '<option value="/">/</option>';

//alert(html);

    // CD subdir-data doesn't exist now
	//for (i=0; i<mocd.subdir_data.length; i++) {
	//	html += '<option value="'+mocd.subdir_data[i]['subdir']+'">/'+mocd.subdir_data[i]['subdir']+'&nbsp;</option>';
	//}
    html += "<option value='no subdir list yet'>no subdir yet</option>";
	html += "</select>&nbsp;&nbsp;";

	html += 'Media Type:<select id="sel_type" name="sel_type">';
	html += '<option value="all">(all)&nbsp;</option>';
	html += '<option value="image">Image&nbsp;</option>';
	html += '<option value="audio">Audio&nbsp;</option>';
	html += '<option value="video">Video&nbsp;</option>';
	html += "</select>&nbsp;&nbsp;";

	html += 'Display:<select id="sel_disp" name="sel_disp">';
	html += '<option value="list">List&nbsp;</option>';
	html += '<option value="tile">Tile&nbsp;</option>';
	html += "</select>&nbsp;&nbsp;";


	jQuery('#mocd_control').html(html);


	jQuery("select").change(function () {
		var i;

		var sel_subdir = jQuery('#sel_subdir').val();
		var sel_type = jQuery('#sel_type').val();
		var sel_disp = jQuery('#sel_disp').val();

		if (mocd.prev_disp != sel_disp) {
			if (sel_disp == 'list') {
				mocd.make_file_list();
			} else {
				mocd.make_thumbnail_table();
			}
			mocd.prev_disp = sel_disp;
		}

		for (i=0; i<mocd.files_data.length; i++) {
			var disp = true;
			if (sel_type != 'all') {
				if (mocd.files_data[i]['post_mime_type'].substr(0,5) != sel_type) {
					disp = false;
				}
			}
			if (sel_subdir != 'all') {
				if (sel_subdir == '/') {
					if (mocd.files_data[i]['file'].indexOf("/")>=0) {
						disp = false;
					}
				} else if (mocd.files_data[i]['file'].substr(0, sel_subdir.length) != sel_subdir) {
					disp = false;
				}
			}
			jQuery('#mocd_media_tl_'+i).css('display', disp?((sel_disp=='list')?'block':'inline-table'):'none' );
		}
		
	});
}


// jQuery appear plugin
//(function($){$.fn.appear=function(f,o){var s=$.extend({one:true},o);return this.each(function(){var t=$(this);t.appeared=false;if(!f){t.trigger('appear',s.data);return;}var w=$(window);var c=function(){if(!t.is(':visible')){t.appeared=false;return;}var a=w.scrollLeft();var b=w.scrollTop();var o=t.offset();var x=o.left;var y=o.top;if(y+t.height()>=b&&y<=b+w.height()&&x+t.width()>=a&&x<=a+w.width()){if(!t.appeared)t.trigger('appear',s.data);}else{t.appeared=false;}};var m=function(){t.appeared=true;if(s.one){w.unbind('scroll',c);var i=$.inArray(c,$.fn.appear.checks);if(i>=0)$.fn.appear.checks.splice(i,1);}f.apply(this,arguments);};if(s.one)t.one('appear',s.data,m);else t.bind('appear',s.data,m);w.scroll(c);$.fn.appear.checks.push(c);(c)();});};$.extend($.fn.appear,{checks:[],timeout:null,checkAll:function(){var l=$.fn.appear.checks.length;if(l>0)while(l--)($.fn.appear.checks[l])();},run:function(){if($.fn.appear.timeout)clearTimeout($.fn.appear.timeout);$.fn.appear.timeout=setTimeout($.fn.appear.checkAll,20);}});$.each(['append','prepend','after','before','attr','removeAttr','addClass','removeClass','toggleClass','remove','css','show','hide'],function(i,n){var u=$.fn[n];if(u){$.fn[n]=function(){var r=u.apply(this,arguments);$.fn.appear.run();return r;}}});})(jQuery);


// function name: mocd.make_file_list
// description :  display a list of media
// argument : (void)
// // FIXME this gets called when page/post admin page is loaded, before clicking on the button
// // TODO only generate all this HTML when the button is pressed
mocd.make_file_list = function () {
	var html='<table border="0" style="margin-left:5px;">', i;

	for (i=0; i<mocd.files_data.length; i++) {  // mocd.files_data is apparently all the media items!
		html += '<tr id="mocd_media_tl_'+i+'" style="display:block;">';
		html += '<td id="mocd_media_'+i+'" style="width:60px;height:60px;"></td>';
		html += '<td >'+mocd.files_data[i]['post_title'] + '</td></tr>';
	}
		html += '</table>';
	jQuery('#mocd_selector').html(html);
	for (i=0; i<mocd.files_data.length; i++) {
		var _uploadurl = mocd_constants.uploadurl;
		if (mocd.files_data[i]['thumbnail'].substr(0,4)=='http') _uploadurl='';
		jQuery('#mocd_media_'+i).data('thumbnail' , _uploadurl + mocd.files_data[i]['thumbnail']);  // FIXME .data
	//	jQuery('#mocd_media_'+i).appear(function(){   // FIXME do we need .appear?
	//		html = '<img src="'+jQuery(this).data('thumbnail')+'" width="50" />';
	//		jQuery(this).html(html);	
      //    console.log(html);
	//		jQuery(this).unbind('appear');
	//	});
	}

	mocd.set_selector_event()
}

// function name: mocd.make_thumbnail_table
// description :  display a list of media (tile style)
// argument : (void)
mocd.make_thumbnail_table = function () {
	var html="", i;

	for (i=0; i<mocd.files_data.length; i++) {
		html += '<table id="mocd_media_tl_'+i+'" style="display:inline-table; overflow:hidden;"><tr>';
		html += '<td id="mocd_media_'+i+'" title="'+mocd.files_data[i]['post_title']+'" width="150" height="150">';
		html += '</td></tr></table>';
	}
	jQuery('#mocd_selector').html(html);

	for (i=0; i<mocd.files_data.length; i++) {
		var _uploadurl = mocd.constants.uploadurl;   
		if (mocd.files_data[i]['thumbnail'].substr(0,4)=='http') _uploadurl='';
		jQuery('#mocd_media_'+i).data('thumbnail' , _uploadurl + mocd.files_data[i]['thumbnail']);
	//	jQuery('#mocd_media_'+i).appear(function(){
	//		html = '<img src="'+jQuery(this).data('thumbnail')+'" width="150" />';
//			jQuery(this).html(html);	
//			jQuery(this).unbind('appear');
//		});
	}

	mocd.set_selector_event()
}

// function name: mocd_set_selector_event
// description : set event to open a insert dialog to each images
// argument : (void)
mocd.set_selector_event = function () {
	for (i=0; i<mocd.files_data.length; i++) {
		id = '#mocd_media_'+i;
		jQuery(id).data('id', mocd.files_data[i]['ID']);
		jQuery(id).click(function(){mocd.open_selector_insert_dialog(jQuery(this).data('id'));});
	}
}


// function name: mocd.open_selector_insert_dialog
// description :  open a media insert dialog
// argument : (id) post-id of media
mocd.open_selector_insert_dialog = function (id) {
	var data = {
		action: 'mocd_get_image_insert_screen',
		id: id
	};
	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.post(ajaxurl, data, function(response) {
		mocd_open_selector_insert_dialog_main(response);
	});
}


// function name: mocd.open_selector_insert_dialog_main
// description :  Display a media insert dialog, and make the code for insersion
// argument : (dat)html of the edit screen
mocd.open_selector_insert_dialog_main = function (dat) {
	mocd.selector_html = 	jQuery('#mocd_selector').html();
	jQuery('#mocd_selector').css('display','none');
	jQuery('#mocd_control').css('display','none');
	jQuery('#mocd_edit').html('');
	jQuery('#mocd_edit').append('<div id="mocd.insert_dialog" style="background-color:#fff; position:relative;top:0px;left:10px;"></div>');
	jQuery('#mocd_insert_dialog').html(dat);
	jQuery('#mocd_cancel').click(function(){
		jQuery('#mocd_edit').html('');
		jQuery('#mocd_selector').css('display','block');		
		jQuery('#mocd_control').css('display','block');
	});

	jQuery('#urlnone').click(function(){
		jQuery('#attachments_url').val(jQuery('#urlnone').data("link-url"))
	});
	jQuery('#urlfile').click(function(){
		jQuery('#attachments_url').val(jQuery('#urlfile').data("link-url"))
	});
	jQuery('#urlpost').click(function(){
		jQuery('#attachments_url').val(jQuery('#urlpost').data("link-url"))
	});


	jQuery('#send').click(function(){
		var data = JSON.parse(jQuery('#mocd_data').html());
		var title = jQuery('input#attachments_post_title').val();
		var caption = jQuery('input#attachments_post_excerpt').val();
		var description = jQuery('textarea#attachments_post_content').val();
		var link_url = jQuery('input#attachments_url').val();
		var is_image = data['is_image']; 

		if (is_image) {
			var alt_org = jQuery('input#attachments_image_alt').val();
			var align = jQuery('input:radio[name=attachments_align]:checked').val();
			var size = jQuery('input:radio[name=attachments-image-size]:checked').val();
			var width=0, height=0;
			var iclass='';
			alt = mocd.htmlEncode(alt_org);
		} else {
			alt = "$none$";
		}

		var data = {
			action: 'mocd_update_media_information',
			id:data['posts']['ID'],
			title:title,
			caption:caption,
			description:description,
			alt: alt_org
		};
		jQuery.post(ajaxurl, data, function(response) {});


		title = mocd.htmlEncode(title);
		caption = mocd.htmlEncode(caption);
		description = mocd.htmlEncode(description);

		if (is_image) {
			img_url = /*uploadurl;*/mocd_data['urldir'];
			if (size=='full') {
				width = data['meta']['width'];
				height = data['meta']['height'];
				img_url = uploadurl + data['meta']['file'];
				iclass='size-full';
			}
			if (size=='thumbnail') {
				width = data['meta']['sizes']['thumbnail']['width'];
				height = data['meta']['sizes']['thumbnail']['height'];
				img_url += data['meta']['sizes']['thumbnail']['file'];
				iclass='size-thumbnail';
			}
			if (size=='medium') {
				width = data['meta']['sizes']['medium']['width'];
				height = data['meta']['sizes']['medium']['height'];
				img_url += data['meta']['sizes']['medium']['file'];
				iclass='size-medium';
			}
			if (size=='large') {
				width = data['meta']['sizes']['large']['width'];
				height = data['meta']['sizes']['large']['height'];
				img_url += data['meta']['sizes']['large']['file'];
				iclass='size-large';
			}
		}

		//alert(title+'\n'+alt+'\n'+caption+'\n'+description+'\n'+link_url+'\n'+align+'\n'+size);

		var html = '';

		if (is_image) {
			if (caption != "") {
				html += '[caption id="attachment_'+data['posts']['ID']+'" align="align'+align+ '" width="'+width+'" caption="'+caption+'"]';
			}
		}
		if (link_url != "") {
			html += '<a href="'+link_url+'">';
		}
		if (is_image) {
			html += '<img src="'+img_url+'" ';
			if (alt!="") {
				html += 'alt="' + alt + '" ';
			}
			if (title!="") {
				html += 'title="' + title + '" ';
			}
			html += 'width="'+width+'" height="'+height+'" class="';
			if (caption=="") {
				html += 'align'+align+' ';
			}
			html += iclass + ' wp-image-'+data['posts']['ID']+'" />';
		} else {
			html += title;
		}
		if (link_url != "") {
			html += '</a>';
		}
		if (is_image) {
			if (caption != "") {
				html += '[/caption]' ;
			}
		}

		MOCDMediaSelector.onClickSubmitButton(html);

	});
}

// function name: mocd.htmlEncode
// description : 
// argument : (value)
mocd.htmlEncode = function (value){
    if (value) {
        value = jQuery('<div />').text(value).html();

        var escaped = value;
        var findReplace = [[/&/g, "&amp;"], [/</g, "&lt;"], [/>/g, "&gt;"], [/"/g, "&quot;"]]
            for(var item in findReplace)
                escaped = escaped.replace(findReplace[item][0], findReplace[item][1]);
        return escaped;


    } else {
        return '';
    }
}
