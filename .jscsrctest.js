var fs = require( 'fs' ),
	config = JSON.parse( fs.readFileSync( '.jscsrc' ) );

delete config.jsDoc;
delete config.validateJSDoc;

module.exports = exports = config;
