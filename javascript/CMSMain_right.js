

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
	'#Form_EditForm' : {	
		changeDetection_fieldsToIgnore : {
			'restricted-chars[Form_EditForm_URLSegment]' : true,
			'Sort' : true	
		}
	}
});