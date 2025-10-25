<?php

/**
 * This class is a trait for StellarPay product types that support subscription.
 *
 * @package StellarPay\Integrations\WooCommerce\Models\ProductTypes\Traits
 * @since 1.8.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Models\ProductTypes\Traits;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use StellarPay\Core\ValueObjects\SubscriptionProductType;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * @since 1.8.0
 *
 * @method SubscriptionProductType getProductType()
 * @method string get_meta(string $key, bool $single = false, string $context = 'view')
 * @method bool is_on_sale()
 */
trait SubscriptionProductUtilities
{
    use AbstractSubscriptionProductUtilities;

    /**
     * @since 1.0.0
     */
    public function getAmount($context = 'view'): string
    {
        if ($this->isOnSale() && ( $saleAmount = $this->getSaleAmount($context) )) {
            return $saleAmount;
        }

        return $this->getRegularAmount($context);
    }

    /**
     * @since 1.8.0
     */
    public function getRegularAmount($context = 'view'): string
    {
        return $this->get_meta(
            dbMetaKeyGenerator($this->getProductType()->getValue() . '_amount', true),
            true,
            $context
        );
    }

    /**
     * @since 1.8.0
     */
    public function getSaleAmount($context = 'view'): string
    {
        return $this->get_meta(
            dbMetaKeyGenerator($this->getProductType()->getValue() . '_saleAmount', true),
            true,
            $context
        );
    }

    /**
     * @since 1.8.0
     * @throws \Exception
     */
    public function getSaleFromDate($context = 'view'): ?DateTimeImmutable
    {
        $date =  $this->get_meta(
            dbMetaKeyGenerator($this->getProductType()->getValue() . '_saleFromDate', true),
            true,
            $context
        );

        if (empty($date)) {
            return null;
        }

        $dateObject = new DateTime('now', new DateTimeZone('UTC'));
        $dateObject->setTimestamp(absint($date));

        $dateObject->setTimezone(wp_timezone()); // Make date local.

        return DateTimeImmutable::createFromMutable($dateObject);
    }

    /**
     * @since 1.8.0
     * @throws \Exception
     */
    public function getSaleToDate($context = 'view'): ?DateTimeImmutable
    {
        $date =  $this->get_meta(
            dbMetaKeyGenerator($this->getProductType()->getValue() . '_saleToDate', true),
            true,
            $context
        );

        if (empty($date)) {
            return null;
        }

        $dateObject = new DateTime('now', new DateTimeZone('UTC'));
        $dateObject->setTimestamp(absint($date));

        $dateObject->setTimezone(wp_timezone()); // Make date local.

        return DateTimeImmutable::createFromMutable($dateObject);
    }

    /**
     * @since 1.0.0
     */
    public function getBillingPeriod($context = 'view'): string
    {
        return $this->get_meta(
            dbMetaKeyGenerator($this->getProductType()->getValue() . '_billingPeriod', true),
            true,
            $context
        );
    }

    /**
     * @since 1.0.0
     */
    public function getRecurringPeriod($context = 'view'): string
    {
        return $this->get_meta(
            dbMetaKeyGenerator($this->getProductType()->getValue() . '_recurringPeriod', true),
            true,
            $context
        );
    }

    /**
     * @since 1.0.0
     */
    public function getRecurringFrequency($context = 'view'): int
    {
        return (int) $this->get_meta(
            dbMetaKeyGenerator($this->getProductType()->getValue() . '_recurringFrequency', true),
            true,
            $context
        );
    }

    /**
     * @since 1.8.0
     */
    public function isOnSale(): bool
    {
        return $this->is_on_sale();
    }
}
