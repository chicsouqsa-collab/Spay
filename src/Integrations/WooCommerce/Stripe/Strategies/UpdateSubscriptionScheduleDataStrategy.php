<?php

/**
 * This class is responsible to generate data from the Woocommerce order and order item to create subscription schedule on the Stripe.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Strategies
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Strategies;

use StellarPay\Integrations\WooCommerce\Stripe\Repositories\SubscriptionRepository;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Order;
use WC_Order_Item;
use DateTime;

/**
 * @since 1.9.0
 */
class UpdateSubscriptionScheduleDataStrategy extends SubscriptionScheduleDataStrategy
{
    use SubscriptionUtilities;

    /**
     * @since 1.9.0
     * @var SubscriptionRepository
     */
    protected SubscriptionRepository $subscriptionRepository;

    /**
     * @since 1.9.0
     * @var ?DateTime
     */
    private ?DateTime $startDate = null;

    /**
     * Set a custom start date for the schedule.
     *
     * @param DateTime $startDate
     * @return $this
     */
    public function setStartDate(DateTime $startDate): self
    {
        $this->startDate = $startDate;
        return $this;
    }

    /**
     * This function generates data for the Stripe new subscription rest api query.
     *
     * @since 1.9.0
     */
    public function generateData(): array
    {

        $phases = $this->generatePhases();

        if ($this->startDate && ! empty($phases[0])) {
            $phases[0]['start_date'] = $this->startDate->getTimestamp();
        }

        $data = [
            'phases' => $phases
        ];


        /**
         * Filter the return value.
         *
         * Developers can use this filter to modify the new subscription schedule data.
         *
         * @since 1.9.0
         *
         * @param array $data
         * @param Subscription $subscription
         * @param WC_Order $order
         * @param WC_Order_Item $orderItem
         */
        return apply_filters(
            'stellarpay_wc_stripe_generate_update_subscription_schedule_data',
            $data,
            $this->subscription,
            $this->order,
            $this->orderItem
        );
    }
}
