<?php
/**
 * Settings page (Settings > AI Disclosure) and option access.
 *
 * @package WpAiDisclosure
 */

declare( strict_types=1 );

namespace WpAiDisclosure;

/**
 * Plugin options: defaults, sanitisation and the settings page.
 */
final class Settings {

	public const OPTION = 'wp_ai_disclosure';

	/**
	 * Option defaults used on activation and as the sanitiser's baseline.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'enabled'       => true,
			'mode'          => 'auto', // One of auto, always, manual.
			'text'          => '',     // Empty means the locale default text.
			'position'      => Notice::POSITION_BOTTOM,
			'link_url'      => '',
			'link_text'     => '',
			'dismissible'   => true,
			'selectors'     => '',     // Extra CSS selectors, one per line.
			'vendors'       => array_keys( Detector::vendors() ),
			'log_enabled'   => true,
		);
	}

	/**
	 * Register the admin menu, the setting and the plugin action link.
	 */
	public static function init(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_init', array( self::class, 'register' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( WP_AI_DISCLOSURE_FILE ), array( self::class, 'action_links' ) );
	}

	/**
	 * Create the option with defaults on first activation.
	 */
	public static function ensure_defaults(): void {
		if ( false === get_option( self::OPTION, false ) ) {
			add_option( self::OPTION, self::defaults() );
		}
	}

	/**
	 * Stored options merged over the defaults.
	 *
	 * @return array<string, mixed>
	 */
	public static function get(): array {
		$stored = get_option( self::OPTION, array() );
		return array_merge( self::defaults(), is_array( $stored ) ? $stored : array() );
	}

	/**
	 * Effective notice text: stored text or the locale default.
	 */
	public static function text(): string {
		$text = trim( (string) self::get()['text'] );
		if ( '' !== $text ) {
			return $text;
		}
		return Notice::default_text_for_locale( determine_locale() );
	}

	/**
	 * Add a Settings link on the plugins screen.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public static function action_links( array $links ): array {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=wp-ai-disclosure' ) ) . '">' . esc_html__( 'Settings', 'wp-ai-disclosure' ) . '</a>' );
		return $links;
	}

	/**
	 * Settings → AI Disclosure.
	 */
	public static function menu(): void {
		add_options_page(
			__( 'AI Disclosure', 'wp-ai-disclosure' ),
			__( 'AI Disclosure', 'wp-ai-disclosure' ),
			'manage_options',
			'wp-ai-disclosure',
			array( self::class, 'page' )
		);
	}

