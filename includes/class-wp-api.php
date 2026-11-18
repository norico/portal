<?php
/**
 * WordPress REST API Integration
 *
 * Handles fetching featured images from WordPress sites via their REST API.
 *
 * @package Portal
 */

namespace Portal\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Portal_WP_API class
 *
 * Integrates with WordPress REST API to fetch featured images.
 * This separation allows for easier testing and maintenance of the API integration logic.
 */
class Portal_WP_API {
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
	}

	/**
	 * Get featured image from WordPress site using REST API.
	 *
	 * @param string $site_url The WordPress site URL.
	 * @param string $post_link The post link from which to extract post ID.
	 * @return string Featured image URL or empty string.
	 */
	public function get_featured_image(string $site_url, string $post_link): string {
		// Generate a unique cache key for this post
		$cache_key = 'portal_feat_img_' . md5( $post_link );

		// Try to get from cache first
		$cached_url = get_transient( $cache_key );
		if ( $cached_url !== false ) {
			return $cached_url === 'none' ? '' : $cached_url;
		}

		// Extract post ID from the link
		$post_id = $this->extract_post_id_from_link( $post_link );

		$api_url = '';

		// Prefer post ID if available (most reliable)
		if ( $post_id > 0 ) {
			$api_url = $this->build_api_url_from_post_id( $site_url, $post_id );
		} else {
			// Fallback: try to extract post slug from URL
			$slug = $this->extract_post_slug_from_link( $post_link, $site_url );
			if ( ! empty( $slug ) ) {
				$api_url = $this->build_api_url_from_slug( $site_url, $slug );
			} else {
				// Last resort: use path
				$path = $this->extract_post_path_from_link( $post_link, $site_url );
				if ( ! empty( $path ) ) {
					$api_url = $this->build_api_url_from_path( $site_url, $path );
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
				'User-Agent' => 'Portal Plugin/1.0',
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
	 * Extract post ID from a WordPress post link.
	 *
	 * @param string $link The post link.
	 * @return int Post ID or 0.
	 */
	private function extract_post_id_from_link(string $link): int {
		// Try to extract ID from GUID (WordPress export namespace)
		$guid = $this->extract_guid_from_link( $link );
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

		// Try to extract ID from link directly
		if ( preg_match( '/\?p=(\d+)/', $link, $matches ) ) {
			return intval( $matches[1] );
		}
		if ( preg_match( '#(/\d{4}/\d{2}/\d{2}/)?(\d+)(/|$|\?)#', $link, $matches ) ) {
			return intval( $matches[2] );
		}

		return 0;
	}

	/**
	 * Extract GUID from a post link.
	 *
	 * @param string $link The post link.
	 * @return string GUID or empty string.
	 */
	private function extract_guid_from_link(string $link): string {
		// This would need to parse RSS item GUID if available
		// For now, return empty as this is handled in the original implementation
		return '';
	}

	/**
	 * Extract post slug from a WordPress post link.
	 *
	 * @param string $url The full URL.
	 * @param string $base_url The site base URL.
	 * @return string The slug or empty.
	 */
	private function extract_post_slug_from_link(string $url, string $base_url): string {
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
	 * Extract post path from a WordPress post link.
	 *
	 * @param string $url The full URL.
	 * @param string $base_url The site base URL.
	 * @return string The path or empty.
	 */
	private function extract_post_path_from_link(string $url, string $base_url): string {
		$path = str_replace( $base_url, '', $url );
		$path = strtok( $path, '?' );
		$path = strtok( $path, '#' );
		$path = rtrim( $path, '/' );

		return ! empty( $path ) ? $path : '';
	}

	/**
	 * Build API URL from post ID.
	 *
	 * @param string $site_url The site URL.
	 * @param int    $post_id  The post ID.
	 * @return string API URL.
	 */
	private function build_api_url_from_post_id(string $site_url, int $post_id): string {
		// Ensure site_url doesn't have trailing slash
		$site_url = rtrim( $site_url, '/' );

		// Parse the site URL to get scheme, host, and port
		$parsed = wp_parse_url( $site_url );
		if ( empty( $parsed['host'] ) ) {
			return '';
		}

		$scheme = isset( $parsed['scheme'] ) ? $parsed['scheme'] . '://' : 'https://';
		$port = isset( $parsed['port'] ) ? ':' . $parsed['port'] : '';

		return $scheme . $parsed['host'] . $port . 'wp-json/wp/v2/posts/' . $post_id . '?_embed';
	}

	/**
	 * Build API URL from slug.
	 *
	 * @param string $site_url The site URL.
	 * @param string $slug     The post slug.
	 * @return string API URL.
	 */
	private function build_api_url_from_slug(string $site_url, string $slug): string {
		// Ensure site_url doesn't have trailing slash
		$site_url = rtrim( $site_url, '/' );

		// Parse the site URL to get scheme, host, and port
		$parsed = wp_parse_url( $site_url );
		if ( empty( $parsed['host'] ) ) {
			return '';
		}

		$scheme = isset( $parsed['scheme'] ) ? $parsed['scheme'] . '://' : 'https://';
		$port = isset( $parsed['port'] ) ? ':' . $parsed['port'] : '';

		return $scheme . $parsed['host'] . $port . 'wp-json/wp/v2/posts?slug=' . rawurlencode( $slug ) . '&_embed';
	}

	/**
	 * Build API URL from path.
	 *
	 * @param string $site_url The site URL.
	 * @param string $path     The post path.
	 * @return string API URL.
	 */
	private function build_api_url_from_path(string $site_url, string $path): string {
		// Ensure site_url doesn't have trailing slash
		$site_url = rtrim( $site_url, '/' );

		// Parse the site URL to get scheme, host, and port
		$parsed = wp_parse_url( $site_url );
		if ( empty( $parsed['host'] ) ) {
			return '';
		}

		$scheme = isset( $parsed['scheme'] ) ? $parsed['scheme'] . '://' : 'https://';
		$port = isset( $parsed['port'] ) ? ':' . $parsed['port'] : '';

		return $scheme . $parsed['host'] . $port . 'wp-json/wp/v2/posts?slug=' . rawurlencode( $path ) . '&_embed';
	}

	/**
	 * Extract featured media URL from REST API response data.
	 *
	 * @param array $data API response data.
	 * @return string Image URL or empty.
	 */
	private function extract_featured_media_url(array $data): string {
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
	 * Check if a URL is a valid image URL (not an emoji, has image extension).
	 *
	 * @param string $url URL to check.
	 * @return bool True if valid image URL.
	 */
	private function is_valid_image_url(string $url): bool {
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
}