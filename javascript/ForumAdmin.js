var _GroupID;
Behaviour.register({
	'#Form_EditForm_Type input':{
		onclick:function(){
			var requiredlogin = $('Form_EditForm_RequiredLogin');
			var usersTab = $('Form_EditForm').getElementsByTagName('ul')[0].getElementsByTagName('li')[2];
			if(this.value == 'consultation'){
				if(requiredlogin.checked != 'checked')
					requiredlogin.checked = 'checked';
				usersTab.style.display = 'block';
				if(_GroupID)
					this.recoverGroupID();
				Element.disable(requiredlogin);
			}else{ // this.value == 'open'
				usersTab.style.display = 'none';
				this.treatGroupIDAs0();
				Element.enable(requiredlogin);
			}
		},

		treatGroupIDAs0:function(){
			var groupIDDiv = $('GroupID');
			var groupIDs = groupIDDiv.getElementsByTagName('option');
			for(var i=0; i<groupIDs.length; i++){
				if(groupIDs[i].selected == true){
					groupIDs[i].selected = false;
					break;
				}
			}
			_GroupID = groupIDs[i].value;
			groupIDs[0].selected = true;
		},
		
		recoverGroupID:function(){
			var groupIDDiv = $('GroupID');
			var groupIDs = groupIDDiv.getElementsByTagName('option');
			groupIDs[0].selected = false;
			for(var i=0; i<groupIDs.length; i++){
				if(groupIDs[i].value == _GroupID){
					groupIDs[i].selected = true;
					break;
				}
			}
			_GrouipID = null;
		}
	},
	
	'#Root_Users #GroupID select':{
		onchange:function() {
			var source = this.getElementsByTagName('option');
			
			for(var i=0; i<source.length; i++){
				if(source[i].selected == true){
					break;
				}
			}
			
			var action=getAjaxURL('getMembersByGroup', source[i].value, 'ajax=1');
			new Ajax.Updater(
				{success: 'MemberList'},
				action,
				{
					method: 'get',
					onFailure: function(response) {errorMessage("Error getting data", response);},
					onComplete: function() {Behaviour.apply($('MemberList'));}
				}
			);
			
			return false;
		}
	}
});

function getAjaxURL(action, param, getvars) {
	var base = document.getElementsByTagName('base')[0].href;
	var url = window.location.href.substr(base.length);
	if(url.match(/^([^?]+)(\?.*)/)){
		url=RegExp.$1;
	}
	if(!url.match(/^([^\/]+\/)$/)){
		url = url+"/";
	}
	
	if(getvars) getvars = "?" + getvars;
	else getvars = "";
	return base + url.replace(/^([^\/]+\/).*/, '$1' + action + '/' + param + getvars);
}

Element.disable = function(el){
	el.disabled = true;
}

Element.enable = function(el){
	el.disabled = false;
}

initialiseCMSIfAForumLoaded = function(){
	if($('Form_EditForm_Type')) {
		var types=($('Form_EditForm_Type').getElementsByTagName('input'));
		for(var i=0; i<types.length; i++){
	
			if(types[i].checked)
			{
				if(types[i].onclick) types[i].onclick();
			}
		}
	}
}

Behaviour.addLoader(function(){
	if($('Form_EditForm') && $('Form_EditForm').observeMethod) {
		$('Form_EditForm').observeMethod('PageLoaded', initialiseCMSIfAForumLoaded());
	}
	
});