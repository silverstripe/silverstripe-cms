(function() {
	tinymce.PluginManager.requireLangPack("ssbuttons");
	var each = tinymce.each;

	tinymce.create('tinymce.plugins.SSButtons', {
		/**
		 * Returns information about the plugin as a name/value array.
		 * The current keys are longname, author, authorurl, infourl and version.
		 *
		 * @returns Name/value array containing information about the plugin.
		 * @type Array 
		 */
		getInfo : function() {
			return {
				longname : 'Special buttons for SilverStripe CMS',
				author : 'Sam Minnée',
				authorurl : 'http://www.siverstripe.com/',
				infourl : 'http://www.silverstripe.com/',
				version : "1.0"
			};
		},


		init : function(ed, url) {
			ed.addButton('sslink', {title : ed.getLang('tinymce_ssbuttons.insertlink'), cmd : 'sslink', 'class' : 'mce_link'}); 
			ed.addButton('ssimage', {title : ed.getLang('tinymce_ssbuttons.insertimage'), cmd : 'ssimage', 'class' : 'mce_image'}); 
			ed.addButton('ssflash', {title : ed.getLang('tinymce_ssbuttons.insertflash'), cmd : 'ssflash', 'class' : 'mce_flash', 'image': url + '/img/flash.gif'}); 

			ed.addCommand("sslink", function(ed) {
				jQuery('#Form_EditorToolbarLinkForm')[0].open();
			});

			ed.addCommand("ssimage", function(ed) {
				jQuery('#Form_EditorToolbarImageForm')[0].open();
			});

			ed.addCommand("ssflash", function(ed) {
				jQuery('#Form_EditorToolbarFlashForm')[0].open();
			});
			
			ed.onNodeChange.add(function(ed, o) {
				if ($('Form_EditorToolbarLinkForm').updateSelection) {
					$('Form_EditorToolbarLinkForm').updateSelection(ed);
					$('Form_EditorToolbarLinkForm').respondToNodeChange(ed);
				}
			});
		}
	});


	// Adds the plugin class to the list of available TinyMCE plugins
	tinymce.PluginManager.add("ssbuttons", tinymce.plugins.SSButtons);
})();