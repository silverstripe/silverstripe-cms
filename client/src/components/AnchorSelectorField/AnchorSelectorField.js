import i18n from 'i18n';
import React from 'react';
import fetch from 'isomorphic-fetch';
import { connect } from 'react-redux';
import { bindActionCreators } from 'redux';
import { formValueSelector } from 'redux-form';
import SilverStripeComponent from 'lib/SilverStripeComponent';
import * as anchorSelectorActions from 'state/anchorSelector/AnchorSelectorActions';
import anchorSelectorStates from 'state/anchorSelector/AnchorSelectorStates';
import fieldHolder from 'components/FieldHolder/FieldHolder';
import CreatableSelect from 'react-select/creatable';
import EmotionCssCacheProvider from 'containers/EmotionCssCacheProvider/EmotionCssCacheProvider';
import getFormState from 'lib/getFormState';
import classnames from 'classnames';
import PropTypes from 'prop-types';

const noop = () => null;

class AnchorSelectorField extends SilverStripeComponent {
  constructor(props) {
    super(props);

    this.handleChange = this.handleChange.bind(this);
    this.handleLoadingError = this.handleLoadingError.bind(this);
  }

  componentDidMount() {
    this.ensurePagesLoaded();
  }

  componentDidUpdate(prevProps) {
    if (this.props.pageId !== prevProps.pageId) {
      this.ensurePagesLoaded();
    }
  }

  /**
   * Lazy-triggers load of the dropdown based on pageId
   *
   * @param {Object} props - Props to check
   * @return {Promise} The promise object
   */
  ensurePagesLoaded(props = this.props) {
    // Only load if dirty and a valid ID
    if (
      props.loadingState === anchorSelectorStates.UPDATING
      || props.loadingState === anchorSelectorStates.SUCCESS
      || !props.pageId
    ) {
      return Promise.resolve();
    }

    // Get anchors that belong to the current field
    let fieldAnchors = [];
    if (props.loadingState === anchorSelectorStates.FIELD_ONLY) {
      fieldAnchors = this.props.anchors;
    }

    // Mark page updating
    props.actions.anchorSelector.beginUpdating(props.pageId);

    // Query endpoint for anchors for this page
    const fetchURL = props.data.endpoint.replace(/:id/, props.pageId);
    return fetch(fetchURL, { credentials: 'same-origin' })
      .then(response => response.json())
      .then((anchors) => {
        // Fold in field anchors and ensure array has only unique values
        const allAnchors = [...new Set([...anchors, ...fieldAnchors])];
        // Update anchors
        props.actions.anchorSelector.updated(props.pageId, allAnchors);
        return allAnchors;
      })
      .catch((error) => {
        props.actions.anchorSelector.updateFailed(props.pageId);
        this.handleLoadingError(error, props);
      });
  }

  /**
   * Get options
   *
   * @return {Array}
   */
  getDropdownOptions() {
    const options = this.props.anchors.map(value => ({ value }));
    // Ensure value is available in the list
    if (this.props.value && !this.props.anchors.find(value => value === this.props.value)) {
      options.unshift({ value: this.props.value });
    }
    return options;
  }

  /**
   * Handles changes to the selected anchor
   *
   * @param {String} value
   */
  handleChange(value) {
    if (typeof this.props.onChange === 'function') {
      this.props.onChange(value ? value.value : '');
    }
  }

  handleLoadingError(error, props = this.props) {
    if (props.onLoadingError === noop) {
      throw error;
    }

    // Custom error handling
    return props.onLoadingError({
      errors: [
        {
          value: error.message,
          type: 'error',
        },
      ],
    });
  }

  render() {
    const { extraClass, CreatableSelectComponent } = this.props;
    const className = classnames('anchorselectorfield', extraClass);
    const options = this.getDropdownOptions();
    const rawValue = this.props.value || '';
    const placeholder = i18n._t('CMS.ANCHOR_SELECT_OR_TYPE', 'Select or enter anchor');
    return (
      <EmotionCssCacheProvider>
        <CreatableSelectComponent
          isSearchable
          isClearable
          options={options}
          className={className}
          name={this.props.name}
          onChange={this.handleChange}
          value={{ value: rawValue }}
          noOptionsMessage={() => i18n._t('CMS.ANCHOR_NO_OPTIONS', 'No options')}
          placeholder={placeholder}
          getOptionLabel={({ value }) => value}
          classNamePrefix="anchorselectorfield"
        />
      </EmotionCssCacheProvider>
    );
  }
}

AnchorSelectorField.propTypes = {
  extraClass: PropTypes.string,
  id: PropTypes.string,
  name: PropTypes.string.isRequired,
  onChange: PropTypes.func,
  value: PropTypes.string,
  attributes: PropTypes.oneOfType([PropTypes.object, PropTypes.array]),
  pageId: PropTypes.number,
  anchors: PropTypes.array,
  loadingState: PropTypes.oneOf(Object
    .keys(anchorSelectorStates)
    .map((key) => anchorSelectorStates[key])),
  onLoadingError: PropTypes.func,
  data: PropTypes.shape({
    endpoint: PropTypes.string,
    targetFieldName: PropTypes.string,
  }),
};

AnchorSelectorField.defaultProps = {
  value: '',
  extraClass: '',
  onLoadingError: noop,
  attributes: {},
  CreatableSelectComponent: CreatableSelect
};

function mapStateToProps(state, ownProps) {
  // Get pageId From selector field
  const selector = formValueSelector(ownProps.formid, getFormState);
  const targetFieldName = (ownProps && ownProps.data && ownProps.data.targetFieldName) || 'PageID';
  const pageId = Number(selector(state, targetFieldName) || 0);

  // Load anchors from page
  let anchors = [];
  const page = pageId
    ? state.cms.anchorSelector.pages.find(next => next.id === pageId)
    : null;
  if (page
    && (
      page.loadingState === anchorSelectorStates.SUCCESS
      || page.loadingState === anchorSelectorStates.DIRTY
      || page.loadingState === anchorSelectorStates.FIELD_ONLY
    )
  ) {
    // eslint-disable-next-line prefer-destructuring
    anchors = page.anchors;
  }

  // Check status
  let loadingState = null;
  if (page) {
    // eslint-disable-next-line prefer-destructuring
    loadingState = page.loadingState;
  } else if (pageId) {
    // Triggers an update
    loadingState = anchorSelectorStates.DIRTY;
  } else {
    // No page = success
    loadingState = anchorSelectorStates.SUCCESS;
  }

  return { pageId, anchors, loadingState };
}

function mapDispatchToProps(dispatch) {
  return {
    actions: {
      anchorSelector: bindActionCreators(anchorSelectorActions, dispatch),
    },
  };
}

const ConnectedAnchorSelectorField
  = connect(mapStateToProps, mapDispatchToProps)(AnchorSelectorField);

export { AnchorSelectorField as Component, ConnectedAnchorSelectorField };

export default fieldHolder(ConnectedAnchorSelectorField);
