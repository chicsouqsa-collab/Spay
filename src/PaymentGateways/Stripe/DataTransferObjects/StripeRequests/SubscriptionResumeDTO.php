<?php

/**
 * Subscription Resume Data Transfer Object.
 *
 * This class is used to manage the data for resuming a subscription.
 *
 * @package StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests
 * @since 1.9.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests;

use StellarPay\PaymentGateways\Stripe\ValueObjects\BillingCycleAnchor;
use StellarPay\PaymentGateways\Stripe\ValueObjects\ProrationBehavior;

/**
 * Class SubscriptionResumeDTO
 *
 * @since 1.9.0
 */
class SubscriptionResumeDTO
{
    /**
     * The Stripe subscription ID.
     *
     * @since 1.9.0
     */
    private string $stripeSubscriptionId;

    /**
     * The billing cycle anchor.
     *
     * @since 1.9.0
     */
    private BillingCycleAnchor $billingCycleAnchor;

    /**
     * The proration behavior.
     *
     * @since 1.9.0
     */
    private ProrationBehavior $prorationBehavior;

    /**
     * Create a new SubscriptionResumeDTO instance from array.
     *
     * @since 1.9.0
     *
     * @param array{
     *     stripeSubscriptionId: string,
     *     billingCycleAnchor: BillingCycleAnchor,
     *     prorationBehavior: ProrationBehavior
     * } $data The data array containing stripeSubscriptionId, billingCycleAnchor and prorationBehavior
     */
    public static function fromArray(array $data): self
    {
        $self = new self();

        $self->stripeSubscriptionId = $data['stripeSubscriptionId'];
        $self->billingCycleAnchor = $data['billingCycleAnchor'];
        $self->prorationBehavior = $data['prorationBehavior'];

        return $self;
    }

    /**
     * Convert the object to an array.
     *
     * This function returns data as an array which is compatible with the Stripe API.
     * You can check subscription resume documentation in Stripe API for more information.
     * https://docs.stripe.com/api/subscriptions/update
     *
     * @since 1.9.0
     * @return array<string, string|null|array<string, string|null>>
     */
    public function toArray(): array
    {
        return [
            'pause_collection' => null,
            'billing_cycle_anchor' => $this->billingCycleAnchor->getValue(),
            'proration_behavior'   => $this->prorationBehavior->getValue(),
        ];
    }

    /**
     * Get the Stripe subscription ID.
     *
     * @since 1.9.0
     */
    public function getStripeSubscriptionId(): string
    {
        return $this->stripeSubscriptionId;
    }
}
