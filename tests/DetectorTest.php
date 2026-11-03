<?php
declare( strict_types=1 );

namespace WpAiDisclosure\Tests;

use PHPUnit\Framework\TestCase;
use WpAiDisclosure\Detector;

final class DetectorTest extends TestCase {

	public function test_detects_vendors_from_script_urls(): void {
		$found = Detector::detect(
			array(
				'https://client.crisp.chat/l.js',
				'https://example.com/wp-content/plugins/ai-engine/app/chatbot.js?ver=2',
				'https://cdn.example.com/unrelated.js',
			)
		);
		self::assertSame( array( 'crisp', 'ai-engine' ), array_keys( $found ) );
		self::assertSame( 'Crisp', $found['crisp']['label'] );
		self::assertNotSame( '', $found['crisp']['selector'] );
	}

	public function test_is_case_insensitive_and_ignores_unknown(): void {
		self::assertSame( array(), Detector::detect( array( 'https://cdn.example.com/app.js' ) ) );
		self::assertArrayHasKey( 'intercom', Detector::detect( array( 'HTTPS://WIDGET.INTERCOM.IO/widget/abc' ) ) );
	}

	public function test_extracts_script_sources_from_html(): void {
		$html = '<html><head><script src="https://a.test/x.js"></script><script type="module" src=\'/b.js\'></script><script>inline()</script></head></html>';
		self::assertSame( array( 'https://a.test/x.js', '/b.js' ), Detector::script_sources( $html ) );
		self::assertSame( array(), Detector::script_sources( '<p>no scripts</p>' ) );
	}

	public function test_vendor_list_and_selector_lookup(): void {
		$vendors = Detector::vendors();
		self::assertArrayHasKey( 'hubspot', $vendors );
		self::assertSame( '', Detector::selector( 'nope' ) );
		self::assertStringContainsString( '#tidio-chat', Detector::selector( 'tidio' ) );
	}
}
