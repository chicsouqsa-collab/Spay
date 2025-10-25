<?php

/**
 * This class is responsible for removing subscriptions in test mode.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\Actions;

use StellarPay\AdminDashboard\Actions\Contracts\TestDataDeletionRule;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Subscriptions\Models\Subscription;

/**
 * @since 1.2.0
 */
class DeleteTestModeSubscriptions implements TestDataDeletionRule
{
    /**
     * @since 1.2.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function __invoke(): void
    {
        while (true) {
            $subscriptions = Subscription::query()
                ->where('payment_gateway_mode', PaymentGatewayMode::TEST)
                ->limit(10)
                ->getAll();

            if (empty($subscriptions)) {
                break;
            }

            foreach ($subscriptions as $subscription) {
                $subscription->delete();
            }
        }
    }
}
