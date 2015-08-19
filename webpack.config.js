var path = require('path');
var webpack = require('webpack');
var ExtractTextPlugin = require('extract-text-webpack-plugin');

var isProduction = process.env.NODE_ENV === 'production'

var out = 'build/';

var conf = {
  entry: {
    'resources/ext.gather.special.collection/init': './resources/ext.gather.special.collection/init.js'
  },
  output: {
    path: path.join(__dirname, out),
    filename: '[name].js'
  },
  module: {
    loaders: [
      { test: /\.(gif|png|jpg)$/, loader: 'url?limit=25000' },
      { test: /\.less$/, loader: ExtractTextPlugin.extract('style', 'css!autoprefixer!less') },
      { test: /\.js$/, exclude: /node_modules/, loader: 'babel-loader' }
    ]
  },
  plugins: [
    new webpack.NoErrorsPlugin(),
    new ExtractTextPlugin('style.css', { allChunks: true }),
    new webpack.optimize.DedupePlugin()
  ]
};

if (!isProduction) {
  conf.devtool = "#inline-source-map";
}

module.exports = conf;
