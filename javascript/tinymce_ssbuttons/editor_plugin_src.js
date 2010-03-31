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
			/**
			 * These map the action buttons to the IDs of the forms that they open/close
			 */
			forms = {
				'sslink' : 'Form_EditorToolbarLinkForm',
				'ssimage' : 'Form_EditorToolbarImageForm',
				'ssflash' : 'Form_EditorToolbarFlashForm'
			};

			ed.addButton('sslink', {title : ed.getLang('tinymce_ssbuttons.insertlink'), cmd : 'sslink', 'class' : 'mce_link'}); 
			ed.addButton('ssimage', {title : ed.getLang('tinymce_ssbuttons.insertimage'), cmd : 'ssimage', 'class' : 'mce_image'}); 
			ed.addButton('ssflash', {title : ed.getLang('tinymce_ssbuttons.insertflash'), cmd : 'ssflash', 'class' : 'mce_flash', 'image': url + '/img/flash.gif'}); 

			/**
			 * Show a side panel, hiding others
			 * If showCommand isn't set, then this will simply hide panels
			 */
			function showSidePanel(showCommand, hideCommands) {
				ed.ss_focus_bookmark = ed.selection.getBookmark();
				hideCommands.each(function(command) { 
					ed.controlManager.setActive(command,false);
					Element.hide(forms[command]); 
				});

				var showForm = null;
				if(forms[showCommand]) {
					showForm = $(forms[showCommand]);
					showForm.toggle(ed);
				}

				if(!showForm || showForm.style.display == "none") {
					ed.controlManager.setActive(showCommand, false);
					// Can't use $('contentPanel'), as its in a different window
					window.parent.document.getElementById('contentPanel').style.display = "none";
				} else {
					ed.controlManager.setActive(showCommand, true);
					window.parent.document.getElementById('contentPanel').style.display = "block";
				}
				window.onresize();
			}

			ed.addCommand("ssclosesidepanel", function(ed) {
				showSidePanel('', [ 'sslink', 'ssimage', 'ssflash' ]);
			});

			ed.addCommand("sslink", function(ed) {
				showSidePanel('sslink', [ 'ssimage', 'ssflash' ]);
			});

			ed.addCommand("ssimage", function(ed) {
				showSidePanel('ssimage', [ 'sslink', 'ssflash' ]);
			});

			ed.addCommand("ssflash", function(ed) {
				showSidePanel('ssflash', [ 'ssimage', 'sslink' ]);
			});

			ed.onNodeChange.add(function(ed, o) {
				if ($('Form_EditorToolbarLinkForm').updateSelection) {
					$('Form_EditorToolbarLinkForm').updateSelection(ed);
					$('Form_EditorToolbarLinkForm').respondToNodeChange(ed);
				}
				$('Form_EditorToolbarImageForm').respondToNodeChange(ed);
			});
			ed.onKeyUp.add(function(ed, o) {
				$('Form_EditorToolbarLinkForm').updateSelection(ed);
			});
		
			// resize image containers when the image is resized.
			if(!tinymce.isOpera && !tinymce.isWebKit) ed.onMouseUp.add(function(ed, o) {
				var node = ed.selection.getNode();
				if(node.nodeName == 'IMG' && ed.dom.getParent(node, 'div')) {
					// we have to delay the resize check here, as this event handler is called before the actual image
					// resizing is done.
					setTimeout(function() {
						var ed		= tinyMCE.activeEditor, // we need to redeclare these for IE.
							node	  = ed.selection.getNode(),
							container = ed.dom.getParent(node, 'div');

						if(node.width && node.width != parseInt(ed.dom.getStyle(container, 'width'))) {
							ed.dom.setStyle(container, 'width', parseInt(node.width));
							ed.execCommand('mceRepaint');
						}
					}, 1);
				}
			});
		}
	});


	// Adds the plugin class to the list of available TinyMCE plugins
	tinymce.PluginManager.add("ssbuttons", tinymce.plugins.SSButtons);
})();