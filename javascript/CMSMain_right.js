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
	'#Form_EditForm' : {	
		changeDetection_fieldsToIgnore : {
			'restricted-chars[Form_EditForm_URLSegment]' : true,
			'Sort' : true	
		}
	}
});