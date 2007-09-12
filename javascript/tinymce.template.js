function nullConverter(url) {
	return url;
}

/**
 * TinyMCE initialisation template.
 * $ variables are replaced by string search & replace.  It's pretty crude.
 */
tinyMCE.init({
	mode : "specific_textareas",
	textarea_trigger : "tinymce",
	width: -1,
	height: 240,
	auto_resize : true,
	theme : "advanced",
	content_css : "$ContentCSS",
	document_base_url: "$BaseURL",
	urlconverter_callback : "nullConverter",
	
	setupcontent_callback : "sapphiremce_setupcontent",
	cleanup_callback : "sapphiremce_cleanup",
	
	Theme_Advanced_Layout_manager : "SimpleLayout",
	theme_advanced_toolbar_location : "manually_placed",
	theme_advanced_toolbar_align : "left",
	theme_advanced_toolbar_parent : "right",
	plugins : "contextmenu,table,emotions,flash",	
	table_inline_editing : true,
	theme_advanced_buttons1 : "italic,underline,strikethrough,separator,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,separator,bullist,numlist,outdent,indent,hr,charmap",
	theme_advanced_buttons2 : "undo,redo,separator,cut,copy,paste,separator,search,replace,separator,flash",
	theme_advanced_buttons3 : "",
	theme_advanced_buttons3_add : "emotions",
/*
	theme_advanced_buttons1 : "bold,italic,underline,strikethrough,separator,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,separator,bullist,numlist,outdent,indent,hr,charmap",
	theme_advanced_buttons2 : "undo,redo,separator,cut,copy,paste,separator,search,replace,separator,link,unlink,anchor,image,separator,cleanup,removeformat,visualaid,code,separator,tablecontrols",
*/	
	safari_warning : false,
	relative_urls : true,
	verify_html : true,
	valid_elements : "+a[id|rel|rev|dir|tabindex|accesskey|type|name|href|target|title|class],-strong/-b[class],-em/-i[class],-strike[class],-u[class],#p[id|dir|class|align],-ol[class],-ul[class],-li[class],br,img[id|dir|longdesc|usemap|class|src|border|alt=|title|width|height|align],-sub[class],-sup[class],-blockquote[dir|class],-table[border=0|cellspacing|cellpadding|width|height|class|align|summary|dir|id|style],-tr[id|dir|class|rowspan|width|height|align|valign|bgcolor|background|bordercolor|style],tbody[id|class|style],thead[id|class|style],tfoot[id|class|style],-td[id|dir|class|colspan|rowspan|width|height|align|valign|scope|style],-th[id|dir|class|colspan|rowspan|width|height|align|valign|scope|style],caption[id|dir|class],-div[id|dir|class|align],-span[class|align],-pre[class|align],address[class|align],-h1[id|dir|class|align],-h2[id|dir|class|align],-h3[id|dir|class|align],-h4[id|dir|class|align],-h5[id|dir|class|align],-h6[id|dir|class|align],hr[class],dd[id|class|title|dir],dl[id|class|title|dir],dt[id|class|title|dir]",
	extended_valid_elements : "img[class|src|alt|title|hspace|vspace|width|height|align|onmouseover|onmouseout|name]"
});
