<?php
/**
 * Public side: assets, auto-detection, shortcode and block.
 *
 * @package WpAiDisclosure
 */

declare( strict_types=1 );

namespace WpAiDisclosure;

final class Frontend {

	public static function init(): void {
		add_action( 'wp_enqueue_scripts', array( self::class, 'assets' ) );
		add_action( 'wp_footer', array( self::class, 'footer' ), 99 );
		add_shortcode( 'ai_disclosure', array( self::class, 'shortcode' ) );
		add_action( 'init', array( self::class, 'block' ) );
	}

	/**
	 * Vendor ids enabled in settings, as [ id => selector ].
	 *
	 * @return array<string, string>
	 */
	private static function enabled_vendor_selectors(): array {
		$out = array();
		foreach ( (array) Settings::get()['vendors'] as $id ) {
			$sel = Detector::selector( (string) $id );
			if ( '' !== $sel ) {
				$out[ (string) $id ] = $sel;
			}
		}
		return $out;
	}

	/**
	 * Scripts enqueued or printed so far, used for server-side detection.
	 *
	 * @return string[]
	 */
	private static function known_script_sources(): array {
		$sources = array();
		$scripts = wp_scripts();
		foreach ( $scripts->queue as $handle ) {
			$src = $scripts->registered[ $handle ]->src ?? '';
			if ( is_string( $src ) && '' !== $src ) {
				$sources[] = $src;
			}
		}
		return $sources;
	}

	public static function assets(): void {
		$o = Settings::get();
		if ( empty( $o['enabled'] ) || is_admin() ) {
			return;
		}
		wp_register_style( 'wp-ai-disclosure', WP_AI_DISCLOSURE_URL . 'assets/ai-disclosure.css', array(), WP_AI_DISCLOSURE_VERSION );
		wp_register_script( 'wp-ai-disclosure', WP_AI_DISCLOSURE_URL . 'assets/ai-disclosure.js', array(), WP_AI_DISCLOSURE_VERSION, array( 'strategy' => 'defer' ) );

		$selectors = array_values( self::enabled_vendor_selectors() );
		foreach ( array_filter( array_map( 'trim', explode( "\n", (string) $o['selectors'] ) ) ) as $extra ) {
			$selectors[] = $extra;
		}

		wp_localize_script(
			'wp-ai-disclosure',
			'wpAiDisclosure',
			array(
				'mode'        => (string) $o['mode'],
				'position'    => (string) $o['position'],
				'dismissible' => (bool) $o['dismissible'],
				'selectors'   => $selectors,
				'ajaxUrl'     => (bool) $o['log_enabled'] ? admin_url( 'admin-ajax.php' ) : '',
				'html'        => Notice::render( Settings::text(), (string) $o['position'], (string) $o['link_url'], (string) $o['link_text'], (bool) $o['dismissible'] ),
			)
		);
		wp_enqueue_style( 'wp-ai-disclosure' );
		wp_enqueue_script( 'wp-ai-disclosure' );
	}

	/**
	 * Server-side hint: if a known widget script is already enqueued, mark the
	 * page so the frontend shows the notice without waiting for DOM detection.
	 */
	public static function footer(): void {
		$o = Settings::get();
		if ( empty( $o['enabled'] ) || 'auto' !== $o['mode'] ) {
			return;
		}
		$found = Detector::detect( self::known_script_sources() );
		$found = array_intersect_key( $found, array_flip( (array) $o['vendors'] ) );
		if ( empty( $found ) ) {
			return;
		}
		echo '<script id="wp-ai-disclosure-detected" type="application/json">' . wp_json_encode( array_keys( $found ) ) . '</script>' . "\n";
	}

	/**
	 * [ai_disclosure text="..." position="inline"]
	 *
	 * @param array<string, string>|string $atts Shortcode attributes.
	 */
	public static function shortcode( $atts ): string {
		$o    = Settings::get();
		$atts = shortcode_atts(
			array(
				'text'     => '',
				'position' => Notice::POSITION_INLINE,
			),
			is_array( $atts ) ? $atts : array(),
			'ai_disclosure'
		);
		$text = '' !== trim( (string) $atts['text'] ) ? (string) $atts['text'] : Settings::text();
		return Notice::render( $text, (string) $atts['position'], (string) $o['link_url'], (string) $o['link_text'], false );
	}

	public static function block(): void {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}
		register_block_type(
			WP_AI_DISCLOSURE_DIR . 'block',
			array(
				'render_callback' => static function ( array $attributes ): string {
					return self::shortcode( array( 'text' => (string) ( $attributes['text'] ?? '' ) ) );
				},
			)
		);
	}
}
