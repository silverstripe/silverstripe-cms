import { graphql } from '@apollo/client/react/hoc';
import gql from 'graphql-tag';

// GraphQL query for retrieving the version history of a specific page. The
// results of the query must be set to the "versions" prop on the component
// that this HOC is applied to (see PageHistoryViewer.js) for binding
// implementation.
const query = gql`
query ReadHistoryViewerPage ($page_id: ID!, $limit: Int!, $offset: Int!) {
  readOnePage(
    versioning: {
      mode: ALL_VERSIONS
    },
    filter: {
      id: { eq: $page_id }
    }
  ) {
    id
    versions (limit: $limit, offset: $offset, sort: {
      version: DESC
    }) {
      pageInfo {
        totalCount
      }
      nodes {
        version
        absoluteLink
        author {
          firstName
          surname
        }
        publisher {
          firstName
          surname
        }
        deleted
        draft
        published
        liveVersion
        latestDraftVersion
        lastEdited
      }
    }
  }
}
`;

const config = {
  options({ recordId, limit, page }) {
    return {
      variables: {
        limit,
        offset: ((page || 1) - 1) * limit,
        page_id: recordId,
      },
      // Never read from the cache. Saved pages should stale the query, and these mutations
      // happen outside the scope of apollo. This view is loaded asynchronously anyway,
      // so caching doesn't make any sense until we're full React/GraphQL.
      fetchPolicy: 'network-only',
    };
  },
  props({
    data: {
      error,
      refetch,
      readOnePage,
      loading: networkLoading,
    },
    ownProps: {
      actions = {
        versions: {}
      },
      limit,
      recordId,
    },
  }) {
    const versions = readOnePage || null;

    const errors = error && error.graphQLErrors &&
      error.graphQLErrors.map((graphQLError) => graphQLError.message);

    return {
      loading: networkLoading || !versions,
      versions,
      graphQLErrors: errors,
      actions: {
        ...actions,
        versions: {
          ...versions,
          goToPage(page) {
            refetch({
              offset: ((page || 1) - 1) * limit,
              limit,
              page_id: recordId,
            });
          }
        },
      },
    };
  },
};

export { query, config };

export default graphql(query, config);
