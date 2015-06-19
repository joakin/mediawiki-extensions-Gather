<?php

namespace Tests\Gather\views;

use PHPUnit_Framework_TestCase;
use Gather\views\TemplateParser;

class StubTemplateParser extends TemplateParser {
	protected function compile( $code ) {
		return false;
	}
}

class TemplateParserTest extends PHPUnit_Framework_TestCase {

	public function tearDown() {
		$compiledTemplateFile = __DIR__ . '/fixtures/compiled/foo.mustache.php';
		if ( is_file( $compiledTemplateFile ) ) {
			unlink( $compiledTemplateFile );
		}
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Failed to compile template 'foo'.
	 */
	public function test_it_should_throw_when_the_template_cant_be_compiled() {
		$templateParser = new StubTemplateParser( __DIR__ . '/fixtures', true );
		$templateParser->getTemplate( 'foo' );
	}

	public function test_it_should_persist_the_compiled_template_to_disk() {
		$templateParser = new TemplateParser( __DIR__ . '/fixtures', true );
		$templateParser->getTemplate( 'foo' );

		$this->assertTrue(
			is_file( __DIR__ . '/fixtures/compiled/foo.mustache.php' ),
			'It persists the compiled template to the "compiled" subfolder'
		);
	}

	public function test_it_shouldnt_recompile_the_template() {
		$templateParser = new TemplateParser( __DIR__ . '/fixtures', true );
		$templateParser->getTemplate( 'foo' );

		$compiledTemplatePath = __DIR__ . '/fixtures/compiled/foo.mustache.php';
		$initialMtime = filemtime( $compiledTemplatePath );

		$templateParser->getTemplate( 'foo' );

		$this->assertTrue(
			filemtime( $compiledTemplatePath ) === $initialMtime,
			'It doesn\'t modify the compiled template file'
		);
	}
}
