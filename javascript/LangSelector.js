var _TRANSLATING_LANG = null;

/**
 * 
 */
LangSelectorClass = Class.create();
LangSelectorClass.prototype = {

	initialize: function() {
		if(this.selectedIndex != 0) {
			_TRANSLATING_LANG = this.value;
		}
	},
	
	onchange: function(e, val) {
		if(this.value != _TRANSLATING_LANG) {
			_TRANSLATING_LANG = this.value;
			document.location = baseHref() + 'admin/?locale=' + this.value;
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
	}	
};
LangSelectorClass.applyTo('#LangSelector');

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