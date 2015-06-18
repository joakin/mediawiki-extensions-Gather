<?php

/**
 * views\helpers\Template.php
 */

namespace Gather\views\helpers;

use Gather\views\TemplateParser;

class Template {
	/**
	 * Easy helper for rendering a template from the Gather extension
	 */
	public static function render( $template, $data=array() ) {
		global $wgGatherRecompileTemplates;

		$templateParser = new TemplateParser(
			__DIR__ . '/../../../templates',
			$wgGatherRecompileTemplates
		);

		return $templateParser->processTemplate( $template, $data );
	}
}
