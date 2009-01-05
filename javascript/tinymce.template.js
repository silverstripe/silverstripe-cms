function nullConverter(url) {
	return url;
}

/**
 * TinyMCE initialisation template.
 * $ variables are replaced by string search & replace.  It's pretty crude.
 */
// Prevents "Error: 'tinyMCE' is undefined" error in IE7 on Newsletter Recipient import.
if((typeof tinyMCE != 'undefined')) {
	tinyMCE.init({
		mode : "none",
		language: "$Lang",
		width: "100%",
		auto_resize : false,
		theme : "advanced",
		content_css : "$ContentCSS",
		body_class : 'typography',
		document_base_url: "$BaseURL",
		urlconverter_callback : "nullConverter",
		
		setupcontent_callback : "sapphiremce_setupcontent",
		cleanup_callback : "sapphiremce_cleanup",
		
		theme_advanced_layout_manager: "SimpleLayout",
		theme_advanced_toolbar_location : "top",
		theme_advanced_toolbar_align : "left",
		theme_advanced_toolbar_parent : "right",
		plugins : "blockquote,contextmenu,table,emotions,paste,../../tinymce_ssbuttons,../../tinymce_advcode,spellchecker",	
		blockquote_clear_tag : "p",
		table_inline_editing : true,
		theme_advanced_buttons1 : "bold,italic,underline,strikethrough,separator,justifyleft,justifycenter,justifyright,justifyfull,styleselect,formatselect,separator,bullist,numlist,outdent,indent,blockquote,hr,charmap",
		theme_advanced_buttons2 : "undo,redo,separator,cut,copy,paste,pastetext,pasteword,spellchecker,separator,ssimage,ssflash,sslink,unlink,anchor,separator,advcode,search,replace,selectall,visualaid,separator,tablecontrols",
		theme_advanced_buttons3 : "",
		spellchecker_languages : "$SpellcheckLangs",
		
		template_templates : [
		    { title : "Three column", src : "assets/snippet.html", description : "A simple 3 column layout"},
		],

		safari_warning : false,
		relative_urls : true,
		verify_html : true
	});
}

Behaviour.register({
    'textarea.htmleditor' : {
        initialize : function() {
            tinyMCE.execCommand("mceAddControl", true, this.id);
            this.isChanged = function() {
                return tinyMCE.getInstanceById(this.id).isDirty();
            }
        }
    }
})
