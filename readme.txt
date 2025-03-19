=== GT Downloads Manager ===
Contributors: gauravtiwari
Tags: downloads, file manager, resources, document manager, download counter
Requires at least: 5.0
Tested up to: 6.3
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 7.4

A lightweight yet powerful downloads manager plugin to showcase and track downloadable resources on your WordPress site.

== Description ==

GT Downloads Manager helps you create, manage, and display downloadable resources on your WordPress site with ease. Organize files by categories, track download counts, and display them using customizable shortcodes or widgets.

### Features

* **Easy Management**: Add, edit, and organize downloadable files from a simple admin interface
* **Multiple File Sources**: Upload files to WordPress Media Library or link to external URLs
* **Download Tracking**: Automatically tracks download counts for analytics
* **Categorization**: Organize downloads into categories for better user navigation
* **Featured Images**: Add featured images to make your downloads visually appealing
* **Responsive Design**: Beautifully displays on all devices with a modern grid layout
* **Multiple Display Options**:
  * Shortcodes for posts and pages
  * Widget for sidebars
  * PHP functions for developers
* **Clean Uninstall**: Option to keep or remove data when uninstalling the plugin

### Usage

**Shortcodes:**

* `[gt_downloads]` - Display all downloads
* `[gt_downloads category="category-name"]` - Display downloads from a specific category
* `[gt_download id="123"]` - Display a specific download

**Widget:**

Use the GT Downloads widget to display downloads in widget areas like sidebars or footers.

**Developer Functions:**

For developers looking to integrate downloads directly into their themes:

```php
$downloads = GTDownloadsManager\Downloads::instance();
$items = $downloads->get_downloads(['category' => 'example']);

foreach ($items as $item) {
    echo $downloads->get_download_html((array)$item);
}
```

== Installation ==

1. Upload `gt-downloads-manager` directory to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Downloads' in your admin menu to start adding downloads
4. Use the shortcodes, widgets, or PHP functions to display downloads on your site

== Frequently Asked Questions ==

= Can I limit the number of downloads shown? =

Yes, you can use the shortcode parameter: `[gt_downloads per_page="5"]` to limit the number of downloads displayed.

= Does this plugin track download counts? =

Yes, each time a user downloads a file, the count is tracked and displayed with the download.

= Can I use this for digital products? =

This plugin is primarily designed for free downloads. For selling digital products, we recommend using an e-commerce solution.

= Will this work with any theme? =

Yes, GT Downloads Manager is designed to work with any properly coded WordPress theme.

= What happens when I uninstall the plugin? =

By default, your download data is preserved when uninstalling. If you want to completely remove all data, enable this option in the plugin settings before uninstalling.

== Screenshots ==

1. Admin downloads listing
2. Adding a new download
3. Download display on the frontend
4. Widget display 
5. Settings page

== Changelog ==

= 1.0 =
* Initial release with core functionality

== Upgrade Notice ==

= 1.0 =
Initial release of GT Downloads Manager.

== Credits ==

* Developed by Gaurav Tiwari
* Icon graphics using Material Design icons