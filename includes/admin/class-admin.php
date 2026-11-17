<?php
/**
 * Portal Admin
 * 
 * Handles admin-specific functionality.
 * 
 * @package Portal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Portal_Admin class
 * 
 * Manages admin menu, settings pages, and related functionality.
 */
class Portal_Admin {

	/**
	 * Plugin directory.
	 *
	 * @var string
	 */
	private string $plugin_dir;

	/**
	 * Available tabs for the settings page.
	 *
	 * @var array
	 */
	private array $tabs = [];

	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	private string $option_name = 'portal_settings';

	/**
	 * Reason why the last feed check failed (for diagnostics).
	 *
	 * @var string
	 */
	private string $last_feed_error = '';

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Get the path to includes/admin/ directory where templates are stored.
		$this->plugin_dir = plugin_dir_path( __FILE__ );
		
		// Define available tabs with their template files.
		// Titles are stored as plain (untranslated) strings because the
		// constructor runs on 'plugins_loaded', before the textdomain is
		// loaded on 'init' (would trigger _load_textdomain_just_in_time on
		// WordPress 6.7+). They are translated when rendering in
		// render_tab_navigation(), well after 'init'.
		$this->tabs = [
			'general'  => [
				'title_key' => 'General',
				'template'  => 'tabs/general.php',
			],
			'display'  => [
				'title_key' => 'Display',
				'template'  => 'tabs/display.php',
			],
		];
	}

	/**
	 * Initialize admin hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Add settings submenu page.
		add_action( 'admin_menu', [ $this, 'add_settings_submenu_page' ] );

		// Register settings.
		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}

	/**
	 * Add settings submenu page.
	 *
	 * @return void
	 */
	public function add_settings_submenu_page(): void {
		// Safe to translate here: the 'admin_menu' hook fires after 'init',
		// so the textdomain is already loaded.
		add_submenu_page(
			'options-general.php',                 // Parent: Settings menu.
			__( 'Portal Settings', 'portal' ),     // Page title.
			__( 'Portal', 'portal' ),              // Menu label.
			'manage_options',                      // Required capability.
			'portal-settings',                     // Page slug.
			[ $this, 'render_settings_page' ]      // Callback function.
		);
	}

	/**
	 * Register plugin settings.
	 *
	 * @return void
	 */
	public function register_settings(): void {
		// Register the main settings option.
		register_setting(
			'portal_settings_group',
			$this->option_name,
			[ $this, 'sanitize_settings' ]
		);
	}

	/**
	 * Sanitize settings before saving.
	 *
	 * @param array $input Settings to sanitize.
	 * @return array Sanitized settings.
	 */
	public function sanitize_settings( array $input ): array {
		// Sanitize each setting here as needed.
		// Example: $input['some_setting'] = sanitize_text_field( $input['some_setting'] ?? '' );
		return $input;
	}

	/**
	 * Get the current active tab.
	 *
	 * @return string
	 */
	private function get_current_tab(): string {
		// Get the tab from the URL, default to 'general'.
		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'general';
		
		// Validate the tab exists.
		if ( ! isset( $this->tabs[ $current_tab ] ) ) {
			$current_tab = 'general';
		}
		
		return $current_tab;
	}

	/**
	 * Render the tab navigation.
	 *
	 * @return void
	 */
	private function render_tab_navigation(): void {
		$current_tab = $this->get_current_tab();
		
		// Only render if we have more than one tab.
		if ( count( $this->tabs ) <= 1 ) {
			return;
		}
		
		echo '<h2 class="nav-tab-wrapper wp-clearfix">';
		
		foreach ( $this->tabs as $tab_slug => $tab ) {
			$active = ( $tab_slug === $current_tab ) ? 'nav-tab-active' : '';
			$url    = add_query_arg( [ 'tab' => $tab_slug ] );
			
			echo '<a href="' . esc_url( $url ) . '" class="nav-tab ' . esc_attr( $active ) . '">';
			// Translate the title key here (after init hook when textdomain is loaded).
			echo esc_html( __( $tab['title_key'], 'portal' ) );
			echo '</a>';
		}
		
		echo '</h2>';
	}

	/**
	 * Render the settings page with tabs.
	 *
	 * @return void
	 */
	public function render_settings_page(): void {
		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'portal' ) );
		}

		$current_tab = $this->get_current_tab();
		$settings    = get_option( $this->option_name, [] );
		
		// Handle form submission for tab-specific settings.
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['portal_save_settings'] ) ) {
			check_admin_referer( 'portal_save_settings' );
			
			// Get the tab from the form submission.
			$submitted_tab = isset( $_POST['portal_tab'] ) ? sanitize_key( $_POST['portal_tab'] ) : $current_tab;
			if ( isset( $this->tabs[ $submitted_tab ] ) ) {
				$current_tab = $submitted_tab;
			}
			
			// Sanitize tab settings.
			$tab_settings = $this->sanitize_tab_settings( $current_tab, $_POST );
			
			// Update settings (only if we have valid data or other fields exist).
			$settings[ $current_tab ] = $tab_settings;
			update_option( $this->option_name, $settings );
			
			// Check if we have sites and add appropriate message.
			if ( isset( $tab_settings['portal_sites'] ) && ! empty( $tab_settings['portal_sites'] ) ) {
				$valid_count = 0;
				foreach ( $tab_settings['portal_sites'] as $site ) {
					if ( $site['valid'] ) {
						$valid_count++;
					}
				}
				$total_count = count( $tab_settings['portal_sites'] );
				
				if ( $valid_count === $total_count ) {
					add_settings_error( 'portal_settings_group', 'settings_saved', _n( 'Settings saved. 1 site with valid RSS feed.', 'Settings saved. All %d sites have valid RSS feeds.', $total_count, 'portal' ), 'success' );
				} elseif ( $valid_count > 0 ) {
					add_settings_error( 'portal_settings_group', 'settings_saved', sprintf( _n( 'Settings saved. %1$d of %2$d site has valid RSS feed.', 'Settings saved. %1$d of %2$d sites have valid RSS feeds.', $total_count, 'portal' ), $valid_count, $total_count ), 'success' );
				} else {
					add_settings_error( 'portal_settings_group', 'settings_saved', sprintf( _n( 'Settings saved. %d site configured but no valid RSS feeds found.', 'Settings saved. %d sites configured but no valid RSS feeds found.', $total_count, 'portal' ), $total_count ), 'warning' );
				}
			} else {
				// Only add success if there are other settings saved
				if ( ! empty( array_filter( $tab_settings ) ) ) {
					add_settings_error( 'portal_settings_group', 'settings_saved', __( 'Settings saved.', 'portal' ), 'success' );
				}
			}
		}
		
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<?php
			// Display WordPress settings errors/notices.
			settings_errors( 'portal_settings_group' );
			?>
			
			<?php $this->render_tab_navigation(); ?>
			
			<form method="post" action="">
				<?php
				// Output security field - use a single nonce for all tabs.
				wp_nonce_field( 'portal_save_settings' );
				
				// Keep current tab in hidden field.
				echo '<input type="hidden" name="portal_tab" value="' . esc_attr( $current_tab ) . '">';
				
				// Load and render the tab template file.
				if ( isset( $this->tabs[ $current_tab ]['template'] ) ) {
					$template_file = $this->plugin_dir . $this->tabs[ $current_tab ]['template'];
					if ( file_exists( $template_file ) ) {
						// Extract settings for this tab and make them available to the template.
						$settings = $settings[ $current_tab ] ?? [];
						// Include the template file.
						include $template_file;
					}
				}
				
				submit_button( __( 'Save Settings', 'portal' ) );
				
				echo '<input type="hidden" name="portal_save_settings" value="1">';
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Check if a URL has a valid RSS feed.
	 *
	 * @param string $site_url The site URL to check.
	 * @return string|false The valid feed URL, or false if none found.
	 */
	private function validate_rss_feed( string $site_url ): string|false {
		$this->last_feed_error = '';

		// Common WordPress feed paths to check.
		$standard_feed_paths = [
			'/feed/',
			'/feed/rss/',
			'/feed/rss2/',
			'/rss/',
			'/rss2/',
			'/wp-rss.php',
			'/wp-feed.php',
			'?feed=rss',
			'?feed=rss2',
		];
		
		// Parse the site URL.
		$parsed = wp_parse_url( $site_url );
		
		if ( empty( $parsed['host'] ) ) {
			return false;
		}
		
		// Build base URL and extract path (port included when present,
		// e.g. http://umbrel.local:8567).
		$scheme = isset( $parsed['scheme'] ) ? strtolower( $parsed['scheme'] ) . '://' : 'https://';
		$host   = $parsed['host'];
		$port   = isset( $parsed['port'] ) ? ':' . $parsed['port'] : '';
		$base   = $scheme . $host . $port;
		// Store path without leading/trailing slashes ('' when the URL has
		// no path). The relative feed paths below already add the leading
		// slash, so an empty path correctly yields '/feed/' and not
		// '//feed/'.
		$path = trim( $parsed['path'] ?? '', '/' );
		
		// Build relative feed paths based on the provided path.
		$relative_feed_paths = [
			$path . '/feed/',
			$path . '/feed/rss/',
			$path . '/feed/rss2/',
			$path . '/rss/',
			$path . '/rss2/',
		];
		
		// Try relative feed paths first (for multilingual sites like /fr/blog/)
		foreach ( $relative_feed_paths as $rel_path ) {
			$feed_url = $base . $rel_path;
			$valid = $this->check_feed_url( $feed_url );
			if ( $valid ) {
				return $feed_url;
			}
		}
		
		// Then try standard feed paths at root.
		foreach ( $standard_feed_paths as $std_path ) {
			$feed_url = $base . $std_path;
			$valid = $this->check_feed_url( $feed_url );
			if ( $valid ) {
				return $feed_url;
			}
		}
		
		return false;
	}

	/**
	 * Check if a feed URL is valid and returns RSS/XML content.
	 *
	 * @param string $feed_url The feed URL to check.
	 * @return bool True if valid RSS feed, false otherwise.
	 */
	private function check_feed_url( string $feed_url ): bool {
		// Use wp_remote_get to fetch the feed (with timeout).
		$response = wp_remote_get( $feed_url, [
			'timeout'     => 10,
			'sslverify'   => false,
			'user-agent'  => 'Portal Plugin RSS Validator',
			'headers'     => [
				'Accept' => 'application/rss+xml, application/xml, text/xml',
			],
		] );
		
		// Check for errors.
		if ( is_wp_error( $response ) ) {
			$this->last_feed_error = $response->get_error_message();
			return false;
		}

		// Check response code.
		$code = wp_remote_retrieve_response_code( $response );
		if ( $code !== 200 ) {
			$this->last_feed_error = 'HTTP ' . $code;
			return false;
		}

		// Check content type.
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( $content_type && ! preg_match( '/(rss|xml|atom)\s*[\/;]/i', $content_type ) ) {
			$this->last_feed_error = 'content-type: ' . $content_type;
			return false;
		}

		// Check if body contains RSS/XML markers.
		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			$this->last_feed_error = 'empty response body';
			return false;
		}

		// Look for RSS/Atom markers in the content.
		$found = ( stripos( $body, '<rss' ) !== false ||
				stripos( $body, '<feed' ) !== false ||
				stripos( $body, '<channel>' ) !== false ||
				stripos( $body, '<?xml' ) !== false );

		if ( ! $found ) {
			$this->last_feed_error = 'no RSS/XML markers in body';
		}

		return $found;
	}

	/**
	 * Sanitize settings for a specific tab.
	 *
	 * @param string $tab    Tab slug.
	 * @param array  $input  Raw input data.
	 * @return array Sanitized settings.
	 */
	private function sanitize_tab_settings( string $tab, array $input ): array {
		$sanitized = [];
		
		// Sanitize regular portal_* fields.
		foreach ( $input as $key => $value ) {
			if ( str_starts_with( $key, 'portal_' ) && 'portal_sites' !== $key ) {
				if ( is_array( $value ) ) {
					$sanitized[ $key ] = array_map( 'sanitize_text_field', $value );
				} else {
					$sanitized[ $key ] = sanitize_text_field( $value );
				}
			}
		}
		
		// Handle WordPress sites array.
		if ( isset( $input['portal_sites'] ) ) {
			$sites = [];
			$invalid_sites = [];
			
			// Ensure it's an array
			if ( ! is_array( $input['portal_sites'] ) ) {
				$input['portal_sites'] = [ $input['portal_sites'] ];
			}
			
			// Sanitize and validate each site URL
			foreach ( $input['portal_sites'] as $url ) {
				$url = trim( $url );
				
				// Skip empty URLs
				if ( empty( $url ) ) {
					continue;
				}
				
				// Validate URL format
				$parsed_url = wp_parse_url( $url );
				
				// Add scheme if missing (default to https)
				if ( empty( $parsed_url['scheme'] ) ) {
					$url = 'https://' . ltrim( $url, '/' );
					$parsed_url = wp_parse_url( $url );
				}
				
				// Validate scheme
				if ( ! isset( $parsed_url['scheme'] ) || ! in_array( strtolower( $parsed_url['scheme'] ), [ 'http', 'https' ], true ) ) {
					$invalid_sites[] = $url;
					continue;
				}
				
				// Validate host
				if ( empty( $parsed_url['host'] ) ) {
					$invalid_sites[] = $url;
					continue;
				}
				
				// Sanitize and normalize URL
				$sanitized_url = esc_url_raw( $url );
				$sanitized_url = rtrim( $sanitized_url, '/' );
				
				// Validate RSS feed exists
				$feed_url = $this->validate_rss_feed( $sanitized_url );
				
				if ( $feed_url ) {
					// Store site with its feed URL
					$sites[] = [
						'site_url' => $sanitized_url,
						'feed_url' => $feed_url,
						'valid'    => true,
					];
				} else {
					// Mark as invalid but still save it
					$sites[] = [
						'site_url' => $sanitized_url,
						'feed_url' => false,
						'valid'    => false,
					];
					$invalid_sites[] = $sanitized_url;
				}
			}
			
			// Add errors for invalid sites
			if ( ! empty( $invalid_sites ) ) {
				foreach ( $invalid_sites as $invalid_site ) {
					add_settings_error(
						'portal_settings_group',
						'invalid_feed_' . sanitize_title( $invalid_site ),
						sprintf(
							__( 'No valid RSS feed found for: %s', 'portal' ),
							esc_url( $invalid_site )
						) . '<br>' . sprintf(
							__( 'Reason: %s', 'portal' ),
							esc_html( $this->last_feed_error )
						),
						'warning'
					);
				}
			}
			
			// Ensure at least one valid site
			$has_valid = false;
			foreach ( $sites as $site ) {
				if ( $site['valid'] ) {
					$has_valid = true;
					break;
				}
			}
			
			if ( ! $has_valid && ! empty( $sites ) ) {
				add_settings_error(
					'portal_settings_group',
					'no_valid_feeds',
					__( 'Warning: None of the entered sites have a valid RSS feed.', 'portal' ),
					'error'
				);
			}
			
			// Store sites even if some are invalid (user can see warnings)
			if ( ! empty( $sites ) ) {
				$sanitized['portal_sites'] = $sites;
			}
		}
		
		return $sanitized;
	}
}
