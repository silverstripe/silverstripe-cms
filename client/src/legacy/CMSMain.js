import $ from 'jquery';
import { joinUrlPaths } from 'lib/urls';

/**
 * Behaviour for the CMS Content Toolbar.
 * Applies to tools on top-level views i.e. '/admin/pages' and '/admin/assets' and
 * their corresponding tools in the SiteTree panel.
 * An example is 'bulk actions' on the Pages view.
 */
$.entwine('ss', function ($) {

  const VIEW_TYPE_TREE = 'treeview';
  const VIEW_TYPE_LIST = 'listview';

	// Faux three column layout
	$('.cms-content-header-info').entwine({
		'from .cms-panel': {
			// Keep the header info's width synced with the TreeView panel's width.
			ontoggle: function (e) {
				var $treeViewPanel = this.closest('.cms-content').find(e.target);

				if ($treeViewPanel.length === 0) {
					return;
				}

				this.parent()[$treeViewPanel.hasClass('collapsed') ? 'addClass' : 'removeClass']('collapsed');
			}
		}
	});

  /**
   * Override super's `onadd` to modify url base the previously select view
   * type.
   *
   * See $('.cms .cms-panel-link') in LeftAndMain.js
   */
  $('.cms-panel-deferred.cms-content-view').entwine({
    onadd: function() {
      if(this.data('no-ajax')) {
        return;
      }
      var viewType = localStorage.getItem('ss.pages-view-type') || VIEW_TYPE_TREE;
      if(this.closest('.cms-content-tools').length > 0) {
        // Always use treeview when in page edit mode
        viewType = VIEW_TYPE_TREE;
      }
      const url = this.data(`url-${viewType}`);

      let clearFiltered = localStorage.getItem('ss.pages-view-filtered');
      if (typeof clearFiltered === 'string' && clearFiltered.toLowerCase() === 'false') {
        // localStorage save `false` as a string
        clearFiltered = false;
      }

      localStorage.setItem('ss.pages-view-filtered', false);

      this.data('deferredNoCache', (clearFiltered || viewType === VIEW_TYPE_LIST));
      this.data('url', url + location.search);
      this._super();
    }
  });

  $('.js-injector-boot .search-holder--cms').entwine({
    search(data) {
      localStorage.setItem('ss.pages-view-filtered', true)

      this._super(data);
    }
  });

  // Customise tree / list view pjax tab loading
  // See $('.cms .cms-panel-link') in LeftAndMain.js
  $('.cms .page-view-link').entwine({
    onclick: function(e){
      e.preventDefault();

      const viewType = $(this).data('view');
      const $contentView = this.closest('.cms-content-view');
      const url = $contentView.data(`url-${viewType}`);
      const isContentViewInSidebar = $contentView.closest('.cms-content-tools').length !== 0;

      localStorage.setItem('ss.pages-view-type', viewType);
      if(isContentViewInSidebar && viewType === VIEW_TYPE_LIST) {
        const baseUrl = $('base').attr('href') || '';  // Edge17 and IE11 need absolute path
        window.location.assign(joinUrlPaths(baseUrl, $contentView.data('url-listviewroot')));

        return;
      }

      $contentView.data('url', url + location.search);
      $contentView.redraw();
    }
  });

  $('.cms .cms-clear-filter').entwine({
    onclick: function(e) {
      e.preventDefault();
      window.location = $(this).prop('href');
    }
  });


	$('.cms-content-toolbar').entwine({

		onmatch: function () {
			var self = this;

			this._super();

			// Initialise the buttons
			$.each(this.find('.cms-actions-buttons-row .tool-button'), function () {
				var $button = $(this),
					toolId = $button.data('toolid'),
					isActive = $button.hasClass('active');

				// We don't care about tools that don't have a related 'action'.
				if (toolId !== void 0) {
					// Set the tool to its closed state.
					$button.data('active', false).removeClass('active');
					$('#' + toolId).hide();

					self.bindActionButtonEvents($button);
				}
			});
		},

		onunmatch: function () {
			var self = this;

			this._super();

			$.each(this.find('.cms-actions-buttons-row .tool-button'), function () {
				var $button = $(this);
				self.unbindActionButtonEvents($button);
			});
		},

		/**
		 * @func bindActionButtonEvents
		 * @param {object} $button
		 * @desc Add event handlers in the '.cmsContentToolbar' namespace.
		 */
		bindActionButtonEvents: function ($button) {
			var self = this;

			$button.on('click.cmsContentToolbar', function (e) {
				self.showHideTool($button);
			});
		},

		/**
		 * @func unbindActionButtonEvents
		 * @param {object} $button
		 * @desc Remove all event handlers in the '.cmsContentToolbar' namespace.
		 */
		unbindActionButtonEvents: function ($button) {
			$button.off('.cmsContentToolbar');
		},

		/**
		 * @func showTool
		 * @param {object} $button
		 * @desc Show a tool in the tools row. Hides all other tools.
		 */
		showHideTool: function ($button) {
			var isActive = $button.data('active'),
				toolId = $button.data('toolid'),
				$action = $('#' + toolId);

			// Hide all tools except the one passed as a param,
			// which gets handled separately.
			$.each(this.find('.cms-actions-buttons-row .tool-button'), function () {
				var $currentButton = $(this),
					$currentAction = $('#' + $currentButton.data('toolid'));

				if ($currentButton.data('toolid') !== toolId) {
					$currentAction.hide();
					$currentButton.data('active', false);
				}
			});

			$button[isActive ? 'removeClass' : 'addClass']('active');
			$action[isActive ? 'hide' : 'show']();
			$button.data('active', !isActive);
		}
	});
});
