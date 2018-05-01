import Injector from 'lib/Injector';
import AnchorSelectorField from 'components/AnchorSelectorField/AnchorSelectorField';
import readOnePageQuery from 'state/history/readOnePageQuery';
import revertToPageVersionMutation from 'state/history/revertToPageVersionMutation';

export default () => {
  Injector.component.register('AnchorSelectorField', AnchorSelectorField);

  Injector.transform(
    'pages-history',
    (updater) => {
      // Add CMS page history GraphQL query to the HistoryViewer
      updater.component('HistoryViewer.pages-controller-cms-content', readOnePageQuery, 'PageHistoryViewer');
    }
  );

  Injector.transform(
    'pages-history-revert',
    (updater) => {
      // Add CMS page revert GraphQL mutation to the HistoryViewerToolbar
      updater.component('HistoryViewerToolbar', revertToPageVersionMutation, 'Page', 'PageRevertMutation');
    }
  );
};
