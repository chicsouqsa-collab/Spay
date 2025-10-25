<?php

/**
 * The Enum class to keep list of all the Webhook Event Sources.
 *
 * @package StellarPay\Core\ValueObjects
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects;

use StellarPay\Core\Support\Enum;
use StellarPay\Core\ValueObjects\Traits\HasLabels;

/**
 * @since 1.3.0 implement HasLabels trait
 * @since 1.1.0
 *
 * @method static WebhookEventSource WOO_ORDER()
 * @method static WebhookEventSource STELLARPAY_SUBSCRIPTION()
 * @method static WebhookEventSource WOO_ORDER_REFUND()
 * @method static WebhookEventSource GENERIC()
 * @method bool isWooOrder()
 * @method bool isWooOrderRefund()
 * @method bool isStellarPaySubscription()
 * @method bool isGeneric()
 */
class WebhookEventSource extends Enum
{
    use HasLabels;

    /**
     * @since 1.1.0
     */
    public const WOO_ORDER = 'woo_order';

    /**
     * @since 1.1.0
     */
    public const STELLARPAY_SUBSCRIPTION = 'stellarpay_subscription';

    /**
     * @since 1.1.0
     */
    public const WOO_ORDER_REFUND = 'woo_order_refund';

    /**
     * @since 1.1.0
     */
    public const GENERIC = 'generic';

    /**
     * @since 1.3.0
     */
    public static function labels(): array
    {
        return [
            self::WOO_ORDER_REFUND => esc_html__('order', 'stellarpay'),
            self::WOO_ORDER => esc_html__('order', 'stellarpay'),
            self::STELLARPAY_SUBSCRIPTION => esc_html__('subscription', 'stellarpay'),
            self::GENERIC => esc_html__('generic', 'stellarpay'),
        ];
    }
}
