function action_send_right() {
	$('action_send_options').toggle();
}

/**
 * Hides the drop-down Send newsletter form if Cancel button is clicked
 */
Behaviour.register( {
	'#action_send_cancel': {
		onclick : function() {
			$('action_send_options').toggle();
			return false;
		}
	}
});

// Called when checkbox on Bounced tab of Mailing List is clicked
Behaviour.register( {
	'#BouncedListTable tr td.markingcheckbox input': {
		onclick : function(e) {
			new Ajax.Request(
				'admin/newsletter/memberblacklisttoggle/' + this.value,
				{
					method: 'post', 
					postBody: 'forceajax=1',
					onComplete: function(response){
						Ajax.Evaluator(response);
					}.bind(this),
					onFailure: ajaxErrorHandler
				}
			);
		}
	}
});

// Don't show unsaved changes confirm() for changes to Bounced tab checkboxes
Behaviour.register({
	'#Form_EditForm' : {
		changeDetection_fieldsToIgnore : {
			'BouncedList[]' : true
		}
	}
});

// Called when X link on Bounced tab of Mailing List is clicked (adapted from TableListField.js)
Behaviour.register( {
	'#BouncedListTable a.deletelink': {
		onclick : function(e) {
		var img = Event.element(e);
		var link = Event.findElement(e,"a");
		var row = Event.findElement(e,"tr");
		
		var confirmed = confirm("Are you sure you want to remove this recipient from your mailing list?");
		if(confirmed)
		{
			img.setAttribute("src",'cms/images/network-save.gif');
			new Ajax.Request(
				link.getAttribute("href"),
				{
					method: 'post', 
					postBody: 'forceajax=1',
					onComplete: function(response){
						Effect.Fade(row);
						Ajax.Evaluator(response);
					}.bind(this),
					onFailure: ajaxErrorHandler
				}
			);
		}
		Event.stop(e);
		}
	}
});

CMSForm.applyTo('#Form_MemberForm','rightbottom');

