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
import { Creatable } from 'react-select';
import getFormState from 'lib/getFormState';
import classnames from 'classnames';

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

  componentWillReceiveProps(nextProps) {
    // Reload if pageId changes
    if (this.props.pageId !== nextProps.pageId) {
      this.ensurePagesLoaded(nextProps);
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
    if (props.loadingState !== anchorSelectorStates.DIRTY || !props.pageId) {
      return Promise.resolve();
    }

    // Mark page updating
    props.actions.anchorSelector.beginUpdating(props.pageId);

    // Query endpoint for anchors for this page
    const fetchURL = props.data.endpoint.replace(/:id/, props.pageId);
    return fetch(fetchURL, { credentials: 'same-origin' })
      .then(response => response.json())
      .then((anchors) => {
        // Update anchors
        props.actions.anchorSelector.updated(props.pageId, anchors);
        return anchors;
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
    const inputProps = {
      id: this.props.id,
    };
    const className = classnames('anchorselectorfield', this.props.extraClass);
    const options = this.getDropdownOptions();
    const value = this.props.value || '';
    const placeholder = i18n._t('CMS.ANCHOR_SELECT_OR_TYPE', 'Select or enter anchor');
    return (
      <Creatable
        searchable
        options={options}
        className={className}
        name={this.props.name}
        inputProps={inputProps}
        onChange={this.handleChange}
        onBlurResetsInput
        value={value}
        placeholder={placeholder}
        labelKey="value"
      />
    );
  }
}

AnchorSelectorField.propTypes = {
  extraClass: React.PropTypes.string,
  id: React.PropTypes.string,
  name: React.PropTypes.string.isRequired,
  onChange: React.PropTypes.func,
  value: React.PropTypes.string,
  attributes: React.PropTypes.oneOfType([React.PropTypes.object, React.PropTypes.array]),
  pageId: React.PropTypes.number,
  anchors: React.PropTypes.array,
  loadingState: React.PropTypes.oneOf(
    Object
      .keys(anchorSelectorStates)
      .map((key) => anchorSelectorStates[key])
  ),
  onLoadingError: React.PropTypes.func,
  data: React.PropTypes.shape({
    endpoint: React.PropTypes.string,
    targetFieldName: React.PropTypes.string,
  }),
};

AnchorSelectorField.defaultProps = {
  value: '',
  extraClass: '',
  onLoadingError: noop,
  attributes: {},
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
  if (page && page.loadingState === anchorSelectorStates.SUCCESS) {
    anchors = page.anchors;
  }

  // Check status
  let loadingState = null;
  if (page) {
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
