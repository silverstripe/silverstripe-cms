module.exports = require('@silverstripe/webpack-config/.eslintrc');

// @todo Remove once upgrading webpack-config to 0.4.0
module.exports.settings['import/resolver'].node.moduleDirectory = [
  '.',
  'client/src',
  '../admin/client/src',
  '../admin/node_modules',
  'vendor/silverstripe/admin/client/src',
  'vendor/silverstripe/admin/node_modules',
  'node_modules'
];