	/**
	 * Register the single array option with its sanitiser.
	 */
	public static function register(): void {
		register_setting(
			'wp_ai_disclosure',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Validate and normalise submitted options.
	 *
	 * @param mixed $input Raw POST data.
	 * @return array<string, mixed>
	 */
	public static function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();
		$d     = self::defaults();

		$position = (string) ( $input['position'] ?? $d['position'] );
		if ( ! in_array( $position, array( Notice::POSITION_BOTTOM, Notice::POSITION_TOP, Notice::POSITION_INLINE ), true ) ) {
			$position = $d['position'];
		}
		$mode = (string) ( $input['mode'] ?? $d['mode'] );
		if ( ! in_array( $mode, array( 'auto', 'always', 'manual' ), true ) ) {
			$mode = $d['mode'];
		}

		$vendors = array();
		foreach ( (array) ( $input['vendors'] ?? array() ) as $v ) {
			$v = sanitize_key( (string) $v );
			if ( array_key_exists( $v, Detector::vendors() ) ) {
				$vendors[] = $v;
			}
		}

		$selectors = array_filter( array_map( 'trim', explode( "\n", (string) ( $input['selectors'] ?? '' ) ) ) );
		$selectors = array_map( static fn( string $s ): string => sanitize_text_field( $s ), $selectors );

		return array(
			'enabled'     => ! empty( $input['enabled'] ),
			'mode'        => $mode,
			'text'        => sanitize_textarea_field( (string) ( $input['text'] ?? '' ) ),
			'position'    => $position,
			'link_url'    => esc_url_raw( (string) ( $input['link_url'] ?? '' ) ),
			'link_text'   => sanitize_text_field( (string) ( $input['link_text'] ?? '' ) ),
			'dismissible' => ! empty( $input['dismissible'] ),
			'selectors'   => implode( "\n", $selectors ),
			'vendors'     => $vendors,
			'log_enabled' => ! empty( $input['log_enabled'] ),
		);
	}

	/**
	 * Render the settings page.
	 */
	public static function page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$o       = self::get();
		$vendors = Detector::vendors();
		$counts  = Log::last_days( 30 );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'AI Disclosure', 'wp-ai-disclosure' ); ?></h1>
			<p><?php esc_html_e( 'Article 50 of the EU AI Act requires that people are told when they interact with an AI system, unless it is obvious. This plugin shows a short notice next to chat widgets and AI-assisted forms and counts how often it was displayed, so you can prove it.', 'wp-ai-disclosure' ); ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( 'wp_ai_disclosure' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable', 'wp-ai-disclosure' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[enabled]" value="1" <?php checked( $o['enabled'] ); ?>> <?php esc_html_e( 'Show the notice on the public site', 'wp-ai-disclosure' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'When to show', 'wp-ai-disclosure' ); ?></th>
						<td>
							<fieldset>
								<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[mode]" value="auto" <?php checked( $o['mode'], 'auto' ); ?>> <?php esc_html_e( 'Automatically, when a known chat widget or a selected CSS selector is found on the page', 'wp-ai-disclosure' ); ?></label><br>
								<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[mode]" value="always" <?php checked( $o['mode'], 'always' ); ?>> <?php esc_html_e( 'On every page', 'wp-ai-disclosure' ); ?></label><br>
								<label><input type="radio" name="<?php echo esc_attr( self::OPTION ); ?>[mode]" value="manual" <?php checked( $o['mode'], 'manual' ); ?>> <?php esc_html_e( 'Only where the block or the [ai_disclosure] shortcode is placed', 'wp-ai-disclosure' ); ?></label>
							</fieldset>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wpaid-text"><?php esc_html_e( 'Notice text', 'wp-ai-disclosure' ); ?></label></th>
						<td>
							<textarea id="wpaid-text" name="<?php echo esc_attr( self::OPTION ); ?>[text]" rows="3" class="large-text" placeholder="<?php echo esc_attr( Notice::default_text_for_locale( determine_locale() ) ); ?>"><?php echo esc_textarea( (string) $o['text'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'Leave empty to use the default text for the site language.', 'wp-ai-disclosure' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Position', 'wp-ai-disclosure' ); ?></th>
						<td>
							<select name="<?php echo esc_attr( self::OPTION ); ?>[position]">
								<option value="bottom" <?php selected( $o['position'], 'bottom' ); ?>><?php esc_html_e( 'Bottom banner', 'wp-ai-disclosure' ); ?></option>
								<option value="top" <?php selected( $o['position'], 'top' ); ?>><?php esc_html_e( 'Top banner', 'wp-ai-disclosure' ); ?></option>
								<option value="inline" <?php selected( $o['position'], 'inline' ); ?>><?php esc_html_e( 'Inline, next to the widget (needs a selector)', 'wp-ai-disclosure' ); ?></option>
							</select>
							<br><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[dismissible]" value="1" <?php checked( $o['dismissible'] ); ?>> <?php esc_html_e( 'Visitors can dismiss the banner for the session', 'wp-ai-disclosure' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Learn more link', 'wp-ai-disclosure' ); ?></th>
						<td>
							<input type="url" name="<?php echo esc_attr( self::OPTION ); ?>[link_url]" value="<?php echo esc_attr( (string) $o['link_url'] ); ?>" class="regular-text" placeholder="https://">
							<input type="text" name="<?php echo esc_attr( self::OPTION ); ?>[link_text]" value="<?php echo esc_attr( (string) $o['link_text'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'How we use AI', 'wp-ai-disclosure' ); ?>">
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Detected widgets', 'wp-ai-disclosure' ); ?></th>
						<td>
							<?php foreach ( $vendors as $id => $label ) : ?>
								<label style="display:inline-block;min-width:12em"><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[vendors][]" value="<?php echo esc_attr( $id ); ?>" <?php checked( in_array( $id, (array) $o['vendors'], true ) ); ?>> <?php echo esc_html( $label ); ?></label>
							<?php endforeach; ?>
							<p class="description"><?php esc_html_e( 'The notice appears automatically when one of these widgets is loaded on a page.', 'wp-ai-disclosure' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="wpaid-selectors"><?php esc_html_e( 'Extra CSS selectors', 'wp-ai-disclosure' ); ?></label></th>
						<td>
							<textarea id="wpaid-selectors" name="<?php echo esc_attr( self::OPTION ); ?>[selectors]" rows="3" class="large-text" placeholder="#my-assistant&#10;form.ai-generated"><?php echo esc_textarea( (string) $o['selectors'] ); ?></textarea>
							<p class="description"><?php esc_html_e( 'One per line. When an element matches, the notice is shown (and anchored to it in inline mode).', 'wp-ai-disclosure' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Audit trail', 'wp-ai-disclosure' ); ?></th>
						<td>
							<label><input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[log_enabled]" value="1" <?php checked( $o['log_enabled'] ); ?>> <?php esc_html_e( 'Count daily impressions (no personal data stored)', 'wp-ai-disclosure' ); ?></label>
							<?php if ( ! empty( $counts ) ) : ?>
								<p class="description">
									<?php
									/* translators: 1: number of impressions, 2: number of days */
									echo esc_html( sprintf( __( '%1$s impressions over the last %2$d days.', 'wp-ai-disclosure' ), number_format_i18n( array_sum( $counts ) ), count( $counts ) ) );
									?>
									<a href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wp_ai_disclosure_export' ), 'wp_ai_disclosure_export' ) ); ?>"><?php esc_html_e( 'Export CSV', 'wp-ai-disclosure' ); ?></a>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<p class="description"><?php esc_html_e( 'This plugin helps you display a transparency notice. It is not legal advice; check the exact wording with your counsel.', 'wp-ai-disclosure' ); ?></p>
		</div>
		<?php
	}
}
