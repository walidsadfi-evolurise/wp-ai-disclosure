<?php
declare( strict_types=1 );

namespace WpAiDisclosure\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WpAiDisclosure\Settings;

final class SettingsTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		Functions\when( 'sanitize_key' )->alias( static fn( string $s ): string => strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', $s ) ) );
		Functions\when( 'sanitize_text_field' )->alias( static fn( string $s ): string => trim( strip_tags( $s ) ) );
		Functions\when( 'sanitize_textarea_field' )->alias( static fn( string $s ): string => trim( strip_tags( $s ) ) );
		Functions\when( 'esc_url_raw' )->alias( static fn( string $s ): string => str_starts_with( $s, 'http' ) ? $s : '' );
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_sanitize_rejects_unknown_values_and_keeps_valid_ones(): void {
		$out = Settings::sanitize(
			array(
				'enabled'   => '1',
				'mode'      => 'whatever',
				'position'  => 'inline',
				'text'      => "<script>x</script>Hello\nworld",
				'link_url'  => 'javascript:alert(1)',
				'vendors'   => array( 'crisp', 'not-a-vendor', 'TIDIO' ),
				'selectors' => " #a \n\n .b ",
			)
		);
		self::assertTrue( $out['enabled'] );
		self::assertSame( 'auto', $out['mode'] );
		self::assertSame( 'inline', $out['position'] );
		self::assertSame( "xHello\nworld", $out['text'] );
		self::assertSame( '', $out['link_url'] );
		self::assertSame( array( 'crisp', 'tidio' ), $out['vendors'] );
		self::assertSame( "#a\n.b", $out['selectors'] );
		self::assertFalse( $out['dismissible'] );
	}

	public function test_get_merges_defaults_over_partial_storage(): void {
		Functions\when( 'get_option' )->justReturn( array( 'mode' => 'always' ) );
		$o = Settings::get();
		self::assertSame( 'always', $o['mode'] );
		self::assertTrue( $o['enabled'] );
		self::assertNotEmpty( $o['vendors'] );
	}

	public function test_text_falls_back_to_locale_default(): void {
		Functions\when( 'get_option' )->justReturn( array( 'text' => '  ' ) );
		Functions\when( 'determine_locale' )->justReturn( 'fr_FR' );
		self::assertStringContainsString( 'intelligence artificielle', Settings::text() );
	}
}
