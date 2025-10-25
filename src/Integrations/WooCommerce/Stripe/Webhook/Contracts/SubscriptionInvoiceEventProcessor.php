<?php

/**
 * This class is used a contract for Stripe event process classes which handle subscription.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\WebhookEventSource;
use StellarPay\Core\Webhooks\EventResponse;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\RenewalOrderRepository;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\InvoiceEventDTO;
use StellarPay\Core\Webhooks\EventProcessor;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Core\ValueObjects\WebhookEventRequestStatus;

/**
 * @since 1.1.0 Make compatible with update EventProcessor clas
 * @since 1.0.0
 */
abstract class SubscriptionInvoiceEventProcessor extends EventProcessor
{
    /**
     * @since 1.0.0
     */
    protected RenewalOrderRepository $renewalOrderRepository;

    /**
     * @since 1.0.0
     */
    public function __construct(EventResponse $eventResponse, RenewalOrderRepository $renewalOrderRepository)
    {
        $this->renewalOrderRepository = $renewalOrderRepository;

        parent::__construct($eventResponse);
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function processEvent(): EventResponse
    {
        $eventDTO  = $this->getEventDTO();
        $invoiceEventDTO = InvoiceEventDTO::fromEvent($eventDTO);
        $subscription = $this->getSubscriptionByEvent($invoiceEventDTO);
        $isSubscription = $subscription instanceof Subscription;

        $this->eventResponse->setWebhookEventSourceType(WebhookEventSource::STELLARPAY_SUBSCRIPTION());

        if (! $isSubscription) {
            return $this->eventResponse
                ->setWebhookEventRequestStatus(WebhookEventRequestStatus::RECORD_NOT_FOUND())
                ->ensureResponse();
        }

        // Exit if order already created for the invoice.
        $renewalOrder = $this->renewalOrderRepository->getOrderByPaymentIntentId($invoiceEventDTO->getPaymentIntentId());
        if ($renewalOrder instanceof \WC_Order) {
            return $this->eventResponse
                ->setWebhookEventRequestStatus(WebhookEventRequestStatus::UNPROCESSABLE())
                ->ensureResponse();
        }

        $this->processSubscriptionInvoice($subscription, $invoiceEventDTO);

        return $this->eventResponse
            ->setWebhookEventSourceId($subscription->id)
            ->ensureResponse();
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    private function getSubscriptionByEvent(InvoiceEventDTO $invoiceEventDTO): ?Subscription
    {
        $subscriptionId = $invoiceEventDTO->getSubscriptionId();

        if (! $subscriptionId) {
            return null;
        }

        return Subscription::findByTransactionId($subscriptionId);
    }

    /**
     * @since 1.0.0
     */
    abstract protected function processSubscriptionInvoice(Subscription $subscription, InvoiceEventDTO $invoiceEventDTO): void;
}
