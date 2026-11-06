<?php
/**
 * Detects known chat / assistant widgets from enqueued script URLs or page HTML.
 *
 * Pure PHP, no WordPress dependency, so it is unit-testable and reusable.
 *
 * @package WpAiDisclosure
 */

declare( strict_types=1 );

namespace WpAiDisclosure;

/**
 * Knows which AI assistant vendors exist, how to spot them in the page, and their labels.
 */
final class Detector {

	/**
	 * Vendor id => [ human label, host/URL fragments that identify its loader, CSS selector of its container ].
	 *
	 * Selectors are best-effort hints for where to anchor the notice; the
	 * frontend falls back to a fixed banner when none matches.
	 *
	 * @var array<string, array{label: string, patterns: string[], selector: string}>
	 */
	private const VENDORS = array(
		'intercom'    => array(
			'label'    => 'Intercom',
			'patterns' => array( 'widget.intercom.io', 'js.intercomcdn.com' ),
			'selector' => '.intercom-lightweight-app, #intercom-container',
		),
		'crisp'       => array(
			'label'    => 'Crisp',
			'patterns' => array( 'client.crisp.chat' ),
			'selector' => '.crisp-client',
		),
		'tidio'       => array(
			'label'    => 'Tidio',
			'patterns' => array( 'code.tidio.co' ),
			'selector' => '#tidio-chat',
		),
		'drift'       => array(
			'label'    => 'Drift',
			'patterns' => array( 'js.driftt.com' ),
			'selector' => '#drift-widget-container',
		),
		'hubspot'     => array(
			'label'    => 'HubSpot Chat',
			'patterns' => array( 'js.hs-scripts.com', 'js.usemessages.com' ),
			'selector' => '#hubspot-messages-iframe-container',
		),
		'zendesk'     => array(
			'label'    => 'Zendesk',
			'patterns' => array( 'static.zdassets.com' ),
			'selector' => 'iframe[title="Button to launch messaging window"]',
		),
		'tawk'        => array(
			'label'    => 'tawk.to',
			'patterns' => array( 'embed.tawk.to' ),
			'selector' => '.widget-visible',
		),
		'freshchat'   => array(
			'label'    => 'Freshchat',
			'patterns' => array( 'wchat.freshchat.com', 'wchat.eu.freshchat.com' ),
			'selector' => '#fc_frame',
		),
		'livechat'    => array(
			'label'    => 'LiveChat',
			'patterns' => array( 'cdn.livechatinc.com' ),
			'selector' => '#chat-widget-container',
		),
		'brevo'       => array(
			'label'    => 'Brevo Conversations',
			'patterns' => array( 'conversations-widget.brevo.com', 'conversations-widget.sendinblue.com' ),
			'selector' => '#brevo-conversations',
		),
		'chatbase'    => array(
			'label'    => 'Chatbase',
			'patterns' => array( 'chatbase.co/embed' ),
			'selector' => '#chatbase-bubble-window, #chatbase-bubble-button',
		),
		'voiceflow'   => array(
			'label'    => 'Voiceflow',
			'patterns' => array( 'cdn.voiceflow.com/widget' ),
			'selector' => '#voiceflow-chat',
		),
		'botpress'    => array(
			'label'    => 'Botpress',
			'patterns' => array( 'cdn.botpress.cloud' ),
			'selector' => '#bp-web-widget, .bpw-widget-btn',
		),
		'dialogflow'  => array(
			'label'    => 'Dialogflow Messenger',
			'patterns' => array( 'gstatic.com/dialogflow-console/fast/messenger' ),
			'selector' => 'df-messenger',
		),
		'wpbot'       => array(
			'label'    => 'WPBot',
			'patterns' => array( '/plugins/chatbot/', '/plugins/wpbot' ),
			'selector' => '#wpbot-chat-container, .wpbot-widget',
		),
		'ai-engine'   => array(
			'label'    => 'AI Engine',
			'patterns' => array( '/plugins/ai-engine/' ),
			'selector' => '.mwai-chatbot-container',
		),
		'kognetiks'   => array(
			'label'    => 'Kognetiks Chatbot',
			'patterns' => array( '/plugins/chatbot-chatgpt/' ),
			'selector' => '#chatbot-chatgpt',
		),
	);

	/**
	 * Match vendors against a list of script URLs (or any strings that may contain them).
	 *
	 * @param string[] $sources Script src values or HTML fragments.
	 * @return array<string, array{label: string, selector: string}> Keyed by vendor id, in VENDORS order.
	 */
	public static function detect( array $sources ): array {
		$haystack = strtolower( implode( "\n", array_map( 'strval', $sources ) ) );
		$found    = array();
		foreach ( self::VENDORS as $id => $vendor ) {
			foreach ( $vendor['patterns'] as $pattern ) {
				if ( str_contains( $haystack, strtolower( $pattern ) ) ) {
					$found[ $id ] = array(
						'label'    => $vendor['label'],
						'selector' => $vendor['selector'],
					);
					break;
				}
			}
		}
		return $found;
	}

	/**
	 * Extract script src attributes from an HTML document.
	 *
	 * @param string $html Full or partial HTML.
	 * @return string[]
	 */
	public static function script_sources( string $html ): array {
		if ( ! preg_match_all( '/<script\b[^>]*\bsrc\s*=\s*["\']([^"\']+)["\']/i', $html, $m ) ) {
			return array();
		}
		return $m[1];
	}

	/**
	 * All known vendor ids with their labels, for the settings UI.
	 *
	 * @return array<string, string>
	 */
	public static function vendors(): array {
		$out = array();
		foreach ( self::VENDORS as $id => $vendor ) {
			$out[ $id ] = $vendor['label'];
		}
		return $out;
	}

	/**
	 * CSS selector for a vendor id, or empty string.
	 *
	 * @param string $id Vendor id as listed in VENDORS.
	 */
	public static function selector( string $id ): string {
		return self::VENDORS[ $id ]['selector'] ?? '';
	}
}
