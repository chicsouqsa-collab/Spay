<?php

/**
 * Service Provider
 *
 * This file is responsible for registering and booting the service provider for plugin admin dashboard.
 *
 * @package StellarPay\AdminDashboard
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard;

use StellarPay\AdminDashboard\Actions\AddClassesToAdminBody;
use StellarPay\AdminDashboard\Actions\DeleteWebhookEventsBasedOnInterval;
use StellarPay\AdminDashboard\RestApi\MigrationLogs;
use StellarPay\AdminDashboard\RestApi\Options;
use StellarPay\AdminDashboard\RestApi\StripeStats;
use StellarPay\AdminDashboard\RestApi\TestModeData;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Hooks;
use StellarPay\Integrations\ActionScheduler\Jobs\DailyRecurringJob;
use StellarPay\Integrations\WooCommerce\Analytics\RestApi\Leaderboards;
use StellarPay\Integrations\WooCommerce\Analytics\RestApi\Orders;
use StellarPay\Integrations\WooCommerce\Analytics\RestApi\Performances;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\AdminDashboard\RestApi\WebhookEventsListPage;
use StellarPay\AdminDashboard\RestApi\SubscriptionsListPage;

use function StellarPay\Core\container;

/**
 * Class ServiceProvider.
 *
* @since 1.0.0`
 */
class ServiceProvider implements \StellarPay\Core\Contracts\ServiceProvider
{
    /**
     * @inheritdoc
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        container()->singleton(Repositories\OptionsRepository::class);

        container()->singleton(AdminMenu::class);
    }

    /**
     * @inheritDoc
     *
     * @since 1.3.0 Register task to delete webhook events data
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        // Remove default admin footer.
        Hooks::addFilter('admin_footer_text', AdminFooterManager::class, '__invoke', 9999);
        Hooks::addFilter('update_footer', AdminFooterManager::class, '__invoke', 9999);

        // Register admin menu.
        Hooks::addAction('admin_menu', AdminMenu::class, 'registerMenus');

        // Register admin bar menu.
        Hooks::addAction('admin_bar_menu', AdminMenu::class, 'registerMenuBar');
        Hooks::addAction('admin_head', AdminMenu::class, 'addMenuBarStyle');
        Hooks::addAction('wp_head', AdminMenu::class, 'addMenuBarStyle');

        // Remove WordPress notices from our plugin's admin pages.
        Hooks::addAction('admin_notices', NoticeManager::class, '__invoke', 0);

        if (container(AccountRepository::class)->isLiveModeConnected()) {
            $this->registerRestApiEndpoints();
        }

        if (! is_ssl()) {
            Hooks::addAction('admin_notices', NoticeManager::class, 'showSslRequiredNotice');
        }

        Hooks::addAction(TestModeDataDeletionRuleRunner::getActionSchedulerJobName(), TestModeDataDeletionRuleRunner::class);
        DailyRecurringJob::registerTask(DeleteWebhookEventsBasedOnInterval::class);

        Hooks::addFilter('admin_body_class', AddClassesToAdminBody::class);
    }

    /**
     * @since 1.1.0 Add "WebhookEventsListPage" api endpoint.
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    private function registerRestApiEndpoints(): void
    {
        $restApiEndpoint = [
            StripeStats::class,
            Options::class,
            Leaderboards::class,
            Orders::class,
            Performances::class,
            TestModeData::class,
            WebhookEventsListPage::class,
            SubscriptionsListPage::class,
            MigrationLogs::class
        ];

        foreach ($restApiEndpoint as $restApiClass) {
            Hooks::addAction('rest_api_init', $restApiClass, 'register');
        }
    }
}
