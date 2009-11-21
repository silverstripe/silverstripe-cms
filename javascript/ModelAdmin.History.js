(function($) {
	
	/**
	 * A simple ajax browser history implementation tailored towards
	 * navigating through search results and different forms loaded into
	 * the ModelAdmin right panels. The logic listens to search and form loading
	 * events, keeps track of the loaded URLs, and will display graphical back/forward
	 * buttons where appropriate. A search action will cause the history to be reset.
	 * 
	 * Note: The logic does not replay save operations or hook into any form actions.
	 * 
	 * Available Events:
	 * - historyAdd
	 * - historyStart
	 * - historyGoFoward
	 * - historyGoBack
	 * 
	 * @todo Switch tab state when re-displaying search forms
	 * @todo Reload search parameters into forms
	 * 
	 * @name ss.ModelAdmin
	 */
	$('.ModelAdmin').concrete('ss', function($){
		return/** @lends ss.ModelAdmin */ {
			
			History: [],
			
			Future: [],
			
			onmatch: function() {
				var self = this;
				
				this._super();
				
				// generate markup
				this.find('#right').prepend(
					'<div class="historyNav">' 
					+ '<a href="#" class="back">&lt; ' + ss.i18n._t('ModelAdmin.HISTORYBACK', 'back') + '</a>'
					+ '<a href="#" class="forward">' + ss.i18n._t('ModelAdmin.HISTORYFORWARD', 'forward') + ' &gt;</a>'
					+ '</div>'
				).find('.back,.forward').hide();
				
				this.find('.historyNav .back').live('click', function() {
					self.goBack();
					return false;
				});

				this.find('.historyNav .forward').live('click', function() {
					self.goForward();
					return false;
				});
			},
			
			redraw: function() {
				this.find('.historyNav .forward').toggle(Boolean(this.Future().length > 0));
				this.find('.historyNav .back').toggle(Boolean(this.History().length > 1));
			},
			
			startHistory: function(url, data) {
				this.trigger('historyStart', {url: url, data: data});
				
				this.setHistory([]);
				this.addHistory(url, data);
			},

			/**
			 * Add an item to the history, to be accessed by goBack and goForward
			 */
			addHistory: function(url, data) {
				this.trigger('historyAdd', {url: url, data: data});
				
				// Combine data into URL
				if(data) {
					if(url.indexOf('?') == -1) url += '?' + $.param(data);
					else url += '&' + $.param(data);
				}

				// Add to history 
				this.History().push(url);

				// Reset future
				this.setFuture([]);
				
				this.redraw();
			},

			goBack: function() {
				if(this.History() && this.History().length) {
					if(this.Future() == null) this.setFuture([]);

					var currentPage = this.History().pop();
					var previousPage = this.History()[this.History().length-1];

					this.Future().push(currentPage);
					
					this.trigger('historyGoBack', {url:previousPage});
					
					// load new location
					$('#Form_EditForm').concrete('ss').loadForm(previousPage);
					
					this.redraw();
				}
			},

			goForward: function() {
				if(this.Future() && this.Future().length) {
					if(this.Future() == null) this.setFuture([]);

					var nextPage = this.Future().pop();

					this.History().push(nextPage);
					
					this.trigger('historyGoForward', {url:nextPage});
					
					// load new location
					$('#Form_EditForm').concrete('ss').loadForm(nextPage);
					
					this.redraw();
				}
			}
		};
	});
	
	/**
	 * A search action will cause the history to be reset.
	 */
	$('#SearchForm_holder form').concrete('ss', function($) {
		return{
			onmatch: function() {
				var self = this;
				this.bind('beforeSubmit', function(e) {
					$('.ModelAdmin').concrete('ss').startHistory(
						self.attr('action'), 
						self.serializeArray()
					);
				});
			}
		};
	});
	
	/**
	 * We have to apply this to the result table buttons instead of the
	 * more generic form loading.
	 */
	$('form[name=Form_ResultsForm] tbody td a').concrete('ss', function($) {
		return{
			onmatch: function() {
				var self = this;
				this.bind('click', function(e) {
					$('.ModelAdmin').addHistory(self.attr('href'));
				});
			}
		};
	});
		
})(jQuery);