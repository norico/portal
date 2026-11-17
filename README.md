# Portal — WordPress News Federator

> Aggregates RSS feeds from multiple WordPress sites into a single display via shortcode.

---

## Overview

**Portal** is a WordPress plugin that fetches and displays RSS feeds from configured WordPress sites. It provides a simple shortcode to embed aggregated news feeds anywhere on your site, with pagination, thumbnails, and multilingual support.

- **Version:** 1.1.0
- **Requires:** PHP 8.2+, WordPress 6.5+
- **License:** GPL-2.0+
- **Author:** norico

---

## Installation

1. Upload the `portal` folder to `/wp-content/plugins/`
2. Activate the plugin through the **Plugins** menu
3. Go to **Settings → Portal** to configure your WordPress sites
4. Use the `[portal_feed]` shortcode on any page/post

---

## Configuration

### Admin Settings (Settings → Portal)

| Tab | Purpose |
|-----|---------|
| **General** | Add WordPress site URLs — the plugin auto-discovers their RSS feeds (`/feed/`, `/feed/rss2/`, etc.) |
| **Display** | Reserved for future display options |

For each site, the plugin validates the RSS feed on save and shows:
- ✓ Green checkmark = valid feed detected
- RSS button = direct link to the feed
- Warning/error notices if no valid feed is found

---

## Shortcode: `[portal_feed]`

### Basic Usage

```html
[portal_feed]
```

### All Attributes

| Attribute | Default | Description |
|-----------|---------|-------------|
| `limit` | `10` | Number of items per page |
| `page` | `1` | Initial page (overridden by `?portal_page=N` URL param) |
| `show_date` | `true` | Show publication date |
| `show_source` | `true` | Show source site hostname |
| `show_thumbnail` | `true` | Show featured image/thumbnail |
| `fallback_image` | `false` | Use RSS enclosure as image fallback |
| `use_api` | `true` | Fetch featured images via WordPress REST API |
| `excerpt_length` | `150` | Excerpt length in characters (≈ words ÷ 10) |
| `class` | `portal-feed` | Custom CSS class for the container |

### Example with Custom Options

```html
[portal_feed limit="5" show_date="false" excerpt_length="200" class="my-custom-feed"]
```

### Pagination

Pagination links are generated automatically when items exceed `limit`. URLs use the `portal_page` query parameter (e.g., `?portal_page=2`).

---

## Image/Thumbnail Resolution

The plugin resolves thumbnails in this priority order (first match wins):

1. **Media RSS** (`<media:content>`, `<media:thumbnail>`) — instant, no HTTP request
2. **WordPress REST API** (`/wp-json/wp/v2/posts/{id}?_embed`) — cached 5 min via transients
3. **RSS Enclosure** (`<enclosure>`) — only if `fallback_image="true"`

The REST API lookup uses post ID (from `wp:post_id`, GUID, or URL patterns) with fallback to slug/path queries.

---

## Architecture

```
portal/
├── portal.php                 # Main plugin file, constants, activation hook
├── index.php                  # Directory access protection
├── includes/
│   ├── class-loader.php       # Plugin bootstrap, hooks, admin links
│   ├── class-shortcode.php    # Shortcode rendering, feed aggregation, pagination
│   ├── class-admin.php        # Settings page, RSS validation, sanitization
│   └── admin/
│       └── tabs/
│           ├── general.php    # Sites management UI (JS add/remove rows)
│           └── display.php    # Placeholder for display options
└── languages/
    ├── portal-fr_FR.po        # French translations
    └── portal-fr_FR.mo
```

### Key Classes

| Class | Responsibility |
|-------|----------------|
| `Portal_Loader` | Constants, hooks, loads shortcode & admin |
| `Portal_Shortcode` | `[portal_feed]` rendering, feed fetching, HTML output |
| `Portal_Admin` | Settings page, RSS feed validation, settings sanitization |

---

## Caching

| Data | Mechanism | TTL |
|------|-----------|-----|
| Featured images (REST API) | WordPress Transients | 5 minutes (300s) |
| Feed items | **None** — fetched real-time on each page load | — |

---

## Internationalization

- Text domain: `portal`
- Domain path: `/languages`
- **French (fr_FR)** translation included
- Translations loaded on `init` hook (WP 6.7+ compatible)

To add a language:
1. Copy `languages/portal-fr_FR.po` to `languages/portal-{locale}.po`
2. Translate strings (Poedit recommended)
3. Compile to `.mo`

---

## Requirements Check

On activation, the plugin verifies:
- PHP ≥ 8.2
- WordPress ≥ 6.5

If requirements aren't met, activation fails with a clear error message.

---

## Hooks & Filters

### Actions
- `plugins_loaded` — Plugin initialization
- `admin_menu` — Add settings submenu page
- `admin_init` — Register settings

### Filters
- `plugin_action_links_{$basename}` — Adds "Settings" link on Plugins page
- `plugin_row_meta` — Adds "Documentation" link on Plugins page

---

## Development

### Adding a New Admin Tab

1. Create template in `includes/admin/tabs/{slug}.php`
2. Register in `Portal_Admin::__construct()`:

```php
$this->tabs = [
    'general' => [ 'title_key' => 'General', 'template' => 'tabs/general.php' ],
    'display' => [ 'title_key' => 'Display', 'template' => 'tabs/display.php' ],
    'newtab'  => [ 'title_key' => 'New Tab',  'template' => 'tabs/newtab.php'  ],
];
```

3. Add translation string for `New Tab` in `.po` files

### Extending Shortcode Attributes

Modify the `shortcode_atts()` array in `Portal_Shortcode::render_shortcode()`.

---

## Troubleshooting

| Issue | Cause | Solution |
|-------|-------|----------|
| "No sites configured" | No sites saved in settings | Add at least one WordPress site URL in Settings → Portal |
| "No valid RSS feeds" | Feed auto-discovery failed | Check site URL is correct; verify `/feed/` returns valid RSS |
| Images not showing | REST API blocked or no featured image | Enable `fallback_image="true"` or check source site has featured images |
| Pagination not working | Caching plugin interferes | Exclude `portal_page` query param from cache |

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history.
---

## License

GPL-2.0+ — See `LICENSE` file or <http://www.gnu.org/licenses/gpl-2.0.txt>

---

## Support

- Documentation: <https://github.com/norico/portal/docs/>
- Issues: <https://github.com/norico/portal/issues/>