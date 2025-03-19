<?php
namespace GTDownloadsManager;

class Settings {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Register settings page
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function add_settings_page() {
        add_submenu_page(
            'gt-downloads-manager',
            __('Downloads Settings', 'gtdownloads-manager'),
            __('Settings', 'gtdownloads-manager'),
            'manage_options',
            'gtdm-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        // Register settings
        register_setting('gtdm_settings', 'gtdm_delete_data_on_uninstall');

        // Add settings section
        add_settings_section(
            'gtdm_uninstall_section',
            __('Uninstall Options', 'gtdownloads-manager'),
            [$this, 'render_uninstall_section'],
            'gtdm_settings'
        );

        // Add settings field
        add_settings_field(
            'gtdm_delete_data',
            __('Delete Plugin Data', 'gtdownloads-manager'),
            [$this, 'render_delete_data_field'],
            'gtdm_settings',
            'gtdm_uninstall_section'
        );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Downloads Manager Settings', 'gtdownloads-manager'); ?></h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('gtdm_settings');
                do_settings_sections('gtdm_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_uninstall_section() {
        echo '<p>' . __('Configure what happens when the plugin is uninstalled.', 'gtdownloads-manager') . '</p>';
    }

    public function render_delete_data_field() {
        $delete_data = get_option('gtdm_delete_data_on_uninstall', false);
        ?>
        <fieldset>
            <label for="gtdm_delete_data">
                <input type="checkbox" id="gtdm_delete_data" name="gtdm_delete_data_on_uninstall" value="1" <?php checked($delete_data, true); ?>>
                <?php _e('Delete all plugin data when uninstalling', 'gtdownloads-manager'); ?>
            </label>
            <p class="description" style="color: #d63638;">
                <strong><?php _e('Warning:', 'gtdownloads-manager'); ?></strong> 
                <?php _e('This will permanently delete all downloads, categories, and settings when the plugin is deleted. This action cannot be undone.', 'gtdownloads-manager'); ?>
            </p>
        </fieldset>
        <?php
    }
}