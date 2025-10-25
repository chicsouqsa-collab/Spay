<?php

/**
 * WebhookSignatureValidator
 *
 * This class is responsible for validating the signature of webhook request.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Webhook;

use Exception;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\EventDTO;
use StellarPay\Vendors\Stripe\Exception\SignatureVerificationException;
use StellarPay\Vendors\Stripe\Webhook;
use UnexpectedValueException;

/**
 * Class WebhookSignatureValidator.
 *
 * @since 1.0.0
 */
class WebhookSignatureValidator
{
    /**
     * This function validates the Stripe webhook request.
     *
     * @since 1.0.0
     */
    public function __invoke(string $stripeSignature, string $webhookSecretKey, string $requestBody): EventDTO
    {
        try {
            $event = Webhook::constructEvent($requestBody, $stripeSignature, $webhookSecretKey);

            return EventDTO::fromStripeEventResponse($event);
        } catch (UnexpectedValueException $e) {
            // Invalid payload
            status_header(400);
            echo wp_json_encode(['Error parsing payload: ' => $e->getMessage()]);
            exit();
        } catch (SignatureVerificationException $e) {
            // Invalid signature
            status_header(400);
            echo wp_json_encode(['Error verifying webhook signature: ' => $e->getMessage()]);
            exit();
        } catch (Exception $e) {
            // Handle any other exceptions
            status_header(500);
            echo wp_json_encode(['Error: ' => $e->getMessage()]);
            exit();
        }
    }
}
