<?php

/**
 * Refund event model for Stripe event.
 *
 * This class is responsible for handling the Stripe refund event data and related logic.
 * This class mainly used to process the refund event data for "charge.refunded, "charge.refund.updated"
 *
 * @package StellarPay\PaymentGateways\Stripe\Models
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\RuntimeException;
use StellarPay\Core\ValueObjects\RefundType;
use StellarPay\Integrations\Stripe\Client;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\Traits\RefundTrait;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Vendors\Stripe\Refund;

use function StellarPay\Core\container;

/**
 * Class RefundEvent
 *
 * @since 1.0.0
 */
class RefundEventDTO extends EventDTO
{
    use RefundTrait;
    use SubscriptionUtilities;

    /**
     * @since 1.0.0
     */
    private ?Refund $refund = null;

    /**
     * @since 1.4.0
     */
    public const REFUNDED_BY_STELLARPAY_METADATA_KEY = 'refundedBy';

    /**
     * @since 1.4.0
     */
    public const SUBSCRIPTION_ID_METADATA_KEY = 'subscriptionId';

    /**
     * @since 1.4.0
     */
    public const REFUND_TYPE_METADATA_KEY = 'refundType';

    /**
     * This function creates an event from the event model.
     *
     * @since 1.0.0
     */
    public static function fromEvent(EventDTO $event): RefundEventDTO
    {
        return new self($event->id, $event->type, $event->data);
    }

    /**
     * This function returns the refund id.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     * @throws StripeAPIException
     */
    private function getRefund(): Refund
    {
        if ($this->refund instanceof Refund) {
            return $this->refund;
        }

        switch ($this->getObjectType()) {
            case 'charge':
                // Stripe stop sending refund data in "charge.refunded" webhook event.
                // For this reason, we should fetch latest refund for payment intent and apply it on website.
                // Read Mode - https://docs.stripe.com/upgrades#2022-11-15
                $refund = container(Client::class)
                    ->getLatestRefundByPaymentIntentId($this->data->object->payment_intent); // @phpstan-ignore-line

                break;
            case 'refund':
                $refund = $this->data->object; // @phpstan-ignore-line
                break;

            default:
                throw new RuntimeException('Unsupported Stripe event data object type.');
        }

        if (! $refund) {
            throw new RuntimeException('Invalid refund.');
        }

        $this->refund = $refund;

        return $this->refund;
    }

    /**
     * This function returns the refund id.
     *
     * @since 1.0.0
     */
    public function getRefundId(): ?string
    {
        return $this->getRefund()->id;
    }

    /**
     * This function returns the refund status.
     *
     * @since 1.0.0
     */
    public function getRefundStatus(): string
    {
        return $this->getRefund()->status;
    }

    /**
     * This function returns the refund amount.
     *
     * @since 1.0.0
     */
    public function getRefundAmount(): int
    {
        return $this->getRefund()->amount;
    }

    /**
     * This function returns the refund reason.
     *
     * @since 1.0.0
     */
    public function getRefundReason(): ?string
    {
        return $this->getRefund()->reason;
    }

    /**
     * This function returns the refund failure reason.
     *
     * @since 1.0.0
     */
    public function getRefundFailureReason(): ?string
    {
        return $this->getRefund()->failure_reason;
    }

    /**
     * This function returns the refund amount.
     *
     * @since 1.0.0
     */
    public function getRefundAmountById(string $refundId): ?int
    {
        /** @var Refund[] $refunds List of refunds */
        $refunds = $this->data->object->refunds->data; // @phpstan-ignore-line

        $refund = array_filter($refunds, static function ($stripeRefund) use ($refundId) {
            return $stripeRefund->id === $refundId;
        });

        if (count($refund) > 0) {
            return $refund[0]->amount;
        }

        return null;
    }

    /**
     * @since 1.4.0
     */
    public function isRefundedFromWebsite(): bool
    {
        return ! empty($this->getRefund()->metadata[self::REFUNDED_BY_STELLARPAY_METADATA_KEY]);
    }

    /**
     * @since 1.4.0
     */
    public function getRefundedSubscriptionId(): int
    {
        return absint($this->getRefund()->metadata[self::SUBSCRIPTION_ID_METADATA_KEY]);
    }

    /**
     * @since 1.4.0
     */
    public function getRefundType(): ?string
    {
        return $this->getRefund()->metadata[self::REFUND_TYPE_METADATA_KEY];
    }

    /**
     * @since 1.4.0
     */
    public function isPartialRefund(): bool
    {
        if (!$this->getRefundType()) {
            return false;
        }

        if (RefundType::LAST_PAYMENT === $this->getRefundType()) {
            return false;
        }

        $subscription = Subscription::find($this->getRefundedSubscriptionId());

        if (!$subscription) {
            return true;
        }

        if ($this->getRefundAmount() < $this->getSubscriptionAmount($subscription)->getMinorAmount()) {
            return true;
        }

        return false;
    }
}
