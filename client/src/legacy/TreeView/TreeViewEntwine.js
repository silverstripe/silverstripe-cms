/**
 * This file mounts the `TreeView` component in place of the legacy page tree.
 *
 * The `onremove` handler unmounts the React root, because jQuery clears the Entwine properties before `onunmatch`
 * runs. A full page load adds the element before `Injector` boots, so the first mount happens in `onmatch`.
 */

/* global window, localStorage */
import jQuery from 'jquery';
import React from 'react';
import { flushSync } from 'react-dom';
import { createRoot } from 'react-dom/client';
import Config from 'lib/Config';
import { loadComponent } from 'lib/Injector';
import { createCallbackProps, loadPanelAndReport } from './treeViewProps';

// Every mount shares this `reloadKey` value, so a tree that mounts again after a panel load keeps its loaded items.
let reloadKey = 0;

jQuery.entwine('ss', ($) => {
  const showLoginDialog = () => {
    // `.login-dialog` is in the page only while the log-in modal is open. The `.leftandmain__login-dialog` container
    // stays after the modal closes. Admin's client/src/legacy/LeftAndMain.js sets both classes.
    if (!$('.login-dialog').length) {
      $('.cms-container').showLoginDialog();
    }
  };

  const getCurrentId = () => $('.cms-edit-form :input[name=ID]').val();

  $('.js-injector-boot .cms-tree-view').entwine({
    ReactRoot: null,
    Component: null,
    TreeViewProps: null,

    onmatch() {
      this._super();
      this.mountTree();
    },

    onadd() {
      this._super();
      this.mountTree();
    },

    onremove() {
      const root = this.getReactRoot();
      if (root) {
        root.unmount();
        this.setReactRoot(null);
      }
      this._super();
    },

    // `refresh()` passes the ID of the current page to `TreeView`. A panel load changes the current page.
    'from .cms-container': {
      onafterstatechange() {
        this.refresh();
      },
    },

    // The legacy tree updated its nodes after each `aftersubmitform` event from a form in `.cms-container`.
    // This handler reloads the tree after the same event.
    'from .cms-container form': {
      onaftersubmitform() {
        reloadKey += 1;
        this.refresh();
      },
    },

    mountTree() {
      if (this.getReactRoot()) {
        return;
      }
      const isSidePanel = this.closest('.cms-content-tools').length > 0;
      // When the member prefers the list view, client/src/legacy/CMSMain.js loads that view in place of this element.
      // The side panel always shows the tree view.
      if (!isSidePanel && localStorage.getItem('ss.pages-view-type') === 'listview') {
        return;
      }
      const cmsContent = this.closest('.cms-content').attr('id');
      this.setComponent(loadComponent('TreeView', cmsContent ? { context: cmsContent } : {}));
      this.setReactRoot(createRoot(this[0]));
      // The functions in the props keep one identity for the life of the mount. A render with new functions renders
      // every item of the tree again.
      this.setTreeViewProps({
        treeId: this.attr('data-tree-id'),
        label: this.attr('data-label'),
        titleRowText: this.attr('data-title-row-text'),
        isSidePanel,
        ...createCallbackProps({
          endpointUrl: Config.getSection('SilverStripe\\CMS\\Controllers\\PageTreeController').url,
          getCurrentId,
          getQueryString: () => window.location.search,
          baseUrl: $('base').attr('href') || '',
          loadPanel: (url) => loadPanelAndReport($('.cms-container'), url),
          showListView: (url) => {
            // client/src/legacy/CMSMain.js reads `ss.pages-view-type` to load the list view in place of the tree.
            localStorage.setItem('ss.pages-view-type', 'listview');
            window.location.assign(url);
          },
          showLoginDialog,
        }),
      });
      this.refresh();
    },

    refresh() {
      const root = this.getReactRoot();
      if (!root) {
        return;
      }
      const TreeView = this.getComponent();

      // `flushSync` renders the items before the browser paints the new panel.
      flushSync(() => {
        root.render(<TreeView {...this.getTreeViewProps()} currentId={getCurrentId()} reloadKey={reloadKey} />);
      });
    },
  });
});
