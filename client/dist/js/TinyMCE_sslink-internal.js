!function(){"use strict";var t={745:function(t,e,n){var i=n(394);e.createRoot=i.createRoot,e.hydrateRoot=i.hydrateRoot},2939:function(t){t.exports=ApolloClient},6648:function(t){t.exports=Injector},3595:function(t){t.exports=InsertLinkModal},7363:function(t){t.exports=React},394:function(t){t.exports=ReactDom},1624:function(t){t.exports=ReactRedux},5265:function(t){t.exports=ShortcodeSerialiser},2196:function(t){t.exports=TinyMCEActionRegistrar},4754:function(t){t.exports=i18n},5311:function(t){t.exports=jQuery}},e={};function n(i){var r=e[i];if(void 0!==r)return r.exports;var o=e[i]={exports:{}};return t[i](o,o.exports,n),o.exports}!function(){var t=u(n(4754)),e=u(n(2196)),i=u(n(7363)),r=n(745),o=n(2939),a=n(1624),l=u(n(5311)),s=u(n(5265)),d=n(3595),c=n(6648);function u(t){return t&&t.__esModule?t:{default:t}}const p="sslinkinternal";let g;e.default.addAction("sslink",{text:t.default._t("CMS.LINKLABEL_PAGE","Page on this site"),onAction:t=>t.execCommand(p),priority:90},g).addCommandWithUrlTest(p,/^\[sitetree_link.+]$/);const f={init(t){t.addCommand(p,(()=>{(0,l.default)(`#${t.id}`).entwine("ss").openLinkInternalDialog()}))}},h="insert-link__dialog-wrapper--internal",m=(0,c.provideInjector)((0,d.createInsertLinkModal)("SilverStripe\\CMS\\Controllers\\CMSPageEditController","editorInternalLink"));l.default.entwine("ss",(e=>{e("textarea.htmleditor").entwine({openLinkInternalDialog(){let t=e(`#${h}`);t.length||(t=e(`<div id="${h}" />`),e("body").append(t)),t.addClass("insert-link__dialog-wrapper"),t.setElement(this),t.open()}}),e(`#${h}`).entwine({ReactRoot:null,renderModal(e){var n=this;const l=ss.store,s=ss.apolloClient,d=this.getOriginalAttributes(),c=this.getRequireLinkText();let u=this.getReactRoot();u||(u=(0,r.createRoot)(this[0]),this.setReactRoot(u)),u.render(i.default.createElement(o.ApolloProvider,{client:s},i.default.createElement(a.Provider,{store:l},i.default.createElement(m,{isOpen:e,onInsert:function(){return n.handleInsert(...arguments)},onClosed:()=>this.close(),title:t.default._t("CMS.LINK_PAGE","Link to a page"),bodyClassName:"modal__dialog",className:"insert-link__dialog-wrapper--internal",fileAttributes:d,identifier:"Admin.InsertLinkInternalModal",requireLinkText:c}))))},getRequireLinkText(){const t=this.getElement().getEditor(),e=t.getInstance().selection,n=t.getSelection();return"A"!==e.getNode().tagName&&""===n.trim()},buildAttributes(t){return{href:`${s.default.serialise({name:"sitetree_link",properties:{id:t.PageID}},!0)}${t.Anchor&&t.Anchor.length?`#${t.Anchor}`:""}`,target:t.TargetBlank?"_blank":"",title:t.Description}},getOriginalAttributes(){const t=this.getElement().getEditor(),n=e(t.getSelectedNode()),i=(n.attr("href")||"").split("#");if(!i[0])return{};const r=s.default.match("sitetree_link",!1,i[0]);return r?{PageID:r.properties.id?parseInt(r.properties.id,10):0,Anchor:i[1]||"",Description:n.attr("title"),TargetBlank:!!n.attr("target")}:{}}})})),tinymce.PluginManager.add(p,(t=>{g=t.getParam("editorIdentifier"),f.init(t)}))}()}();