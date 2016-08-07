function action_publish_right() {
	$('Form_EditForm_action_publish').value = ss.i18n._t('CMSMAIN.PUBLISHING');
	$('Form_EditForm_action_publish').className = 'action loading';
	var publish = true;
	$('Form_EditForm').save(false, null, 'save', publish);
}
function action_revert_right() {
	$('Form_EditForm_action_revert').value = ss.i18n._t('CMSMAIN.RESTORING');
	$('Form_EditForm_action_revert').className = 'action loading';
	Ajax.SubmitForm('Form_EditForm', 'action_revert', {
		onFailure : function(response) {
			errorMessage(ss.i18n._t('CMSMAIN.ERRORREVERTING'), response);
		}
	});
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
	
	if($('Form_EditForm').elements.Version && $('Form_EditForm').elements.Version.value) {
		var message = ss.i18n.sprintf(
			ss.i18n._t('CMSMAIN.RollbackConfirmation'),
			$('Form_EditForm').elements.Version.value
		);
	} else {
		var message = ss.i18n._t('CMSMAIN.CopyPublishedConfirmation');
	}
	
	if(confirm(message)) {
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
			alert(ss.i18n._t('CMSMAIN.PageTypeSaveAlert'));
		}
	},

	'#Form_EditForm' : {	
		changeDetection_fieldsToIgnore : {
			'restricted-chars[Form_EditForm_URLSegment]' : true,
			'Sort' : true,
			'LiveURLSegment' : true
		}
	},

	// ParentType / ParentID field combination
	'#Form_EditForm_ParentType' : {
		initialize : function() {
			var parentTypeRootEl = $('Form_EditForm_ParentType_root');
			var parentTypeSubpageEl = $('Form_EditForm_ParentType_subpage');
			if(parentTypeRootEl) {
				parentTypeRootEl.onclick = this.rootClick.bind(this);
			}
			if(parentTypeSubpageEl) {
				parentTypeSubpageEl.onclick = this.showHide;
			}
			this.showHide();
		},
		
		rootClick : function() {
			$('Form_EditForm_ParentID').setValue(0);
			this.showHide();
		},
		
		showHide : function() {
			var parentTypeRootEl = $('Form_EditForm_ParentType_root');
			if(parentTypeRootEl && parentTypeRootEl.checked) {
				Element.hide('ParentID');
			} else {
				Element.show('ParentID');
			}
		}
	}
});