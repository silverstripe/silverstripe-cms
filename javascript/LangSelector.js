var _TRANSLATING_LANG = null;

/**
 * 
 */
LangSelector = Class.create();
LangSelector.prototype = {

	initialize: function() {
		if(this.selectedIndex != 0) {
			this.showlangtree();
			_TRANSLATING_LANG = this.value;
		}
	},
	
	onshow: function() {
		if(this.value) this.showlangtree();
	},
	
	onchange: function(e, val) {
		if(this.value != _TRANSLATING_LANG) {
			_TRANSLATING_LANG = this.value;
			this.showlangtree();
		}
	},

	selectValue: function(lang) {
		this.value = lang;
		if(this.value != lang) {
			var newLang = document.createElement('option');
		  	newLang.text = lang;
		  	newLang.value = lang;		  	
			try {
		    	this.add(newLang, null); // standards compliant
		    } catch(ex) {
		    	this.add(newLang); // IE only
		    }
		    this.value = lang;
		}
	},

	showlangtree: function() {
		if(this.value) {
			$('sitetree').innerHTML='&nbsp;<img src="cms/images/network-save.gif>&nbsp;loading...';
			new Ajax.Request('admin/switchlanguage/' + this.value, {
				method : 'post', 
				onSuccess: Ajax.Evaluator,
				onFailure : Ajax.Evaluator
			});
		}
	}	
};
LangSelector.applyTo('#LangSelector');

/**
 * 
 */
TranslatorCreator = Class.create();
TranslatorCreator.prototype = {

	onSelectionChanged: function(selectedNode) {
		if(_TRANSLATING_LANG && Element.hasClassName(selectedNode,'untranslated')) {
			$start = confirm('Would you like to start a translation for this page?');
			if($start) Element.removeClassName(selectedNode,'untranslated');
			return $start;
		}
	},

	initialize: function() {
		$('sitetree').observeMethod('SelectionChanged', this.onSelectionChanged.bind(this));
	}
	
}
TranslatorCreator.applyTo('#LangSelector_holder');