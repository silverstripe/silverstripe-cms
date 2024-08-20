/* global tinymce, ss */
import i18n from 'i18n';
import TinyMCEActionRegistrar from 'lib/TinyMCEActionRegistrar';
import React from 'react';
import { createRoot } from 'react-dom/client';
import { ApolloProvider } from '@apollo/client';
import { Provider } from 'react-redux';
import jQuery from 'jquery';
import ShortcodeSerialiser from 'lib/ShortcodeSerialiser';
import { createInsertLinkModal } from 'containers/InsertLinkModal/InsertLinkModal';
import { provideInjector } from 'lib/Injector';

const commandName = 'sslinkinternal';

const plugin = {
  init(editor) {
    // Add "Page on this site" to link menu for this editor
    TinyMCEActionRegistrar
    .addAction(
      'sslink',
      {
        text: i18n._t('CMS.LINKLABEL_PAGE', 'Page on this site'),
        onAction: (activeEditor) => activeEditor.execCommand(commandName),
        priority: 90,
      },
      editor.getParam('editorIdentifier'),
    )
    .addCommandWithUrlTest(commandName, /^\[sitetree_link.+]$/);

    // Add a command that corresponds with the above menu item
    editor.addCommand(commandName, () => {
      const field = jQuery(`#${editor.id}`).entwine('ss');

      field.openLinkInternalDialog();
    });
  },
};

const modalId = 'insert-link__dialog-wrapper--internal';
const sectionConfigKey = 'SilverStripe\\CMS\\Controllers\\CMSPageEditController';
const formName = 'editorInternalLink';
const InsertLinkInternalModal = provideInjector(createInsertLinkModal(sectionConfigKey, formName));

jQuery.entwine('ss', ($) => {
  $('textarea.htmleditor').entwine({
    openLinkInternalDialog() {
      let dialog = $(`#${modalId}`);

      if (!dialog.length) {
        dialog = $(`<div id="${modalId}" />`);
        $('body').append(dialog);
      }
      dialog.addClass('insert-link__dialog-wrapper');

      dialog.setElement(this);
      dialog.open();
    },
  });

  /**
   * Assumes that $('.insert-link__dialog-wrapper').entwine({}); is defined for shared functions
   */
  $(`#${modalId}`).entwine({
    ReactRoot: null,

    renderModal(isOpen) {
      const store = ss.store;
      const client = ss.apolloClient;
      const handleHide = () => this.close();
      const handleInsert = (...args) => this.handleInsert(...args);
      const attrs = this.getOriginalAttributes();
      const requireLinkText = this.getRequireLinkText();

      // create/update the react component
      let root = this.getReactRoot();
      if (!root) {
        root = createRoot(this[0]);
        this.setReactRoot(root);
      }
      root.render(
        <ApolloProvider client={client}>
          <Provider store={store}>
            <InsertLinkInternalModal
              isOpen={isOpen}
              onInsert={handleInsert}
              onClosed={handleHide}
              title={i18n._t('CMS.LINK_PAGE', 'Link to a page')}
              bodyClassName="modal__dialog"
              className="insert-link__dialog-wrapper--internal"
              fileAttributes={attrs}
              identifier="Admin.InsertLinkInternalModal"
              requireLinkText={requireLinkText}
            />
          </Provider>
        </ApolloProvider>
      );
    },

    /**
     * @param {Object} data - Posted data
     * @return {Object}
     */
    buildAttributes(data) {
      const shortcode = ShortcodeSerialiser.serialise({
        name: 'sitetree_link',
        properties: { id: data.PageID },
      }, true);

      // Add anchor
      const anchor = data.Anchor && data.Anchor.length ? `#${data.Anchor}` : '';
      const href = `${shortcode}${anchor}`;

      return {
        href,
        target: data.TargetBlank ? '_blank' : '',
        title: data.Description,
      };
    },

    getOriginalAttributes() {
      const editor = this.getElement().getEditor();

      // Find "a" node, issue https://github.com/silverstripe/silverstripe-cms/issues/2439
      let linkNode = editor.getSelectedNode();
      if (linkNode.nodeName !== 'A') {
        let count = 0;
        while (count < 10 && linkNode.parentNode && linkNode.nodeName !== 'A') {
          linkNode = linkNode.parentNode;
          count +=1;
        }
      }
      const node = $(linkNode);

      // Get href
      const hrefParts = (node.attr('href') || '').split('#');
      if (!hrefParts[0]) {
        return {};
      }

      // check if page is safe
      const shortcode = ShortcodeSerialiser.match('sitetree_link', false, hrefParts[0]);
      if (!shortcode) {
        return {};
      }

      return {
        PageID: shortcode.properties.id ? parseInt(shortcode.properties.id, 10) : 0,
        Anchor: hrefParts[1] || '',
        Description: node.attr('title'),
        TargetBlank: !!node.attr('target'),
      };
    },
  });
});

// Adds the plugin class to the list of available TinyMCE plugins
tinymce.PluginManager.add(commandName, (editor) => plugin.init(editor));

export default plugin;
