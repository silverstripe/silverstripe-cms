(function($) {
	
	/**
	 * @class All forms in the right content panel should have closeable jQuery UI style titles.
	 * @name ss.contentPanel.form
	 */
	$('#contentPanel form').concrete('ss', function($){
		return/** @lends ss.contentPanel.form */{
			onmatch: function() {
			  // Style as title bar
				this.find(':header:first').titlebar({
					closeButton:true
				});
				// The close button should close the east panel of the layout
				this.find(':header:first .ui-dialog-titlebar-close').bind('click', function(e) {
					$('body.CMSMain').concrete('ss').MainLayout().close('east');
				
					return false;
				});
			}
		};
	});
	
	/**
	 * @class Control the site tree filter.
	 * Toggles search form fields based on a dropdown selection,
	 * similar to "Smart Search" criteria in iTunes.
	 * @name ss.Form_SeachTreeForm
	 */
	$('#Form_SearchTreeForm').concrete('ss', function($) {
		return/** @lends ss.Form_SeachTreeForm */{
		
			/**
			 * @type DOMElement
			 */
			SelectEl: null,
		
			onmatch: function() {
				var self = this;
			
				// TODO Cant bind to onsubmit/onreset directly because of IE6
				this.bind('submit', function(e) {return self._submitForm(e);});
				this.bind('reset', function(e) {return self._resetForm(e);});

				// only the first field should be visible by default
				this.find('.field').not(':first').hide();

				// generate the field dropdown
				this.setSelectEl($('<select name="options" class="options"></select>')
					.appendTo(this.find('fieldset:first'))
					.bind('change', function(e) {self._addField(e);})
				);

				this._setOptions();
			
			},
		
			_setOptions: function() {
				var self = this;
			
				// reset existing elements
				self.SelectEl().find('option').remove();
			
				// add default option
				// TODO i18n
				jQuery('<option value="0">Add Criteria</option>').appendTo(self.SelectEl());
			
				// populate dropdown values from existing fields
				this.find('.field').each(function() {
					$('<option />').appendTo(self.SelectEl())
						.val(this.id)
						.text($(this).find('label').text());
				});
			},
		
			/**
			 * Filter tree based on selected criteria.
			 */
			_submitForm: function(e) {
				var self = this;
				var data = [];
			
				// convert from jQuery object literals to hash map
				$(this.serializeArray()).each(function(i, el) {
					data[el.name] = el.value;
				});
			
				// Set new URL
				$('#sitetree')[0].setCustomURL(this.attr('action') + '&action_getfilteredsubtree=1', data);

				// Disable checkbox tree controls that currently don't work with search.
				// @todo: Make them work together
				if ($('#sitetree')[0].isDraggable) $('#sitetree')[0].stopBeingDraggable();
				this.find('.checkboxAboveTree :checkbox').val(false).attr('disabled', true);
			
				// disable buttons to avoid multiple submission
				//this.find(':submit').attr('disabled', true);
			
				this.find(':submit[name=action_getfilteredsubtree]').addClass('loading');
			
				this._reloadSitetree();
			
				return false;
			},
		
			_resetForm: function(e) {
				this.find('.field').clearFields().not(':first').hide();
			
				// Reset URL to default
				$('#sitetree')[0].clearCustomURL();

				// Enable checkbox tree controls
				this.find('.checkboxAboveTree :checkbox').attr('disabled', 'false');

				// reset all options, some of the might be removed
				this._setOptions();
			
				this._reloadSitetree();
			
				return false;
			},
		
			_addField: function(e) {
				var $select = $(e.target);
				// show formfield matching the option
				this.find('#' + $select.val()).show();
			
				// remove option from dropdown, each field should just exist once
				this.find('option[value=' + $select.val() + ']').remove();
			
				// jump back to default entry
				$select.val(0);
			
				return false;
			},
		
			_reloadSitetree: function() {
				var self = this;
			
				$('#sitetree')[0].reload({
					onSuccess :  function(response) {
						self.find(':submit').attr('disabled', false).removeClass('loading');
						self.find('.checkboxAboveTree :checkbox').attr('disabled', 'true');
						statusMessage('Filtered tree','good');
					},
					onFailure : function(response) {
						self.find(':submit').attr('disabled', false).removeClass('loading');
						self.find('.checkboxAboveTree :checkbox').attr('disabled', 'true');
						errorMessage('Could not filter site tree<br />' + response.responseText);
					}
				});
			}
		};
	});
	
	/**
	 * @class Simple form with a page type dropdown
	 * which creates a new page through #Form_EditForm and adds a new tree node.
	 * @name ss.Form_AddPageOptionsForm
	 * @requires ss.i18n
	 * @requires ss.Form_EditForm
	 */
	$('#Form_AddPageOptionsForm').concrete(function($) {
	  return/** @lends ss.Form_AddPageOptionsForm */{
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
				jQuery(tree).bind('selectionchanged', function(e, data) {self.treeSelectionChanged(e, data);});
				
				this.find(':input[name=PageType]').bind('change', this.typeDropdownChanged);
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
			},

			treeSelectionChanged : function(e, data) {
			  var selectedNode = data.node;
			  
				if(selectedNode.hints && selectedNode.hints.defaultChild) {
					this.find(':input[name=PageType]').val(selectedNode.hints.defaultChild);
				}
				
				var parentID = this.Tree().getIdxOf(selectedNode);
				this.find(':input[name=ParentID]').val(parentID ? parentID : 0);
			},

			typeDropdownChanged : function() {
			  var tree = this.Tree();
			  
				// Don't do anything if we're already on an appropriate node
				var sel = tree.firstSelected();
				if(sel && sel.hints && sel.hints.allowedChildren) {
					var allowed = sel.hints.allowedChildren;
					for(i=0;i<allowed.length;i++) {
						if(allowed[i] == this.value) return;
					}
				}

				// Otherwise move to the default parent for that.
				if(siteTreeHints && siteTreeHints[this.value] ) {
					var newNode = tree.getTreeNodeByIdx(siteTreeHints[this.value].defaultParent);
					if(newNode) tree.changeCurrentTo(newNode);
				}
			}
		};
	});
	
	/**
	 * @class Simple form with a page type dropdown
	 * which creates a new page through #Form_EditForm and adds a new tree node.
	 * @name ss.Form_AddPageOptionsForm
	 * @requires ss.i18n
	 * @requires ss.reports_holder
	 */
	$('#Form_ReportForm').concrete(function($) {
	  return/** @lends ss.reports_holder */{
			onmatch: function() {
				var self = this;
				
				this.bind('submit', function(e) {
					return self._submit(e);
				});
				
				// integrate with sitetree selection changes
				jQuery('#sitetree').bind('selectionchanged', function(e, data) {
					self.find(':input[name=ID]').val(data.node.getIdx());
					self.trigger('submit');
				});
				
				// move submit button to the top
				this.find('#ReportClass').after(this.find('.Actions'));
				
				// links in results
				this.find('ul a').bind('click', function(e) {
					var $link = $(this);
					$link.addClass('loading');
					jQuery('#Form_EditForm').concrete('ss').loadForm(
						$(this).attr('href'),
						function(e) {
							$link.removeClass('loading');
						}
					);
					return false;
				});
			},
			
			_submit: function(e) {
				var self = this;
				
				// dont process if no report is selected
				var reportClass = this.find(':input[name=ReportClass]').val();
				if(!reportClass) return false;
				
				var button = this.find(':submit:first');
				button.addClass('loading');
				
				jQuery.ajax({
					url: this.attr('action'),
					data: this.serializeArray(),
					dataType: 'html',
					success: function(data, status) {
						// replace current form
						self.replaceWith(data);
					},
					complete: function(xmlhttp, status) {
						button.removeClass('loading');
					}
				});
				
				return false;
			}
		};
	});
})(jQuery);