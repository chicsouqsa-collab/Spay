<?php

/**
 * This class responsible to provide enum for the Woocommerce order status.
 *
 * @package StellarPay\Integrations\WooCommerce\ValueObjects
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\ValueObjects;

use StellarPay\Core\Support\Enum;

/**
 * @since 1.9.0 Add "checkout-draft" order status.
 * @since 1.0.0
 *
 * @method static OrderStatus COMPLETED()
 * @method static OrderStatus CANCELED()
 * @method static OrderStatus PENDING()
 * @method static OrderStatus CHECKOUT_DRAFT()
 * @method static OrderStatus ON_HOLD()
 * @method static OrderStatus PROCESSING()
 * @method static OrderStatus REFUNDED()
 * @method static OrderStatus FAILED()
 * @method bool isCompleted()
 * @method bool isCanceled()
 * @method bool isPending()
 * @method bool isCheckoutDraft()
 * @method bool isOnHold()
 * @method bool isProcessing()
 * @method bool isRefunded()
 * @method bool isFailed()
 */
class OrderStatus extends Enum
{
    /**
     * @since 1.0.0
     */
    public const COMPLETED = 'completed';

    /**
     * @since 1.0.0
     */
    public const CANCELED = 'canceled';

    /**
     * @since 1.0.0
     */
    public const PENDING = 'pending';

    /**
     * @since 1.9.0
     */
    public const CHECKOUT_DRAFT = 'checkout-draft';

    /**
     * @since 1.0.0
     */
    public const ON_HOLD = 'on-hold';

    /**
     * @since 1.0.0
     */
    public const PROCESSING = 'processing';

    /**
     * @since 1.0.0
     */
    public const REFUNDED = 'refunded';

    /**
     * @since 1.0.0
     */
    public const FAILED = 'failed';
}
