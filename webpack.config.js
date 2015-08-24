var path = require( 'path' ),
	webpack = require( 'webpack' ),
	ExtractTextPlugin = require( 'extract-text-webpack-plugin' ),
	UpdateResourceLoaderConfigPlugin = require( 'update-resourceloader-config-plugin' ),
	isProduction = process.env.NODE_ENV === 'production',
	out = 'build/',
	conf = null;

conf = {
	entry: {
		'resources/ext.gather.special.collection/init': './resources/ext.gather.special.collection/init.js',
		'resources/ext.gather.special.usercollections/init': './resources/ext.gather.special.usercollections/init.js'
	},
	output: {
		path: path.join( __dirname, out ),
		filename: '[name].js'
	},
	module: {
		loaders: [ {
			test: /\.(gif|png|jpg)$/,
			loader: 'url?limit=25000'
		}, {
			test: /\.less$/,
			loader: ExtractTextPlugin.extract( 'style', 'css!autoprefixer!less' )
		}, {
			test: /\.js$/,
			exclude: /node_modules/,
			loader: 'babel-loader'
		} , {
			test: /\.(mustache|hogan)$/,
			loader: 'mustache?noShortcut' // + isProduction ? '?minify' : ''
		} ]
	},
	plugins: [
		// new webpack.NoErrorsPlugin(),
		new ExtractTextPlugin( 'style.css', {
			allChunks: true
		} ),
		new webpack.optimize.DedupePlugin(),
		new UpdateResourceLoaderConfigPlugin( {
			i18n: 'mw.msg',
			aliases: {
				View: 'mobile.startup',
				Button: 'mobile.startup',
				Icon: 'mobile.startup',
				icons: 'mobile.startup',
				toast: 'mobile.toast',
				InfiniteScroll: 'mobile.infiniteScroll'
			}
		} )
	]
};

if ( !isProduction ) {
	conf.devtool = '#inline-source-map';
}

module.exports = conf;
