<?php

/**
 * This class is used access subscription data.
 *
 * @package StellarPay\Subscriptions\DataTransferObjects
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\DataTransferObjects;

use DateTime;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\ValueObjects\Money;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Core\ValueObjects\SubscriptionPeriod;
use StellarPay\Core\ValueObjects\SubscriptionSource;
use StellarPay\Core\ValueObjects\SubscriptionStatus;
use StellarPay\Subscriptions\Models\Subscription;

/**
 * @since 1.8.0 Add support for initial amount, recurring amount and currency code.
 * @since 1.3.0 New properties `expiresAt` and `expiresAtGmt`
 * @since 1.0.0
 */
class SubscriptionQueryData
{
    /**
     * @since 1.0.0
     */
    public int $id;

    /**
     * @since 1.0.0
     */
    public int $customerId;

    /**
     * @since 1.0.0
     */
    public ?int $firstOrderId;

    /**
     * @since 1.0.0
     */
    public ?int $firstOrderItemId;

    /**
     * @since 1.0.0
     */
    public SubscriptionPeriod $period;

    /**
     * @since 1.0.0
     */
    public ?int $frequency;

    /**
     * @since 1.0.0
     */
    public SubscriptionStatus $status;

    /**
     * @since 1.0.0
     */
    public ?string $transactionId;

    /**
     * @since 1.0.0
     */
    public ?int $billingTotal;

    /**
     * @since 1.0.0
     */
    public ?int $billedCount;

    /**
     * @since 1.8.0
     */
    public ?Money $initialAmount;

    /**
     * @since 1.8.0
     */
    public ?Money $recurringAmount;

    /**
     * @since 1.8.0
     */
    public ?string $currencyCode;

    /**
     * @since 1.0.0
     */
    public PaymentGatewayMode $paymentGatewayMode;

    /**
     * @since 1.0.0
     */
    public DateTime $createdAt;

    /**
     * @since 1.0.0
     */
    public DateTime $createdAtGmt;

    /**
     * @since 1.0.0
     */
    public DateTime $updatedAt;

    /**
     * @since 1.0.0
     */
    public DateTime $updatedAtGmt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $startedAt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $startedAtGmt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $endedAt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $endedAtGmt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $trialStartedAt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $trialStartedAtGmt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $trialEndedAt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $trialEndedAtGmt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $nextBillingAt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $nextBillingAtGmt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $expiredAt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $expiredAtGmt;

    /**
     * @since 1.9.0
     */
    public ?DateTime $resumedAt;

    /**
     * @since 1.9.0
     */
    public ?DateTime $resumedAtGmt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $suspendedAt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $suspendedAtGmt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $canceledAt;

    /**
     * @since 1.0.0
     */
    public ?DateTime $canceledAtGmt;

    /**
     * @since 1.0.0
     */
    public SubscriptionSource $source;

    /**
     * @since 1.3.0
     */
    public ?DateTime $expiresAt;

    /**
     * @since 1.3.0
     */
    public ?DateTime $expiresAtGmt;

