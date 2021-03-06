<?php

namespace Sitepilot\Plugin\Providers;

use Sitepilot\Plugin\Services\CacheService;
use Sitepilot\Plugin\Services\BrandingService;
use Sitepilot\Plugin\Services\DashboardService;
use Sitepilot\Framework\Support\ServiceProvider;
use Sitepilot\Plugin\Services\LitespeedCacheService;

class DashboardServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap application services and hooks.
     */
    public function boot(DashboardService $dashboard): void
    {
        $this->add_action('admin_menu', '@register_admin_menu');

        if ($dashboard->support_enabled()) {
            $this->add_action('in_admin_footer', '@admin_support_script');
        }
    }

    /**
     * Register Sitepilot menu to the dashboard.
     */
    public function register_admin_menu(BrandingService $branding): void
    {
        $slug = $this->app->namespace('menu', '-');

        add_menu_page(
            $branding->name(),
            $branding->name(),
            'publish_posts',
            $slug,
            '',
            $branding->menu_icon(),
            2
        );

        $page_hook_suffix = add_submenu_page(
            $slug,
            $branding->name(),
            __('Dashboard', 'sitepilot'),
            'publish_posts',
            $slug,
            [$this, 'render'],
            -99
        );

        $this->add_action("admin_print_scripts-{$page_hook_suffix}", '@enqueue_assets');
    }

    /**
     * Enqueue dashboard assets.
     */
    public function enqueue_assets(
        CacheService $cache,
        BrandingService $branding,
        DashboardService $dashboard,
        LitespeedCacheService $litespeedCache
    ): void {
        $id = $this->app->namespace('dashboard', '-');

        wp_enqueue_style(
            $id,
            $this->app->public_url('css/dashboard.css'),
            ['wp-components'],
            $this->app->script_version()
        );

        wp_enqueue_script(
            $id,
            $this->app->public_url('js/dashboard.js'),
            ['wp-api', 'wp-i18n', 'wp-components', 'wp-element'],
            $this->app->script_version(),
            true
        );

        global $wp_version;

        $cache_status = __('Off', 'sitepilot');
        if ($cache->is_page_cache_enabled() || $litespeedCache->is_enabled()) {
            $cache_status = sprintf('%s v%s', $branding->name(), $this->app->version());
        } elseif (defined('LSCWP_V')) {
            $cache_status = sprintf(__('LiteSpeed v%s'), LSCWP_V);
        }

        wp_localize_script(
            $id,
            'sitepilot',
            array(
                'version' => 'v' . $this->app->version(),
                'plugin_url' => $this->app->url(),
                'branding_name' => $branding->name(),
                'support_email' => $branding->support_email(),
                'support_url' => $branding->support_website(),
                'server_name' => gethostname(),
                'php_version' => 'v' . phpversion(),
                'wp_version' => 'v' . $wp_version,
                'powered_by' => $branding->powered_by(),
                'support_enabled' => $dashboard->support_enabled(),
                'cache_status' => $cache_status
            )
        );
    }

    /**
     * Render the dashboard.
     */
    public function render(): void
    {
        echo '<div class="sp-dashboard sitepilot" id="sitepilot-dashboard"></div>';
    }

    /**
     * Enqueue support widget.
     */
    public function admin_support_script(BrandingService $branding): void
    {
        $screen = get_current_screen();

        if (!empty($screen->id) && in_array($screen->id, ['dashboard', 'toplevel_page_sitepilot-menu'])) {
            echo $branding->support_widget();
        }
    }
}