Behaviour.register({
	'#Form_EditForm' : {
		initialise : function() {
            this.openTab = null;
			this.prepareForm();
		},
		
		/**
		 * Processing called whenever a page is loaded in the right - including the initial one
		 */
		prepareForm : function() {
			ajaxActionsAtTop('Form_EditForm', 'form_actions_right', 'right');
		},
		
		/**
		 * Request a page from the server via Ajax
		 */
		getPageFromServer : function(id, type, otherid,openTabName) {
			
            this.openTab = openTabName;
            
			if(id && parseInt(id) == id) {
				this.receivingID = id;
	
				// Treenode might not exist if that part of the tree is closed
				var treeNode = $('sitetree').getTreeNodeByIdx(id);
				if(treeNode) treeNode.addNodeClass('loading');
				statusMessage("loading...");
                
                		var requestURL = 'admin/newsletter/show' + type + '/' + id;
                
                		if( otherid ) {
					requestURL = 'admin/newsletter/shownewsletter/' + otherid;
				}
				new Ajax.Request(baseHref() + requestURL, {
					asynchronous : true,
					method : 'post', 
					postBody : /*'ID=' +  id + 'type=' + type + */'ajax=1'+ (otherid?('&otherid='+otherid):''),
					onSuccess : this.successfullyReceivedPage.bind(this),
					onFailure : function(response) { 
						errorMessage('error loading page',response);
					}
				});
				// Hide the action buttons if 'Drafts' or 'Sent Items' is clicked on
				if ('drafts' == type || 'sent' == type)
				{
					Element.hide('form_actions');
					Element.hide('form_actions_right');
				}

			} else {
				throw("getPageFromServer: Bad page ID: " + id);
			}
		},
		
		successfullyReceivedPage : function(response) {
			this.loadNewPage(response.responseText);
			
			// Treenode might not exist if that part of the tree is closed
			var treeNode = $('sitetree').getTreeNodeByIdx(this.receivingID);
			if(treeNode) {
				$('sitetree').changeCurrentTo(treeNode);
				treeNode.removeNodeClass('loading');
			}
			statusMessage('');
      
      onload_init_tabstrip();
            
      if( this.openTab ) {
          openTab( this.openTab );
          this.openTab = null;    
      }
		},
		
		didntReceivePage : function(response) {
			errorMessage('error loading page', response); 
			$('sitetree').getTreeNodeByIdx(this.elements.ID.value).removeNodeClass('loading');
		},
				
		/**
		 * Load a new page into the right-hand form
		 */
		loadNewPage : function(formContent) {
			rightHTML = formContent;
			rightHTML = rightHTML.replace(/href *= *"#/g, 'href="' + window.location.href.replace(/#.*$/,'') + '#');

			// Prepare iframes for removal, otherwise we get loading bugs
			var i, allIframes = this.getElementsByTagName('iframe');
			if(allIframes) for(i=0;i<allIframes.length;i++) {
				allIframes[i].contentWindow.location.href = 'about:blank';
				allIframes[i].parentNode.removeChild(allIframes[i]);
			}
			
			this.innerHTML = rightHTML;
			
			allIframes = this.getElementsByTagName('iframe');
			if(allIframes) for(i=0;i<allIframes.length;i++) {
				try {
					allIframes[i].contentWindow.location.href = allIframes[i].src;
				} catch(er) {alert('Error in NewsletterAdmin_right.js #Form_EditForm::loadNewPage(): ' + er.message);}
			}
			
			_TAB_DIVS_ON_PAGE = [];

			try {
				var tabs = document.getElementsBySelector('#Form_EditForm ul.tabstrip');
			} catch(er) {/* alert('a: '+ er.message + '\n' + er.line);*/ }
			try {
				for(var i=0;i<tabs.length;i++) if(tabs[i].tagName) initTabstrip(tabs[i]);
			} catch(er) { /*alert('b: '+ er.message + '\n' + er.line); */}

			if((typeof tinyMCE != 'undefined') && tinyMCE.instances) {
				tinyMCE.instances = [];
				tinyMCE.isLoaded = false;
				tinyMCE.onLoad();
			}
			
			onload_init_tabstrip();

			// if(this.prepareForm) this.prepareForm();
			Behaviour.apply($('Form_EditForm'));
			if(this.prepareForm) 
				this.prepareForm();
				
			this.resetElements();
				
			window.ontabschanged();
			
		},
		
		/**
		 * Save the contens of the form, by submitting it and resetting is changed checker
		 * on success.
		 */
		save: function(ifChanged, callAfter) {
			
			_AJAX_LOADING = true;
			if(typeof tinyMCE != 'undefined') tinyMCE.triggerSave();
	
			var __callAfter = callAfter;
			var __form = this;
			
			if(__form.notify) __form.notify('BeforeSave', __form.elements.ID.value);
			
			var success = function(response) {
			
				Ajax.Evaluator(response);
				__form.resetElements();

				if(__callAfter) __callAfter();
				if(__form.notify) __form.notify('PageSaved', __form.elements.ID.value);
				_AJAX_LOADING = false;
			}
			
			var action = 'action_save';
			
			if(ifChanged) {
				var data = this.serializeChangedFields('ID') + '&ajax=1&' + action + '=1';
			} else {
				var data = this.serializeAllFields() + '&ajax=1&' + action + '=1';
			}
			
			// alert(this.action + '\n\n' + data);
			
			new Ajax.Request(this.action, {
				method : this.method,
				postBody: data,
				onSuccess : success,
				onFailure : function(response) {
					alert(response.responseText);
					errorMessage('Error saving content', response);
					_AJAX_LOADING = false;
				}
			});
		}
	}
});

Behaviour.register({
	'#action_send_options' : {
		/**
		 * Open the form
		 */
		open : function() {
			this.elements.NewsletterID.value = $('Form_EditForm').elements.ID.value;
			
			var allInputs = this.getElementsByTagName('input');
			this.submitButton = allInputs[allInputs.length-1];
			this.submitButton.onclick = this.send.bind(this);
			this.style.display = '';
		},
		close : function() {
			this.style.display = 'none';
		},
		toggle : function() {
			if(this.style.display == '') this.close();
			else this.open();
		},
		
		send_test: function() {
			// Show a "submitting..." box
			if(!this.sendingText) {
				this.sendingText = document.createElement('div');
				this.sendingText.innerHTML = 'Sending newsletter...';
				this.sendingText.className = 'sendingText';
				Element.setOpacity(this.sendingText, 0.9);
				this.appendChild(this.sendingText);
			}
			this.sendingText.style.display = '';
			
			// Save always because change detection doesn't work for IE on newly created drafts.
			// Use onclick instead of form.save() to make things work in IE.
			$('Form_EditForm_action_save').onclick();
			
			// Send the request
			ajaxSubmitForm(false, this.onCompleteTest.bind(this), this, '', 'sendnewsletter')
			
			return false;
		},
		
		/**
		 * Called by AJAX when 'Send newsletter' button is clicked
		 *
		 */
		send: function() {
			// If 'Send to the entire mailing list' option is chosen
			if( $('SendTypeList').checked) { 
				return this.send_to_list('List');
			// If 'Send to only people not previously sent to' option is chosen
			} else if ($('SendTypeUnsent').checked) {
				return this.send_to_list('Unsent');
			} else {
				return this.send_test();
			}
		},
		
		/**
		 * Submit the option form and carry out the action
		 *
		 * @param sendType string 'List' if sending to entire list and 'Unsent' if only sending to people not previously sent to
		 */
		send_to_list : function(sendType) {
			// Show a "submitting..." box
			/*if(!this.sendingText) {
				this.sendingText = document.createElement('div');
				this.sendingText.innerHTML = 'Sending newsletter...';
				this.sendingText.className = 'sendingText';
				Element.setOpacity(this.sendingText, 0.9);
				this.appendChild(this.sendingText);
			}
			this.sendingText.style.display = '';*/
			
			// Save always because change detection doesn't work for IE on newly created drafts.
			// Use onclick instead of form.save() to make things work in IE.
			$('Form_EditForm_action_save').onclick();
			
			if( $('SendProgressBar') )
				$('SendProgressBar').start();
			
			// Send the request
			Ajax.SubmitForm( $('Form_EditForm'), 'action_sendnewsletter', {
				extraData: '&SendType=' + sendType,
				onSuccess: this.incrementProcess.bind(this),
				onFailure: function(response) {
					statusMessage(response.responseText);
				}
			});
			/*var form = $('Form_EditForm');
			var data = form.serializeChangedFields('ID','type') + '&ajax=1&action_savenewsletter=1&SendType=List';
			
			new Ajax.Request(form.action, {
				method : form.method,
				postBody: data,
				onSuccess : this.incrementProcess.bind(this),
				onFailure : function(response) {
					errorMessage('Error sending to mailing list', response);
				}
			});*/
			
			return false;
		},
		
		incrementProcess: function( response ) {
			var processParts = response.responseText.match( /(\d+):(\d+)\/(\d+)/ );
			
			if( !processParts || parseInt( processParts[2] ) >= parseInt( processParts[3] ) ) {
				this.onComplete( response );
				return;
			}
			
			
			// update the progress bar
			$('SendProgressBar').setProgress( ( parseInt( processParts[2] ) / parseInt( processParts[3] ) ) * 100 ); 
			var estimate = $('SendProgressBar').estimateTime();
			$('SendProgressBar').setText( 'Sent ' + processParts[2] + ' of ' + processParts[3] + '. ' + estimate + ' remaining...' );
			
			// set the action to the batch process controller
			var updateRequest = baseHref() + 'processes/next/' + processParts[1] + '/10?ajax=1';
			
			var request = new Ajax.Request( updateRequest, {
				onSuccess: this.incrementProcess.bind(this),
				onFailure: function( response ) {
					errorMessage( response.responseText );
				}
			});
		},
		
		/**
		 * Process the action's Ajax response
		 */
		onComplete: function( response ) {
			// $('SendProgressBar').setProgress( 100 ); 
			// $('SendProgressBar').setText( 'Done!' );
			$('SendProgressBar').reset();
			// this.elements.Message.value = '';
			
 			// Reload the "Sent Status Report" tab after a Newsletter is sent
			var id = this.elements.NewsletterID.value;
			var request = new Ajax.Request(baseHref() + 'admin/newsletter/getsentstatusreport/'  + id + '?ajax=1', {
				onSuccess: function( response ) {
					$('Root_SentStatusReport').innerHTML = response.responseText;
				},
				onFailure: function(response) {
					statusMessage('Could not automatically refresh Sent Status Report tab', 'bad');
				}
			});
        this.close();
      if( response ) 
        Ajax.Evaluator(response);
		},
		
		onCompleteTest: function( response ) {
			// this.sendingText.innerHTML = '';
			if( this.sendingText.parentNode == this )
				this.removeChild( this.sendingText );
      this.close();
      if( response ) 
        Ajax.Evaluator(response);
		}
	}
});

Behaviour.register({
	/**
	 * When the iframe has loaded, apply the listeners
	 */
	'div#ImportFile' : {
		frameLoaded: function( doc ) {
			this.showTable = true;
			var fileField = doc.getElementsByTagName('input')[0];
			var idField = doc.getElementsByTagName('input')[1];
			
			idField.value = $('Form_EditForm_ID').value;

			fileField.onchange = this.selectedFile.bind(this);
		},
		loadTable: function( doc ) {
			this.innerHTML = doc.getElementsByTagName('body')[0].innerHTML;
		}
	}
});

// @TODO: Check if this code is used anymore now that NewsletterList was removed.
NewsletterList = Class.create();
NewsletterList.applyTo('table.NewsletterList');
NewsletterList.prototype = {
    initialize: function() {
        this.tableBody = this.getElementsByTagName('tbody')[0];
        this.deleteLinks = this.getElementsByTagName('a');
        
        for( var i = 0; i < this.deleteLinks.length; i++ ) {
            this.deleteLinks[i].onclick = this.deleteNewsletter.bindAsEventListener(this);
        }
    },
    
    deleteNewsletter: function( event ) {
        var link = event.target;
              
        if( event.srcElement )
            link = event.srcElement;
        
        var rowNode = link.parentNode.parentNode;
        
        new Ajax.Request( link.href, {
            onSuccess: this.removeRow,
            onFailure: function( response ) {
                alert('The newsletter could not be deleted');    
            }
        });
    },
    
    removeRow: function( response ) {
        this.tableBody.removeChild( $(response.responseText) );
    }
}

/**
 * Handle auto-saving.  Detects if changes have been made, and if so save everything on the page.
 * If confirmation is true it will ask for confirmation.
 */
function autoSave(confirmation, callAfter) {
	if(typeof tinyMCE != 'undefined') tinyMCE.triggerSave();

	var __forms = []
	if($('Form_EditForm')) __forms.push($('Form_EditForm'));
	if($('Form_SubForm')) __forms.push($('Form_SubForm'));
	if($('Form_MemberForm')) __forms.push($('Form_MemberForm'));

	var __somethingHasChanged = false;
	var __callAfter = callAfter;
	
	__forms.each(function(form) {
		if(form.isChanged && form.isChanged()) {
			__somethingHasChanged = true;
		}
	});
	
	if(__somethingHasChanged) {
		// Note: discard and cancel options are no longer used since switching to confirm dialog.
		// 	save is still used if confirmation = false
		var options = {
			save: function() {
				statusMessage('saving...', '', true);
				var i;
				for(i=0;i<__forms.length;i++) {
					if(__forms[i].isChanged && __forms[i].isChanged()) {
						if(i == 0) __forms[i].save(true, __callAfter);
						else __forms[i].save(true);
					}
				}
			},
			discard: function() {
				__forms.each(function(form) { form.resetElements(false); });
				if(__callAfter) __callAfter();
			},
			cancel: function() {
			}
		}
		
		if(confirmation ) {
			if(confirm("Are you sure you want to navigate away from this page?\n\nWARNING: Your changes have not been saved.\n\nPress OK to continue, or Cancel to stay on the current page."))
			{
				// OK was pressed, call function for what was clicked on
				if(__callAfter) __callAfter();
			} else {
				// Cancel was pressed, stay on the current page
				return false;
			}
		} else {
			options.save();
		}

	} else {
		if(__callAfter) __callAfter();
	}
}

function reloadRecipientsList() {
	$('Form_EditForm').getPageFromServer($('Form_EditForm_ID').value, 'recipients', 0, 'Root_Recipients');	
}

/*RecipientImportField = Class.create();
RecipientImportField.applyTo('iframe.RecipientImportField');
RecipientImportField.prototype = {
	initialize: function() {
		this.src = document.getElementsByTagName('base')[0].href + this.src;	
	}	
}*/

/**
 * We don't want hitting the enter key in the subject field
 * to submit the form.
 */
 Behaviour.register({
 	'#Form_EditForm_Subject' : {
 		onkeypress : function(event) {
 			event = (event) ? event : window.event;
 			var kc = event.keyCode ? event.keyCode : event.charCode;
 			if(kc == 13) {
 				return false;
 			}
 		}
 	}
 });
 
/**
 * Handle 'add one' link action. Adds a new draft to the site tree and loads it up to edit.
 */
function addNewDraft(parentID) {
	$('add_type').value = 'draft';
	$('addtype_options').onsubmit();
}
