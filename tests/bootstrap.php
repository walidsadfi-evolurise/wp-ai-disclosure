<?php
/**
 * PHPUnit bootstrap: Brain Monkey replaces WordPress functions.
 *
 * @package WpAiDisclosure
 */

declare( strict_types=1 );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'ABSPATH', '/tmp/' );
define( 'WP_AI_DISCLOSURE_VERSION', '0.0.0-test' );
define( 'WP_AI_DISCLOSURE_FILE', dirname( __DIR__ ) . '/wp-ai-disclosure.php' );
define( 'WP_AI_DISCLOSURE_DIR', dirname( __DIR__ ) . '/' );
define( 'WP_AI_DISCLOSURE_URL', 'https://example.test/wp-content/plugins/wp-ai-disclosure/' );

require_once dirname( __DIR__ ) . '/includes/class-detector.php';
require_once dirname( __DIR__ ) . '/includes/class-notice.php';
require_once dirname( __DIR__ ) . '/includes/class-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-log.php';
require_once dirname( __DIR__ ) . '/includes/class-frontend.php';
