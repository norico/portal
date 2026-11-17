# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-11-17

### Added
- Pagination support for feed items (via `limit` attribute and `?portal_page=` query parameter)
- Transient caching for featured images fetched via WordPress REST API (5-minute TTL)
- French translations (fr_FR) — complete admin UI and frontend strings
- Admin UI improvements:
  - Dynamic add/remove site rows with JavaScript
  - Visual validation feedback (green checkmark for valid feeds)
  - Direct "RSS" button linking to discovered feed
  - Warning/error notices for invalid or missing feeds

### Changed
- Improved thumbnail resolution priority:
  1. Media RSS tags (`<media:content>`, `<media:thumbnail>`) — instant, no HTTP request
  2. WordPress REST API (`/wp-json/wp/v2/posts/{id}?_embed`) — cached via transients
  3. RSS Enclosure (`<enclosure>`) — optional fallback via `fallback_image` attribute
- REST API lookup now uses post ID (from `wp:post_id`, GUID patterns, or URL) with slug/path fallback

### Fixed
- Feed fetching now retrieves all items before pagination (previously limited per-feed)
- Date sorting across all aggregated feeds (newest first)
- WordPress 6.7+ compatibility: textdomain loaded on `init` hook to avoid `_load_textdomain_just_in_time` notices

## [1.0.0] - 2026-11-16

### Added
- Initial release
- Shortcode `[portal_feed]` for displaying aggregated RSS feeds
- Admin settings page under Settings → Portal with "General" tab
- Auto-discovery of WordPress RSS feeds (`/feed/`, `/feed/rss2/`, `/rss/`, etc.)
- RSS feed validation on save (checks HTTP 200, content-type, RSS/XML markers)
- Site management: add multiple WordPress sites, each validated independently
- Basic HTML output with title, date, source, thumbnail, excerpt
- Inline CSS styles (self-contained, no external dependencies)
- GPL-2.0+ license

---

## Upgrade Notes

### 1.0.0 → 1.1.0
- **No breaking changes** — existing shortcodes and settings continue to work
- New `fallback_image` attribute defaults to `false` (was not available before)
- Pagination activates automatically when items exceed `limit`
- Featured images now cached for 5 minutes (reduces REST API calls on repeat visits)
- Admin UI now shows validation status per site — re-save settings to refresh

---

## Links

- [Repository](https://github.com/norico/portal/)
- [Issues](https://github.com/norico/portal/issues/)
- [Documentation](https://github.com/norico/portal/docs/)