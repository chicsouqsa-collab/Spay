<?php

/**
 * This action class adds an Order note when a StellarPay subscription
 * has the payment method updated via SubscriptionsListPage REST API.
 *
 * @since 1.8.0
 * @package StellarPay\Integrations\WooCommerce\Orders\Actions
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Orders\Actions;

use StellarPay\AdminDashboard\RestApi\SubscriptionsListPage;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\ValueObjects\ModifierContextType;
use StellarPay\Integrations\WooCommerce\Utils\OrderNote;
use StellarPay\Subscriptions\Models\Subscription;

/**
 * @since 1.8.0
 */
class AddOrderNoteOnStellarPaySubscriptionPaymentMethodUpdated
{
    /**
     * @since 1.8.0
     * @throws BindingResolutionException|Exception
     */
    public function __invoke(Subscription $subscription, string $token)
    {
        if (SubscriptionsListPage::isEditPaymentMethodRequest()) {
            OrderNote::onSubscriptionPaymentMethodUpdate($subscription, $token, ModifierContextType::ADMIN());
        }
    }
}
