/* global tinymce, editorIdentifier, ss */
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
import { updatedCurrentField } from '../state/anchorSelector/AnchorSelectorActions';

const commandName = 'sslinkanchor';

// Link to external url
TinyMCEActionRegistrar
  .addAction(
    'sslink',
    {
      text: i18n._t('CMS.LINKLABEL_ANCHOR', 'Anchor on a page'),
      onAction: (activeEditor) => activeEditor.execCommand(commandName),
      priority: 60,
    },
    editorIdentifier,
  )
  .addCommandWithUrlTest(commandName, /^\[sitetree_link.+]#[^#\]]+$/);

const plugin = {
  init(editor) {
    editor.addCommand(commandName, () => {
      const field = jQuery(`#${editor.id}`).entwine('ss');
      // Get the anchors in the current field and save them as props for AnchorSelectorField
      const currentPageID = Number(jQuery('#Form_EditForm_ID').val() || 0);
      const validTargets = jQuery(editor.getBody())
        .find('[id],[name]')
        .toArray()
        .map((element) => element.id || element.name);
      ss.store.dispatch(updatedCurrentField(currentPageID, validTargets, editor.id));
      // Open the anchor link form
      field.openLinkAnchorDialog();
    });
  },
};

const modalId = 'insert-link__dialog-wrapper--anchor';
const sectionConfigKey = 'SilverStripe\\CMS\\Controllers\\CMSPageEditController';
const formName = 'editorAnchorLink';
const InsertLinkInternalModal = provideInjector(createInsertLinkModal(sectionConfigKey, formName));

jQuery.entwine('ss', ($) => {
  $('textarea.htmleditor').entwine({
    openLinkAnchorDialog() {
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
      const editor = this.getElement().getEditor();
      const selection = editor.getInstance().selection;
      const selectionContent = editor.getSelection();
      const tagName = selection.getNode().tagName;
      const requireLinkText = tagName !== 'A' && selectionContent.trim() === '';
      const currentPageID = Number($('#Form_EditForm_ID').val() || 0);

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
              title={i18n._t('CMS.LINK_ANCHOR', 'Link to an anchor on a page')}
              bodyClassName="modal__dialog"
              className="insert-link__dialog-wrapper--anchor"
              fileAttributes={attrs}
              identifier="Admin.InsertLinkAnchorModal"
              requireLinkText={requireLinkText}
              currentPageID={currentPageID}
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
