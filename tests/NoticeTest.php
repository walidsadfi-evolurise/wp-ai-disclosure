<?php
declare( strict_types=1 );

namespace WpAiDisclosure\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WpAiDisclosure\Notice;

final class NoticeTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'esc_html' )->alias( static fn( string $s ): string => htmlspecialchars( $s, ENT_QUOTES, 'UTF-8' ) );
		Functions\when( 'esc_attr' )->alias( static fn( string $s ): string => htmlspecialchars( $s, ENT_QUOTES, 'UTF-8' ) );
		Functions\when( 'esc_url' )->alias( static fn( string $s ): string => str_starts_with( $s, 'javascript:' ) ? '' : htmlspecialchars( $s, ENT_QUOTES, 'UTF-8' ) );
		Functions\when( '__' )->returnArg();
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_default_text_follows_locale_prefix(): void {
		self::assertStringContainsString( 'intelligence artificielle', Notice::default_text_for_locale( 'fr_FR' ) );
		self::assertStringContainsString( 'KI-System', Notice::default_text_for_locale( 'de_DE_formal' ) );
		self::assertSame( Notice::default_texts()['en'], Notice::default_text_for_locale( 'ja' ) );
	}

	public function test_render_escapes_text_and_adds_landmark(): void {
		$html = Notice::render( '<b>AI</b> & "quotes"', 'bottom' );
		self::assertStringContainsString( 'role="note"', $html );
		self::assertStringContainsString( 'aria-live="polite"', $html );
		self::assertStringContainsString( '&lt;b&gt;AI&lt;/b&gt; &amp; &quot;quotes&quot;', $html );
		self::assertStringNotContainsString( '<b>', $html );
		self::assertStringContainsString( 'wp-ai-disclosure--bottom', $html );
		self::assertStringContainsString( 'wp-ai-disclosure__dismiss', $html );
	}

	public function test_inline_has_no_dismiss_and_unknown_position_falls_back(): void {
		$html = Notice::render( 'x', 'inline' );
		self::assertStringNotContainsString( '__dismiss', $html );
		self::assertStringContainsString( 'wp-ai-disclosure--bottom', Notice::render( 'x', 'sideways' ) );
	}

	public function test_link_is_rendered_and_javascript_urls_are_dropped(): void {
		$html = Notice::render( 'x', 'top', 'https://example.test/ai', 'How we use AI' );
		self::assertStringContainsString( 'href="https://example.test/ai"', $html );
		self::assertStringContainsString( '>How we use AI<', $html );

		$bad = Notice::render( 'x', 'top', 'javascript:alert(1)', 'click' );
		self::assertStringNotContainsString( 'javascript:', $bad );
	}
}
