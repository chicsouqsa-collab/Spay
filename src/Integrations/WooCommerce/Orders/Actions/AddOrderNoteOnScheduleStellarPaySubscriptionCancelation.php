<?php

/**
 * This action class adds an Order note when a StellarPay subscription
 * is scheduled to be canceled at the end of the current
 * period via SubscriptionsListPage REST API.
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
class AddOrderNoteOnScheduleStellarPaySubscriptionCancelation
{
    /**
     * @since 1.8.0
     * @throws Exception|BindingResolutionException
     */
    public function __invoke(Subscription $subscription)
    {
        if (SubscriptionsListPage::isCancelationRequest()) {
            OrderNote::onScheduleSubscriptionCancelation(
                $subscription,
                ModifierContextType::ADMIN()
            );
        }
    }
}
