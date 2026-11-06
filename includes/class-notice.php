<?php
/**
 * Builds the disclosure notice markup.
 *
 * @package WpAiDisclosure
 */

declare( strict_types=1 );

namespace WpAiDisclosure;

/**
 * Renders the transparency notice and holds the default texts per language.
 */
final class Notice {

	public const POSITION_BOTTOM = 'bottom';
	public const POSITION_TOP    = 'top';
	public const POSITION_INLINE = 'inline';

	/**
	 * Default notice text per locale prefix. Keep it short: it has to be read
	 * before the first message, not scrolled past.
	 *
	 * @return array<string, string>
	 */
	public static function default_texts(): array {
		return array(
			'en' => 'You are interacting with an AI system. Its answers may be inaccurate; a human is available on request.',
			'fr' => 'Vous interagissez avec un système d’intelligence artificielle. Ses réponses peuvent être inexactes ; un humain est disponible sur demande.',
			'de' => 'Sie interagieren mit einem KI-System. Die Antworten können ungenau sein; auf Wunsch steht ein Mensch zur Verfügung.',
			'es' => 'Está interactuando con un sistema de inteligencia artificial. Sus respuestas pueden ser inexactas; hay una persona disponible si lo solicita.',
			'it' => 'Stai interagendo con un sistema di intelligenza artificiale. Le risposte potrebbero essere imprecise; una persona è disponibile su richiesta.',
			'nl' => 'U communiceert met een AI-systeem. De antwoorden kunnen onjuist zijn; op verzoek is een mens beschikbaar.',
		);
	}

	/**
	 * Pick the default text for a WordPress locale such as "fr_FR".
	 *
	 * @param string $locale WordPress locale (language_REGION).
	 */
	public static function default_text_for_locale( string $locale ): string {
		$texts  = self::default_texts();
		$prefix = strtolower( substr( $locale, 0, 2 ) );
		return $texts[ $prefix ] ?? $texts['en'];
	}

	/**
	 * Render the notice. Returns escaped HTML; callers can echo it directly.
	 *
	 * @param string $text      Notice text (plain text; line breaks become <br>).
	 * @param string $position  One of the POSITION_* constants.
	 * @param string $link_url  Optional "learn more" URL.
	 * @param string $link_text Optional link label.
	 * @param bool   $dismiss   Whether to render a dismiss button (banner positions only).
	 */
	public static function render( string $text, string $position = self::POSITION_BOTTOM, string $link_url = '', string $link_text = '', bool $dismiss = true ): string {
		$position = in_array( $position, array( self::POSITION_BOTTOM, self::POSITION_TOP, self::POSITION_INLINE ), true ) ? $position : self::POSITION_BOTTOM;
		$id       = 'wp-ai-disclosure';
		$classes  = 'wp-ai-disclosure wp-ai-disclosure--' . $position;

		$body = nl2br( esc_html( $text ) );
		if ( '' !== $link_url ) {
			$label = '' !== $link_text ? $link_text : $link_url;
			$body .= ' <a class="wp-ai-disclosure__link" href="' . esc_url( $link_url ) . '" rel="noopener">' . esc_html( $label ) . '</a>';
		}

		$button = '';
		if ( $dismiss && self::POSITION_INLINE !== $position ) {
			$button = '<button type="button" class="wp-ai-disclosure__dismiss" aria-label="' . esc_attr( self::dismiss_label() ) . '">&times;</button>';
		}

		return '<div id="' . $id . '" class="' . esc_attr( $classes ) . '" role="note" aria-live="polite" data-wp-ai-disclosure="1">'
			. '<span class="wp-ai-disclosure__icon" aria-hidden="true">AI</span>'
			. '<span class="wp-ai-disclosure__text">' . $body . '</span>'
			. $button
			. '</div>';
	}

	/**
	 * Accessible label for the dismiss button.
	 */
	private static function dismiss_label(): string {
		if ( function_exists( '__' ) ) {
			return __( 'Dismiss this notice', 'wp-ai-disclosure' );
		}
		return 'Dismiss this notice';
	}
}
