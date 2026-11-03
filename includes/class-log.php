<?php
/**
 * Impression counter. Stores one integer per day, nothing about visitors.
 *
 * @package WpAiDisclosure
 */

declare( strict_types=1 );

namespace WpAiDisclosure;

final class Log {

	public const OPTION   = 'wp_ai_disclosure_log';
	public const MAX_DAYS = 400;

	public static function init(): void {
		add_action( 'wp_ajax_wp_ai_disclosure_hit', array( self::class, 'ajax_hit' ) );
		add_action( 'wp_ajax_nopriv_wp_ai_disclosure_hit', array( self::class, 'ajax_hit' ) );
		add_action( 'admin_post_wp_ai_disclosure_export', array( self::class, 'export' ) );
	}

	/**
	 * Increment today's counter. Returns the new value.
	 */
	public static function hit( ?string $day = null ): int {
		$day = $day ?? gmdate( 'Y-m-d' );
		$log = self::all();
		$log[ $day ] = ( $log[ $day ] ?? 0 ) + 1;
		if ( count( $log ) > self::MAX_DAYS ) {
			ksort( $log );
			$log = array_slice( $log, -self::MAX_DAYS, null, true );
		}
		update_option( self::OPTION, $log, false );
		return $log[ $day ];
	}

	/**
	 * @return array<string, int> day => count
	 */
	public static function all(): array {
		$log = get_option( self::OPTION, array() );
		return is_array( $log ) ? array_map( 'intval', $log ) : array();
	}

	/**
	 * @return array<string, int>
	 */
	public static function last_days( int $days ): array {
		$log = self::all();
		ksort( $log );
		return array_slice( $log, -$days, null, true );
	}

	/**
	 * AJAX endpoint called once per page view where the notice was rendered.
	 * No nonce by design: it is a public counter and a nonce would break page caching.
	 * Rate limiting is left to the web server; the counter is not security-sensitive.
	 */
	public static function ajax_hit(): void {
		if ( empty( Settings::get()['log_enabled'] ) ) {
			wp_send_json_success( array( 'logged' => false ), 200 );
		}
		self::hit();
		wp_send_json_success( array( 'logged' => true ), 200 );
	}

	public static function export(): void {
		if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'wp_ai_disclosure_export' ) ) {
			wp_die( esc_html__( 'Not allowed.', 'wp-ai-disclosure' ), '', array( 'response' => 403 ) );
		}
		$log = self::all();
		ksort( $log );
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="ai-disclosure-impressions.csv"' );
		echo "day,impressions\n";
		foreach ( $log as $day => $count ) {
			echo esc_html( $day ) . ',' . (int) $count . "\n";
		}
		exit;
	}
}
