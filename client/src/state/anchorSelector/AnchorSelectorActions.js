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
 * By default forces list of anchors for a page ID to be loaded from the server each time the page
 * is selected to select on if it's anchors from.
 *
 * @param {Number} pageId - ID of page to query for
 * @param {Array} anchors - List of anchor strings
 * @param {Boolean} cacheResult - false: Refresh anchor list, true: cache result
 * @returns {Object}
 */
export function updated(pageId, anchors, cacheResult = false) {
  return {
    type: ACTION_TYPES.ANCHORSELECTOR_UPDATED,
    payload: { pageId, anchors, cacheResult },
  };
}

/**
 * Get the anchors that belong in a specific field.
 * The server doesn't know about anchors that haven't been saved yet, so this allows
 * a WYSIWYG field to register its own anchors.
 *
 * @param {Number} pageId - ID of page to query for
 * @param {Array} anchors - List of anchor strings
 * @param {String} fieldID - ID of the field these anchors belong to
 * @returns {Object}
 */
export function updatedCurrentField(pageId, anchors, fieldID) {
  return {
    type: ACTION_TYPES.ANCHORSELECTOR_CURRENT_FIELD,
    payload: { pageId, anchors, fieldID },
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
