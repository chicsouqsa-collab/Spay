<?php

/**
 * This class is responsible to provide enum for subscription status.
 *
 * @package StellarPay\Core\ValueObjects
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects;

use StellarPay\Core\Support\Enum;
use StellarPay\Core\ValueObjects\Traits\HasLabels;

/**
 * @since 1.9.0 Added "PAUSED" status
 * @since 1.3.0 Replace "failing" with "past_due" and implement HasLabels trait
 * @since 1.0.0
 *
 * @method static SubscriptionStatus PENDING()
 * @method static SubscriptionStatus PROCESSING()
 * @method static SubscriptionStatus ACTIVE()
 * @method static SubscriptionStatus EXPIRED()
 * @method static SubscriptionStatus COMPLETED()
 * @method static SubscriptionStatus PAST_DUE()
 * @method static SubscriptionStatus CANCELED()
 * @method static SubscriptionStatus SUSPENDED()
 * @method static SubscriptionStatus ABANDONED()
 * @method static SubscriptionStatus PAUSED()
 * @method bool isPending()
 * @method bool isProcessing()
 * @method bool isActive()
 * @method bool isExpired()
 * @method bool isCompleted()
 * @method bool isPastDue()
 * @method bool isCanceled()
 * @method bool isSuspended()
 * @method bool isAbandoned()
 * @method bool isPaused()
 * @method bool isFailing()
 */
class SubscriptionStatus extends Enum
{
    use HasLabels;

    /**
     * @since 1.0.0
     */
    public const PENDING = 'pending';

    /**
     * @since 1.0.0
     */
    public const PROCESSING = 'processing';

    /**
     * @since 1.0.0
     */
    public const ACTIVE = 'active';

    /**
     * @since 1.0.0
     */
    public const EXPIRED = 'expired';

    /**
     * @since 1.0.0
     */
    public const COMPLETED = 'completed';

    /**
     * @deprecated Replace it with "PAST_DUE"
     * @since 1.0.0
     */
    public const FAILING = 'failing';

    /**
     * @since 1.3.0
     */
    public const PAST_DUE = 'past_due';

    /**
     * @since 1.0.0
     */
    public const CANCELED = 'canceled';

    /**
     * @since 1.0.0
     */
    public const SUSPENDED = 'suspended';

    /**
     * @since 1.0.0
     */
    public const ABANDONED = 'abandoned';

    /**
     * @since 1.9.0
     */
    public const PAUSED = 'paused';

    /**
     * @since 1.0.0
     *
     * @return array
     */
    public static function labels(): array
    {
        return [
            self::PENDING => esc_html__('Pending', 'stellarpay'),
            self::PROCESSING => esc_html__('Processing', 'stellarpay'),
            self::ACTIVE => esc_html__('Active', 'stellarpay'),
            self::EXPIRED => esc_html__('Expired', 'stellarpay'),
            self::COMPLETED => esc_html__('Completed', 'stellarpay'),
            self::FAILING => esc_html__('Failed', 'stellarpay'),
            self::PAST_DUE => esc_html__('Past Due', 'stellarpay'),
            self::CANCELED => esc_html__('Canceled', 'stellarpay'),
            self::SUSPENDED => esc_html__('Suspended', 'stellarpay'),
            self::ABANDONED => esc_html__('Abandoned', 'stellarpay'),
            self::PAUSED => esc_html__('Paused', 'stellarpay'),
        ];
    }
}
