const webpack = require('webpack');
const Config = require('./webpack.config');

if (Array.isArray(Config)) {
  for (var i = 0; i < Config.length; i++) {
    Config[i].devtool = 'source-map';
  }
}

module.exports = Config;
