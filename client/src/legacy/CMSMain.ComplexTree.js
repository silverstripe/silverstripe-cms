/**
 * File: CMSMain.ComplexTree.js
 *
 * CMS-specific Entwine handlers for the ComplexTreeView component.
 * Intercepts tree actions and uses PJAX navigation (loadPanel) instead of
 * full page navigation, integrating with the CMS's PJAX system for seamless navigation.
 */
import $ from 'jquery';
import i18n from 'i18n';

$.entwine('ss', function($) {
  /**
   * Entwine handler for complex tree view container.
   * Manages navigation actions within the modern React-based tree component.
   * NOTE: This extends the base definition from ComplexTreeViewEntwine.js
   */
  $('.complex-tree-view__container').entwine({
    /**
     * Handle edit action - navigate to page edit form using PJAX
     * Called when user clicks "View" in context menu or clicks on a tree item
     * NOTE: This is a read-only operation, so we don't refresh the tree
     *
     * @param {Event} e The event object
     * @param {number} nodeID The page ID to edit
     */
    oneditnode: function(e, nodeID) {
      const url = i18n.sprintf('/admin/pages/edit/show/%s', nodeID);
      $('.cms-container').entwine('.ss').loadPanel(url);
    },

    /**
     * Handle add child action - navigate to add page form using PJAX
     * Called when user clicks a "Add page under this page" context menu option
     *
     * @param {Event} e The event object
     * @param {number} parentID The parent page ID
     * @param {string} className The class name to create
     */
    onaddchildnode: function(e, parentID, className) {
      const url = $.path.addSearchParams('/admin/pages/add', {
        ParentID: parentID,
        RecordType: className
      });
      $('.cms-container').entwine('.ss').loadPanel(url);
    },

    /**
     * Handle show as list action - switch to list view for children using PJAX
     * Called when user clicks "Show children as list" context menu option
     *
     * @param {Event} e The event object
     * @param {number} parentID The parent page ID
     */
    onshowaslistnode: function(e, parentID) {
      localStorage.setItem('ss.pages-view-type', 'listview');
      const url = $.path.addSearchParams('/admin/pages', {
        ParentID: parentID
      });
      $('.cms-container').entwine('.ss').loadPanel(url);
    },

    /**
     * Handle duplicate action - POST to duplicate endpoint then navigate with PJAX
     * Called when user clicks "This page only" in duplicate context menu
     * Refreshes the parent node after navigation to show the new page
     *
     * @param {Event} e The event object
     * @param {number} nodeID The page ID to duplicate
     */
    onduplicatenode: function(e, nodeID) {
      const $container = this;
      const url = `/admin/pages/tree/duplicate/${nodeID}`;
      $.post(url, (response) => {
        if (response && response.success && response.newNodeID) {
          const editUrl = i18n.sprintf('/admin/pages/edit/show/%s', response.newNodeID);
          $('.cms-container').entwine('.ss').loadPanel(editUrl);
          // Wait for PJAX navigation to complete before refreshing tree
          // This prevents FOUT by keeping the tree visible during navigation
          setTimeout(() => {
            // Refresh the parent node to show the new duplicate
            const parentID = response.parentID || 0;
            $container.trigger('refreshparentnode', [parentID]);
          }, 300);
        }
      });
    },

    /**
     * Handle duplicate with children action - POST to endpoint then navigate with PJAX
     * Called when user clicks "This page and subpages" in duplicate context menu
     * Refreshes the parent node after navigation to show the new page
     *
     * @param {Event} e The event object
     * @param {number} nodeID The page ID to duplicate
     */
    onduplicatewithchildrennode: function(e, nodeID) {
      const $container = this;
      const url = `/admin/pages/tree/duplicateWithChildren/${nodeID}`;
      $.post(url, (response) => {
        if (response && response.success && response.newNodeID) {
          const editUrl = i18n.sprintf('/admin/pages/edit/show/%s', response.newNodeID);
          $('.cms-container').entwine('.ss').loadPanel(editUrl);
          // Wait for PJAX navigation to complete before refreshing tree
          // This prevents FOUT by keeping the tree visible during navigation
          setTimeout(() => {
            // Refresh the parent node to show the new duplicate
            const parentID = response.parentID || 0;
            $container.trigger('refreshparentnode', [parentID]);
          }, 300);
        }
      });
    },

    /**
     * Listen for state changes from CMS to reload tree when needed
     * Updates tree view when form is submitted or state changes
     */
    'from .cms-container': {
      onaftersubmitform: function(e, data) {
        // Refresh tree after form submission if tree is visible
        const $treeContainer = $('.complex-tree-view__container');
        if ($treeContainer.length > 0 && $treeContainer.is(':visible')) {
          // Extract the current record ID from the form
          const $editForm = $('.cms-edit-form');
          if ($editForm.length > 0) {
            const recordID = $editForm.find('input[name=ID]').val();
            if (recordID) {
              // Trigger custom refresh event on the tree container
              $treeContainer.trigger('refreshnodes', [recordID]);
            }
          }
        }
      },

      onafterstatechange: function(e, data) {
        // Called when PJAX navigation occurs
        // Tree may need to refresh based on the new panel content
        const $treeContainer = $('.complex-tree-view__container');
        if ($treeContainer.length > 0 && $treeContainer.is(':visible')) {
          // Update tree selection if a record is being edited
          const $editForm = $('.cms-edit-form');
          if ($editForm.length > 0) {
            const recordID = $editForm.find('input[name=ID]').val();
            if (recordID) {
              // Tree component will highlight the currently edited record
              $treeContainer.trigger('selectnode', [recordID]);
            }
          }
        }
      }
    }
  });
});
