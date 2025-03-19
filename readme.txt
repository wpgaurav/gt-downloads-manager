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