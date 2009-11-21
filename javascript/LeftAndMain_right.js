/**
 * Handle auto-saving.  Detects if changes have been made, and if so save everything on the page.
 * If confirmation is true it will ask for confirmation.
 */
function autoSave(confirmation, callAfter) {
	// Note: TinyMCE coupling
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
				statusMessage(ss.i18n._t('CMSMAIN.SAVING'), '', true);
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
			if(confirm(ss.i18n._t('LeftAndMain.CONFIRMUNSAVED'))) 
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