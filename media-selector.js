/**
 */

var MrlMediaSelector = function()
{

	// this function is called when the insert button is pressed.
	this.onClickSubmitButton = function(html)
	{
		self.parent.onMrlMediaSelector_ShortCode( html );
	};
};

//唯一の MrlMediaSelector インスタンスを生成
var MrlMediaSelector = new MrlMediaSelector();

window.onload = function()
{
	//MrlMediaSelector.initialize();
};


var mocd_files_data;
var mocd_subdir_data;
var mocd_get_data_count = 0;
var mocd_get_data_id = -1;

// function name: (none)
// description :  initialization
// argument : (void)
jQuery(document).ready(function() {

	var data = {
		action: 'mocd_get_media_subdir'
	};
	jQuery.post(ajaxurl, data, function(response) {
		mocd_subdir_data = JSON.parse(response);
		mocd_get_data_count++;
	});


	var data = {
		action: 'mocd_get_media_list'
	};
	jQuery.post(ajaxurl, data, function(response) {
		mocd_files_data = JSON.parse(response);
		mocd_get_data_count++;
	});

	mocd_get_data_id = setInterval(mocd_prepare, 20);

});

function mocd_prepare() 
{
	if (mocd_get_data_count < 2) return;
	if (mocd_get_data_id>0) {
		clearInterval(mocd_get_data_id);
		mocd_get_data_id = -1;
	}

	mocd_make_selector_control()
	mocd_make_file_list();
}

var mocd_prev_disp = 'list';


// function name: mocd_make_selector_control
// description :  display pull-down menus and prepare events for menu changes.
// argument : (void)
function mocd_make_selector_control()
{
	var html, i;
	html = '&nbsp; Directory:<select id="sel_subdir" name="sel_subdir">';
	html += '<option value="all">(all)&nbsp;</option>';
	html += '<option value="/">/</option>';

//alert(html);

	for (i=0; i<mocd_subdir_data.length; i++) {
		html += '<option value="'+mocd_subdir_data[i]['subdir']+'">/'+mocd_subdir_data[i]['subdir']+'&nbsp;</option>';
	}
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

		if (mocd_prev_disp != sel_disp) {
			if (sel_disp == 'list') {
				mocd_make_file_list();
			} else {
				mocd_make_thumbnail_table();
			}
			mocd_prev_disp = sel_disp;
		}

		for (i=0; i<mocd_files_data.length; i++) {
			var disp = true;
			if (sel_type != 'all') {
				if (mocd_files_data[i]['post_mime_type'].substr(0,5) != sel_type) {
					disp = false;
				}
			}
			if (sel_subdir != 'all') {
				if (sel_subdir == '/') {
					if (mocd_files_data[i]['file'].indexOf("/")>=0) {
						disp = false;
					}
				} else if (mocd_files_data[i]['file'].substr(0, sel_subdir.length) != sel_subdir) {
					disp = false;
				}
			}
			jQuery('#mocd_media_tl_'+i).css('display', disp?((sel_disp=='list')?'block':'inline-table'):'none' );
		}
		
	});
}



(function($){$.fn.appear=function(f,o){var s=$.extend({one:true},o);return this.each(function(){var t=$(this);t.appeared=false;if(!f){t.trigger('appear',s.data);return;}var w=$(window);var c=function(){if(!t.is(':visible')){t.appeared=false;return;}var a=w.scrollLeft();var b=w.scrollTop();var o=t.offset();var x=o.left;var y=o.top;if(y+t.height()>=b&&y<=b+w.height()&&x+t.width()>=a&&x<=a+w.width()){if(!t.appeared)t.trigger('appear',s.data);}else{t.appeared=false;}};var m=function(){t.appeared=true;if(s.one){w.unbind('scroll',c);var i=$.inArray(c,$.fn.appear.checks);if(i>=0)$.fn.appear.checks.splice(i,1);}f.apply(this,arguments);};if(s.one)t.one('appear',s.data,m);else t.bind('appear',s.data,m);w.scroll(c);$.fn.appear.checks.push(c);(c)();});};$.extend($.fn.appear,{checks:[],timeout:null,checkAll:function(){var l=$.fn.appear.checks.length;if(l>0)while(l--)($.fn.appear.checks[l])();},run:function(){if($.fn.appear.timeout)clearTimeout($.fn.appear.timeout);$.fn.appear.timeout=setTimeout($.fn.appear.checkAll,20);}});$.each(['append','prepend','after','before','attr','removeAttr','addClass','removeClass','toggleClass','remove','css','show','hide'],function(i,n){var u=$.fn[n];if(u){$.fn[n]=function(){var r=u.apply(this,arguments);$.fn.appear.run();return r;}}});})(jQuery);


// function name: mocd_make_file_list
// description :  display a list of media
// argument : (void)
function mocd_make_file_list()
{
	var html='<table border="0" style="margin-left:5px;">', i;

	for (i=0; i<mocd_files_data.length; i++) {
		html += '<tr id="mocd_media_tl_'+i+'" style="display:block;">';
		html += '<td id="mocd_media_'+i+'" style="width:60px;height:60px;"></td>';
		html += '<td >'+mocd_files_data[i]['post_title'] + '</td></tr>';
	}
		html += '</table>';
	jQuery('#mocd_selector').html(html);
	for (i=0; i<mocd_files_data.length; i++) {
		var _uploadurl = uploadurl;
		if (mocd_files_data[i]['thumbnail'].substr(0,4)=='http') _uploadurl='';
		jQuery('#mocd_media_'+i).data('thumbnail' , _uploadurl + mocd_files_data[i]['thumbnail']);
		jQuery('#mocd_media_'+i).appear(function(){
			html = '<img src="'+jQuery(this).data('thumbnail')+'" width="50" />';
			jQuery(this).html(html);	
console.log(html);
			jQuery(this).unbind('appear');
		});
	}

	mocd_set_selector_event()
}

