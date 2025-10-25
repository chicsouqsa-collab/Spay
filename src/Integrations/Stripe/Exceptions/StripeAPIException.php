<?php

/**
 * StripeAPIException
 *
 * This class is used to manage the Stripe API exception.
 *
 * @package StellarPay/Core/HelperFunctions
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe\Exceptions;

use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Vendors\Stripe\ErrorObject;
use StellarPay\Vendors\Stripe\Exception\ApiErrorException;

/**
 * Class GetStripeAccountException
 *
 * @since 1.0.0
 */
class StripeAPIException extends Exception
{
    /**
     * @since 1.0.0
     */
    private ApiErrorException $stripeException;

    /**
     * Constructor.
     *
     * @since 1.0.0
     */
    public function __construct(ApiErrorException $e)
    {
        $this->stripeException = $e;

        parent::__construct(esc_html($e->getMessage()), $e->getCode());
    }
    /**
     * @since 1.0.0
     */
    public function isResourceNotFound(): bool
    {
        return $this->stripeException->getStripeCode() === ErrorObject::CODE_RESOURCE_MISSING;
    }

    /**
     * @since 1.4.0
     */
    public function isPlatformApiKeyExpired(): bool
    {
        return $this->stripeException->getStripeCode() === ErrorObject::CODE_PLATFORM_API_KEY_EXPIRED;
    }

    /**
     * @since 1.0.0
     */
    public function getStripeErrorCode(): ?string
    {
        return $this->stripeException->getStripeCode();
    }
}
