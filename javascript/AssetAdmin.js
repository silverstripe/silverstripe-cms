/**
 * Configuration for the left hand tree
 */
if(typeof SiteTreeHandlers == 'undefined') SiteTreeHandlers = {};
SiteTreeHandlers.parentChanged_url = 'admin/assets/ajaxupdateparent';
SiteTreeHandlers.orderChanged_url = 'admin/assets/ajaxupdatesort';
SiteTreeHandlers.loadPage_url = 'admin/assets/getitem';
SiteTreeHandlers.loadTree_url = 'admin/assets/getsubtree';
SiteTreeHandlers.showRecord_url = 'admin/assets/show/';
SiteTreeHandlers.controller_url = 'admin/assets';

var _HANDLER_FORMS = {
	addpage : 'Form_AddForm',
	deletepage : 'Form_DeleteItemsForm',
	sortitems : 'sortitems_options'
};

(function($) {
	$('.AssetAdmin').concrete('ss', function($) {
		return {
			onmatch: function() {
				
				this._super();
			}
		};
	});
	
	/**
	 * Delete selected folders through "batch actions" tab.
	 */
	$('#Form_BatchActionsForm').concrete('ss').register(
		'admin/assets/batchactions/delete', 
		function(ids) {
			var confirmed = confirm(
				ss.i18n.sprintf(
					ss.i18n._t(
						'AssetAdmin.BATCHACTIONSDELETECONFIRM',
						"Do you really want to delete %d folders?"
					),
					ids.length
				)
			);
			return (confirmed) ? ids : false;
		}
	);
	
	/**
	 * @class Simple form with a page type dropdown
	 * which creates a new page through #Form_EditForm and adds a new tree node.
	 * @name ss.Form_AddForm
	 * @requires ss.i18n
	 * @requires ss.Form_EditForm
	 */
	$('#Form_AddForm').concrete(function($) {
	  return/** @lends ss.Form_AddForm */{
			/**
			 * @type DOMElement
			 */
			Tree: null,
			
			/**
			 * @type Array Internal counter to create unique page identifiers prior to ajax saving
			 */
			_NewPages: [],
			
			onmatch: function() {
				var self = this;
				
				this.bind('submit', function(e) {
				  return self._submit(e);
				});
				
				Observable.applyTo(this[0]);
				
				var tree = jQuery('#sitetree')[0];
				this.setTree(tree);
			},
			
			_submit: function(e) {
				var newPages = this._NewPages();
				var tree = this.Tree();
				var parentID = (tree.firstSelected()) ? tree.getIdxOf(tree.firstSelected()) : 0;

				// TODO: Remove 'new-' code http://open.silverstripe.com/ticket/875
				if(parentID && parentID.substr(0,3) == 'new') {
					alert(ss.i18n._t('CMSMAIN.WARNINGSAVEPAGESBEFOREADDING'));
				}
				
				if(tree.firstSelected() && jQuery(tree.firstSelected()).hasClass("nochildren")) {
					alert(ss.i18n._t('CMSMAIN.CANTADDCHILDREN') );
				} 
				
				// Optionally initalize the new pages tracker
				if(!newPages[parentID] ) newPages[parentID] = 1;

				// default to first button
				var button = jQuery(this).find(':submit:first');
				button.addClass('loading');
				
				// collect data and submit the form
				var data = jQuery(this).serializeArray();
				data.push({name:'Suffix',value:newPages[parentID]++});
				data.push({name:button.attr('name'),value:button.val()});
				jQuery('#Form_EditForm').concrete('ss').loadForm(
					jQuery(this).attr('action'),
					function() {
						button.removeClass('loading');
					},
					{type: 'POST', data: data}
				);
				
				this.set_NewPages(newPages);

				return false;
			}
		};
	});
	
	$('#Form_SyncForm').concrete('ss', function($) {
		return {
			onmatch: function() {
				this.bind('submit', this._onsubmit);			
				this._super();
			},
			_onsubmit: function(e) {
				var button = jQuery(this).find(':submit:first');
				button.addClass('loading');
				$.get(
					jQuery(this).attr('action'),
					function() {
						button.removeClass('loading');
					}
				);
				
				return false;
			}
		};
	});
}(jQuery));


Behaviour.register({
	'#Form_EditForm_Files': {
		removeFile : function(fileID) {
			var record;
			if(record = $('record-' + fileID)) {
				record.parentNode.removeChild(record);
			} 
		}
	},	
	
	'#Form_EditForm_Files a.deletelink' : {
		onclick : function(event) {
			// Send request
			new Ajax.Request(this.href + (this.href.indexOf("?") == -1 ? "?" : "&") + "ajax=1", {
				method : 'get',
				onSuccess : Ajax.Evaluator,
				onFailure : function(response) {errorMessage('Server Error', response);}
			});
			Event.stop(event);
			return false;
		}
	},
	
	
	'#Form_EditForm' : {
		changeDetection_fieldsToIgnore : {
			'Files[]' : true
		}
	}
});

/**
 * We don't want hitting the enter key in the name field
 * to submit the form.
 */
 Behaviour.register({
 	'#Form_EditForm_Name' : {
 		onkeypress : function(event) {
 			event = (event) ? event : window.event;
 			var kc = event.keyCode ? event.keyCode : event.charCode;
 			if(kc == 13) {
 				return false;
 			}
 		}
 	}
 });
