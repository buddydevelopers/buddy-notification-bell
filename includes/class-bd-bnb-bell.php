<?php
/**
 * Frontend bell: shortcode, menu injection, asset enqueue, HTML output.
 *
 * @package Buddy_Notification_Bell
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

class BD_BNB_Bell {

	public static function init() {
		add_shortcode( 'buddy_notification_bell', array( __CLASS__, 'shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
		add_action( 'wp_footer', array( __CLASS__, 'maybe_render_floating' ) );

		if ( ! self::is_block_theme() ) {
			add_filter( 'wp_nav_menu_items', array( __CLASS__, 'inject_into_menu' ), 10, 2 );
		}
	}

	/**
	 * Returns true when the active theme is a block (FSE) theme.
	 *
	 * @return bool
	 */
	private static function is_block_theme() {
		return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme();
	}

	/**
	 * Enqueue frontend assets and pass settings to JS.
	 */
	public static function enqueue() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		wp_enqueue_style(
			'bnb-bell-style',
			BNB_URL . 'assets/css/bnb-bell.css',
			array(),
			BNB_VERSION
		);

		wp_enqueue_script(
			'bnb-bell',
			BNB_URL . 'assets/js/bnb-bell.js',
			array( 'jquery', 'heartbeat' ),
			BNB_VERSION,
			true
		);

		$buddyboss_active = defined( 'BP_PLATFORM_VERSION' );
		$buddyboss_mode   = $buddyboss_active && 'yes' === get_option( 'bnb_buddyboss_mode', '' );

		$js_data = apply_filters( 'bd_bnb_get_js_settings', array(
			'ajaxUrl'           => admin_url( 'admin-ajax.php' ),
			'nonce'             => wp_create_nonce( 'bnb_nonce' ),
			'lastNotified'      => BD_BNB_Manager::get_latest_notification_id(),
			'soundUrl'          => BNB_URL . 'assets/sounds/Pling-bell.mp3',
			'soundEnabled'      => get_option( 'bnb_sound_enabled', 'yes' ),
			'showCount'         => get_option( 'bnb_show_count', 'yes' ),
			'pollInterval'      => 30,
			'buddybossMode'           => $buddyboss_mode ? 'yes' : 'no',
			'buddybossSelector'       => apply_filters( 'bd_bnb_buddyboss_notification_selector', '#header-notifications-dropdown-elem > a' ),
			'buddybossMessageSelector' => apply_filters( 'bd_bnb_buddyboss_message_selector', '#header-messages-dropdown-elem .notification-link span:first' ),
			'i18n'              => array(
				'loading' => __( 'Loading...', 'buddy-notification-bell' ),
				'dismiss' => __( 'Dismiss', 'buddy-notification-bell' ),
			),
		) );

		wp_localize_script( 'bnb-bell', 'bnbData', $js_data );
	}

	/**
	 * Shortcode handler — supports size and position attributes.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ) {
		$atts = shortcode_atts(
			array(
				'size'     => 'normal',
				'position' => 'right',
			),
			$atts,
			'buddy_notification_bell'
		);

		$size     = in_array( $atts['size'], array( 'small', 'normal', 'large' ), true ) ? $atts['size'] : 'normal';
		$position = in_array( $atts['position'], array( 'left', 'right' ), true ) ? $atts['position'] : 'right';

		return self::render( $size, $position );
	}

	/**
	 * Injects the bell into the primary nav menu (classic themes only).
	 *
	 * @param string   $items
	 * @param stdClass $args
	 * @return string
	 */
	public static function inject_into_menu( $items, $args ) {
		if ( ! is_user_logged_in() ) {
			return $items;
		}
		
		$disable  = get_option( 'make_default_visible', '' );
		$location = apply_filters( 'buddy_theme_location', 'primary' );

		if ( isset( $args->theme_location ) && $location === $args->theme_location && 'yes' !== $disable ) {
			$items .= '<li class="bnb-menu-item">' . self::render() . '</li>';
		}

		return $items;
	}

	/**
	 * Renders floating bell via wp_footer when the setting is explicitly enabled.
	 */
	public static function maybe_render_floating() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		if ( 'yes' !== get_option( 'bnb_floating_bell', '' ) ) {
			return;
		}

		echo '<div class="bnb-floating-wrap">' . self::render() . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Renders the bell widget HTML shell (notification list loaded via AJAX).
	 *
	 * @param string $size     small|normal|large
	 * @param string $position left|right
	 * @return string
	 */
	public static function render( $size = 'normal', $position = 'right' ) {
		if ( ! is_user_logged_in() ) {
			return '';
		}

		// Allow themes/plugins to fully replace the output.
		$notifications = bp_notifications_get_notifications_for_user( bp_loggedin_user_id(), 'object' );
		$override      = apply_filters( 'buddy_notification_output', '', $notifications );
		if ( $override ) {
			return $override;
		}

		$count          = is_array( $notifications ) ? count( $notifications ) : 0;
		$show_count     = get_option( 'bnb_show_count', 'yes' );
		$all_url        = trailingslashit( bp_loggedin_user_domain() . bp_get_notifications_slug() );
		$bell_icon      = apply_filters( 'buddy_bell_icon', self::get_default_icon() );
		$buddyboss_mode = defined( 'BP_PLATFORM_VERSION' ) && 'yes' === get_option( 'bnb_buddyboss_mode', '' );

		$wrapper_class = implode( ' ', array(
			'bnb-bell-wrapper',
			'bnb-size-' . $size,
			'bnb-position-' . $position,
			$buddyboss_mode ? 'bnb-buddyboss-mode' : '',
		) );

		$allowed_icon_html = array(
			'svg'    => array( 'class' => true, 'xmlns' => true, 'viewbox' => true, 'aria-hidden' => true, 'focusable' => true ),
			'path'   => array( 'fill' => true, 'd' => true ),
			'circle' => array( 'cx' => true, 'cy' => true, 'r' => true, 'fill' => true, 'stroke' => true, 'stroke-width' => true ),
			'i'      => array( 'class' => true ),
			'span'   => array( 'class' => true ),
		);

		ob_start();
		?>
		<div class="<?php echo esc_attr( trim( $wrapper_class ) ); ?>"
		     role="navigation"
		     aria-label="<?php esc_attr_e( 'Notifications', 'buddy-notification-bell' ); ?>">

			<button
				class="bnb-bell-button"
				type="button"
				aria-label="<?php esc_attr_e( 'Notifications', 'buddy-notification-bell' ); ?>"
				aria-expanded="false"
				aria-haspopup="<?php echo esc_attr( $buddyboss_mode ? 'false' : 'true' ); ?>"
			>
				<?php echo wp_kses( $bell_icon, $allowed_icon_html ); ?>
				<?php if ( 'yes' === $show_count ) : ?>
				<span class="bnb-count"<?php echo ( $count > 0 ) ? '' : ' style="display:none;"'; ?>>
					<?php echo esc_html( number_format_i18n( $count ) ); ?>
				</span>
				<?php endif; ?>
			</button>

			<?php if ( ! $buddyboss_mode ) : ?>
			<div class="bnb-dropdown"
			     role="dialog"
			     aria-label="<?php esc_attr_e( 'Notifications panel', 'buddy-notification-bell' ); ?>"
			     hidden>

				<div class="bnb-dropdown-header">
					<span class="bnb-dropdown-title"><?php esc_html_e( 'Notifications', 'buddy-notification-bell' ); ?></span>
					<button class="bnb-mark-all-read" type="button">
						<?php esc_html_e( 'Mark all read', 'buddy-notification-bell' ); ?>
					</button>
				</div>

				<div class="bnb-notification-list">
					<div class="bnb-loading"><?php esc_html_e( 'Loading...', 'buddy-notification-bell' ); ?></div>
				</div>

				<div class="bnb-empty" style="display:none;">
					<p><?php esc_html_e( "You're all caught up!", 'buddy-notification-bell' ); ?></p>
				</div>

				<div class="bnb-dropdown-footer">
					<a href="<?php echo esc_url( $all_url ); ?>" class="bnb-see-all">
						<?php esc_html_e( 'See all notifications', 'buddy-notification-bell' ); ?>
					</a>
				</div>

			</div>
			<?php endif; ?>

		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Default bell SVG icon.
	 *
	 * @return string
	 */
	private static function get_default_icon() {
		return '<svg class="bnb-bell-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512" aria-hidden="true" focusable="false">
			<path fill="currentColor" d="M439.39 362.29c-19.32-20.76-55.47-51.99-55.47-154.29 0-77.7-54.48-139.9-127.94-155.16V32c0-17.67-14.32-32-31.98-32s-31.98 14.33-31.98 32v20.84C118.56 68.1 64.08 130.3 64.08 208c0 102.3-36.15 133.53-55.47 154.29-6 6.45-8.66 14.16-8.61 21.71.11 16.4 12.98 32 32.1 32h383.8c19.12 0 32-15.6 32.1-32 .05-7.55-2.61-15.27-8.61-21.71zM224 512c35.32 0 63.97-28.65 63.97-64H160.03c0 35.35 28.65 64 63.97 64z"/>
		</svg>';
	}
}
