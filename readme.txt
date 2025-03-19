=== GT Downloads Manager ===
Contributors: gauravtiwari
Tags: downloads, file manager, resources, document manager, download counter, file sharing, gutenberg blocks
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
Requires PHP: 7.4

A lightweight yet powerful downloads manager plugin to showcase and track downloadable resources on your WordPress site.

== Description ==

GT Downloads Manager helps you create, manage, and display downloadable resources on your WordPress site with ease. Track download counts, organize files by categories, and present them in an elegant, responsive layout that looks great on all devices.

### Key Features

* **Modern Admin Interface**: Intuitive dashboard to add, edit, and manage downloadable resources
* **Multiple File Sources**: Upload files to WordPress Media Library or link to external URLs
* **Download Tracking**: Automatically tracks and displays download counts
* **Responsive Layouts**: Choose between grid or table layouts that work on all devices
* **Categorization**: Organize downloads into categories for easier navigation
* **Featured Images**: Add visual appeal with featured images for each download
* **Search & Filter**: Find downloads quickly with built-in search and category filtering
* **Gutenberg Blocks**: Easy-to-use blocks for displaying downloads in the block editor
* **Shortcodes**: Display downloads anywhere using simple shortcodes with customization options
* **Widget Support**: Show downloads in sidebars and widget-ready areas
* **Developer-Friendly**: Rich set of filters for theme and plugin developers to customize functionality
* **Clean Uninstall**: Optional data removal when uninstalling the plugin

### Display Options

GT Downloads Manager provides multiple ways to display your downloads:

* **Grid Layout**: Modern card-based layout (default)
* **Table Layout**: Organized tabular layout with sortable columns
* **Customizable Image Sizes**: Choose from WordPress image sizes for featured images
* **Widget View**: Compact list view for sidebars

### Usage

**Gutenberg Blocks:**

The plugin provides two custom Gutenberg blocks:
* **GT Single Download** - Display a specific download item
* **GT Downloads List** - Display a list of downloads with various options

**Basic Shortcodes:**

* `[gt_downloads]` - Display all downloads in grid layout
* `[gt_download id="123"]` - Display a specific download
* `[gt_downloads category="category-name"]` - Display downloads from a specific category

**Advanced Shortcode Options:**

* `[gt_downloads type="table"]` - Display downloads in table layout
* `[gt_downloads per_page="10"]` - Limit the number of downloads shown
* `[gt_downloads image="thumbnail"]` - Control the featured image size
* `[gt_downloads category="guides" type="table" image="medium"]` - Combined options

**Widget:**

Use the GT Downloads widget to display downloads in widget areas like sidebars or footers with options for:
* Title
* Category filter
* Number of downloads to show

**Developer Functions:**

Integration with themes or plugins:

```php
// Get downloads instance
$downloads = GTDownloadsManager\Downloads::instance();

// Get all downloads in a specific category
$items = $downloads->get_downloads([
    'category' => 'tutorials',
    'per_page' => 5,
    'page' => 1,
    'search' => 'guide'
]);

// Display downloads
foreach ($items as $item) {
    echo $downloads->get_download_html((array)$item);
}

// Get download URL that tracks download counts
$download_url = $downloads->get_download_url($download_data);
== Installation ==

Upload gt-downloads-manager directory to the /wp-content/plugins/ directory
Activate the plugin through the 'Plugins' menu in WordPress
Navigate to 'Downloads' in your admin menu to start adding downloads
Use Gutenberg blocks, shortcodes, widgets, or PHP functions to display downloads on your site
== Frequently Asked Questions ==

= How do I use Gutenberg blocks to display downloads? =

In the block editor, search for "GT Downloads" to find our custom blocks. We provide:

"GT Single Download" block for displaying a specific download
"GT Downloads List" block for displaying multiple downloads with various options
= How do I display downloads in a table layout? =

With Gutenberg blocks, select the "GT Downloads List" block and choose "Table Layout" in the block settings.

With shortcodes, use the type parameter: [gt_downloads type="table"]

= Can I change the image size of featured images? =

Yes, both Gutenberg blocks and shortcodes support this. In blocks, use the "Featured Image Size" setting. With shortcodes, use the image parameter: [gt_downloads image="large"] or any registered image size in WordPress.

= How do I limit the number of downloads shown? =

In blocks, use the "Items Per Page" setting. With shortcodes, use the per_page parameter: [gt_downloads per_page="5"]

= Does this plugin track download counts? =

Yes, each time a user downloads a file, the count is tracked and can be displayed with the download.

= Can I use this for selling digital products? =

This plugin is designed for free downloads. For selling digital products, we recommend using an e-commerce solution.

= Will this work with any theme? =

Yes, GT Downloads Manager is designed to work with any properly coded WordPress theme.

= What happens when I uninstall the plugin? =

By default, your download data is preserved when uninstalling. If you want to completely remove all data, enable this option in the plugin settings before uninstalling.

= Is the plugin translatable? =

Yes, the plugin is fully translatable and uses the 'gtdownloads-manager' text domain.

== Developer Documentation ==

GT Downloads Manager offers multiple filters for developers to customize its output and behavior:

Available Filters
Content Filters:

dm_before_download - HTML content before each download item
dm_after_download - HTML content after each download item
dm_title - Customize the title HTML
dm_description - Customize the description HTML
dm_category - Customize the category HTML
dm_download_button - Customize the download button
dm_download_icon - Customize the download icon SVG
dm_category_icon - Customize the category icon SVG
Example:

Query Filters:

dm_downloads_args - Modify query arguments before getting downloads
Example:

== Screenshots ==

Admin downloads listing with search and filtering
Adding a new download with media uploader
Downloads displayed in grid layout on the frontend
Downloads displayed in table layout
Widget display on sidebar
Settings page for plugin configuration
Gutenberg blocks for downloads
== Changelog ==

= 1.0 =

Initial release with core functionality
Grid and table layout options
Download tracking and statistics
Widget support
Category organization
Developer API and filters
Gutenberg blocks support
== Upgrade Notice ==

= 1.0 = Initial release of GT Downloads Manager. Enjoy the new features and enhancements!