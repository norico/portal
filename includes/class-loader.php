<?php
/**
 * Portal Plugin Loader
 * 
 * Handles plugin initialization and hook registration.
 * 
 * @package Portal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Portal_Loader class
 * 
 * Central class for loading and managing plugin components.
 */
class Portal_Loader {

	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	public const VERSION = '1.1.0';

	/**
	 * Required PHP version.
	 *
	 * @var string
	 */
	public const REQUIRED_PHP = '8.2';

	/**
	 * Required WordPress version.
	 *
	 * @var string
	 */
	public const REQUIRED_WP = '6.5';

	/**
	 * Plugin file path.
	 *
	 * @var string
	 */
	private string $plugin_file;

	/**
	 * Plugin directory.
	 *
	 * @var string
	 */
	private string $plugin_dir;

	/**
	 * Plugin URL.
	 *
	 * @var string
	 */
	private string $plugin_url;

	/**
	 * Plugin basename.
	 *
	 * @var string
	 */
	private string $plugin_basename;

	/**
	 * Constructor.
	 *
	 * @param string $plugin_file Path to the main plugin file.
	 */
	public function __construct( string $plugin_file ) {
		$this->plugin_file     = $plugin_file;
		$this->plugin_dir      = plugin_dir_path( $plugin_file );
		$this->plugin_url      = plugin_dir_url( $plugin_file );
		$this->plugin_basename = plugin_basename( $plugin_file );
	}

	/**
	 * Initialize the plugin.
	 *
	 * @return void
	 */
	public function init(): void {
		// Add plugin action links.
		add_filter( "plugin_action_links_{$this->plugin_basename}", [ $this, 'add_plugin_action_links' ] );

		// Add plugin row meta.
		add_filter( 'plugin_row_meta', [ $this, 'add_plugin_row_meta' ], 10, 2 );

		// Load shortcode (needed on frontend and admin).
		$this->load_shortcode();

		// Load admin functionality.
		if ( is_admin() ) {
			$this->load_admin();
		}
	}


	/**
	 * Add settings link to plugin action links.
	 *
	 * @param array $links Plugin action links.
	 * @return array Modified plugin action links.
	 */
	public function add_plugin_action_links( array $links ): array {
		$settings_url  = admin_url( 'admin.php?page=portal-settings' );
		// 'default' uses WordPress core translations ("Réglages" in fr_FR).
		// Core textdomain is loaded early in the bootstrap, so this is safe
		// on any hook, no _load_textdomain_just_in_time notice.
		$settings_link = '<a href="' . esc_url( $settings_url ) . '">' . __( 'Settings', 'default' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Add documentation link to plugin row meta.
	 *
	 * @param array  $links Plugin row meta links.
	 * @param string $file  Plugin file.
	 * @return array Modified plugin row meta links.
	 */
	public function add_plugin_row_meta( array $links, string $file ): array {
		if ( $this->plugin_basename === $file ) {
			// Safe to translate here: this filter runs when the plugins list
			// table is rendered, well after 'init' (textdomain loaded).
			$links[] = '<a href="https://github.com/norico/portal/docs/" target="_blank">' . __( 'Documentation', 'portal' ) . '</a>';
		}
		return $links;
	}

	/**
	 * Load shortcode functionality.
	 *
	 * @return void
	 */
	private function load_shortcode(): void {
		if ( file_exists( $this->plugin_dir . 'includes/class-shortcode.php' ) ) {
			require_once $this->plugin_dir . 'includes/class-shortcode.php';
			new Portal_Shortcode();
		}
	}

	/**
	 * Load admin functionality.
	 *
	 * @return void
	 */
	private function load_admin(): void {
		// Load admin class.
		require_once $this->plugin_dir . 'includes/admin/class-admin.php';

		// Initialize admin.
		$admin = new Portal_Admin();
		$admin->init();
	}

	/**
	 * Check if plugin requirements are met.
	 *
	 * @return bool True if requirements are met, false otherwise.
	 */
	public function check_requirements(): bool {
		// Check PHP version.
		if ( version_compare( PHP_VERSION, self::REQUIRED_PHP, '<' ) ) {
			return false;
		}

		// Check WordPress version.
		if ( version_compare( get_bloginfo( 'version' ), self::REQUIRED_WP, '<' ) ) {
			return false;
		}

		return true;
	}
}
