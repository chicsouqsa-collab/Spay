<?php

/**
 * RefundDataStrategy.
 *
 * This class is used to generate data for the refund.
 *
 * @package StellarPay/Integrations/WooCommerce/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Strategies;

use StellarPay\Core\Contracts\DataStrategy;
use StellarPay\Core\ValueObjects\ModifierContextType;
use StellarPay\Integrations\WooCommerce\Stripe\ValueObjects\Money;
use WC_Order;

/**
 * class RefundDataStrategy.
 *
 * @since 1.0.0
 */
class RefundDataStrategy implements DataStrategy
{
    /**
     * @since 1.0.0
     */
    private WC_Order $order;

    /**
     * @since 1.0.0
     */
    private float $amount;

    /**
     * @since 1.0.0
     * @var string
     */
    private string $reason;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct($amount, WC_Order $order, $reason)
    {
        $this->amount = $amount;
        $this->order = $order;
        $this->reason = trim($reason);
    }

    /**
     * @inheritdoc
     *
     * @since 1.0.0
     */
    public function generateData(): array
    {
        $orderAmount = Money::make($this->amount, $this->order->get_currency());

        $data =  [
            'payment_intent' => $this->order->get_transaction_id(),
            'amount' => $orderAmount->getMinorAmount(),
        ];

        $data['metadata'] = [
            'site_url' => esc_url(get_site_url()),
            'order_id' => $this->order->get_id(),
            'refundBy' => ModifierContextType::ADMIN,
        ];

        if ($this->reason) {
            $data['reason'] = $this->reason;
            $data = $this->maybeMoveRefundReasonToMetaData($data);
        }

        return $data;
    }

    /**
     * Maybe move refund reason to metadata.
     *
     * @since 1.0.0
     */
    public function maybeMoveRefundReasonToMetaData(array $data): array
    {
        // Check a list of whitelisted refund reasons on https://docs.stripe.com/api/refunds/create#create_refund-reason
        $whitelistedRefundReasons = [
            'duplicate',
            'fraudulent',
            'requested_by_customer',
        ];

        if (in_array($data['reason'], $whitelistedRefundReasons, true)) {
            return $data;
        }

        $data['metadata']['refund_reason'] = $this->formatReason($data['reason']);
        unset($data['reason']);

        return $data;
    }

    /**
     * Format the refund reason.
     *
     * Only 500 characters are allowed for the refund reason: https://docs.stripe.com/api/metadata
     *
     * @since 1.0.0
     */
    private function formatReason(string $reason): string
    {
        if (strlen($reason) > 500) {
            $reason = substr($reason, 0, 497) . '...';
        }

        return $reason;
    }
}
