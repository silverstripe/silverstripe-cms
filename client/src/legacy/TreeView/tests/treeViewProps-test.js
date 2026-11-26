/* global jest, test, expect, beforeEach */
import backend from 'lib/Backend';
import { createCallbackProps, loadPanelAndReport } from '../treeViewProps';

jest.mock('lib/Backend', () => ({
  __esModule: true,
  default: { get: jest.fn() },
}));

beforeEach(() => {
  backend.get.mockReset();
});

const baseItem = { id: 3, editUrl: 'admin/pages/edit/show/3?stage=Stage', listViewUrl: 'admin/pages?ParentID=3' };

function makeProps(overrides = {}) {
  const options = {
    endpointUrl: 'admin/pages/tree',
    getCurrentId: () => '12',
    getQueryString: () => '?locale=en_NZ',
    baseUrl: '/',
    loadPanel: jest.fn(() => true),
    showListView: jest.fn(),
    showLoginDialog: jest.fn(),
    ...overrides,
  };
  return { props: createCallbackProps(options), options };
}

// Create the error that `Backend` rejects with for a response outside 2xx.
function makeHttpError(status, headers = {}) {
  const error = new Error('Forbidden');
  error.response = { status, headers: new Headers(headers) };
  return error;
}

test('createCallbackProps fetches children and paths as XHR from the endpoint URL', async () => {
  backend.get.mockResolvedValue({ json: () => Promise.resolve({ nodes: [], path: [] }) });
  const { props } = makeProps();

  await props.fetchChildren(0);
  await props.fetchPath(14);

  expect(backend.get.mock.calls).toEqual([
    ['admin/pages/tree/children/0', { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }],
    ['admin/pages/tree/14', { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' }],
  ]);
});

test('createCallbackProps resolves with the value under each response key', async () => {
  const { props } = makeProps();
  backend.get.mockResolvedValueOnce({ json: () => Promise.resolve({ nodes: [{ id: 1 }] }) });
  await expect(props.fetchChildren(0)).resolves.toEqual([{ id: 1 }]);

  backend.get.mockResolvedValueOnce({ json: () => Promise.resolve({ nodes: [], limited: true }) });
  await expect(props.fetchChildren(3)).resolves.toEqual([]);

  backend.get.mockResolvedValueOnce({ json: () => Promise.resolve({ node: { id: 14 }, path: [2, 3] }) });
  await expect(props.fetchPath(14)).resolves.toEqual([2, 3]);
});

test('createCallbackProps rejects a failed fetch, and opens the log-in dialog only when the session expired', async () => {
  const { props, options } = makeProps();
  backend.get.mockRejectedValueOnce(new Error('Not Found'));
  await expect(props.fetchPath(999)).rejects.toThrow('Not Found');
  expect(options.showLoginDialog).not.toHaveBeenCalled();

  backend.get.mockRejectedValueOnce(makeHttpError(403));
  await expect(props.fetchChildren(0)).rejects.toThrow('Forbidden');
  expect(options.showLoginDialog).not.toHaveBeenCalled();

  backend.get.mockRejectedValueOnce(makeHttpError(403, { 'X-Reauthenticate': '1' }));
  await expect(props.fetchChildren(0)).rejects.toThrow('Forbidden');
  expect(options.showLoginDialog).toHaveBeenCalledTimes(1);
});

test("onOpen loads the page with the current query string in place of the item's", () => {
  const request = {};
  const { props, options } = makeProps({ loadPanel: jest.fn(() => request) });
  expect(props.onOpen(baseItem)).toBe(request);
  expect(options.loadPanel).toHaveBeenCalledWith('admin/pages/edit/show/3?locale=en_NZ');
  expect(makeProps({ loadPanel: () => false }).props.onOpen(baseItem)).toBe(false);

  const noQuery = makeProps({ getQueryString: () => '' });
  noQuery.props.onOpen(baseItem);
  expect(noQuery.options.loadPanel).toHaveBeenCalledWith('admin/pages/edit/show/3');
});

test('onOpen reads the open page when clicked and skips it, returning false', () => {
  let currentId = '12';
  const { props, options } = makeProps({ getCurrentId: () => currentId });
  currentId = '3';

  expect(props.onOpen(baseItem)).toBe(false);
  expect(props.onOpen({ id: 4, editUrl: null })).toBe(false);

  expect(options.loadPanel).not.toHaveBeenCalled();
});

// Create a jQuery-like container.
// The `loadPanel()` of this container starts a state change or not, like the legacy `loadPanel()`.
function makePanelContainer({ hasStateChangeStarted = true, request = null } = {}) {
  const handlers = new Set();
  return {
    handlers,
    on: jest.fn((name, handler) => handlers.add(handler)),
    off: jest.fn((name, handler) => handlers.delete(handler)),
    loadPanel: jest.fn(() => {
      if (hasStateChangeStarted) {
        handlers.forEach((handler) => handler());
      }
    }),
    getStateChangeXHR: () => request,
  };
}

test('loadPanelAndReport returns the panel request once a state change starts, true without one, else false', () => {
  const request = { then: () => {} };
  const started = makePanelContainer({ request });
  expect(loadPanelAndReport(started, 'admin/pages/edit/show/3')).toBe(request);
  expect(started.loadPanel).toHaveBeenCalledWith('admin/pages/edit/show/3');
  expect(started.handlers.size).toBe(0);

  expect(loadPanelAndReport(makePanelContainer(), 'admin/pages/edit/show/3')).toBe(true);

  const cancelled = makePanelContainer({ hasStateChangeStarted: false, request });
  expect(loadPanelAndReport(cancelled, 'admin/pages/edit/show/3')).toBe(false);
  expect(cancelled.handlers.size).toBe(0);
});

test('onShowAsList opens the list view URL joined to the base', () => {
  const { props, options } = makeProps();
  props.onShowAsList(baseItem);
  expect(options.showListView).toHaveBeenCalledWith('/admin/pages?ParentID=3');
});
