/* global jest, describe, beforeEach, it, expect, setTimeout */

jest.mock('isomorphic-fetch', () =>
  () => Promise.resolve({
    json: () => ['anchor3', 'anchor4'],
  })
);
jest.mock('i18n');

import React from 'react';
import ReactTestUtils from 'react-addons-test-utils';
import { Component as AnchorSelectorField } from '../AnchorSelectorField';
import anchorSelectorStates from 'state/anchorSelector/AnchorSelectorStates';

describe('AnchorSelectorField', () => {
  let props = null;
  let field = null;

  beforeEach(() => {
    props = {
      id: 'Form_Test',
      name: 'Test',
      data: {
        endpoint: 'url-callback',
      },
      pageId: 4,
      anchors: ['anchor1', 'anchor2'],
      value: 'selectedanchor',
      loadingState: anchorSelectorStates.SUCCESS,
      actions: {
        anchorSelector: {
          beginUpdating: jest.fn(),
          updated: jest.fn(),
          updateFailed: jest.fn(),
        },
      },
    };
  });

  describe('componentDidMount()', () => {
    it('Loads dirty selectors', () => {
      props.loadingState = anchorSelectorStates.DIRTY;
      field = ReactTestUtils.renderIntoDocument(
        <AnchorSelectorField {...props} />
      );
      expect(props.actions.anchorSelector.beginUpdating)
        .toHaveBeenCalledWith(4);
    });
  });

  describe('getDropdownOptions()', () => {
    it('Merges value with page anchors', () => {
      field = ReactTestUtils.renderIntoDocument(
        <AnchorSelectorField {...props} />
      );
      expect(field.getDropdownOptions()).toEqual([
        { value: 'selectedanchor' },
        { value: 'anchor1' },
        { value: 'anchor2' },
      ]);
    });
  });

  describe('ensurePagesLoaded', () => {
    it('Triggers loading on dirty', () => {
      props.loadingState = anchorSelectorStates.DIRTY;
      field = ReactTestUtils.renderIntoDocument(
        <AnchorSelectorField {...props} />
      );
      return field
        .ensurePagesLoaded()
        .then((result) => {
          expect(props.actions.anchorSelector.beginUpdating)
            .toHaveBeenCalledWith(4);
          expect(props.actions.anchorSelector.updated)
            .toHaveBeenCalledWith(4, ['anchor3', 'anchor4']);
          expect(props.actions.anchorSelector.updateFailed)
            .not
            .toHaveBeenCalled();
          expect(result).toEqual(['anchor3', 'anchor4']);
        });
    });

    it('Does not trigger updating', () => {
      props.loadingState = anchorSelectorStates.UPDATING;
      field = ReactTestUtils.renderIntoDocument(
        <AnchorSelectorField {...props} />
      );
      return field
        .ensurePagesLoaded()
        .then((result) => {
          expect(props.actions.anchorSelector.beginUpdating)
            .not
            .toHaveBeenCalled();
          expect(props.actions.anchorSelector.updated)
            .not
            .toHaveBeenCalled();
          expect(props.actions.anchorSelector.updateFailed)
            .not
            .toHaveBeenCalled();
          expect(result).toBe(undefined);
        });
    });
  });
});
