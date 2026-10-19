<?php
/**
 * Admin settings page using the WordPress Settings API.
 *
 * @package Buddy_Notification_Bell
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class BD_BNB_Settings {

	/**
	 * Returns true when BuddyBoss Platform is active.
	 *
	 * @return bool
	 */
	public static function is_buddyboss_active() {
		return defined( 'BP_PLATFORM_VERSION' );
	}

	/**
	 * Registers all admin hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'add_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'admin_head', array( __CLASS__, 'menu_icon_color' ) );
		add_action( 'current_screen', array( __CLASS__, 'suppress_external_notices' ) );
	}

	/**
	 * On our settings page: run first in admin_notices, output settings_errors,
	 * then wipe all remaining notice callbacks so third-party notices never render.
	 */
	public static function suppress_external_notices() {
		$screen = get_current_screen();
		if ( ! $screen || 'toplevel_page_buddy-notification-bell' !== $screen->id ) {
			return;
		}
		add_action( 'admin_notices', array( __CLASS__, 'output_page_notices' ), -999 );
	}

	public static function output_page_notices() {
		settings_errors();
		remove_all_actions( 'admin_notices' );
		remove_all_actions( 'all_admin_notices' );
	}

	/**
	 * Output orange color for the Notification menu icon.
	 */
	public static function menu_icon_color() {
		?>
		<style>
			#toplevel_page_buddy-notification-bell .wp-menu-image::before {
				color: #f0640c !important;
			}
		</style>
		<?php
	}

	/**
	 * Register top-level admin menu, positioned below BuddyPress.
	 */
	public static function add_menu() {
		add_menu_page(
			__( 'Notification', 'buddy-notification-bell' ),
			__( 'Notification', 'buddy-notification-bell' ),
			'manage_options',
			'buddy-notification-bell',
			array( __CLASS__, 'render_page' ),
			'dashicons-bell',
			30
		);
	}

	/**
	 * Register all settings with sanitize callbacks.
	 */
	public static function register_settings() {
		register_setting(
			'bnb_general',
			'bnb_sound_enabled',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
				'default'           => 'yes',
			)
		);
		register_setting(
			'bnb_general',
			'bnb_show_count',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
				'default'           => 'yes',
			)
		);
		register_setting(
			'bnb_general',
			'bnb_bell_position',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_position' ),
				'default'           => 'right',
			)
		);
		register_setting(
			'bnb_general',
			'bnb_notification_style',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_notification_style' ),
				'default'           => 'individual',
			)
		);

		if ( self::is_buddyboss_active() ) {
			register_setting(
				'bnb_buddyboss',
				'bnb_buddyboss_mode',
				array(
					'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
					'default'           => '',
				)
			);
		}

		register_setting(
			'bnb_display',
			'make_default_visible',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
				'default'           => '',
			)
		);
		register_setting(
			'bnb_display',
			'bnb_floating_bell',
			array(
				'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
				'default'           => '',
			)
		);
	}

	/**
	 * Enqueue admin CSS/JS only on our settings page.
	 *
	 * @param string $hook
	 */
	public static function enqueue_admin_assets( $hook ) {
		if ( 'toplevel_page_buddy-notification-bell' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'bnb-admin-fonts',
			'https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700&display=swap',
			array(),
			null
		);

		add_filter(
			'style_loader_tag',
			static function ( $html, $handle ) {
				if ( 'bnb-admin-fonts' !== $handle ) {
					return $html;
				}

				$preconnect  = '<link rel="preconnect" href="https://fonts.googleapis.com">' . "\n";
				$preconnect .= '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>' . "\n";

				return $preconnect . $html;
			},
			10,
			2
		);

		wp_enqueue_style(
			'bnb-admin-style',
			BNB_URL . 'assets/css/bnb-admin.css',
			array( 'bnb-admin-fonts' ),
			self::asset_version( 'assets/css/bnb-admin.css' )
		);

		wp_enqueue_script(
			'bnb-admin',
			BNB_URL . 'assets/js/bnb-admin.js',
			array( 'jquery' ),
			self::asset_version( 'assets/js/bnb-admin.js' ),
			true
		);

		wp_localize_script(
			'bnb-admin',
			'bnbAdmin',
			array(
				'copied' => __( 'Copied', 'buddy-notification-bell' ),
			)
		);
	}

	/**
	 * Cache-bust admin assets when the file changes, without a plugin version bump.
	 *
	 * @param string $relative Path relative to the plugin root.
	 * @return string
	 */
	private static function asset_version( $relative ) {
		$path  = BNB_PATH . $relative;
		$mtime = file_exists( $path ) ? filemtime( $path ) : BNB_VERSION;

		return BNB_VERSION . '.' . $mtime;
	}

	/**
	 * Render the settings page with tabs.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab  = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$valid_tabs  = array( 'general', 'display', 'shortcode' );
		if ( self::is_buddyboss_active() ) {
			$valid_tabs[] = 'buddyboss';
		}
		$active_tab = in_array( $active_tab, $valid_tabs, true ) ? $active_tab : 'general';
		?>
		<div class="wrap bnb-settings-wrap">
			<div class="bnb-settings-shell">

				<header class="bnb-settings-header">
					<div class="bnb-header-stage" aria-hidden="true">
						<span class="bnb-header-orb bnb-header-orb--a"></span>
						<span class="bnb-header-orb bnb-header-orb--b"></span>
						<span class="bnb-header-grid"></span>
						<span class="bnb-header-ring bnb-header-ring--1"></span>
						<span class="bnb-header-ring bnb-header-ring--2"></span>
						<span class="bnb-header-ring bnb-header-ring--3"></span>
					</div>

					<div class="bnb-header-content">
						<div class="bnb-header-brand">
							<span class="bnb-settings-mark" aria-hidden="true">
								<span class="bnb-settings-mark-pulse"></span>
								<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512">
									<path fill="currentColor" d="M439.39 362.29c-19.32-20.76-55.47-51.99-55.47-154.29 0-77.7-54.48-139.9-127.94-155.16V32c0-17.67-14.32-32-31.98-32s-31.98 14.33-31.98 32v20.84C118.56 68.1 64.08 130.3 64.08 208c0 102.3-36.15 133.53-55.47 154.29-6 6.45-8.66 14.16-8.61 21.71.11 16.4 12.98 32 32.1 32h383.8c19.12 0 32-15.6 32.1-32 .05-7.55-2.61-15.27-8.61-21.71zM224 512c35.32 0 63.97-28.65 63.97-64H160.03c0 35.35 28.65 64 63.97 64z"/>
								</svg>
							</span>
							<div class="bnb-settings-header-text">
								<p class="bnb-kicker"><?php esc_html_e( 'Realtime community alerts', 'buddy-notification-bell' ); ?></p>
								<h1><?php esc_html_e( 'Buddy Notification Bell', 'buddy-notification-bell' ); ?></h1>
								<p class="bnb-lede"><?php esc_html_e( 'Shape how members hear, see, and open every new notification.', 'buddy-notification-bell' ); ?></p>
								<div class="bnb-header-aside">
									<span class="bnb-chip">
										<?php
										/* translators: %s: plugin version number */
										echo esc_html( sprintf( __( 'v%s', 'buddy-notification-bell' ), BNB_VERSION ) );
										?>
									</span>
									<span class="bnb-chip bnb-chip--live">
										<span class="bnb-chip-dot" aria-hidden="true"></span>
										<?php esc_html_e( 'Live sync', 'buddy-notification-bell' ); ?>
									</span>
								</div>
							</div>
						</div>
					</div>
				</header>

				<nav class="nav-tab-wrapper bnb-nav-tabs" aria-label="<?php esc_attr_e( 'Settings sections', 'buddy-notification-bell' ); ?>">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddy-notification-bell&tab=general' ) ); ?>"
					   class="nav-tab <?php echo esc_attr( 'general' === $active_tab ? 'nav-tab-active' : '' ); ?>">
						<?php esc_html_e( 'General', 'buddy-notification-bell' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddy-notification-bell&tab=display' ) ); ?>"
					   class="nav-tab <?php echo esc_attr( 'display' === $active_tab ? 'nav-tab-active' : '' ); ?>">
						<?php esc_html_e( 'Display', 'buddy-notification-bell' ); ?>
					</a>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddy-notification-bell&tab=shortcode' ) ); ?>"
					   class="nav-tab <?php echo esc_attr( 'shortcode' === $active_tab ? 'nav-tab-active' : '' ); ?>">
						<?php esc_html_e( 'Shortcode', 'buddy-notification-bell' ); ?>
					</a>
					<?php if ( self::is_buddyboss_active() ) : ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddy-notification-bell&tab=buddyboss' ) ); ?>"
					   class="nav-tab <?php echo esc_attr( 'buddyboss' === $active_tab ? 'nav-tab-active' : '' ); ?>">
						<?php esc_html_e( 'BuddyBoss', 'buddy-notification-bell' ); ?>
					</a>
					<?php endif; ?>
				</nav>

				<div class="bnb-settings-body<?php echo esc_attr( 'shortcode' === $active_tab ? ' bnb-settings-body--docs' : '' ); ?>">
					<?php if ( 'shortcode' === $active_tab ) : ?>
						<?php self::render_shortcode_tab(); ?>
					<?php else : ?>
						<form method="post" action="options.php" class="bnb-settings-form">
							<?php
							settings_fields( 'bnb_' . $active_tab );
							if ( 'display' === $active_tab ) {
								self::render_display_fields();
							} elseif ( 'buddyboss' === $active_tab && self::is_buddyboss_active() ) {
								self::render_buddyboss_fields();
							} else {
								self::render_general_fields();
							}
							?>
							<div class="bnb-settings-footer">
								<p class="bnb-footer-hint"><?php esc_html_e( 'Changes apply on the next page load for members.', 'buddy-notification-bell' ); ?></p>
								<?php submit_button( __( 'Save Changes', 'buddy-notification-bell' ) ); ?>
							</div>
						</form>
					<?php endif; ?>
				</div>

			</div>
		</div>
		<?php
	}

	/**
	 * Renders the General tab fields.
	 *
	 * @return void
	 */
	private static function render_general_fields() {
		$sound    = get_option( 'bnb_sound_enabled', 'yes' );
		$count    = get_option( 'bnb_show_count', 'yes' );
		$position = get_option( 'bnb_bell_position', 'right' );
		$style    = get_option( 'bnb_notification_style', 'individual' );
		?>
		<div class="bnb-settings-list">
			<?php
			self::render_setting_open(
				__( 'Notification Sound', 'buddy-notification-bell' ),
				__( 'Play a sound alert when a new notification arrives.', 'buddy-notification-bell' ),
				'',
				'sound'
			);
			self::render_switch( 'bnb_sound_enabled', $sound, __( 'Notification Sound', 'buddy-notification-bell' ) );
			self::render_setting_close();

			self::render_setting_open(
				__( 'Count Badge', 'buddy-notification-bell' ),
				__( 'Show the unread notification count on the bell.', 'buddy-notification-bell' ),
				'',
				'badge'
			);
			self::render_switch( 'bnb_show_count', $count, __( 'Count Badge', 'buddy-notification-bell' ) );
			self::render_setting_close();

			self::render_setting_open(
				__( 'Bell Position in Menu', 'buddy-notification-bell' ),
				__( 'Where the bell sits inside the primary navigation menu.', 'buddy-notification-bell' ),
				'',
				'position'
			);
			?>
			<div class="bnb-segment" role="radiogroup" aria-label="<?php esc_attr_e( 'Bell Position in Menu', 'buddy-notification-bell' ); ?>">
				<label class="bnb-segment-option">
					<input type="radio" name="bnb_bell_position" value="left" <?php checked( 'left', $position ); ?>>
					<span class="bnb-segment-label">
						<span class="bnb-segment-title"><?php esc_html_e( 'Left', 'buddy-notification-bell' ); ?></span>
						<span class="bnb-segment-meta"><?php esc_html_e( 'Before items', 'buddy-notification-bell' ); ?></span>
					</span>
				</label>
				<label class="bnb-segment-option">
					<input type="radio" name="bnb_bell_position" value="right" <?php checked( 'right', $position ); ?>>
					<span class="bnb-segment-label">
						<span class="bnb-segment-title"><?php esc_html_e( 'Right', 'buddy-notification-bell' ); ?></span>
						<span class="bnb-segment-meta"><?php esc_html_e( 'After items', 'buddy-notification-bell' ); ?></span>
					</span>
				</label>
			</div>
			<?php
			self::render_setting_close();

			self::render_setting_open(
				__( 'Notification List Style', 'buddy-notification-bell' ),
				__( 'Choose how notifications appear in the dropdown.', 'buddy-notification-bell' ),
				'stack',
				'style'
			);
			?>
			<div class="bnb-choices" role="radiogroup" aria-label="<?php esc_attr_e( 'Notification List Style', 'buddy-notification-bell' ); ?>">
				<label class="bnb-choice">
					<input type="radio" name="bnb_notification_style" value="individual" <?php checked( 'individual', $style ); ?>>
					<span class="bnb-choice-preview bnb-choice-preview--individual" aria-hidden="true">
						<span></span><span></span><span></span>
					</span>
					<span class="bnb-choice-body">
						<span class="bnb-choice-title"><?php esc_html_e( 'Individual', 'buddy-notification-bell' ); ?></span>
						<span class="bnb-choice-text"><?php esc_html_e( 'Show each notification on its own row, the way BuddyPress lists them.', 'buddy-notification-bell' ); ?></span>
					</span>
					<span class="bnb-choice-mark" aria-hidden="true"></span>
				</label>
				<label class="bnb-choice">
					<input type="radio" name="bnb_notification_style" value="grouped" <?php checked( 'grouped', $style ); ?>>
					<span class="bnb-choice-preview bnb-choice-preview--grouped" aria-hidden="true">
						<span class="bnb-choice-badge">3</span>
						<span></span>
					</span>
					<span class="bnb-choice-body">
						<span class="bnb-choice-title"><?php esc_html_e( 'Grouped', 'buddy-notification-bell' ); ?></span>
						<span class="bnb-choice-text"><?php esc_html_e( 'Combine similar notifications into one summary, such as "3 friendship requests".', 'buddy-notification-bell' ); ?></span>
					</span>
					<span class="bnb-choice-mark" aria-hidden="true"></span>
				</label>
			</div>
			<?php self::render_setting_close(); ?>
		</div>
		<?php
	}

	/**
	 * Renders the Display tab fields.
	 *
	 * @return void
	 */
	private static function render_display_fields() {
		$disable_menu = get_option( 'make_default_visible', '' );
		$floating     = get_option( 'bnb_floating_bell', '' );
		?>
		<div class="bnb-settings-list">
			<?php
			self::render_setting_open(
				__( 'Disable Menu Bell', 'buddy-notification-bell' ),
				__( 'Stop adding the bell to the primary menu. Place it yourself with the shortcode.', 'buddy-notification-bell' ),
				'',
				'menu'
			);
			self::render_switch( 'make_default_visible', $disable_menu, __( 'Disable Menu Bell', 'buddy-notification-bell' ) );
			self::render_setting_close();

			self::render_setting_open(
				__( 'Floating Bell', 'buddy-notification-bell' ),
				__( 'Pin a bell button to the bottom-right corner. Useful on block themes that do not use a classic menu.', 'buddy-notification-bell' ),
				'',
				'float'
			);
			self::render_switch( 'bnb_floating_bell', $floating, __( 'Floating Bell', 'buddy-notification-bell' ) );
			self::render_setting_close();
			?>
		</div>
		<?php
	}

	/**
	 * Renders the BuddyBoss tab fields.
	 *
	 * @return void
	 */
	private static function render_buddyboss_fields() {
		$mode = get_option( 'bnb_buddyboss_mode', '' );
		?>
		<div class="bnb-settings-list">
			<?php
			self::render_setting_open(
				__( 'BuddyBoss Notification Panel', 'buddy-notification-bell' ),
				__( 'Open the BuddyBoss notification panel when the bell is clicked, instead of the built-in dropdown.', 'buddy-notification-bell' ),
				'',
				'boss'
			);
			self::render_switch( 'bnb_buddyboss_mode', $mode, __( 'BuddyBoss Notification Panel', 'buddy-notification-bell' ) );
			self::render_setting_close();
			?>
		</div>
		<div class="bnb-note">
			<p><?php esc_html_e( 'BuddyBoss notifications are not real-time. The bell still detects new notifications and plays the sound, then hands the window to BuddyBoss.', 'buddy-notification-bell' ); ?></p>
			<p>
				<?php esc_html_e( 'To target a custom BuddyBoss template, override the panel selector with this filter:', 'buddy-notification-bell' ); ?>
				<code>bd_bnb_buddyboss_notification_selector</code>
			</p>
		</div>
		<?php
	}

	/**
	 * Opens a setting row. Call render_setting_close() after the control.
	 *
	 * @param string $title       Setting title.
	 * @param string $description Help text under the title.
	 * @param string $modifier    Optional layout modifier, such as "stack".
	 * @param string $icon        Optional icon key for the tile mark.
	 * @return void
	 */
	private static function render_setting_open( $title, $description, $modifier = '', $icon = '' ) {
		$class = 'bnb-setting';
		if ( '' !== $modifier ) {
			$class .= ' bnb-setting--' . sanitize_html_class( $modifier );
		}
		if ( '' !== $icon ) {
			$class .= ' bnb-setting--icon-' . sanitize_html_class( $icon );
		}
		?>
		<section class="<?php echo esc_attr( $class ); ?>">
			<div class="bnb-setting-copy">
				<span class="bnb-setting-icon" aria-hidden="true"></span>
				<div class="bnb-setting-copy-text">
					<h2><?php echo esc_html( $title ); ?></h2>
					<?php if ( '' !== $description ) : ?>
						<p><?php echo esc_html( $description ); ?></p>
					<?php endif; ?>
				</div>
			</div>
			<div class="bnb-setting-control">
		<?php
	}

	/**
	 * Closes a setting row opened by render_setting_open().
	 *
	 * @return void
	 */
	private static function render_setting_close() {
		?>
			</div>
		</section>
		<?php
	}

	/**
	 * Renders an on/off switch. Checked value is "yes".
	 *
	 * @param string $name  Input name.
	 * @param string $value Stored option value.
	 * @param string $label Accessible name for the switch.
	 * @return void
	 */
	private static function render_switch( $name, $value, $label ) {
		?>
		<label class="bnb-switch">
			<input
				type="checkbox"
				class="bnb-switch-input"
				name="<?php echo esc_attr( $name ); ?>"
				value="yes"
				aria-label="<?php echo esc_attr( $label ); ?>"
				<?php checked( 'yes', $value ); ?>
			>
			<span class="bnb-switch-ui" aria-hidden="true"></span>
		</label>
		<?php
	}

	/**
	 * Render the Shortcode info tab (no form, just info).
	 *
	 * @return void
	 */
	private static function render_shortcode_tab() {
		$shortcode   = '[buddy_notification_bell]';
		$php_usage   = "<?php echo do_shortcode( '[buddy_notification_bell]' ); ?>";
		$icon_filter = "add_filter( 'buddy_bell_icon', function( \$icon ) {\n\treturn '<i class=\"fas fa-bell\"></i>';\n} );";
		?>
		<div class="bnb-shortcode-info">
			<article class="bnb-info-card">
				<div class="bnb-info-card-head">
					<h2><?php esc_html_e( 'Place the bell anywhere', 'buddy-notification-bell' ); ?></h2>
				</div>
				<p><?php esc_html_e( 'Use this shortcode in pages, widgets, or theme templates.', 'buddy-notification-bell' ); ?></p>
				<div class="bnb-shortcode-box">
					<code><?php echo esc_html( $shortcode ); ?></code>
					<button type="button" class="bnb-copy-btn" data-clipboard="<?php echo esc_attr( $shortcode ); ?>">
						<?php esc_html_e( 'Copy', 'buddy-notification-bell' ); ?>
					</button>
				</div>
			</article>

			<article class="bnb-info-card">
				<div class="bnb-info-card-head">
					<h2><?php esc_html_e( 'PHP template', 'buddy-notification-bell' ); ?></h2>
					<button type="button" class="bnb-copy-btn bnb-copy-btn--quiet" data-clipboard="<?php echo esc_attr( $php_usage ); ?>">
						<?php esc_html_e( 'Copy', 'buddy-notification-bell' ); ?>
					</button>
				</div>
				<p><?php esc_html_e( 'Print the bell from a theme template.', 'buddy-notification-bell' ); ?></p>
				<pre class="bnb-code-block"><code><?php echo esc_html( $php_usage ); ?></code></pre>
			</article>

			<article class="bnb-info-card">
				<div class="bnb-info-card-head">
					<h2><?php esc_html_e( 'Custom bell icon', 'buddy-notification-bell' ); ?></h2>
					<button type="button" class="bnb-copy-btn bnb-copy-btn--quiet" data-clipboard="<?php echo esc_attr( $icon_filter ); ?>">
						<?php esc_html_e( 'Copy', 'buddy-notification-bell' ); ?>
					</button>
				</div>
				<p><?php esc_html_e( 'Replace the default icon from your theme functions.php.', 'buddy-notification-bell' ); ?></p>
				<pre class="bnb-code-block"><code><?php echo esc_html( $icon_filter ); ?></code></pre>
			</article>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Sanitize callbacks
	// -------------------------------------------------------------------------

	/**
	 * Sanitizes a yes/no checkbox — returns 'yes' or empty string.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public static function sanitize_yes_no( $value ) {
		return ( 'yes' === $value ) ? 'yes' : '';
	}

	/**
	 * Sanitizes the notification style — returns 'individual' or 'grouped'.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public static function sanitize_notification_style( $value ) {
		return in_array( $value, array( 'individual', 'grouped' ), true ) ? $value : 'individual';
	}

	/**
	 * Sanitizes the bell position — returns 'right' or 'left'.
	 *
	 * @param mixed $value
	 * @return string
	 */
	public static function sanitize_position( $value ) {
		$allowed = array( 'right', 'left' );
		return in_array( $value, $allowed, true ) ? $value : 'right';
	}
}
