/* global jest, test, describe, beforeEach, it, expect, setTimeout */

import React from 'react';
import anchorSelectorStates from 'state/anchorSelector/AnchorSelectorStates';
import { render, screen } from '@testing-library/react';
import { Component as AnchorSelectorField } from '../AnchorSelectorField';

jest.mock('isomorphic-fetch', () =>
  () => Promise.resolve({
    json: () => ['anchor3', 'anchor4'],
  }));
jest.mock('i18n');

function makeProps(obj = {}) {
  return {
    id: 'Form_Test',
    name: 'Test',
    data: {
      endpoint: 'url-callback',
    },
    pageId: 4,
    anchors: ['anchor1', 'anchor2'],
    value: 'selectedanchor',
    loadingState: anchorSelectorStates.SUCCESS,
    CreatableSelectComponent: ({ options }) => (
      <div data-testid="test-creatable-select">
        {options.map(option => <div key={option.value} data-option={option.value}/>)}
      </div>
    ),
    ...obj,
  };
}

test('AnchorSelectorField componentDidMount() Loads dirty selectors', async () => {
  const beginUpdating = jest.fn();
  render(<AnchorSelectorField {...makeProps({
    loadingState: anchorSelectorStates.DIRTY,
    actions: {
      anchorSelector: {
        beginUpdating,
        updated: () => {},
        updateFailed: () => {},
      },
    },
  })}
  />);
  await screen.findByTestId('test-creatable-select');
  expect(beginUpdating).toBeCalledWith(4);
});

test('AnchorSelectorField Merges value with page anchors', async () => {
  const beginUpdating = jest.fn();
  render(<AnchorSelectorField {...makeProps({
    loadingState: anchorSelectorStates.DIRTY,
    actions: {
      anchorSelector: {
        beginUpdating,
        updated: () => {},
        updateFailed: () => {},
      },
    },
  })}
  />);
  const select = await screen.findByTestId('test-creatable-select');
  const options = select.querySelectorAll('[data-option]');
  expect(options).toHaveLength(3);
  expect(options[0].getAttribute('data-option')).toBe('selectedanchor');
  expect(options[1].getAttribute('data-option')).toBe('anchor1');
  expect(options[2].getAttribute('data-option')).toBe('anchor2');
});

test('AnchorSelectorField componentDidMount() Does not load success selectors', async () => {
  const beginUpdating = jest.fn();
  render(<AnchorSelectorField {...makeProps({
    loadingState: anchorSelectorStates.SUCCESS,
    actions: {
      anchorSelector: {
        beginUpdating,
        updated: () => {},
        updateFailed: () => {},
      },
    },
  })}
  />);
  await screen.findByTestId('test-creatable-select');
  expect(beginUpdating).not.toBeCalled();
});

test('AnchorSelectorField ensurePagesLoaded Triggers loading on dirty', async () => {
  const beginUpdating = jest.fn();
  const updated = jest.fn();
  const updateFailed = jest.fn();
  render(<AnchorSelectorField {...makeProps({
    loadingState: anchorSelectorStates.DIRTY,
    actions: {
      anchorSelector: {
        beginUpdating,
        updated,
        updateFailed,
      },
    },
  })}
  />);
  await screen.findByTestId('test-creatable-select');
  expect(beginUpdating).toBeCalledWith(4);
  expect(updated).toBeCalledWith(4, ['anchor3', 'anchor4']);
  expect(updateFailed).not.toBeCalled();
});

test('AnchorSelectorField ensurePagesLoaded Does not trigger updating', async () => {
  const beginUpdating = jest.fn();
  const updated = jest.fn();
  const updateFailed = jest.fn();
  render(<AnchorSelectorField {...makeProps({
    loadingState: anchorSelectorStates.UPDATING,
    actions: {
      anchorSelector: {
        beginUpdating,
        updated,
        updateFailed,
      },
    },
  })}
  />);
  await screen.findByTestId('test-creatable-select');
  expect(beginUpdating).not.toBeCalled();
  expect(updated).not.toBeCalled();
  expect(updateFailed).not.toBeCalled();
});
