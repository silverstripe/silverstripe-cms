/**
 * Loads, views and removes records in the main view.
 */
RightContent = Class.create();
RightContent.applyTo('#Form_EditForm');
RightContent.prototype = {
	
	welcomeMessage: "<h1>SilverStripe CMS</h1><p>Welcome to SilverStripe CMS! Please choose click on one of the items on the left pane.</p>",
	
	initialize : function() {
	},
	
	updateCMSContent: function(el, currentTab, link, customCallBack) {
		if(!customCallBack) customCallBack = function(){};
		
		if(el || link){
			var reqLink = (el && el.href) ? el.href : link;
			
			if(typeof(currentTab) != 'undefined')
				$('Form_EditForm').openTab = currentTab;
			
			statusMessage("loading...", null, true);
			
			new Ajax.Request(reqLink, {
				asynchronous : true,
				postBody : 'ajax=1',
				onSuccess: customCallBack.bind(this),
				onComplete : this.successfullyReceivedPage.bind(this),
				onFailure : function(response) { 
					errorMessage('Error loading page',response);
				}
			});
		}else{
			$('Form_EditForm').innerHTML = this.welcomeMessage;
		}
	},
	
	successfullyReceivedPage : function(response) {
		$('Form_EditForm').loadNewPage(response.responseText);
	    $('Form_EditForm').initialize();
	    onload_init_tabstrip();
	    // TODO
	    // try to reopen saved tab (before ajax request)
	    if($('Form_EditForm').openTab) {
	        openTab($('Form_EditForm').openTab);
	    } else {
    		$('Form_EditForm').openTab = this.getCurrentTab();
    		openTab($('Form_EditForm').openTab);
	  	}

	  	clearStatusMessage();
	},
	
	remove: function(e) {
		if(window.confirm('Are you sure you want to delete?')){
			var el = Event.element(e);
			Ajax.SubmitForm($('Form_EditForm'), el.name, {
				postBody : 'ajax=1',
				onSuccess: Ajax.Evaluator,
				onFailure: ajaxErrorHandler
			});
		}
		Event.stop(e);
		return false;
	},
	
	deleteEffect: function() {
		new Effect.Fade(this, {duration:2});
		window.setTimeout(function() {
			$('Form_EditForm').updateCMSContent();
			new Effect.Appear($('Form_EditForm'), {duration:3});
		}, 3000);
	},
	
	tabExists: function(tabName) {
		if($('Root')){
			var tags = $('Root').getElementsByTagName('li');
			for(var i=0; i<tags.length; i++){
				var current = tags[i].getElementsByTagName('a')[0];
				var match = current.id.match(/tab-(.+)$/);
				if(match[0] == tabName) {
					return true;
				}
			}
		}
		return false;
	},
	
	getCurrentTab: function() {
		var current;
		if($('Root')){
			var tags = $('Root').getElementsByTagName('li');
			for(var i=0; i<tags.length; i++){
				if(Element.hasClassName(tags[0], 'current')){
					current = tags[i].getElementsByTagName('a')[0];
				}else{
					current = tags[0].getElementsByTagName('a')[0]
				}
				current.id.match(/tab-(.+)$/);
				return RegExp.$1; 
			}
		}
	},
	
	setTitle: function(title) {
		var titleHolder = $$('div#right .title div');
		if(titleHolder[0]) {
			titleHolder[0].innerHTML = title; 
		}
	}
}

var action_delete_right = function(e) { 
 	$('Form_EditForm').remove(e); 
}  