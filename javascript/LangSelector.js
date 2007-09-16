var _TRANSLATING_LANG = null;
LangSelector = Class.create();
LangSelector.prototype = {

	initialize: function() {
		this.selector = $('LangSelector');
		//this.selector.addEventListener("click", this.a, null);

		if(this.selector) this.selector.holder = this;
		if(this.selector.selectedIndex != 0) {
			this.showlangtree();
			_TRANSLATING_LANG = this.selector.value;
		}
	},
	
	destroy: function() {
		if(this.selector) this.selector.holder = null;
		this.selector = null;
	},
	
	onshow: function() {
		if(this.selector.value) this.showlangtree();
	},
	
	onchange: function() {
		if (this.selector.value != _TRANSLATING_LANG) {
			if (this.selector.selectedIndex != 0) _TRANSLATING_LANG = this.selector.value;
			else _TRANSLATING_LANG = null;
			this.showlangtree();
		}
	},

	selectValue: function(lang) {
		this.selector.value = lang;
		if (this.selector.value != lang) {
			var newLang = document.createElement('option');
		  	newLang.text = lang;
		  	newLang.value = lang;		  	
			try {
		    	this.selector.add(newLang, null); // standards compliant
		    } catch(ex) {
		    	this.selector.add(newLang); // IE only
		    }
		    this.selector.value = lang;
		}
	},

	showlangtree: function() {
		$('sitetree').innerHTML='&nbsp;<img src="cms/images/network-save.gif>&nbsp;loading...';
		if(this.selector.value) {
			new Ajax.Request('admin/switchlanguage/' + this.selector.value, {
				method : 'post', 
				onSuccess: Ajax.Evaluator,
				onFailure : Ajax.Evaluator
			});
		}
	}	
};

LangSelector.applyTo('#LangSelector');

TranslatorCreator = Class.create();
TranslatorCreator.prototype = {

	onSelectionChanged: function(selectedNode) {
		if (_TRANSLATING_LANG && Element.hasClassName(selectedNode,'untranslated')) {
			$start = confirm('Would you like to start a translation for this page?');
			if ($start) Element.removeClassName(selectedNode,'untranslated');
			return $start;
		}
	},

	initialize: function() {
		$('sitetree').observeMethod('SelectionChanged', this.onSelectionChanged.bind(this));
	}
	
}

TranslatorCreator.applyTo('#LangSelector_holder');