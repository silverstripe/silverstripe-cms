import { graphql } from 'react-apollo';
import gql from 'graphql-tag';

const mutation = gql`
mutation revertPageToVersion($id:ID!, $fromStage:VersionedStage!, $toStage:VersionedStage!, $fromVersion:Int!) {
  copySilverStripeSiteTreeToStage(Input: {
    ID: $id
    FromVersion: $fromVersion
    FromStage: $fromStage
    ToStage: $toStage
  }) {
    ID
  }
}

`;

const config = {
  props: ({ mutate, ownProps: { actions } }) => {
    const revertToVersion = (id, fromVersion, fromStage, toStage) => mutate({
      variables: {
        id,
        fromVersion,
        fromStage,
        toStage,
      },
    });

    return {
      actions: {
        ...actions,
        revertToVersion,
      },
    };
  },
  options: {
    // Refetch versions after mutation is completed
    refetchQueries: ['ReadHistoryViewerPage']
  }
};

export { mutation, config };

export default graphql(mutation, config);
