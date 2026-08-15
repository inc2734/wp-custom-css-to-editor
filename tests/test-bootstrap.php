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
	private $bootstraps = [];

	public function setUp(): void {
		parent::setUp();
		$this->bootstrap = $this->create_bootstrap();
	}

	public function tearDown(): void {
		foreach ( $this->bootstraps as $bootstrap ) {
			remove_action( 'enqueue_block_editor_assets', [ $bootstrap, '_enqueue_block_editor_assets' ] );
			remove_filter( 'block_editor_settings_all', [ $bootstrap, '_block_editor_settings_all' ] );
			remove_filter( 'tiny_mce_before_init', [ $bootstrap, '_tiny_mce_before_init' ], 11 );
			remove_action( 'admin_head', [ $bootstrap, '_add_editor_style' ] );
			remove_action( 'customize_controls_head', [ $bootstrap, '_add_editor_style' ] );
		}
		parent::tearDown();
	}

	private function create_bootstrap( $wordpress_version = null ) {
		$current_wordpress_version = $GLOBALS['wp_version'];

		if ( null !== $wordpress_version ) {
			$GLOBALS['wp_version'] = $wordpress_version;
		}

		$bootstrap = new Testable_Bootstrap(
			".entry, p {\n\tcolor: red;\n}"
		);
		$this->bootstraps[] = $bootstrap;

		$GLOBALS['wp_version'] = $current_wordpress_version;
		return $bootstrap;
	}

	public function test_relies_on_core_for_block_editor_css_in_wordpress_7() {
		if ( version_compare( $GLOBALS['wp_version'], '7.0', '<' ) ) {
			$this->markTestSkipped( 'WordPress 7.0 or later is required.' );
		}

		$this->assertFalse(
			has_filter( 'block_editor_settings_all', [ $this->bootstrap, '_block_editor_settings_all' ] )
		);
		$this->assertFalse(
			has_action( 'enqueue_block_editor_assets', [ $this->bootstrap, '_enqueue_block_editor_assets' ] )
		);
	}

	public function test_registers_tinymce_filter() {
		$this->assertSame(
			11,
			has_filter( 'tiny_mce_before_init', [ $this->bootstrap, '_tiny_mce_before_init' ] )
		);
	}

	public function test_wordpress_7_adds_customizer_css_once() {
		if ( version_compare( $GLOBALS['wp_version'], '7.0', '<' ) ) {
			$this->markTestSkipped( 'WordPress 7.0 or later is required.' );
		}

		$custom_css = '.from-customizer{color:red;}';
		$this->assertNotWPError( wp_update_custom_css_post( $custom_css ) );

		wp_styles();
		wp_scripts();
		$editor_settings = get_block_editor_settings( [], new WP_Block_Editor_Context() );
		$matching_styles = array_filter(
			$editor_settings['styles'],
			function ( $style ) use ( $custom_css ) {
				return isset( $style['css'] ) && $custom_css === $style['css'];
			}
		);

		$this->assertCount( 1, $matching_styles );
	}

	public function test_registers_editor_settings_filter_before_wordpress_7() {
		$bootstrap = $this->create_bootstrap( '6.9' );

		$this->assertSame(
			10,
			has_filter( 'block_editor_settings_all', [ $bootstrap, '_block_editor_settings_all' ] )
		);
		$this->assertFalse(
			has_action( 'enqueue_block_editor_assets', [ $bootstrap, '_enqueue_block_editor_assets' ] )
		);
	}

	public function test_registers_legacy_editor_action_before_wordpress_5_8() {
		$bootstrap = $this->create_bootstrap( '5.7' );

		$this->assertSame(
			10,
			has_action( 'enqueue_block_editor_assets', [ $bootstrap, '_enqueue_block_editor_assets' ] )
		);
		$this->assertFalse(
			has_filter( 'block_editor_settings_all', [ $bootstrap, '_block_editor_settings_all' ] )
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
