<?php

/**
 * views\TemplateParser.php
 */

namespace Gather\views;

use TemplateParser as BaseTemplateParser;
use LightnCandy;
use Exception;

/**
 * A simplified version of Flow's Flow\TemplateHelper class based on Erik
 * Bernhardson's sample implementation [0].
 *
 * FIXME: This class and all compiled templates should be removed once
 * T02937 [1] is resolved.
 *
 * [0] https://gist.github.com/anonymous/dd63bccdb955f49a1e76
 * [1] https://phabricator.wikimedia.org/T102937
 */
class TemplateParser extends BaseTemplateParser {

	/**
	 * Constructs the location of the the source Mustache template and
	 * where to store the compiled PHP that will be used to render it.
	 *
	 * @param string $templateName
	 *
	 * @return string[]
	 * @throws \UnexpectedValueException Disallows upwards directory traversal via
	 *  $templateName (see TemplateParser#getTemplateFilename).
	 */
	public function getTemplateFilenames( $templateName ) {
		$templateFilename = $this->getTemplateFilename( $templateName );

		return array(
			'template' => $templateFilename,
			'compiled' => "{$this->templateDir}/compiled/{$templateName}.mustache.php",
		);
	}

	/**
	 * Returns a given template function if found, otherwise throws an exception.
	 *
	 * @param string $templateName
	 *
	 * @return \Closure
	 * @throws Exception
	 */
	public function getTemplate( $templateName ) {
		if ( isset( $this->renderers[$templateName] ) ) {
			return $this->renderers[$templateName];
		}

		$filenames = $this->getTemplateFilenames( $templateName );

		if ( $this->forceRecompile ) {
			$code = $this->compile( file_get_contents( $filenames['template'] ), $this->templateDir );

			if ( !$code ) {
				throw new Exception( "Failed to compile template '$templateName'." );
			}

			if ( !file_put_contents( $filenames['compiled'], $code ) ) {
				throw new Exception( "Failed to save updated compiled template '$templateName'" );
			}
		}

		$this->renderers[$templateName] = $renderer = require $filenames['compiled'];

		return $renderer;
	}

	/**
	 * @param string $code Handlebars code
	 * @param string $templateDir Directory templates are stored in
	 *
	 * @return string PHP code
	 */
	protected function compile( $code ) {
		return LightnCandy::compile(
			$code,
			array(
				'flags' => LightnCandy::FLAG_ERROR_EXCEPTION
					| LightnCandy::FLAG_MUSTACHE,
			)
		);
	}
}
