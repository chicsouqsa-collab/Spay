<?php

/**
 * This class is responsible to provide rest api endpoint which used to get subscription status.
 *
 * @package StellarPay\Integrations\WooCommerce\Views\MyAccountPage\RestApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views\MyAccountPage\RestApi;

use StellarPay\Core\Constants;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\MySubscriptionDTO;
use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\Templates\StatusLabel;
use StellarPay\RestApi\Endpoints\ApiRoute;
use StellarPay\Subscriptions\Models\Subscription;
use WP_Error;
use WP_REST_Request;

/**
 * @since 1.0.0
 */
class SubscriptionStatus extends ApiRoute
{
    /**
     * @inheritdoc
     * @since 1.0.0
     */
    protected string $namespace = Constants::PLUGIN_SLUG . '/v1/subscriptions';

    /**
     * @inheritdoc
     * @since 1.0.0
     */
    protected string $endpoint = 'subscription-status';

    /**
     * @inheritdoc
     * @since 1.0.0
     * @throws Exception
     */
    public function register(): void
    {
        register_rest_route(
            $this->getNamespace(),
            $this->getEndpoint(),
            [
                'methods' => 'GET',
                'callback' => [$this, 'processRequest'],
                'permission_callback' => [$this, 'permissionCheck'],
                'args' => [
                    'id' => [
                        'required' => true,
                        'type' => 'integer',
                        'minimum' => 1,
                        'sanitize_callback' => 'absint'
                    ],
                    'orderId' => [
                        'required' => true,
                        'type' => 'integer',
                        'minimum' => 1,
                        'sanitize_callback' => 'absint'
                    ]
                ],
            ]
        );
    }

    /**
     * @since 1.0.0
     */
    public function permissionCheck(WP_REST_Request $request): bool
    {
        // phpcs:ignore WordPress.WP.Capabilities
        return parent::permissionCheck($request) && ( current_user_can('manage_options') || current_user_can('view_order', $request->get_param('orderId')) );
    }

    /**
     * @since 1.1.0 Refactor to use `StatusLabel` class
     * @since 1.0.0
     * @throws BindingResolutionException|Exception
     */
    public function processRequest(WP_REST_Request $request)
    {
        $subscriptionId = $request->get_param('id');

        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            return new WP_Error(
                'stellarpay_subscription_not_found',
                esc_html__('Subscription not found.', 'stellarpay'),
                ['status' => 404]
            );
        }

        $subscriptionDto = MySubscriptionDTO::fromSubscription($subscription);

        $statusBadgeElement = (new StatusLabel($subscriptionDto))->getHTML();

        return rest_ensure_response([
            'status' => $subscription->status->getValue(),
            'statusLabel' => $subscription->getFormattedStatusLabel(),
            'nextBillingAt' => $subscriptionDto->nextBillingAt,
            'statusBadgeElement' => $statusBadgeElement,
            'expiresAt' => $subscription->expiresAt,
            'pausedAt' => $subscription->suspendedAt,
        ]);
    }
}
