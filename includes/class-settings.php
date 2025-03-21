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
            esc_html__('Downloads Settings', 'gt-downloads-manager'),
            esc_html__('Settings', 'gt-downloads-manager'),
            'manage_options',
            'gtdm-settings',
            [$this, 'render_settings_page']
        );
    }

    public function register_settings() {
        // Register settings with sanitization callback
        register_setting('gtdm_settings', 'gtdm_delete_data_on_uninstall', [
            'sanitize_callback' => [$this, 'sanitize_checkbox']
        ]);

        // Add settings section
        add_settings_section(
            'gtdm_uninstall_section',
            esc_html__('Uninstall Options', 'gt-downloads-manager'),
            [$this, 'render_uninstall_section'],
            'gtdm_settings'
        );

        // Add settings field
        add_settings_field(
            'gtdm_delete_data',
            esc_html__('Delete Plugin Data', 'gt-downloads-manager'),
            [$this, 'render_delete_data_field'],
            'gtdm_settings',
            'gtdm_uninstall_section'
        );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Downloads Manager Settings', 'gt-downloads-manager'); ?></h1>
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
        echo '<p>' . esc_html__('Configure what happens when the plugin is uninstalled.', 'gt-downloads-manager') . '</p>';
    }

    public function render_delete_data_field() {
        $delete_data = get_option('gtdm_delete_data_on_uninstall', false);
        ?>
        <fieldset>
            <label for="gtdm_delete_data">
                <input type="checkbox" id="gtdm_delete_data" name="gtdm_delete_data_on_uninstall" value="1" <?php checked($delete_data, true); ?>>
                <?php esc_html_e('Delete all plugin data when uninstalling', 'gt-downloads-manager'); ?>
            </label>
            <p class="description" style="color: #d63638;">
                <strong><?php esc_html_e('Warning:', 'gt-downloads-manager'); ?></strong> 
                <?php esc_html_e('This will permanently delete all downloads, categories, and settings when the plugin is deleted. This action cannot be undone.', 'gt-downloads-manager'); ?>
            </p>
        </fieldset>
        <?php
    }

    public function sanitize_checkbox($input) {
        return $input ? 1 : 0;
    }
}