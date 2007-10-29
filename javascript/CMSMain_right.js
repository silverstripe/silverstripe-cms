function action_publish_right() {
	$('Form_EditForm_action_publish').value = 'Publishing...';
	$('Form_EditForm_action_publish').className = 'action loading';
	var publish = true;
	$('Form_EditForm').save(false, null, 'save', publish);
}
function action_revert_right() {
	$('Form_EditForm_action_revert').value = 'Restoring...';
	$('Form_EditForm_action_revert').className = 'action loading';
	Ajax.SubmitForm('Form_EditForm', 'action_revert', {
		onSuccess : Ajax.Evaluator,
		onFailure : function(response) {
			errorMessage('Error reverting to live content', response);
		}
	});
}
/*function action_submit_right() {
	$('action_submit_options').configure('CanApprove', 'Awaiting editor approval');
	$('action_submit_options').toggle();
}*/
function action_reject_right() {
	$('action_submit_options').configure('CanReject', 'Content declined');
	$('action_submit_options').toggle();
}
function action_submit_right() {
	$('action_submit_options').configure('CanPublish', 'Awaiting final approval');
	$('action_submit_options').toggle();
}
function action_rollback_right() {
	var options = {
		OK: function() {
			var pageID = $('Form_EditForm').elements.ID.value;
		
			Ajax.SubmitForm('Form_EditForm', 'action_rollback', {
				onSuccess : function(response) {
					$('Form_EditForm').getPageFromServer(pageID);
					statusMessage(response.responseText,'good');
				},
				onFailure : function(response) {
					errorMessage('Error rolling back content', response);
				}
			});
		},
		Cancel:function() {
		}
	}
	
	if(confirm("Do you really want to copy the published content to the stage site?")) {
		options.OK();
	} else {
		return false;
	}
}

/**
 * Email containing the link to the archived version of the page
 */
function action_email_right() {
	window.open( 'mailto:?subject=' + $('Form_EditForm_ArchiveEmailSubject').value + '&body=' + $('Form_EditForm_ArchiveEmailMessage').value, 'archiveemail' );
}

function action_print_right() {
	var printURL = $('Form_EditForm').action.replace(/\?.*$/,'') + '/printable/' + $('Form_EditForm').elements.ID.value;
	if(printURL.substr(0,7) != 'http://') printURL = baseHref() + printURL;
	
	window.open(printURL, 'printable');
}



Submit_ActionPropertiesForm = Class.extend('ActionPropertiesForm');
Submit_ActionPropertiesForm.applyTo('#action_submit_options');
Submit_ActionPropertiesForm.prototype = {
	/**
	 * Define how this form is to be used
	 */
	configure : function(securityLevel, status) {
		this.securityLevel = securityLevel;
		this.elements.Status.value = status
		this.elements.Status.parentNode.getElementsByTagName('span')[0].innerHTML= status;			
	}
}

function suggestStageSiteLink() {
	var el = $('viewStageSite');
	el.flasher = setInterval(flashColor.bind(el), 300);
	setTimeout(stopFlashing.bind(el), 3000);
}
function flashColor() {
	if(!this.style.color) this.style.color = '';
	this.style.color =  (this.style.color == '') ? '#00FF00' : '';
}
function stopFlashing() {
	clearInterval(this.flasher);
}


Behaviour.register({
	'a.cmsEditlink' : {
		onclick : function() {
			if(this.href.match(/admin\/show\/([0-9]+)($|#|\?)/)) {
				$('Form_EditForm').getPageFromServer(RegExp.$1);
				return false;
			}
		}
	}
});

Behaviour.register({
	'select#Form_EditForm_ClassName' : {
		onchange: function() {
			alert('The page type will be updated after the page is saved');
		}
	}
});

Behaviour.register({
	'#Form_EditForm' : {	
		changeDetection_fieldsToIgnore : {
			'restricted-chars[Form_EditForm_URLSegment]' : true,
			'Sort' : true	
		}
	}	
});
