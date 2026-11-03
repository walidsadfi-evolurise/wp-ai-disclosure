<?php
declare( strict_types=1 );

namespace WpAiDisclosure\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPUnit\Framework\TestCase;
use WpAiDisclosure\Log;

final class LogTest extends TestCase {

	/** @var array<string, int> */
	private array $store = array();

	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();
		$this->store = array();
		Functions\when( 'get_option' )->alias( fn() => $this->store );
		Functions\when( 'update_option' )->alias(
			function ( string $k, array $v ): bool {
				$this->store = $v;
				return true;
			}
		);
	}

	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	public function test_hit_increments_per_day(): void {
		self::assertSame( 1, Log::hit( '2026-11-04' ) );
		self::assertSame( 2, Log::hit( '2026-11-04' ) );
		self::assertSame( 1, Log::hit( '2026-11-05' ) );
		self::assertSame( array( '2026-11-04' => 2, '2026-11-05' => 1 ), Log::all() );
	}

	public function test_retention_keeps_the_newest_days(): void {
		for ( $i = 0; $i < Log::MAX_DAYS + 5; $i++ ) {
			Log::hit( gmdate( 'Y-m-d', strtotime( "2025-01-01 +{$i} days" ) ) );
		}
		$all = Log::all();
		self::assertCount( Log::MAX_DAYS, $all );
		self::assertArrayNotHasKey( '2025-01-01', $all );
		self::assertSame( array( gmdate( 'Y-m-d', strtotime( '2025-01-01 +' . ( Log::MAX_DAYS + 4 ) . ' days' ) ) ), array_slice( array_keys( $all ), -1 ) );
	}

	public function test_last_days_slices_sorted(): void {
		Log::hit( '2026-01-03' );
		Log::hit( '2026-01-01' );
		Log::hit( '2026-01-02' );
		self::assertSame( array( '2026-01-02' => 1, '2026-01-03' => 1 ), Log::last_days( 2 ) );
	}
}
