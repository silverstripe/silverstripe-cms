/* global tinymce, ss */
import i18n from 'i18n';
import TinyMCEActionRegistrar from 'lib/TinyMCEActionRegistrar';
import React from 'react';
import ReactDOM from 'react-dom';
import { ApolloProvider } from 'react-apollo';
import jQuery from 'jquery';
import ShortcodeSerialiser from 'lib/ShortcodeSerialiser';
import { createInsertLinkModal } from 'containers/InsertLinkModal/InsertLinkModal';
import { provideInjector } from 'lib/Injector';

const commandName = 'sslinkinternal';

// Link to external url
TinyMCEActionRegistrar
  .addAction('sslink', {
    text: i18n._t('CMS.LINKLABEL_PAGE', 'Page on this site'),
    onclick: (editor) => editor.execCommand(commandName),
    priority: 53,
  })
  .addCommandWithUrlTest(commandName, /^\[sitetree_link.+]$/);

const plugin = {
  init(editor) {
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
    renderModal(show) {
      const store = ss.store;
      const client = ss.apolloClient;
      const handleHide = () => this.close();
      const handleInsert = (...args) => this.handleInsert(...args);
      const attrs = this.getOriginalAttributes();
      const requireLinkText = this.getRequireLinkText();

      // create/update the react component
      ReactDOM.render(
        <ApolloProvider store={store} client={client}>
          <InsertLinkInternalModal
            show={show}
            onInsert={handleInsert}
            onHide={handleHide}
            title={i18n._t('CMS.LINK_PAGE', 'Link to a page')}
            bodyClassName="modal__dialog"
            className="insert-link__dialog-wrapper--internal"
            fileAttributes={attrs}
            identifier="Admin.InsertLinkInternalModal"
            requireLinkText={requireLinkText}
          />
        </ApolloProvider>,
        this[0]
      );
    },

    /**
     * Determine whether to show the link text field
     *
     * @return {Boolean}
     */
    getRequireLinkText() {
      const selection = this.getElement().getEditor().getInstance().selection;
      const selectionContent = selection.getContent() || '';
      const tagName = selection.getNode().tagName;
      const requireLinkText = tagName !== 'A' && selectionContent.trim() === '';

      return requireLinkText;
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
      const node = $(editor.getSelectedNode());

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
