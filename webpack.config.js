const webpack = require('webpack');
const autoprefixer = require('autoprefixer');
const ExtractTextPlugin = require('extract-text-webpack-plugin');

const PATHS = {
  MODULES: './node_modules',
  CMS_JS_SRC: './client/src',
  CMS_JS_DIST: './client/dist/js',
  CMS_CSS_SRC: './client/src/styles',
  CMS_CSS_DIST: './client/dist/styles',
};

// Used for autoprefixing css properties (same as Bootstrap Aplha.2 defaults)
const SUPPORTED_BROWSERS = [
  'Chrome >= 35',
  'Firefox >= 31',
  'Edge >= 12',
  'Explorer >= 9',
  'iOS >= 8',
  'Safari >= 8',
  'Android 2.3',
  'Android >= 4',
  'Opera >= 12',
];

module.exports = {
  entry: {
    bundle: `${PATHS.CMS_JS_SRC}/bundles/bundle.js`,
    // See https://github.com/webpack/webpack/issues/300#issuecomment-45313650
    SilverStripeNavigator: [`${PATHS.CMS_JS_SRC}/legacy/SilverStripeNavigator.js`],
  },
  resolve: {
    modulesDirectories: [PATHS.CMS_JS_SRC, PATHS.MODULES],
  },
  output: {
    path: './client/dist',
    filename: 'js/[name].js',
  },
  externals: {
    i18n: 'i18n',
    jQuery: 'jQuery',
    'lib/Router': 'Router',
  },
  devtool: 'source-map',
  module: {
    loaders: [
      {
        test: /\.js$/,
        exclude: /(node_modules|thirdparty)/,
        loader: 'babel',
        query: {
          presets: ['es2015', 'react'],
          plugins: ['transform-object-assign'/* , 'transform-object-rest-spread' */],
          comments: false,
        },
      },
      {
        test: /\.scss$/,
        loader: ExtractTextPlugin.extract([
          'css?sourceMap&minimize',
          'postcss?sourceMap',
          'resolve-url',
          'sass?sourceMap',
        ], {
          publicPath: '../', // needed because bundle.css is in a subfolder
        }),
      },
      {
        test: /\.css$/,
        loader: ExtractTextPlugin.extract([
          'css?sourceMap&minimize',
          'postcss?sourceMap',
          'resolve-url',
        ], {
          publicPath: '../', // needed because bundle.css is in a subfolder
        }),
      },
      {
        test: /\.(png|gif|jpg|svg)$/,
        loader: 'file?name=images/[name].[ext]',
      },
      {
        test: /\.(woff|eot|ttf)$/,
        loader: 'file?name=fonts/[name].[ext]',
      },
    ],
  },
  postcss: [
    autoprefixer({ browsers: SUPPORTED_BROWSERS }),
  ],
  plugins: [
    new webpack.ProvidePlugin({
      $: 'jquery',
      jQuery: 'jquery',
      'ss.i18n': 'i18n',
    }),
    new webpack.optimize.UglifyJsPlugin({
      compress: {
        unused: false,
        warnings: false,
      },
    }),
    new ExtractTextPlugin('styles/bundle.css', { allChunks: true }),
  ],
};
