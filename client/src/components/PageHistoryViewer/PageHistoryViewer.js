import { compose } from 'redux';
import readOnePageQuery from 'state/history/readOnePageQuery';

/**
 * Transformer function which binds the readOnePageQuery GraphQL query to the HistoryViewer
 * component.
 */
const withPagesHistoryViewer = (HistoryViewer) => compose(
  readOnePageQuery
)(HistoryViewer);

export default withPagesHistoryViewer;