// function name: mocd_make_thumbnail_table
// description :  display a list of media (tile style)
// argument : (void)
function mocd_make_thumbnail_table()
{
	var html="", i;

	for (i=0; i<mocd_files_data.length; i++) {
		html += '<table id="mocd_media_tl_'+i+'" style="display:inline-table; overflow:hidden;"><tr>';
		html += '<td id="mocd_media_'+i+'" title="'+mocd_files_data[i]['post_title']+'" width="150" height="150">';
		html += '</td></tr></table>';
	}
	jQuery('#mocd_selector').html(html);

	for (i=0; i<mocd_files_data.length; i++) {
		var _uploadurl = uploadurl;
		if (mocd_files_data[i]['thumbnail'].substr(0,4)=='http') _uploadurl='';
		jQuery('#mocd_media_'+i).data('thumbnail' , _uploadurl + mocd_files_data[i]['thumbnail']);
		jQuery('#mocd_media_'+i).appear(function(){
			html = '<img src="'+jQuery(this).data('thumbnail')+'" width="150" />';
			jQuery(this).html(html);	
			jQuery(this).unbind('appear');
		});
	}

	mocd_set_selector_event()
}

// function name: mocd_set_selector_event
// description : set event to open a insert dialog to each images
// argument : (void)
function mocd_set_selector_event()
{
	for (i=0; i<mocd_files_data.length; i++) {
		id = '#mocd_media_'+i;
		jQuery(id).data('id', mocd_files_data[i]['ID']);
		jQuery(id).click(function(){mocd_open_selector_insert_dialog(jQuery(this).data('id'));});
	}
}


// function name: mocd_open_selector_insert_dialog
// description :  open a media insert dialog
// argument : (id) post-id of media
function mocd_open_selector_insert_dialog(id)
{
	var data = {
		action: 'mocd_get_image_insert_screen',
		id: id
	};
	// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
	jQuery.post(ajaxurl, data, function(response) {
		mocd_open_selector_insert_dialog_main(response);
	});
}


// function name: mocd_open_selector_insert_dialog_main
// description :  Display a media insert dialog, and make the code for insersion
// argument : (dat)html of the edit screen
function mocd_open_selector_insert_dialog_main(dat)
{
	mocd_selector_html = 	jQuery('#mocd_selector').html();
	jQuery('#mocd_selector').css('display','none');
	jQuery('#mocd_control').css('display','none');
	jQuery('#mocd_edit').html('');
	jQuery('#mocd_edit').append('<div id="mocd_insert_dialog" style="background-color:#fff; position:relative;top:0px;left:10px;"></div>');
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
		var mocd_data = JSON.parse(jQuery('#mocd_data').html());
		var title = jQuery('input#attachments_post_title').val();
		var caption = jQuery('input#attachments_post_excerpt').val();
		var description = jQuery('textarea#attachments_post_content').val();
		var link_url = jQuery('input#attachments_url').val();
		var is_image = mocd_data['is_image']; 

		if (is_image) {
			var alt_org = jQuery('input#attachments_image_alt').val();
			var align = jQuery('input:radio[name=attachments_align]:checked').val();
			var size = jQuery('input:radio[name=attachments-image-size]:checked').val();
			var width=0, height=0;
			var iclass='';
			alt = mocd_htmlEncode(alt_org);
		} else {
			alt = "$none$";
		}

		var data = {
			action: 'mocd_update_media_information',
			id:mocd_data['posts']['ID'],
			title:title,
			caption:caption,
			description:description,
			alt: alt_org
		};
		jQuery.post(ajaxurl, data, function(response) {});


		title = mocd_htmlEncode(title);
		caption = mocd_htmlEncode(caption);
		description = mocd_htmlEncode(description);

		if (is_image) {
			img_url = /*uploadurl;*/mocd_data['urldir'];
			if (size=='full') {
				width = mocd_data['meta']['width'];
				height = mocd_data['meta']['height'];
				img_url = uploadurl + mocd_data['meta']['file'];
				iclass='size-full';
			}
			if (size=='thumbnail') {
				width = mocd_data['meta']['sizes']['thumbnail']['width'];
				height = mocd_data['meta']['sizes']['thumbnail']['height'];
				img_url += mocd_data['meta']['sizes']['thumbnail']['file'];
				iclass='size-thumbnail';
			}
			if (size=='medium') {
				width = mocd_data['meta']['sizes']['medium']['width'];
				height = mocd_data['meta']['sizes']['medium']['height'];
				img_url += mocd_data['meta']['sizes']['medium']['file'];
				iclass='size-medium';
			}
			if (size=='large') {
				width = mocd_data['meta']['sizes']['large']['width'];
				height = mocd_data['meta']['sizes']['large']['height'];
				img_url += mocd_data['meta']['sizes']['large']['file'];
				iclass='size-large';
			}
		}

		//alert(title+'\n'+alt+'\n'+caption+'\n'+description+'\n'+link_url+'\n'+align+'\n'+size);

		var html = '';

		if (is_image) {
			if (caption != "") {
				html += '[caption id="attachment_'+mocd_data['posts']['ID']+'" align="align'+align+ '" width="'+width+'" caption="'+caption+'"]';
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
			html += iclass + ' wp-image-'+mocd_data['posts']['ID']+'" />';
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

		MrlMediaSelector.onClickSubmitButton(html);

	});
}

// function name: mocd_htmlEncode
// description : 
// argument : (value)
function mocd_htmlEncode(value){
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
