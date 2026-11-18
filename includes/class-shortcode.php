<?php
/**
 * Portal Shortcode
 * 
 * Handles the shortcode to display aggregated RSS feed items.
 * 
 * @package Portal
 */

defined( 'ABSPATH' ) || exit;

/**
 * Portal_Shortcode class
 *
 * Manages the shortcode output for displaying aggregated RSS feeds.
 */
class Portal_Shortcode {

	/**
	 * Settings option name.
	 *
	 * @var string
	 */
	private string $option_name = 'portal_settings';

	/**
	 * Default number of items to display.
	 *
	 * @var int
	 */
	private int $default_limit = 10;

	/**
	 * Whether to use fallback image from content.
	 *
	 * @var bool
	 */
	private bool $use_fallback_image = false;

	/**
	 * Cache timeout in seconds.
	 *
	 * @var int
	 */
	private int $cache_timeout = 300; // 5 minutes

	/**
	 * REST API timeout in seconds.
	 *
	 * @var int
	 */
	private int $api_timeout = 5;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_shortcode( 'portal_feed', [ $this, 'render_shortcode' ] );
	}

	/**
	 * Render the portal feed shortcode.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	public function render_shortcode( array $atts ): string {
		try {
			// Parse shortcode attributes.
			$atts = shortcode_atts( [
				'limit'          => $this->default_limit,
				'page'           => 1,
				'show_date'      => 'true',
				'show_source'    => 'true',
				'show_thumbnail' => 'true',
				'fallback_image' => 'false',
				'use_api'        => 'true',
				'excerpt_length' => 150,
				'class'          => 'portal-feed',
			], $atts, 'portal_feed' );

			// Get current page from query if not set in attributes
			if ( isset( $_GET['portal_page'] ) ) {
				$atts['page'] = max( 1, intval( $_GET['portal_page'] ) );
			}

			// Set fallback image setting EARLY so it's available during feed fetching
			$this->use_fallback_image = filter_var( $atts['fallback_image'], FILTER_VALIDATE_BOOLEAN );

			// Get sites from settings - safely
			$settings = get_option( $this->option_name, [] );
			$sites = [];
			
			if ( isset( $settings['general'] ) && is_array( $settings['general'] ) ) {
				$sites = $settings['general']['portal_sites'] ?? [];
			}

			// If no sites configured
			if ( empty( $sites ) || ! is_array( $sites ) ) {
				return $this->error_message( esc_html__( 'No sites configured. Please add WordPress sites in Portal settings.', 'portal' ) );
			}

			// Get valid feed URLs
			$valid_feeds = [];
			foreach ( $sites as $site ) {
				if ( is_array( $site ) && ! empty( $site['feed_url'] ) && ! empty( $site['valid'] ) ) {
					$valid_feeds[] = esc_url_raw( $site['feed_url'] );
				}
			}

			// If no valid feeds
			if ( empty( $valid_feeds ) ) {
				return $this->error_message( esc_html__( 'No valid RSS feeds found for the configured sites.', 'portal' ) );
			}

			// Fetch and aggregate all feed items (get ALL items, not limited)
			$all_items = [];
			foreach ( $valid_feeds as $feed_url ) {
				$items = $this->fetch_feed_items( $feed_url );
				if ( ! empty( $items ) ) {
					$all_items = array_merge( $all_items, $items );
				}
			}

			// If no items found
			if ( empty( $all_items ) ) {
				return $this->error_message( esc_html__( 'No feed items found.', 'portal' ) );
			}

			// Sort all items by date (newest first)
			usort( $all_items, function( $a, $b ) {
				return strtotime( $b['date'] ) <=> strtotime( $a['date'] );
			} );

			// Get pagination parameters
			$page = max( 1, intval( $atts['page'] ) );
			$per_page = max( 1, intval( $atts['limit'] ) );
			
			// Calculate total pages
			$total_items = count( $all_items );
			$total_pages = ceil( $total_items / $per_page );
			
			// Get items for current page
			$offset = ( $page - 1 ) * $per_page;
			$items = array_slice( $all_items, $offset, $per_page );

			// Build HTML output
			$html = $this->build_html_output( $items, $atts );
			
			// Add pagination if more than one page
			if ( $total_pages > 1 ) {
				$html .= $this->build_pagination( $page, $total_pages );
			}
			
			return $html;

		} catch ( \Throwable $e ) {
			return $this->error_message( esc_html__( 'Portal feed error. Please check plugin settings.', 'portal' ) );
		}
	}

	/**
	 * Fetch feed items from a URL.
	 *
	 * @param string $feed_url The feed URL.
	 * @return array Array of feed items.
	 */
	private function fetch_feed_items( string $feed_url ): array {
		// Validate URL
		$feed_url = esc_url_raw( trim( $feed_url ) );
		if ( empty( $feed_url ) ) {
			return [];
		}

		// Check if fetch_feed function exists
		if ( ! function_exists( 'fetch_feed' ) ) {
			return [];
		}

		// Suppress warnings
		$feed = @fetch_feed( $feed_url );

		if ( is_wp_error( $feed ) ) {
			return [];
		}

		if ( ! is_object( $feed ) || ! method_exists( $feed, 'get_item_quantity' ) || ! $feed->get_item_quantity() ) {
			return [];
		}

		$items = [];
		$feed_items = $feed->get_items();

		if ( ! is_array( $feed_items ) ) {
			return [];
		}

		foreach ( $feed_items as $item ) {
			if ( ! is_object( $item ) ) {
				continue;
			}

			// Get featured image/thumbnail URL
			$thumbnail_url = $this->get_feed_item_thumbnail( $item );

			$items[] = [
				'title'       => $item->get_title() ?: '',
				'link'        => $item->get_link() ?: '',
				'date'        => $item->get_date( 'Y-m-d H:i:s' ) ?: date( 'Y-m-d H:i:s' ),
				'description' => $item->get_description() ?: '',
				'content'     => $item->get_content() ?: '',
				'feed_url'    => $feed_url,
				'author'      => $item->get_author() ? $item->get_author()->get_name() : '',
				'thumbnail'   => $thumbnail_url,
			];
		}

		return $items;
	}

	/**
	 * Get the thumbnail/image URL from a feed item.
	 *
	 * @param object $item SimplePie feed item.
	 * @return string Thumbnail URL or empty string.
	 */
	private function get_feed_item_thumbnail( $item ): string {
		// Try Media RSS first (no HTTP request, instant)
		$thumbnail = $this->get_media_rss_thumbnail( $item );
		if ( ! empty( $thumbnail ) ) {
			return $thumbnail;
		}

		// Try WordPress REST API - ONE request only
		$thumbnail = $this->get_wp_rest_featured_image( $item );
		if ( ! empty( $thumbnail ) ) {
			return $thumbnail;
		}

		// Fallback: try enclosure (only if enabled)
		if ( $this->use_fallback_image ) {
			$thumbnail = $this->get_enclosure_thumbnail( $item );
			if ( ! empty( $thumbnail ) ) {
				return $thumbnail;
			}
		}

		return '';
	}

	/**
	 * Get thumbnail from Media RSS tags.
	 *
	 * @param object $item SimplePie feed item.
	 * @return string Thumbnail URL or empty string.
	 */
	private function get_media_rss_thumbnail( $item ): string {
		$media_ns = 'http://search.yahoo.com/mrss/';
		
		// Try content tag (full size image)
		$media_content = $item->get_item_tags( $media_ns, 'content' );
		if ( ! empty( $media_content ) ) {
			foreach ( $media_content as $content ) {
				if ( ! empty( $content['attribs']['']['url'] ) ) {
					$url = $content['attribs']['']['url'];
					if ( $this->is_valid_image_url( $url ) ) {
						return esc_url_raw( $url );
					}
				}
				if ( ! empty( $content['attribs']['url'] ) ) {
					$url = $content['attribs']['url'];
					if ( $this->is_valid_image_url( $url ) ) {
						return esc_url_raw( $url );
					}
				}
				if ( ! empty( $content['data'] ) ) {
					$url = $content['data'];
					if ( $this->is_valid_image_url( $url ) ) {
						return esc_url_raw( $url );
					}
				}
			}
		}
		
		// Try thumbnail tag
		$media_thumbnail = $item->get_item_tags( $media_ns, 'thumbnail' );
		if ( ! empty( $media_thumbnail ) ) {
			foreach ( $media_thumbnail as $thumbnail ) {
				if ( ! empty( $thumbnail['attribs']['']['url'] ) ) {
					$url = $thumbnail['attribs']['']['url'];
					if ( $this->is_valid_image_url( $url ) ) {
						return esc_url_raw( $url );
					}
				}
				if ( ! empty( $thumbnail['attribs']['url'] ) ) {
					$url = $thumbnail['attribs']['url'];
					if ( $this->is_valid_image_url( $url ) ) {
						return esc_url_raw( $url );
					}
				}
				if ( ! empty( $thumbnail['data'] ) ) {
					$url = $thumbnail['data'];
					if ( $this->is_valid_image_url( $url ) ) {
						return esc_url_raw( $url );
					}
				}
			}
		}

		return '';
	}

	/**
	 * Get featured image via WordPress REST API.
	 * Uses ONE request only with smart fallback.
	 *
	 * @param object $item SimplePie feed item.
	 * @return string Thumbnail URL or empty string.
	 */
	private function get_wp_rest_featured_image( $item ): string {
		$post_link = $item->get_link();
		if ( empty( $post_link ) ) {
			return '';
		}

		$parsed = wp_parse_url( $post_link );
		if ( empty( $parsed['host'] ) ) {
			return '';
		}
		
		$scheme = isset( $parsed['scheme'] ) ? $parsed['scheme'] . '://' : 'https://';
		// Include the port when present, e.g. http://umbrel.local:8567.
		$port = isset( $parsed['port'] ) ? ':' . $parsed['port'] : '';
		$base_url = trailingslashit( $scheme . $parsed['host'] . $port );

		// Generate a unique cache key for this post
		$cache_key = 'portal_feat_img_' . md5( $post_link );
		
		// Try to get from cache first
		$cached_url = get_transient( $cache_key );
		if ( $cached_url !== false ) {
			return $cached_url === 'none' ? '' : $cached_url;
		}

		// Get post ID if available
		$post_id = $this->get_wp_post_id( $item );
		
		$api_url = '';
		
		// Prefer post ID if available (most reliable)
		if ( $post_id > 0 ) {
			$api_url = $base_url . 'wp-json/wp/v2/posts/' . $post_id . '?_embed';
		} else {
			// Fallback: use slug
			$slug = $this->get_post_slug_from_url( $post_link, $base_url );
			if ( ! empty( $slug ) ) {
				$api_url = $base_url . 'wp-json/wp/v2/posts?slug=' . rawurlencode( $slug ) . '&_embed';
			} else {
				// Last resort: use path
				$path = $this->get_post_path_from_url( $post_link, $base_url );
				if ( ! empty( $path ) ) {
					$api_url = $base_url . 'wp-json/wp/v2/posts?slug=' . rawurlencode( $path ) . '&_embed';
				}
			}
		}

		// If no API URL could be constructed
		if ( empty( $api_url ) ) {
			set_transient( $cache_key, 'none', $this->cache_timeout );
			return '';
		}

		// Make the API request
		$response = wp_remote_get( $api_url, [
			'timeout'   => $this->api_timeout,
			'sslverify' => false,
			'headers'   => [
				'User-Agent' => 'Mozilla/5.0 (compatible; Portal Plugin/1.0)',
				'Accept'     => 'application/json',
			],
		] );

		$image_url = '';
		
		if ( ! is_wp_error( $response ) ) {
			$code = wp_remote_retrieve_response_code( $response );
			if ( $code === 200 ) {
				$body = wp_remote_retrieve_body( $response );
				$data = json_decode( $body, true );
				
				// Extract image URL
				$image_url = $this->extract_featured_media_url( $data );
			}
		}

		// Cache the result (even if empty to avoid repeated requests)
		$cache_value = empty( $image_url ) ? 'none' : $image_url;
		set_transient( $cache_key, $cache_value, $this->cache_timeout );

		return $image_url;
	}

	/**
	 * Extract featured media URL from REST API response data.
	 *
	 * @param array $data API response data.
	 * @return string Image URL or empty.
	 */
	private function extract_featured_media_url( array $data ): string {
		// Handle array of posts (when using slug query)
		if ( ! empty( $data[0] ) && is_array( $data[0] ) ) {
			$data = $data[0];
		}

		// Check if _embedded exists and has featured media
		if ( empty( $data['_embedded'] ) || empty( $data['_embedded']['wp:featuredmedia'] ) || empty( $data['_embedded']['wp:featuredmedia'][0] ) ) {
			return '';
		}

		$featured_media = $data['_embedded']['wp:featuredmedia'][0];
		
		// Try different fields for image URL
		$possible_urls = [];
		if ( ! empty( $featured_media['source_url'] ) ) {
			$possible_urls[] = $featured_media['source_url'];
		}
		if ( ! empty( $featured_media['guid']['rendered'] ) ) {
			$possible_urls[] = $featured_media['guid']['rendered'];
		}
		if ( ! empty( $featured_media['link'] ) ) {
			$possible_urls[] = $featured_media['link'];
		}

		foreach ( $possible_urls as $url ) {
			if ( $this->is_valid_image_url( $url ) ) {
				return esc_url_raw( $url );
			}
		}

		return '';
	}

	/**
	 * Get WordPress post ID from feed item.
	 *
	 * @param object $item SimplePie feed item.
	 * @return int Post ID or 0.
	 */
	private function get_wp_post_id( $item ): int {
		// Try wp:post_id tag (WordPress specific - most reliable)
		$wp_ns = 'http://wordpress.org/export/1.2/';
		$post_id_tags = $item->get_item_tags( $wp_ns, 'post_id' );
		if ( ! empty( $post_id_tags[0]['data'] ) ) {
			$post_id = intval( $post_id_tags[0]['data'] );
			if ( $post_id > 0 ) {
				return $post_id;
			}
		}

		// Try other WordPress namespaces
		$wp_ns_alt = 'http://wordpress.org/export/1.1/';
		$post_id_tags = $item->get_item_tags( $wp_ns_alt, 'post_id' );
		if ( ! empty( $post_id_tags[0]['data'] ) ) {
			$post_id = intval( $post_id_tags[0]['data'] );
			if ( $post_id > 0 ) {
				return $post_id;
			}
		}

		// Try to extract ID from GUID
		$guid = $item->get_id( true );
		if ( ! empty( $guid ) ) {
			// Try ?p=ID pattern
			if ( preg_match( '/\?p=(\d+)/', $guid, $matches ) ) {
				return intval( $matches[1] );
			}
			// Try /YYYY/MM/DD/ID or /ID pattern
			if ( preg_match( '#(/\d{4}/\d{2}/\d{2}/)?(\d+)(/|$|\?)#', $guid, $matches ) ) {
				return intval( $matches[2] );
			}
		}

		// Try to extract ID from link
		$link = $item->get_link();
		if ( ! empty( $link ) ) {
			if ( preg_match( '/\?p=(\d+)/', $link, $matches ) ) {
				return intval( $matches[1] );
			}
			if ( preg_match( '#(/\d{4}/\d{2}/\d{2}/)?(\d+)(/|$|\?)#', $link, $matches ) ) {
				return intval( $matches[2] );
			}
		}

		return 0;
	}

	/**
	 * Extract post slug from URL.
	 * For URL like https://site.com/2024/11/post-name/ returns 'post-name'
	 * For URL like https://site.com/post-name/ returns 'post-name'
	 *
	 * @param string $url The full URL.
	 * @param string $base_url The site base URL.
	 * @return string The slug or empty.
	 */
	private function get_post_slug_from_url( string $url, string $base_url ): string {
		// Remove base URL from the beginning
		$path = str_replace( $base_url, '', $url );
		
		// Remove query string and fragments
		$path = strtok( $path, '?' );
		$path = strtok( $path, '#' );
		
		// Remove trailing slash
		$path = rtrim( $path, '/' );
		
		if ( empty( $path ) ) {
			return '';
		}

		// Remove date prefix if present (YYYY/MM/DD/)
		$path = preg_replace( '#^\d{4}/\d{2}/\d{2}/#', '', $path );
		
		// Remove any remaining numbers at the start (post ID)
		$path = preg_replace( '#^\d+/#', '', $path );
		
		// Remove trailing slash
		$path = rtrim( $path, '/' );
		
		if ( ! empty( $path ) ) {
			return $path;
		}

		return '';
	}

	/**
	 * Extract post path from URL.
	 * Returns everything after the base URL.
	 *
	 * @param string $url The full URL.
	 * @param string $base_url The site base URL.
	 * @return string The path or empty.
	 */
	private function get_post_path_from_url( string $url, string $base_url ): string {
		$path = str_replace( $base_url, '', $url );
		$path = strtok( $path, '?' );
		$path = strtok( $path, '#' );
		$path = rtrim( $path, '/' );
		
		return ! empty( $path ) ? $path : '';
	}

	/**
	 * Get thumbnail from enclosure.
	 *
	 * @param object $item SimplePie feed item.
	 * @return string Thumbnail URL or empty string.
	 */
	private function get_enclosure_thumbnail( $item ): string {
		$enclosure = $item->get_enclosure();
		if ( $enclosure && method_exists( $enclosure, 'get_link' ) ) {
			$link = $enclosure->get_link();
			if ( ! empty( $link ) && $this->is_valid_image_url( $link ) ) {
				return esc_url_raw( $link );
			}
		}
		return '';
	}

	/**
	 * Check if a URL is a valid image URL (not an emoji, has image extension).
	 *
	 * @param string $url URL to check.
	 * @return bool True if valid image URL.
	 */
	private function is_valid_image_url( string $url ): bool {
		if ( empty( $url ) ) {
			return false;
		}

		// Check if it's a valid URL
		if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		// Check for emojis in the entire URL (emojis should not appear in image URLs)
		if ( preg_match( '/[\x{1F300}-\x{1F6FF}\x{1F900}-\x{1F9FF}\x{1F600}-\x{1F64F}\x{2600}-\x{26FF}\x{2700}-\x{27BF}]/u', $url ) ) {
			return false;
		}

		// Remove query string and fragments
		$clean_url = strtok( $url, '?' );
		$clean_url = strtok( $clean_url, '#' );

		// Check for common image extensions
		$image_extensions = [ 'jpg', 'jpeg', 'png', 'gif', 'webp', 'avif', 'svg' ];
		foreach ( $image_extensions as $ext ) {
			if ( str_ends_with( strtolower( $clean_url ), '.' . $ext ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build HTML output for feed items.
	 *
	 * @param array $items Feed items.
	 * @param array $atts Shortcode attributes.
	 * @return string HTML output.
	 */
	private function build_html_output( array $items, array $atts ): string {
		$show_date = filter_var( $atts['show_date'], FILTER_VALIDATE_BOOLEAN );
		$show_source = filter_var( $atts['show_source'], FILTER_VALIDATE_BOOLEAN );
		$show_thumbnail = filter_var( $atts['show_thumbnail'], FILTER_VALIDATE_BOOLEAN );
		$excerpt_length = intval( $atts['excerpt_length'] );
		$html_class = sanitize_html_class( $atts['class'] );

		$html = '<div class="' . esc_attr( $html_class ) . '">';

		foreach ( $items as $item ) {
			$html .= '<article class="portal-feed-item">';

			// Thumbnail (featured image)
			if ( $show_thumbnail && ! empty( $item['thumbnail'] ) ) {
				$html .= '<div class="portal-feed-item-thumbnail">';
				$html .= '<a href="' . esc_url( $item['link'] ) . '" target="_blank" rel="noopener noreferrer">';
				$html .= '<img src="' . esc_url( $item['thumbnail'] ) . '" alt="' . esc_attr( $item['title'] ) . '" loading="lazy" style="aspect-ratio: 16/9;object-fit: cover;width:100%">';
				$html .= '</a>';
				$html .= '</div>';
			}

			// Title with link
			$html .= '<h3 class="portal-feed-item-title">';
			$html .= '<a href="' . esc_url( $item['link'] ) . '" target="_blank" rel="noopener noreferrer">';
			$html .= esc_html( $item['title'] );
			$html .= '</a>';
			$html .= '</h3>';

			// Meta information (date and source)
			$html .= '<div class="portal-feed-item-meta">';
			if ( $show_date && ! empty( $item['date'] ) ) {
				$date_format = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
				$html .= '<span class="portal-feed-item-date">' . date_i18n( $date_format, strtotime( $item['date'] ) ) . '</span>';
			}
			if ( $show_source ) {
				if ( $show_date && ! empty( $item['date'] ) ) {
					$html .= '<span class="portal-feed-item-separator"> | </span>';
				}
				$source_host = wp_parse_url( $item['feed_url'], PHP_URL_HOST );
				$html .= '<span class="portal-feed-item-source">' . esc_html( $source_host ) . '</span>';
			}
			$html .= '</div>';

			// Description/Excerpt
			if ( ! empty( $item['description'] ) ) {
				$html .= '<div class="portal-feed-item-excerpt">';
				if ( $excerpt_length > 0 ) {
					$excerpt = wp_trim_words( $item['description'], $excerpt_length / 10, '...' );
					$html .= wp_kses_post( $excerpt );
				} else {
					$html .= wp_kses_post( $item['description'] );
				}
				$html .= '</div>';
			}

			$html .= '</article>';
		}

		$html .= '</div>';
		$html .= $this->get_inline_styles();

		return $html;
	}

	/**
	 * Build pagination links.
	 *
	 * @param int $current_page Current page number.
	 * @param int $total_pages Total number of pages.
	 * @return string HTML pagination.
	 */
	private function build_pagination( int $current_page, int $total_pages ): string {
		// Don't show pagination if only one page
		if ( $total_pages <= 1 ) {
			return '';
		}

		$html = '<nav class="portal-feed-pagination" aria-label="' . esc_attr__( 'Feed pagination', 'portal' ) . '">';

		// Previous link
		if ( $current_page > 1 ) {
			$prev_url = $this->get_page_url( $current_page - 1 );
			$html .= '<a href="' . esc_url( $prev_url ) . '" class="portal-page-link prev" aria-label="' . esc_attr__( 'Previous page', 'portal' ) . '">← ' . esc_html__( 'Previous', 'portal' ) . '</a>';
		} else {
			$html .= '<span class="portal-page-link prev disabled">← ' . esc_html__( 'Previous', 'portal' ) . '</span>';
		}

		// Page numbers
		$html .= '<span class="portal-pagination-info">' . sprintf( esc_html__( 'Page %1$d of %2$d', 'portal' ), $current_page, $total_pages ) . '</span>';

		// Next link
		if ( $current_page < $total_pages ) {
			$next_url = $this->get_page_url( $current_page + 1 );
			$html .= '<a href="' . esc_url( $next_url ) . '" class="portal-page-link next" aria-label="' . esc_attr__( 'Next page', 'portal' ) . '">' . esc_html__( 'Next', 'portal' ) . ' →</a>';
		} else {
			$html .= '<span class="portal-page-link next disabled">' . esc_html__( 'Next', 'portal' ) . ' →</span>';
		}

		$html .= '</nav>';

		return $html;
	}

	/**
	 * Get URL for a specific page.
	 *
	 * @param int $page Page number.
	 * @return string Page URL.
	 */
	private function get_page_url( int $page ): string {
		// Get current URL
		$url = remove_query_arg( 'portal_page' );
		
		// Add portal_page parameter
		if ( $page > 1 ) {
			$url = add_query_arg( 'portal_page', $page, $url );
		}
		
		return $url;
	}

	/**
	 * Get inline styles for the feed output.
	 *
	 * @return string Inline CSS.
	 */
	private function get_inline_styles(): string {
		return '<style type="text/css">.portal-feed{margin:20px 0}.portal-feed-item{margin-bottom:30px;padding-bottom:20px;border-bottom:1px solid #eee}.portal-feed-item:last-child{border-bottom:none}.portal-feed-item-thumbnail{margin-bottom:15px}.portal-feed-item-thumbnail img{max-width:100%;height:auto;border-radius:4px}.portal-feed-item-title{margin:0 0 8px 0}.portal-feed-item-title a{color:#1e73be;text-decoration:none}.portal-feed-item-title a:hover{text-decoration:underline}.portal-feed-item-meta{color:#666;font-size:13px;margin-bottom:10px}.portal-feed-item-excerpt{line-height:1.5}.portal-feed-error{color:#d63638;font-style:italic;padding:10px;background:#f8f9fa;border:1px solid #d63638;border-radius:4px;}.portal-feed-pagination{text-align:center;margin:30px 0 20px;}.portal-feed-pagination .portal-page-link{display:inline-block;padding:8px 16px;margin:0 4px;border:1px solid #ddd;text-decoration:none;color:#1e73be;border-radius:4px;}.portal-feed-pagination .portal-page-link:hover{background:#f0f0f1;border-color:#ccc;}.portal-feed-pagination .portal-page-link.disabled{color:#aaa;border-color:#eee;cursor:not-allowed;}.portal-pagination-info{display:inline-block;padding:8px 16px;margin:0 10px;color:#666;}</style>';
	}

	/**
	 * Generate error message HTML.
	 *
	 * @param string $message Error message.
	 * @return string HTML error message.
	 */
	private function error_message( string $message ): string {
		return '<p class="portal-feed-error">' . $message . '</p>' . $this->get_inline_styles();
	}
}
