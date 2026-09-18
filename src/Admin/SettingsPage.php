<?php

declare(strict_types=1);

namespace DevMode\Admin;

use DevMode\Constants;

/**
 * Admin settings page for managing watched dev plugins
 *
 * @codeCoverageIgnore WordPress glue code
 */
class SettingsPage
{
    private const PAGE_SLUG = 'dev-mode-settings';
    private const NONCE_ACTION = 'dev_mode_save_settings';

    public static ?string $hookSuffix = null;

    /**
     * Register the settings page
     *
     * @return void
     */
    public static function register(): void
    {
        add_action('admin_menu', [self::class, 'addMenuPage']);
        add_action('admin_init', [self::class, 'handleSave']);
        add_action('admin_enqueue_scripts', [self::class, 'enqueueAssets']);
    }

    /**
     * Add the settings page to the admin menu
     *
     * @return void
     */
    public static function addMenuPage(): void
    {
        self::$hookSuffix = add_options_page(
            'Dev Mode Settings',
            'Dev Mode',
            'manage_options',
            self::PAGE_SLUG,
            [self::class, 'renderPage']
        );
    }

    /**
     * Enqueue assets for the tabbed settings page layout
     *
     * @param string $hook The current admin page
     * @return void
     */
    public static function enqueueAssets(string $hook): void
    {
        if ($hook !== self::$hookSuffix) {
            return;
        }

        wp_enqueue_style(
            'dev-mode-settings',
            Constants::url() . 'assets/css/settings-page.css',
            [],
            Constants::VERSION
        );

        wp_enqueue_script(
            'dev-mode-settings',
            Constants::url() . 'assets/js/settings-page.js',
            [],
            Constants::VERSION,
            true
        );
    }

    /**
     * Handle form submission
     *
     * @return void
     */
    public static function handleSave(): void
    {
        if (!isset($_POST['dev_mode_save'])) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', self::NONCE_ACTION)) {
            return;
        }

        $selectedPlugins = isset($_POST['watched_plugins']) && is_array($_POST['watched_plugins'])
            ? array_map('sanitize_text_field', $_POST['watched_plugins'])
            : [];

        update_option(Constants::OPTION_WATCHED_PLUGINS, $selectedPlugins);

        // Redirect to avoid form resubmission
        wp_safe_redirect(add_query_arg('saved', '1', admin_url('options-general.php?page=' . self::PAGE_SLUG)));
        exit;
    }

    /**
     * Get all installed plugins
     *
     * @return array<string, array> Plugin data indexed by plugin file
     */
    public static function getAllPlugins(): array
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        return get_plugins();
    }

    /**
     * Get the currently watched plugins
     *
     * @return array<string> Array of plugin file paths
     */
    public static function getWatchedPlugins(): array
    {
        $watched = get_option(Constants::OPTION_WATCHED_PLUGINS, null);

        // If never saved, return null to indicate first visit
        if ($watched === null) {
            return [];
        }

        return is_array($watched) ? $watched : [];
    }

    /**
     * Check if this is the first visit (settings never saved)
     *
     * @return bool
     */
    public static function isFirstVisit(): bool
    {
        return get_option(Constants::OPTION_WATCHED_PLUGINS, null) === null;
    }

    /**
     * Render the settings page
     *
     * @return void
     */
    public static function renderPage(): void
    {
        $allPlugins = self::getAllPlugins();
        $watchedPlugins = self::getWatchedPlugins();
        $isFirstVisit = self::isFirstVisit();
        $saved = isset($_GET['saved']);

        ?>
        <div class="wrap">
            <h1>Dev Mode Settings</h1>

            <?php if ($saved) : ?>
                <div class="notice notice-success is-dismissible">
                    <p>Settings saved successfully.</p>
                </div>
            <?php endif; ?>

            <h2 class="nav-tab-wrapper dev-mode-tabs">
                <a href="#" class="nav-tab nav-tab-active" data-tab="settings">Settings</a>
                <a href="#" class="nav-tab" data-tab="data">Data</a>
            </h2>

            <div class="dev-mode-tab-panel" data-tab-panel="settings">
                <p>Select which plugins to show in the Dev Mode dashboard widget.
                   These plugins will be available for quick toggle on/off from the dashboard.</p>

                <?php if ($isFirstVisit) : ?>
                    <div class="notice notice-info">
                        <p><strong>First time setup:</strong> Common dev plugins have been pre-selected below.
                           Adjust the selection and click Save to begin.</p>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <?php wp_nonce_field(self::NONCE_ACTION); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Watched Plugins</th>
                            <td>
                                <fieldset>
                                    <?php foreach ($allPlugins as $pluginFile => $pluginData) : ?>
                                        <?php
                                        // On first visit, pre-check default plugins
                                        $isChecked = $isFirstVisit
                                            ? in_array($pluginFile, Constants::DEFAULT_WATCHED_PLUGINS, true)
                                            : in_array($pluginFile, $watchedPlugins, true);
                                        ?>
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input
                                                type="checkbox"
                                                name="watched_plugins[]"
                                                value="<?php echo esc_attr($pluginFile); ?>"
                                                <?php checked($isChecked); ?>
                                            >
                                            <?php echo esc_html($pluginData['Name']); ?>
                                            <span style="color: #666;">
                                                (<?php echo esc_html($pluginFile); ?>)
                                            </span>
                                        </label>
                                    <?php endforeach; ?>
                                </fieldset>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit"
                               name="dev_mode_save"
                               class="button-primary"
                               value="Save Settings">
                    </p>
                </form>
            </div>

            <div class="dev-mode-tab-panel" data-tab-panel="data" hidden>
                <?php DashboardWidget::renderWidget(); ?>
            </div>
        </div>
        <?php
    }
}
