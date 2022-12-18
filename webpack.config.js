const Path = require('path');
const { JavascriptWebpackConfig, CssWebpackConfig } = require('@silverstripe/webpack-config');
const CopyWebpackPlugin = require('copy-webpack-plugin');

const PATHS = {
  ROOT: Path.resolve(),
  SRC: Path.resolve('client/src'),
  DIST: Path.resolve('client/dist'),
  LEGACY_SRC: Path.resolve('client/src/legacy'),
};

const config = [
  // Main JS bundles
  new JavascriptWebpackConfig('js', PATHS, 'silverstripe/cms')
    .setEntry({
      bundle: `${PATHS.SRC}/bundles/bundle.js`,
      SilverStripeNavigator: `${PATHS.LEGACY_SRC}/SilverStripeNavigator.js`,
      'TinyMCE_sslink-internal': `${PATHS.LEGACY_SRC}/TinyMCE_sslink-internal.js`,
      'TinyMCE_sslink-anchor': `${PATHS.LEGACY_SRC}/TinyMCE_sslink-anchor.js`,
    })
    .mergeConfig({
      plugins: [
        new CopyWebpackPlugin({
          patterns: [
            {
              from: `${PATHS.SRC}/images`,
              to: `${PATHS.DIST}/images`
            },
          ]
        }),
      ],
    })
    .getConfig(),
  // sass to css
  new CssWebpackConfig('css', PATHS)
    .setEntry({
      bundle: `${PATHS.SRC}/styles/bundle.scss`,
      SilverStripeNavigator: `${PATHS.SRC}/styles/SilverStripeNavigator.scss`,
    })
    .getConfig(),
];

// Use WEBPACK_CHILD=js or WEBPACK_CHILD=css env var to run a single config
module.exports = (process.env.WEBPACK_CHILD)
  ? config.find((entry) => entry.name === process.env.WEBPACK_CHILD)
  : config;
