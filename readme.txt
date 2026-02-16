=== GT Downloads Manager ===
Contributors: gauravtiwari
Tags: downloads, file manager, resources, wordpress blocks, shortcode
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 8.1
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Table-driven downloads manager with REST API, dynamic blocks, tracked links, and a built-in docs page.

== Description ==

GT Downloads Manager 2.0 stores download records in its own custom table:

- Table: `{$wpdb->prefix}gtdm_downloads`
- Columns include title, description, media/direct source, categories, tags, status, and `download_count`
- No dependency on `wp_posts` for download data

### What you get in v2

- Server-rendered and AJAX-enhanced discovery UI
- Search, category filtering, tag filtering, sorting, and pagination
- REST API namespace `gtdm/v2`
- Dynamic Gutenberg blocks
- Download tracking with simple anti-abuse throttling
- Admin menu with custom download manager UI
- In-plugin Docs submenu with shortcode and REST usage

### Shortcodes

- `[gtdm_download id="123" image="medium"]`
- `[gtdm_downloads category="" tag="" search="" sort="newest" per_page="12" page="1" layout="grid" filters="1"]`

### Query vars (no-JS fallback)

- `gtdm_s`
- `gtdm_cat`
- `gtdm_tag`
- `gtdm_sort`
- `gtdm_page`

### REST API endpoints

- `GET /wp-json/gtdm/v2/downloads`
- `GET /wp-json/gtdm/v2/downloads/<id>`
- `GET /wp-json/gtdm/v2/terms/categories`
- `GET /wp-json/gtdm/v2/terms/tags`
- `POST /wp-json/gtdm/v2/downloads/<id>/track`

### Blocks

- `gtdm/downloads-query`
- `gtdm/download-filters`
- `gtdm/download-card`

== Installation ==

1. Upload plugin files to `/wp-content/plugins/gt-downloads-manager`.
2. Activate the plugin through the Plugins screen.
3. Go to **Downloads** in wp-admin and create download entries.
4. Open **Downloads > Docs** for endpoint and shortcode reference.
5. Insert shortcodes or blocks where you want downloads to appear.

== Frequently Asked Questions ==

= Can this plugin run without custom post types? =

Yes. Download records are stored in the plugin's own custom table.

= Does this support direct external URLs and Media Library files? =

Yes. Choose source type in the download edit screen.

= Is JavaScript required for filtering? =

No. Filtering and pagination work without JavaScript using query vars and full page reloads.

= Does v2 migrate v1 custom-table data? =

No. Version 2.0 is a breaking rebuild with hard reset behavior.

== Changelog ==

= 2.0.0 =

- Rebuilt plugin architecture with modular service layers
- Rebuilt storage on a dedicated plugin table (`gtdm_downloads`)
- Added full discovery suite (search, category, tag, sort, pagination)
- Added REST API v2 endpoints for listings, terms, and tracking
- Added new dynamic blocks and modern block build tooling (`@wordpress/scripts`)
- Added tracked download controller route with throttled counting
- Added Docs submenu page under plugin menu
- Removed legacy `dm_download` URL and legacy shortcode/block/widget stack

= 1.0 =

- Initial release

== Upgrade Notice ==

= 2.0.0 =

Important breaking release.

- This version performs a hard reset of legacy table-based data.
- There is no automatic migration from v1 data model.
- Legacy `?dm_download=...` URLs and v1 shortcodes/blocks are not supported.
