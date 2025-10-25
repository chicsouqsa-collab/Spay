<?php

/**
 * This class is responsible for managing the `my-subscriptions` endpoint.
 *
 * @package StellarPay\Integrations\WooCommerce\Endpoints
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Endpoints;

use StellarPay\Core\Facades\QueryVars;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Core\EnqueueScript;
use StellarPay\Core\Exceptions\BindingResolutionException;

use function StellarPay\Core\getNonceActionName;
use function StellarPay\Core\getNonceUrl;

/**
 * MySubscriptionsEndpoint class
 *
 * @since 1.7.0 Remove __invoke function. We should register hook in the service provider.
 * @since 1.1.0
 */
class MySubscriptionsEndpoint
{
    /**
     * @since 1.1.0
     */
    public const MY_SUBSCRIPTIONS_SLUG = 'my-subscriptions';

    /**
     * Enqueue assets.
     *
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function enqueueAssets(): void
    {
        global $wp;

        $shouldEnqueue = QueryVars::has(self::MY_SUBSCRIPTIONS_SLUG)
                         || QueryVars::has('view-order')
                         || is_order_received_page();

        if (!$shouldEnqueue) {
            return;
        }

        $scriptId = 'stellarpay-my-account-subscriptions';

        (new EnqueueScript($scriptId, "/build/$scriptId.js"))
            ->loadInFooter()
            ->loadStyle()
            ->registerLocalizeData('stellarPaySubscriptions', ['apiRoute' => 'stellarpay/v1/subscriptions/subscription-status'])
            ->enqueue();
    }

    /**
     * Add `Subscriptions` item to My account page.
     *
     * @since 1.1.0
     */
    public function addMenuItem(array $items): array
    {
        $updatedItems = [];

        foreach ($items as $key => $value) {
            $updatedItems[$key] = $value;

            if ('orders' !== $key) {
                continue;
            }

            $updatedItems[self::MY_SUBSCRIPTIONS_SLUG] = esc_html__('Subscriptions', 'stellarpay');
        }

        return $updatedItems;
    }

    /**
     * Make `Subscriptions` menu item active when viewing
     * a subscription.
     *
     * @since 1.1.0
     */
    public function makeSubscriptionMenuItemActive(array $classes, string $endpoint): array
    {
        global $wp;

        if (self::MY_SUBSCRIPTIONS_SLUG !== $endpoint) {
            return $classes;
        }

        $isViewSubscription = QueryVars::get(self::MY_SUBSCRIPTIONS_SLUG, false);

        if (!$isViewSubscription) {
            return $classes;
        }

        $classes[] = 'is-active';

        return $classes;
    }

    /**
     * Add `my-subscriptions` to the WooCommerce query vars so
     * is_wc_endpoint_url() will return true when visiting
     * `my-subscriptions` tab.
     *
     * @since 1.1.0
     */
    public function addSubscriptionsSlugToWooCommerceQueryVars($queryVars): array
    {
        $queryVars[self::MY_SUBSCRIPTIONS_SLUG] = self::MY_SUBSCRIPTIONS_SLUG;

        return $queryVars;
    }

    /**
     * Check if the current page is My Subscription pages
     *
     * @since 1.1.0
     */
    public static function isPage(): bool
    {
        return QueryVars::has(self::MY_SUBSCRIPTIONS_SLUG);
    }

    /**
     * Check if the current page is the Subscription Update Payment
     * Method page
     *
     * @since 1.1.0
     */
    public static function isSubscriptionUpdatePaymentMethodPage(): bool
    {
        return (bool) self::getQueryVars();
    }

    /**
     * Get the subscription from Update Payment Method query vars.
     *
     * @since 1.1.0
     */
    public static function getSubscriptionFromQueryVars(): ?Subscription
    {
        if (! $data = self::getQueryVars()) {
            return null;
        }

        $subscriptionId = absint($data['subscriptionId'] ?? 0);

        if (empty($subscriptionId)) {
            return null;
        }

        $subscription = Subscription::find($subscriptionId);

        if (! $subscription) {
            return null;
        }

        return $subscription;
    }

    /**
     * Get the query vars data from Update Payment Method page.
     *
     * @since 1.1.0
     */
    public static function getQueryVars(): ?array
    {
        if (QueryVars::missing(self::MY_SUBSCRIPTIONS_SLUG)) {
            return null;
        }

        $subscriptionQueryVars = QueryVars::get(self::MY_SUBSCRIPTIONS_SLUG);

        if (empty($subscriptionQueryVars)) {
            return null;
        }

        $subscriptionData = explode('/', $subscriptionQueryVars);
        $subscriptionId = absint($subscriptionData[0] ?? 0);
        $action = $subscriptionData[1] ?? '';

        if (empty($subscriptionId)) {
            return null;
        }

        return [
            'subscriptionId' => $subscriptionId,
            'action' => $action,
        ];
    }

    /**
     * Output Invalid subscription notice.
     *
     * @since 1.1.0
     */
    public static function invalidSubscriptionNotice()
    {
        wc_print_notice(
            sprintf(
                (
                    // translators: %s - link to the subscriptions page.
                    __('Invalid subscription <a href="%s" class="wc-forward">My subscriptions</a>', 'stellarpay')
                ),
                esc_url(wc_get_page_permalink('myaccount') . self::MY_SUBSCRIPTIONS_SLUG)
            ),
            'error'
        );
    }

    /**
     * Get an action URL with nonce.
     *
     * @since 1.1.0
     */
    public static function getActionNonceURL(string $action, int $subscriptionId): string
    {
        return add_query_arg(
            [
                'action' => $action,
            ],
            getNonceUrl(
                self::getNonceAction($action, $subscriptionId),
                wc_get_endpoint_url(self::MY_SUBSCRIPTIONS_SLUG, (string) $subscriptionId)
            )
        );
    }

    /**
     * Get nonce action name.
     *
     * @since 1.1.0
     */
    public static function getNonceAction(string $action, int $subscriptionId): string
    {
        $userId = get_current_user_id();

        return getNonceActionName("{$action}-{$userId}-{$subscriptionId}");
    }

    /**
     * Get the subscription URL
     *
     * @since 1.1.0
     */
    public static function getSubscriptionURL(int $subscriptionId): string
    {
        return wc_get_account_endpoint_url(self::MY_SUBSCRIPTIONS_SLUG . '/' . $subscriptionId);
    }

    /**
     * Get an action URL with nonce.
     *
     * @since 1.1.0
     */
    public static function getActionURL(string $action, int $subscriptionId): string
    {
        return  self::getSubscriptionURL($subscriptionId) . '/' . $action;
    }
}
