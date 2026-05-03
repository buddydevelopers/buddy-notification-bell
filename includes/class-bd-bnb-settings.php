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
		// General section.
		add_settings_section(
			'bnb_general',
			__( 'General', 'buddy-notification-bell' ),
			'__return_false',
			'bnb_general'
		);

		register_setting( 'bnb_general', 'bnb_sound_enabled', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
			'default'           => 'yes',
		) );
		register_setting( 'bnb_general', 'bnb_show_count', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
			'default'           => 'yes',
		) );
		register_setting( 'bnb_general', 'bnb_bell_position', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_position' ),
			'default'           => 'right',
		) );
		register_setting( 'bnb_general', 'bnb_notification_style', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_notification_style' ),
			'default'           => 'individual',
		) );

		add_settings_field(
			'bnb_sound_enabled',
			__( 'Notification Sound', 'buddy-notification-bell' ),
			array( __CLASS__, 'field_sound_enabled' ),
			'bnb_general',
			'bnb_general'
		);
		add_settings_field(
			'bnb_show_count',
			__( 'Count Badge', 'buddy-notification-bell' ),
			array( __CLASS__, 'field_show_count' ),
			'bnb_general',
			'bnb_general'
		);
		add_settings_field(
			'bnb_bell_position',
			__( 'Bell Position in Menu', 'buddy-notification-bell' ),
			array( __CLASS__, 'field_bell_position' ),
			'bnb_general',
			'bnb_general'
		);
		add_settings_field(
			'bnb_notification_style',
			__( 'Notification List Style', 'buddy-notification-bell' ),
			array( __CLASS__, 'field_notification_style' ),
			'bnb_general',
			'bnb_general'
		);

		// Display section.
		add_settings_section(
			'bnb_display',
			__( 'Display', 'buddy-notification-bell' ),
			'__return_false',
			'bnb_display'
		);

		register_setting( 'bnb_display', 'make_default_visible', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
			'default'           => '',
		) );
		register_setting( 'bnb_display', 'bnb_floating_bell', array(
			'sanitize_callback' => array( __CLASS__, 'sanitize_yes_no' ),
			'default'           => '',
		) );

		add_settings_field(
			'make_default_visible',
			__( 'Disable Auto Bell in Menu', 'buddy-notification-bell' ),
			array( __CLASS__, 'field_disable_auto_bell' ),
			'bnb_display',
			'bnb_display'
		);
		add_settings_field(
			'bnb_floating_bell',
			__( 'Show Floating Bell', 'buddy-notification-bell' ),
			array( __CLASS__, 'field_floating_bell' ),
			'bnb_display',
			'bnb_display'
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
			'bnb-admin-style',
			BNB_URL . 'assets/css/bnb-admin.css',
			array(),
			BNB_VERSION
		);

		wp_enqueue_script(
			'bnb-admin',
			BNB_URL . 'assets/js/bnb-admin.js',
			array( 'jquery' ),
			BNB_VERSION,
			true
		);
	}

	/**
	 * Render the settings page with tabs.
	 */
	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap bnb-settings-wrap">

			<div class="bnb-settings-header">
				<svg class="bnb-settings-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" aria-hidden="true">
					<path fill="currentColor" d="M439.39 362.29c-19.32-20.76-55.47-51.99-55.47-154.29 0-77.7-54.48-139.9-127.94-155.16V32c0-17.67-14.32-32-31.98-32s-31.98 14.33-31.98 32v20.84C118.56 68.1 64.08 130.3 64.08 208c0 102.3-36.15 133.53-55.47 154.29-6 6.45-8.66 14.16-8.61 21.71.11 16.4 12.98 32 32.1 32h383.8c19.12 0 32-15.6 32.1-32 .05-7.55-2.61-15.27-8.61-21.71zM224 512c35.32 0 63.97-28.65 63.97-64H160.03c0 35.35 28.65 64 63.97 64z"/>
				</svg>
				<div class="bnb-settings-header-text">
					<h1><?php esc_html_e( 'Notification Bell', 'buddy-notification-bell' ); ?></h1>
					<p class="bnb-settings-version"><?php echo esc_html( sprintf( __( 'Version %s', 'buddy-notification-bell' ), BNB_VERSION ) ); ?></p>
				</div>
			</div>

			<nav class="nav-tab-wrapper bnb-nav-tabs">
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddy-notification-bell&tab=general' ) ); ?>"
				   class="nav-tab <?php echo 'general' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'General', 'buddy-notification-bell' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddy-notification-bell&tab=display' ) ); ?>"
				   class="nav-tab <?php echo 'display' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Display', 'buddy-notification-bell' ); ?>
				</a>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=buddy-notification-bell&tab=shortcode' ) ); ?>"
				   class="nav-tab <?php echo 'shortcode' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<?php esc_html_e( 'Shortcode', 'buddy-notification-bell' ); ?>
				</a>
			</nav>

			<div class="bnb-settings-body">
				<?php if ( 'shortcode' === $active_tab ) : ?>
					<?php self::render_shortcode_tab(); ?>
				<?php else : ?>
					<form method="post" action="options.php">
						<?php
						settings_fields( 'bnb_' . $active_tab );
						do_settings_sections( 'bnb_' . $active_tab );
						submit_button( __( 'Save Changes', 'buddy-notification-bell' ) );
						?>
					</form>
				<?php endif; ?>
			</div>

		</div>
		<?php
	}

	/**
	 * Render the Shortcode info tab (no form, just info).
	 */
	private static function render_shortcode_tab() {
		?>
		<div class="bnb-shortcode-info">
			<div class="bnb-info-card">
				<h3><?php esc_html_e( 'Place the Bell Anywhere', 'buddy-notification-bell' ); ?></h3>
				<p><?php esc_html_e( 'Use the shortcode below to place the notification bell anywhere on your site — pages, widgets, or theme templates.', 'buddy-notification-bell' ); ?></p>
				<div class="bnb-shortcode-box">
					<code>[buddy_notification_bell]</code>
					<button class="bnb-copy-btn button" data-clipboard="[buddy_notification_bell]">
						<?php esc_html_e( 'Copy', 'buddy-notification-bell' ); ?>
					</button>
				</div>
			</div>

			<div class="bnb-info-card">
				<h3><?php esc_html_e( 'PHP Template Usage', 'buddy-notification-bell' ); ?></h3>
				<p><?php esc_html_e( 'You can also call it directly from your theme templates:', 'buddy-notification-bell' ); ?></p>
				<div class="bnb-shortcode-box">
					<code>&lt;?php echo do_shortcode( \'[buddy_notification_bell]\' ); ?&gt;</code>
				</div>
			</div>

			<div class="bnb-info-card">
				<h3><?php esc_html_e( 'Custom Bell Icon', 'buddy-notification-bell' ); ?></h3>
				<p><?php esc_html_e( 'Override the default bell icon using this filter in your theme\'s functions.php:', 'buddy-notification-bell' ); ?></p>
				<div class="bnb-shortcode-box">
					<code>add_filter( 'buddy_bell_icon', function( $icon ) {<br>&nbsp;&nbsp;return '&lt;i class="fas fa-bell"&gt;&lt;/i&gt;';<br>} );</code>
				</div>
			</div>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// Field renderers
	// -------------------------------------------------------------------------

	public static function field_sound_enabled() {
		$value = get_option( 'bnb_sound_enabled', 'yes' );
		?>
		<label>
			<input type="checkbox" name="bnb_sound_enabled" value="yes" <?php checked( 'yes', $value ); ?>>
			<?php esc_html_e( 'Play a sound alert when a new notification arrives', 'buddy-notification-bell' ); ?>
		</label>
		<?php
	}

	public static function field_show_count() {
		$value = get_option( 'bnb_show_count', 'yes' );
		?>
		<label>
			<input type="checkbox" name="bnb_show_count" value="yes" <?php checked( 'yes', $value ); ?>>
			<?php esc_html_e( 'Show unread notification count badge on the bell', 'buddy-notification-bell' ); ?>
		</label>
		<?php
	}

	public static function field_bell_position() {
		$value = get_option( 'bnb_bell_position', 'right' );
		?>
		<select name="bnb_bell_position">
			<option value="right" <?php selected( 'right', $value ); ?>><?php esc_html_e( 'Right (after menu items)', 'buddy-notification-bell' ); ?></option>
			<option value="left" <?php selected( 'left', $value ); ?>><?php esc_html_e( 'Left (before menu items)', 'buddy-notification-bell' ); ?></option>
		</select>
		<p class="description"><?php esc_html_e( 'Position of the bell within the primary navigation menu.', 'buddy-notification-bell' ); ?></p>
		<?php
	}

	public static function field_disable_auto_bell() {
		$value = get_option( 'make_default_visible', '' );
		?>
		<label>
			<input type="checkbox" name="make_default_visible" value="yes" <?php checked( 'yes', $value ); ?>>
			<?php esc_html_e( 'Disable automatic bell injection into the primary menu', 'buddy-notification-bell' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'When checked, use the shortcode to place the bell manually.', 'buddy-notification-bell' ); ?></p>
		<?php
	}

	public static function field_notification_style() {
		$value = get_option( 'bnb_notification_style', 'individual' );
		?>
		<fieldset>
			<label style="display:block; margin-bottom:6px;">
				<input type="radio" name="bnb_notification_style" value="individual" <?php checked( 'individual', $value ); ?>>
				<?php esc_html_e( 'Individual — show each notification separately (like BuddyPress)', 'buddy-notification-bell' ); ?>
			</label>
			<label style="display:block;">
				<input type="radio" name="bnb_notification_style" value="grouped" <?php checked( 'grouped', $value ); ?>>
				<?php esc_html_e( 'Grouped — combine similar notifications (e.g. "3 friendship requests")', 'buddy-notification-bell' ); ?>
			</label>
		</fieldset>
		<p class="description"><?php esc_html_e( 'Individual shows a separate item for every notification. Grouped collapses the same type into one summary item.', 'buddy-notification-bell' ); ?></p>
		<?php
	}

	public static function field_floating_bell() {
		$value = get_option( 'bnb_floating_bell', '' );
		?>
		<label>
			<input type="checkbox" name="bnb_floating_bell" value="yes" <?php checked( 'yes', $value ); ?>>
			<?php esc_html_e( 'Show a fixed floating bell button (bottom-right corner)', 'buddy-notification-bell' ); ?>
		</label>
		<p class="description"><?php esc_html_e( 'Useful for block/FSE themes that do not use a traditional navigation menu.', 'buddy-notification-bell' ); ?></p>
		<?php
	}

	// -------------------------------------------------------------------------
	// Sanitize callbacks
	// -------------------------------------------------------------------------

	public static function sanitize_yes_no( $value ) {
		return ( 'yes' === $value ) ? 'yes' : '';
	}

	public static function sanitize_notification_style( $value ) {
		return in_array( $value, array( 'individual', 'grouped' ), true ) ? $value : 'individual';
	}

	public static function sanitize_position( $value ) {
		$allowed = array( 'right', 'left' );
		return in_array( $value, $allowed, true ) ? $value : 'right';
	}
}
