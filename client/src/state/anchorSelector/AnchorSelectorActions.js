import ACTION_TYPES from './AnchorSelectorActionTypes';

/**
 * Begin querying a page for anchors
 *
 * @param {Number} pageId - ID of page to query for
 * @returns {Object}
 */
export function beginUpdating(pageId) {
  return {
    type: ACTION_TYPES.ANCHORSELECTOR_UPDATING,
    payload: { pageId },
  };
}

/**
 * Finish updating a anchors for a page
 *
 * @param {Number} pageId - ID of page to query for
 * @param {Array} anchors - List of anchor strings
 * @returns {Object}
 */
export function updated(pageId, anchors) {
  return {
    type: ACTION_TYPES.ANCHORSELECTOR_UPDATED,
    payload: { pageId, anchors },
  };
}

/**
 * Mark a tree as failed
 *
 * @param {Number} pageId - ID of page that update failed
 * @returns {Object}
 */
export function updateFailed(pageId) {
  return {
    type: ACTION_TYPES.ANCHORSELECTOR_UPDATE_FAILED,
    payload: { pageId },
  };
}
