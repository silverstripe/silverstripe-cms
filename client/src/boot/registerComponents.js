import Injector from 'lib/Injector';
import AnchorSelectorField from 'components/AnchorSelectorField/AnchorSelectorField';
import readOnePageQuery from 'state/history/readOnePageQuery';
import rollbackPageMutation from 'state/history/rollbackPageMutation';

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
      updater.component(
        'HistoryViewerToolbar.VersionedAdmin.HistoryViewer.SiteTree.HistoryViewerVersionDetail',
        // This was using `copyToStage` incorrectly which also provides from and to stage
        // arguments. The "rollback" mutation correctly handles relations and is a more consumable
        // API endpoint.
        rollbackPageMutation,
        'PageRevertMutation'
      );
    }
  );
};
