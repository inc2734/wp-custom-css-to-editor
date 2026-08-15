<?php
/**
 * @package inc2734/wp-custom-css-to-editor
 * @author inc2734
 * @license GPL-2.0+
 */

namespace Inc2734\WP_Custom_CSS_To_Editor;

class Bootstrap {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter( 'block_editor_settings_all', [ $this, '_block_editor_settings_all' ] );
		add_filter( 'tiny_mce_before_init', [ $this, '_tiny_mce_before_init' ], 11 );
	}

	/**
	 * Add the additional CSS to the block editor.
	 *
	 * WordPress adds these styles to the editor canvas and prefixes them with
	 * `.editor-styles-wrapper`.
	 *
	 * @param array $editor_settings The current block editor settings.
	 * @return array
	 */
	public function _block_editor_settings_all( $editor_settings ) {
		$css = $this->_get_minified_custom_css();
		if ( ! $css ) {
			return $editor_settings;
		}

		if ( ! isset( $editor_settings['styles'] ) || ! is_array( $editor_settings['styles'] ) ) {
			$editor_settings['styles'] = [];
		}

		$editor_settings['styles'][] = [ 'css' => $css ];
		return $editor_settings;
	}

	/**
	 * Additional CSS to the classic editor.
	 *
	 * @param array $mce_init An array with TinyMCE config.
	 * @return array
	 */
	public function _tiny_mce_before_init( $mce_init ) {
		$css = $this->_get_minified_custom_css();
		if ( ! $css ) {
			return $mce_init;
		}

		$css = preg_replace_callback(
			'/([^\{\}\(\)@]+?)(\{[^\}]*\})/s',
			function ( $matches ) {
				$selectors = array_map(
					function ( $selector ) {
						return trim( $selector );
					},
					explode( ',', $matches[1] )
				);
				$new_css   = [];
				foreach ( $selectors as $selector ) {
					$new_css[] = sprintf(
						'.mce-content-body.mceContentBody %1$s',
						$selector
					);
				}
				return implode( ',', $new_css ) . $matches[2];
			},
			$css
		);

		if ( ! isset( $mce_init['content_style'] ) ) {
			$mce_init['content_style'] = '';
		}

		$mce_init['content_style'] .= addslashes( $css );
		return $mce_init;
	}

	/**
	 * Minify CSS.
	 *
	 * @param string $css CSS.
	 * @return string
	 */
	protected function _minify( $css ) {
		$css = str_replace( [ "\t", "\n", "\r" ], '', $css );
		$css = preg_replace( '|\s*([\{\}])\s*|ms', '$1', $css );
		return $css;
	}

	/**
	 * Return minified custom css.
	 *
	 * @return string
	 */
	protected function _get_minified_custom_css() {
		$css = wp_get_custom_css();
		return $this->_minify( $css );
	}
}
