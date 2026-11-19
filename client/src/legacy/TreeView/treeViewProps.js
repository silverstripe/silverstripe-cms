/**
 * This file holds the requests and callbacks that TreeViewEntwine.js passes to `TreeView` as props.
 * `SilverStripe\CMS\Controllers\PageTreeController` serves the requests.
 */

import backend from 'lib/Backend';
import { joinUrlPaths } from 'lib/urls';

function fetchJson(url, onReauthenticate) {
  // With `X-Requested-With`, an expired session responds 403 with `X-Reauthenticate`, like a jQuery request of the CMS.
  // Without `X-Requested-With`, the response is a redirect to the log-in page.
  return backend.get(url, { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' })
    .then((response) => response.json())
    .catch((error) => {
      // `Backend` rejects with the response in `error.response`.
      if (error.response && error.response.headers.get('X-Reauthenticate')) {
        onReauthenticate();
      }
      throw error;
    });
}

// Load a panel through `container.loadPanel()` and report whether a state change started. The return value is `false`
// when no state change started, for example after the member cancels the unsaved-changes prompt. Otherwise the return
// value is the panel request, or `true` when the state change has no request.
export function loadPanelAndReport(container, url) {
  let hasStateChangeStarted = false;
  const handleBeforeStateChange = () => {
    hasStateChangeStarted = true;
  };
  container.on('beforestatechange', handleBeforeStateChange);
  try {
    container.loadPanel(url);
  } finally {
    container.off('beforestatechange', handleBeforeStateChange);
  }
  if (!hasStateChangeStarted) {
    return false;
  }
  return container.getStateChangeXHR() || true;
}

// Build the callback props of `TreeView`. `endpointUrl` is the URL of `PageTreeController`.
export function createCallbackProps({
  endpointUrl,
  getCurrentId,
  getQueryString,
  baseUrl,
  loadPanel,
  showListView,
  showLoginDialog,
}) {
  const fetchJsonKey = (key, ...urlParts) => fetchJson(joinUrlPaths(endpointUrl, ...urlParts), showLoginDialog)
    .then((body) => body[key]);

  // Open the item like a legacy tree click: drop the query string of `item.editUrl` and add the current query string.
  // Return the result of `loadPanel()`, or `false` when nothing loads.
  const openItem = (item) => {
    if (!item.editUrl || String(item.id) === String(getCurrentId())) {
      return false;
    }
    const url = item.editUrl.split('?')[0];
    const query = getQueryString().replace(/^\?/, '');
    return loadPanel(query ? `${url}?${query}` : url);
  };
  const showAsList = (item) => {
    showListView(joinUrlPaths(baseUrl, item.listViewUrl));
  };

  return {
    fetchChildren: (parentId) => fetchJsonKey('nodes', 'children', String(parentId)),
    fetchPath: (id) => fetchJsonKey('path', String(id)),
    onOpen: openItem,
    onShowAsList: showAsList,
  };
}
