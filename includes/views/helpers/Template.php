<?php

/**
 * views\helpers\Template.php
 */

namespace Gather\views\helpers;

use TemplateParser;

class Template {
	/**
	 * Easy helper for rendering a template from the Gather extension
	 */
	public static function render( $template, $data=array() ) {
		$templateParser = new TemplateParser( __DIR__ . '/../../../templates' );
		return $templateParser->processTemplate( $template, $data );
	}
}
