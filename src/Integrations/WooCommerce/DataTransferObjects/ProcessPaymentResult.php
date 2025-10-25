<?php

/**
 * This class is used to transfer the result of the payment process.
 *
 * @package StellarPay\Integrations\WooCommerce\DataTransferObjects
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\DataTransferObjects;

use StellarPay\Integrations\WooCommerce\ValueObjects\ProcessPaymentResultType;

/**
 * @since 1.7.0
 */
class ProcessPaymentResult
{
    /**
     * @since 1.7.0
     */
    public string $redirect = '';

    /**
     * @since 1.7.0
     */
    public ProcessPaymentResultType $result;

    /**
     * @since 1.7.0
     */
    public bool $retry = true;

    /**
     * @since 1.7.0
     */
    public string $code = '';

    /**
     * @since 1.7.0
     */
    public string $message = '';

    /**
     * @since 1.7.0
     */
    public bool $subscriptionWithZeroOrderValue = false;

    /**
     * @since 1.7.0
     *
     * @param array<string, mixed> $additionalData The array of additional data to set. Array keys must be strings.
     */
    public array $additionalData = [];

    /**
     * @since 1.7.0
     */
    public function setError(string $code, string $errorMessage): self
    {
        $this->code = $code;
        $this->message = $errorMessage;
        $this->result = ProcessPaymentResultType::FAILURE();
        $this->retry = true;

        return $this;
    }

    /**
     * @since 1.7.0
     */
    public function setRetry(bool $retry): self
    {
        $this->retry = $retry;

        return $this;
    }

    /**
     * @since 1.7.0
     */
    public function setResult(ProcessPaymentResultType $result): self
    {
        $this->result = $result;

        return $this;
    }

    /**
     * @since 1.7.0
     */
    public function setRedirect(string $redirect): self
    {
        $this->redirect = $redirect;

        return $this;
    }

    /**
     * @since 1.7.0
     */
    public function subscriptionWithZeroOrderValue(): self
    {
        $this->subscriptionWithZeroOrderValue = true;

        return $this;
    }

    /**
     * @since 1.7.0
     * @param array<string, mixed> $additionalData The array of additional data to set. Array keys must be strings.
     */
    public function setAdditionalData(array $additionalData, bool $reset = false): self
    {
        if ($reset) {
            $this->additionalData = [];
        }

        $this->additionalData = array_merge($this->additionalData, $additionalData);

        return $this;
    }

    /**
     * @since 1.7.0
     */
    public function toArray(): array
    {
        return array_merge(
            [
                'redirect' => $this->redirect,
                'result' => $this->result->getValue(),
                'retry' => $this->retry,
                'code' => $this->code,
                'message' => $this->message,
                'subscriptionWithZeroOrderValue' => $this->subscriptionWithZeroOrderValue,
            ],
            $this->additionalData
        );
    }
}
