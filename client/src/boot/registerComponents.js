import Injector from 'lib/Injector';
import AnchorSelectorField from 'components/AnchorSelectorField/AnchorSelectorField';
import withPagesHistoryViewer from 'components/PageHistoryViewer/PageHistoryViewer';

export default () => {
  Injector.component.register('AnchorSelectorField', AnchorSelectorField);

  Injector.transform(
    'pages-history',
    (updater) => {
      // Add CMS page history to the HistoryViewer
      updater.component(
        'HistoryViewer.pages-controller-cms-content',
        withPagesHistoryViewer,
        'PageHistoryViewer'
      );
    }
  );
};
