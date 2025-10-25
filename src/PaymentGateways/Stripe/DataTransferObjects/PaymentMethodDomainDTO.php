<?php

/**
 * This class represents saved Stripe payment method data.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Models
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\DataTransferObjects;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;

use function StellarPay\Core\container;

/**
 * Class PaymentMethodDomainDTO
 *
 * @since 1.0.0
 */
class PaymentMethodDomainDTO
{
    /**
     * @since 1.0.0
     */
    private array $paymentMethodDomainArray;

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException
     * @throws BindingResolutionException
     */
    public static function fromArray(array $array): self
    {
        $self = container(self::class);
        $self->validate($array);

        $self->paymentMethodDomainArray = $array;

        return $self;
    }

    /**
     * @since 1.0.0
     */
    public function getId(): string
    {
        return $this->paymentMethodDomainArray['id'];
    }

    /**
     * @since 1.0.0
     */
    public function isEnabled(): bool
    {
        return $this->paymentMethodDomainArray['enabled'];
    }

    /**
     * @since 1.0.0
     */
    public function getDomain(): string
    {
        return $this->paymentMethodDomainArray['domain'];
    }

    /**
     * @since 1.0.0
     */
    public function toArray(): array
    {
        return $this->paymentMethodDomainArray;
    }

    /**
     * @since 1.0.0
     * @throws InvalidPropertyException
     */
    private function validate(array $array): void
    {
        $requiredKeys = ['id', 'enabled', 'apple_pay','google_pay', 'paypal'];
        $arrayKeys = array_keys($array);

        if (array_diff($requiredKeys, $arrayKeys)) {
            throw new InvalidPropertyException('Invalid payment method domain data format');
        }
    }
}
