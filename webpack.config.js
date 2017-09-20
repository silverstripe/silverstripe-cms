const Path = require('path');
const webpackConfig = require('@silverstripe/webpack-config');
const {
  resolveJS,
  externalJS,
  moduleJS,
  pluginJS,
  moduleCSS,
  pluginCSS,
} = webpackConfig;

const ENV = process.env.NODE_ENV;
const PATHS = {
  MODULES: 'node_modules',
  FILES_PATH: '../',
  ROOT: Path.resolve(),
  SRC: Path.resolve('client/src'),
  DIST: Path.resolve('client/dist'),
  LEGACY_SRC: Path.resolve('client/src/legacy'),
};

const config = [
  {
    name: 'js',
    entry: {
      bundle: `${PATHS.SRC}/bundles/bundle.js`,
      // See https://github.com/webpack/webpack/issues/300#issuecomment-45313650
      SilverStripeNavigator: `${PATHS.LEGACY_SRC}/SilverStripeNavigator.js`,
      'TinyMCE_sslink-internal': `${PATHS.LEGACY_SRC}/TinyMCE_sslink-internal.js`,
      'TinyMCE_sslink-anchor': `${PATHS.LEGACY_SRC}/TinyMCE_sslink-anchor.js`,
    },
    output: {
      path: PATHS.DIST,
      filename: 'js/[name].js',
    },
    devtool: (ENV !== 'production') ? 'source-map' : '',
    resolve: resolveJS(ENV, PATHS),
    externals: externalJS(ENV, PATHS),
    module: moduleJS(ENV, PATHS),
    plugins: pluginJS(ENV, PATHS),
  },
  {
    name: 'css',
    entry: {
      bundle: `${PATHS.SRC}/styles/bundle.scss`,
      SilverStripeNavigator: `${PATHS.SRC}/styles/SilverStripeNavigator.scss`,
    },
    output: {
      path: PATHS.DIST,
      filename: 'styles/[name].css',
    },
    devtool: (ENV !== 'production') ? 'source-map' : '',
    module: moduleCSS(ENV, PATHS),
    plugins: pluginCSS(ENV, PATHS),
  },
];

// Use WEBPACK_CHILD=js or WEBPACK_CHILD=css env var to run a single config
module.exports = (process.env.WEBPACK_CHILD)
  ? config.find((entry) => entry.name === process.env.WEBPACK_CHILD)
  : module.exports = config;
