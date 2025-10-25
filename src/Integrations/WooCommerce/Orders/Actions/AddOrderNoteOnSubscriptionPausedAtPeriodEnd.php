<?php

/**
 * This action class adds an Order note when a StellarPay subscription
 * is scheduled to be paused at period end.
 *
 * @since 1.9.0
 * @package StellarPay\Integrations\WooCommerce\Orders\Actions
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Orders\Actions;

use StellarPay\AdminDashboard\RestApi\SubscriptionsListPage;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Integrations\WooCommerce\Utils\OrderNote;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Core\ValueObjects\ModifierContextType;

/**
 * @since 1.9.0
 */
class AddOrderNoteOnSubscriptionPausedAtPeriodEnd
{
    /**
     * @since 1.9.0
     * @throws Exception
     */
    public function __invoke(Subscription $subscription): void
    {
        if (SubscriptionsListPage::isPauseRequest()) {
            OrderNote::onSubscriptionPausedAtPeriodEnd($subscription, ModifierContextType::ADMIN());
        }
    }
}
