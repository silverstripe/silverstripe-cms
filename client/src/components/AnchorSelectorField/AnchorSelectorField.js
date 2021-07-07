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
import PropTypes from 'prop-types';

const noop = () => null;

class AnchorSelectorField extends SilverStripeComponent {
  constructor(props) {
    super(props);
    this.state = {
      anchors: [],
    };

    this.handleChange = this.handleChange.bind(this);
    this.handleLoadingError = this.handleLoadingError.bind(this);
  }

  componentDidMount() {
    this.fetchAnchors();
  }

  componentDidUpdate(prevProps) {
    if (this.props.pageId !== prevProps.pageId) {
      this.fetchAnchors();
    }
  }

  /**
   * Load values for the dropdown based on pageId
   *
   * @param {Object} props - Props to check
   */
  fetchAnchors(props = this.props) {
    if (!props.pageId) {
      return;
    }
    const doFetch = async () => {
      const fetchURL = props.data.endpoint.replace(/:id/, props.pageId);
      const response = await fetch(fetchURL, { credentials: 'same-origin' });
      let anchors = [];
      if (response.ok) {
        anchors = await response.json();
      }
      return Promise.resolve(anchors);
    };
    doFetch()
      .then(anchors => {
        this.setState({ anchors });
      })
      .catch((error) => this.handleLoadingError(error, props));
  }

  /**
   * Get options
   *
   * @return {Array}
   */
  getDropdownOptions() {
    const options = this.state.anchors.map(value => ({ value }));
    // Ensure value is available in the list
    if (this.props.value && !this.state.anchors.find(value => value === this.props.value)) {
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
  extraClass: PropTypes.string,
  id: PropTypes.string,
  name: PropTypes.string.isRequired,
  onChange: PropTypes.func,
  value: PropTypes.string,
  attributes: PropTypes.oneOfType([PropTypes.object, PropTypes.array]),
  pageId: PropTypes.number,
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
};


function mapStateToProps(state, ownProps) {
  // Get pageId From selector field
  const selector = formValueSelector(ownProps.formid, getFormState);
  const targetFieldName = (ownProps && ownProps.data && ownProps.data.targetFieldName) || 'PageID';
  const pageId = Number(selector(state, targetFieldName) || 0);
  return { pageId };
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
