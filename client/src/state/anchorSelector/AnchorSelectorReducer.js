import deepFreeze from 'deep-freeze-strict';
import ACTION_TYPES from './AnchorSelectorActionTypes';
import anchorSelectorStates from './AnchorSelectorStates';

/**
 * Default state
 */
const initialState = deepFreeze({ pages: [] });

export default function anchorSelectorReducer(state = initialState, action = null) {
  /**
   * Update details for the page
   *
   * @param {String} loadingState - State flag for this page
   * @param {Array} anchors - Anchors for this page
   * @return {Object} Updated state
   */
  const updatePage = (loadingState, anchors) => {
    const id = action.payload.pageId;
    return deepFreeze({
      pages: [
        ...state.pages.filter(next => next.id !== id),
        {
          id,
          loadingState,
          anchors,
        },
      ].sort((left, right) => (left.id - right.id)),
    });
  };

  // Update page status
  switch (action.type) {
    case ACTION_TYPES.ANCHORSELECTOR_UPDATING: {
      return updatePage(anchorSelectorStates.UPDATING, []);
    }

    case ACTION_TYPES.ANCHORSELECTOR_UPDATED: {
      return updatePage(anchorSelectorStates.SUCCESS, action.payload.anchors);
    }

    case ACTION_TYPES.ANCHORSELECTOR_UPDATE_FAILED: {
      return updatePage(anchorSelectorStates.FAILED, []);
    }

    default:
      return state;
  }
}
