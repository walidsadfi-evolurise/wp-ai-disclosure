<?php
/**
 * Plugin Name:       AI Disclosure
 * Plugin URI:        https://github.com/walidsadfi-evolurise/wp-ai-disclosure
 * Description:       Tells visitors when they are interacting with an AI system (EU AI Act, art. 50). Auto-detects chat widgets, adds a block and a shortcode, counts impressions for your audit trail.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            Walid Sadfi
 * Author URI:        https://www.malt.fr/profile/walidsadfi
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wp-ai-disclosure
 * Domain Path:       /languages
 *
 * @package WpAiDisclosure
 */

declare( strict_types=1 );

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WP_AI_DISCLOSURE_VERSION', '0.1.0' );
define( 'WP_AI_DISCLOSURE_FILE', __FILE__ );
define( 'WP_AI_DISCLOSURE_DIR', plugin_dir_path( __FILE__ ) );
define( 'WP_AI_DISCLOSURE_URL', plugin_dir_url( __FILE__ ) );

require_once WP_AI_DISCLOSURE_DIR . 'includes/class-detector.php';
require_once WP_AI_DISCLOSURE_DIR . 'includes/class-notice.php';
require_once WP_AI_DISCLOSURE_DIR . 'includes/class-settings.php';
require_once WP_AI_DISCLOSURE_DIR . 'includes/class-log.php';
require_once WP_AI_DISCLOSURE_DIR . 'includes/class-frontend.php';

add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain( 'wp-ai-disclosure', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
		WpAiDisclosure\Settings::init();
		WpAiDisclosure\Log::init();
		WpAiDisclosure\Frontend::init();
	}
);

register_activation_hook(
	__FILE__,
	static function (): void {
		WpAiDisclosure\Settings::ensure_defaults();
	}
);
