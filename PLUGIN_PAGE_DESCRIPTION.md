# GT Downloads Manager

**Create a proper downloads hub in WordPress, without hijacking your posts table.**

GT Downloads Manager is built for people who publish resources, lead magnets, checklists, templates, and tools, then struggle to keep them organized and easy to discover.

I built it to solve a simple problem: WordPress makes publishing posts easy, but managing a real downloads library is usually messy. You end up forcing everything into posts, custom hacks, or a plugin that breaks the moment requirements change. This plugin gives you a dedicated data model, clean frontend output, and a usable admin workflow.

## Short Description (WordPress.org style)

Manage downloadable resources in a dedicated custom table with Gutenberg blocks, REST API filters, tracked links, and no dependency on `wp_posts`.

## Who This Plugin Is For

- Bloggers publishing free guides, PDFs, and templates
- Agencies building client resource libraries
- Product teams shipping changelogs, files, and docs
- Educators sharing worksheets, notes, and downloadable assets

If your downloads catalog is bigger than a few links in a post, this is for you.

## Why GT Downloads Manager

Most download plugins either feel too basic or too bloated. This one focuses on what actually matters in production:

- Dedicated custom table storage (`wp_gtdm_downloads`), not `wp_posts`
- Fast filtering and listing by search, category, tag, sort, and page
- Works with and without JavaScript (server fallback included)
- Gutenberg blocks for modern editors, shortcodes for flexible placement
- Built-in tracked download URLs with simple anti-abuse throttling
- REST API ready for headless, custom frontends, and integrations

## Core Features

### 1) Dedicated Downloads Data Layer

Your records are stored in a plugin-owned table:

- `{$wpdb->prefix}gtdm_downloads`
- Fields include title, excerpt, description, source type, file/direct URL, categories, tags, status, and download count
- No runtime dependency on posts, post meta, or taxonomy tables for download records

This gives you cleaner control and predictable behavior when your catalog grows.

### 2) Better Admin Workflow

The backend UI is now designed for real editing sessions, not just raw fields.

- Bold, cleaner admin layout with grouped sections
- `Save Download` and `Save & Add Another` actions
- Media picker for file attachment selection
- Media picker for featured image selection
- Categories and Tags submenus under Downloads
- Term management screens for rename/delete and quick filtering

### 3) Discovery UI That People Actually Use

Frontend discovery includes:

- Search by keyword
- Filter by category and tag
- Sort by newest, oldest, popular, title A-Z, title Z-A
- Pagination
- Grid and table layouts
- Accessible filter forms

JavaScript enhancement is included, but if JS fails, it still works with query vars and full reloads.

### 4) Modern Gutenberg Blocks

Three dynamic blocks are included:

- `gtdm/downloads-query`
- `gtdm/download-filters`
- `gtdm/download-card`

Editor improvements:

- Category and tag suggestion chips with 300ms debounce
- Card block search by download name (not numeric ID entry)
- Card block defaults to latest published download if no selection

### 5) Shortcodes for Quick Placement

- `[gtdm_download id="123" image="medium"]`
- `[gtdm_downloads category="" tag="" search="" sort="newest" per_page="12" page="1" layout="grid" filters="1"]`

### 6) Download Delivery + Tracking

Each download can point to:

- Media Library file (`file_source: media`)
- Direct URL (`file_source: direct`)

Tracked links increment download counts with throttle protection via cookie + transient window.

## Frontend Query Variables (No-JS Fallback)

- `gtdm_s`
- `gtdm_cat`
- `gtdm_tag`
- `gtdm_sort`
- `gtdm_page`

## Requirements

- WordPress 6.4+
- PHP 8.1+

## REST API Documentation

Base namespace:

- `/wp-json/gtdm/v2`

All read routes are public. Tracking is also public by design (with throttle checks).

### Endpoint Summary

| Method | Route | Purpose |
|---|---|---|
| `GET` | `/downloads` | Filtered downloads list + rendered HTML fragment |
| `GET` | `/downloads/{id}` | Single published download payload |
| `GET` | `/downloads/search` | Search downloads by title/slug for UI selectors |
| `GET` | `/terms/categories` | Category suggestions/list |
| `GET` | `/terms/tags` | Tag suggestions/list |
| `POST` | `/downloads/{id}/track` | Track a download click |

### 1) `GET /wp-json/gtdm/v2/downloads`

Returns a filtered result set plus server-rendered HTML (useful for progressive enhancement).

**Query parameters**

