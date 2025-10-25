<?php

/**
 * @package StellarPay\Core\ValueObjects
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects;

use StellarPay\Core\Support\Enum;

/**
 * @since 1.3.0
 *
 * @method static self WEBHOOK()
 * @method static self ADMIN()
 * @method static self CUSTOMER()
 * @method static self SYSTEM()
 * @method bool isSystem()
 * @method bool isAdmin()
 * @method bool isWebhook()
 * @method bool isCustomer()
 */
class ModifierContextType extends Enum
{
    use Traits\HasLabels;

    /**
     * @since 1.3.0
     */
    public const WEBHOOK = 'webhook';

    /**
     * @since 1.3.0
     */
    public const ADMIN = 'admin';

    /**
     * @since 1.3.0
     */
    public const CUSTOMER = 'customer';

    /**
     * @since 1.3.0
     */
    public const SYSTEM = 'system';

    /**
     * @since 1.3.0
     *
     * @return array
     */
    public static function labels(): array
    {
        return [
            self::ADMIN => esc_html__('admin', 'stellarpay'),
            self::WEBHOOK => esc_html__('webhook', 'stellarpay'),
            self::CUSTOMER => esc_html__('customer', 'stellarpay'),
            self::SYSTEM => esc_html__('system', 'stellarpay'),
        ];
    }
}
