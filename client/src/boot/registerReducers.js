import Injector from 'lib/Injector';
import { combineReducers } from 'redux';
import anchorSelectorReducer from 'state/anchorSelector/AnchorSelectorReducer';

export default () => {
  Injector.reducer.register('cms', combineReducers({
    anchorSelector: anchorSelectorReducer,
  }));
};
