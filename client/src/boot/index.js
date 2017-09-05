/* global window */

import registerReducers from 'boot/registerReducers';
import registerComponents from 'boot/registerComponents';

window.document.addEventListener('DOMContentLoaded', () => {
  registerComponents();
  registerReducers();
});