- `search` (`string`) keyword search across title/description/excerpt
- `category` (`string`) category slug
- `tag` (`string`) tag slug
- `sort` (`string`) one of: `newest`, `oldest`, `popular`, `title_asc`, `title_desc`
- `page` (`integer`)
- `per_page` (`integer`, max 50)
- `layout` (`string`) `grid` or `table`
- `context_url` (`string`) optional URL used for pagination base

**Example**

```bash
curl "https://example.com/wp-json/gtdm/v2/downloads?search=seo&category=guides&sort=popular&page=1&per_page=12"
```

**Response shape**

```json
{
  "items": [
    {
      "id": 1,
      "title": "Sample Table Download",
      "description": "<p>...</p>",
      "excerpt": "...",
      "download_url": "https://example.com/gtdm-download/1/",
      "download_count": 3,
      "source": "direct",
      "file_id": 0,
      "direct_url": "https://example.com/file.pdf",
      "featured_image": "https://example.com/uploads/...jpg",
      "categories": [{ "id": 123, "slug": "guides", "name": "Guides" }],
      "tags": [{ "id": 456, "slug": "pdf", "name": "Pdf" }],
      "status": "publish",
      "created_at": "2026-02-16 10:00:00",
      "updated_at": "2026-02-16 10:10:00"
    }
  ],
  "html": "<div class=\"gtdm-grid\">...</div>",
  "page": 1,
  "per_page": 12,
  "total": 1,
  "total_pages": 1,
  "state": {
    "search": "seo",
    "category": "guides",
    "tag": "",
    "sort": "popular",
    "page": 1,
    "per_page": 12,
    "layout": "grid",
    "filters": 0,
    "image": "medium"
  }
}
```

### 2) `GET /wp-json/gtdm/v2/downloads/{id}`

Returns a single published download.

**Example**

```bash
curl "https://example.com/wp-json/gtdm/v2/downloads/1"
```

**Errors**

- `404` with code `gtdm_not_found` when missing/unpublished

### 3) `GET /wp-json/gtdm/v2/downloads/search`

Used for fast selection UIs in block inspector/admin helpers.

**Query parameters**

- `search` (`string`) title/slug lookup
- `per_page` (`integer`, default 12)

**Example**

```bash
curl "https://example.com/wp-json/gtdm/v2/downloads/search?search=checklist&per_page=8"
```

**Response shape**

```json
[
  {
    "id": 1,
    "title": "Sample Table Download",
    "slug": "sample-table-download",
    "status": "publish"
  }
]
```

### 4) `GET /wp-json/gtdm/v2/terms/categories`

Returns category terms from download records and term registry.

**Query parameters**

- `search` (`string`) slug/name filter
- `per_page` (`integer`, default 10)

**Example**

```bash
curl "https://example.com/wp-json/gtdm/v2/terms/categories?search=guid&per_page=8"
```

### 5) `GET /wp-json/gtdm/v2/terms/tags`

Returns tag terms from download records and term registry.

**Query parameters**

- `search` (`string`) slug/name filter
- `per_page` (`integer`, default 10)

**Example**

```bash
curl "https://example.com/wp-json/gtdm/v2/terms/tags?search=pdf&per_page=8"
```

**Term response shape (`categories` and `tags`)**

```json
[
  {
    "id": 1299682799,
    "name": "Guides",
    "slug": "guides",
    "count": 1
  }
]
```

### 6) `POST /wp-json/gtdm/v2/downloads/{id}/track`

Tracks download attempts. Returns whether increment happened or request was throttled.

**Example**

```bash
curl -X POST "https://example.com/wp-json/gtdm/v2/downloads/1/track"
```

**Response shape**

```json
{
  "tracked": true,
  "throttled": false,
  "download_count": 4
}
```

If throttle blocks repeated requests inside the window:

```json
{
  "tracked": false,
  "throttled": true,
  "download_count": 4
}
```

## Developer Notes

- Tracking throttle interval is filterable via `gtdm_track_interval`
- Sort sanitization is strict; invalid values fall back to `newest`
- Term and download suggestion routes are ideal for editor UIs and custom block controls

## Suggested CTA Copy for Plugin Page

- **Primary CTA:** Download GT Downloads Manager
- **Secondary CTA:** View REST API Docs
- **Trust line:** Built for real download catalogs, not one-off file links.

## Existing Snippet Reference

Your current public snippet:

- https://gauravtiwari.org/snippet/gt-downloads-manager-plugin/

This Markdown expands it into a full plugin-page draft + implementation-level API docs for docs pages, sales pages, or WordPress.org content adaptation.
