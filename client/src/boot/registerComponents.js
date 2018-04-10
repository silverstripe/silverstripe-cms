import Injector from 'lib/Injector';
import AnchorSelectorField from 'components/AnchorSelectorField/AnchorSelectorField';
import readOnePageQuery from 'state/history/readOnePageQuery';

export default () => {
  Injector.component.register('AnchorSelectorField', AnchorSelectorField);

  Injector.transform(
    'pages-history',
    (updater) => {
      // Add CMS page history GraphQL query HOC to the HistoryViewer
      updater.component(
        'HistoryViewer.pages-controller-cms-content',
        readOnePageQuery,
        'PageHistoryViewer'
      );
    }
  );
};
