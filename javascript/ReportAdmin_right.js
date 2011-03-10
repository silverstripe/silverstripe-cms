Behaviour.register({
	'body.ReportAdmin #Form_EditForm' : {
		initialise : function() {
      		this.openTab = null;
			this.prepareForm();
		},
		
		/**
		 * Processing called whenever a page is loaded in the right - including the initial one
		 */
		prepareForm : function() {
			ajaxActionsAtTop('Form_EditForm', 'form_actions', 'right');

			// Custom code for reports section - link the search button to ajax
			var updateReportButtonHolder = $('action_updatereport');
			if(updateReportButtonHolder) prepareAjaxActions(updateReportButtonHolder, 'Form_EditForm');
		},
		
		/**
		 * Request a page from the server via Ajax
		 */
		getPageFromServer : function(id) {
			if(id) {
				this.receivingID = id;

				// Treenode might not exist if that part of the tree is closed
				var treeNode = $('sitetree').getTreeNodeByIdx(id);
				
				if(treeNode) treeNode.addNodeClass('loading');
				
				statusMessage(ss.i18n._t('LOADING', 'loading...')); 

				var requestURL = 'admin/reports/show/' + id;
				new Ajax.Request(requestURL, {
					asynchronous : true,
					method : 'post', 
					postBody : 'ajax=1',
					onSuccess : this.successfullyReceivedPage.bind(this),
					onFailure : function(response) { 
						errorMessage('error loading page',response);
					}
				});
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

    		// Note: TinyMCE coupling
			tinymce_removeAll();

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
				} catch(er) {alert(er.message);}
			}
			
			_TAB_DIVS_ON_PAGE = [];

			try {
				var tabs = document.getElementsBySelector('#Form_EditForm ul.tabstrip');
			} catch(er) {/* alert('a: '+ er.message + '\n' + er.line);*/ }
			try {
				for(var i=0;i<tabs.length;i++) if(tabs[i].tagName) initTabstrip(tabs[i]);
			} catch(er) { /*alert('b: '+ er.message + '\n' + er.line); */}

			// if(this.prepareForm) this.prepareForm();
			Behaviour.apply($('Form_EditForm'));
			if(this.prepareForm) 
				this.prepareForm();
				
			this.resetElements();
				
			window.ontabschanged();
		}
	}
});