    /**
     * @since 1.0.0
     */
    public static function fromObject(object $subscriptionQueryObject): self
    {
        $self = new self();

        $self->id = absint($subscriptionQueryObject->id);
        $self->customerId = absint($subscriptionQueryObject->customer_id);
        $self->firstOrderId = $subscriptionQueryObject->first_order_id ? absint($subscriptionQueryObject->first_order_id) : null;
        $self->firstOrderItemId = $subscriptionQueryObject->first_order_item_id ? absint($subscriptionQueryObject->first_order_item_id) : null;
        $self->period = new SubscriptionPeriod($subscriptionQueryObject->period);
        $self->frequency = $subscriptionQueryObject->frequency ? absint($subscriptionQueryObject->frequency) : null;
        $self->status = new SubscriptionStatus($subscriptionQueryObject->status);
        $self->transactionId = $subscriptionQueryObject->transaction_id ?? null;
        $self->billingTotal = $subscriptionQueryObject->billing_total ? absint($subscriptionQueryObject->billing_total) : null;
        $self->billedCount = $subscriptionQueryObject->billed_count ? absint($subscriptionQueryObject->billed_count) : null;
        $self->currencyCode = $subscriptionQueryObject->currency_code ?? null;
        $self->initialAmount = $subscriptionQueryObject->initial_amount ? Money::fromMinorAmount(absint($subscriptionQueryObject->initial_amount), $self->currencyCode) : null;
        $self->recurringAmount = $subscriptionQueryObject->recurring_amount ? Money::fromMinorAmount(absint($subscriptionQueryObject->recurring_amount), $self->currencyCode) : null;
        $self->paymentGatewayMode = new PaymentGatewayMode($subscriptionQueryObject->payment_gateway_mode);
        $self->createdAt = Temporal::toDateTime($subscriptionQueryObject->created_at);
        $self->createdAtGmt = Temporal::toGMTDateTime($subscriptionQueryObject->created_at_gmt);
        $self->updatedAt = Temporal::toDateTime($subscriptionQueryObject->updated_at);
        $self->updatedAtGmt = Temporal::toGMTDateTime($subscriptionQueryObject->updated_at_gmt);
        $self->startedAt = $subscriptionQueryObject->started_at ? Temporal::toDateTime($subscriptionQueryObject->started_at) : null;
        $self->startedAtGmt = $subscriptionQueryObject->started_at_gmt ? Temporal::toGMTDateTime($subscriptionQueryObject->started_at_gmt) : null;
        $self->endedAt = $subscriptionQueryObject->ended_at ? Temporal::toDateTime($subscriptionQueryObject->ended_at) : null;
        $self->endedAtGmt = $subscriptionQueryObject->ended_at_gmt ? Temporal::toGMTDateTime($subscriptionQueryObject->ended_at_gmt) : null;
        $self->trialStartedAt = $subscriptionQueryObject->trial_started_at ? Temporal::toDateTime($subscriptionQueryObject->trial_started_at) : null;
        $self->trialStartedAtGmt = $subscriptionQueryObject->trial_started_at_gmt ? Temporal::toGMTDateTime($subscriptionQueryObject->trial_started_at_gmt) : null;
        $self->trialEndedAt = $subscriptionQueryObject->trial_started_at ? Temporal::toDateTime($subscriptionQueryObject->trial_started_at) : null;
        $self->trialEndedAtGmt = $subscriptionQueryObject->trial_started_at_gmt ? Temporal::toGMTDateTime($subscriptionQueryObject->trial_started_a_gmtt) : null;
        $self->nextBillingAt = $subscriptionQueryObject->next_billing_at ? Temporal::toDateTime($subscriptionQueryObject->next_billing_at) : null;
        $self->nextBillingAtGmt = $subscriptionQueryObject->next_billing_at_gmt ? Temporal::toGMTDateTime($subscriptionQueryObject->next_billing_at_gmt) : null;
        $self->expiredAt = $subscriptionQueryObject->expired_at ? Temporal::toDateTime($subscriptionQueryObject->expired_at) : null;
        $self->expiredAtGmt = $subscriptionQueryObject->expired_at_gmt ? Temporal::toGMTDateTime($subscriptionQueryObject->expired_at_gmt) : null;
        $self->suspendedAt = $subscriptionQueryObject->suspended_at ? Temporal::toDateTime($subscriptionQueryObject->suspended_at) : null;
        $self->suspendedAtGmt = $subscriptionQueryObject->suspended_at_gmt ? Temporal::toGMTDateTime($subscriptionQueryObject->suspended_at_gmt) : null;
        $self->canceledAt = $subscriptionQueryObject->canceled_at ? Temporal::toDateTime($subscriptionQueryObject->canceled_at) : null;
        $self->canceledAtGmt = $subscriptionQueryObject->canceled_at_gmt ? Temporal::toGMTDateTime($subscriptionQueryObject->canceled_at_gmt) : null;
        $self->source = new SubscriptionSource($subscriptionQueryObject->source);
        $self->expiresAt = $subscriptionQueryObject->expires_at ? Temporal::toDateTime($subscriptionQueryObject->expires_at) : null;
        $self->expiresAtGmt = $subscriptionQueryObject->expires_at_gmt ? Temporal::toGMTDateTime($subscriptionQueryObject->expires_at_gmt) : null;
        $self->resumedAt = $subscriptionQueryObject->resumed_at ? Temporal::toDateTime($subscriptionQueryObject->resumed_at) : null;
        $self->resumedAtGmt = $subscriptionQueryObject->resumed_at_gmt ? Temporal::toGMTDateTime($subscriptionQueryObject->resumed_at_gmt) : null;

        return $self;
    }

    /**
     * Convert DTO to subscription
     */
    public function toSubscription(): Subscription
    {
        $attributes = get_object_vars($this);

        return new Subscription($attributes);
    }
}
