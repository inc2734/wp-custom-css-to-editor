<?php
/**
 * Tests for Bootstrap.
 */

class Testable_Bootstrap extends Inc2734\WP_Custom_CSS_To_Editor\Bootstrap {

	private $custom_css;

	public function __construct( $custom_css ) {
		$this->custom_css = $custom_css;
		parent::__construct();
	}

	public function set_custom_css( $custom_css ) {
		$this->custom_css = $custom_css;
	}

	protected function _get_minified_custom_css() {
		return $this->_minify( $this->custom_css );
	}
}

class Bootstrap_Test extends WP_UnitTestCase {

	private $bootstrap;

	public function setUp(): void {
		parent::setUp();
		$this->bootstrap = new Testable_Bootstrap(
			".entry, p {\n\tcolor: red;\n}"
		);
	}

	public function tearDown(): void {
		remove_filter( 'block_editor_settings_all', [ $this->bootstrap, '_block_editor_settings_all' ] );
		remove_filter( 'tiny_mce_before_init', [ $this->bootstrap, '_tiny_mce_before_init' ], 11 );
		parent::tearDown();
	}

	public function test_registers_editor_filters() {
		$this->assertSame(
			10,
			has_filter( 'block_editor_settings_all', [ $this->bootstrap, '_block_editor_settings_all' ] )
		);
		$this->assertSame(
			11,
			has_filter( 'tiny_mce_before_init', [ $this->bootstrap, '_tiny_mce_before_init' ] )
		);
	}

	public function test_adds_custom_css_to_block_editor_settings() {
		$editor_settings = [
			'styles' => [
				[ 'css' => 'body{background:white;}' ],
			],
		];

		$actual = $this->bootstrap->_block_editor_settings_all( $editor_settings );

		$this->assertSame(
			[
				[ 'css' => 'body{background:white;}' ],
				[ 'css' => '.entry, p{color: red;}' ],
			],
			$actual['styles']
		);
	}

	public function test_initializes_block_editor_styles() {
		$actual = $this->bootstrap->_block_editor_settings_all( [] );

		$this->assertSame(
			[ [ 'css' => '.entry, p{color: red;}' ] ],
			$actual['styles']
		);
	}

	public function test_does_not_change_editor_settings_without_custom_css() {
		$this->bootstrap->set_custom_css( '' );
		$editor_settings = [ 'styles' => [ [ 'css' => 'body{background:white;}' ] ] ];

		$this->assertSame(
			$editor_settings,
			$this->bootstrap->_block_editor_settings_all( $editor_settings )
		);
	}

	public function test_adds_prefixed_custom_css_to_tinymce() {
		$mce_init = [ 'content_style' => 'body{background:white;}' ];

		$actual = $this->bootstrap->_tiny_mce_before_init( $mce_init );

		$this->assertSame(
			'body{background:white;}.mce-content-body.mceContentBody .entry,.mce-content-body.mceContentBody p{color: red;}',
			$actual['content_style']
		);
	}
}
