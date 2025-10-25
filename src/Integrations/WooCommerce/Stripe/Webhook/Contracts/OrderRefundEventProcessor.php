<?php

/**
 * OrderRefundEventProcessor event processor.
 *
 * This class is responsible for processing the order refund event.
 *
 * @package StellarPay/Integrations/WooCommerce/Stripe/Webhook
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Webhook\Contracts;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\WebhookEventSource;
use StellarPay\Core\Webhooks\EventResponse;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRefundRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\EventDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\RefundEventDTO;
use StellarPay\Core\Webhooks\EventProcessor;
use StellarPay\Core\ValueObjects\WebhookEventRequestStatus;
use WC_Order;
use WC_Order_Refund;

/**
 * OrderRefundEventProcessor event processor.
 *
 * This class is responsible for processing the order refund event.
 *
 * @since 1.1.0 Make compatible with update EventProcessor clas
 * @since 1.0.0
 */
abstract class OrderRefundEventProcessor extends EventProcessor
{
    /**
     * @since 1.0.0
     */
    protected OrderRefundRepository $orderRefundRepository;

    /**
     * @since 1.0.0
     */
    private OrderRepository $orderRepository;

    /**
     * @since 1.0.0
     */
    public function __construct(
        EventResponse $eventResponse,
        OrderRepository $orderRepository,
        OrderRefundRepository $orderRefundRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->orderRefundRepository = $orderRefundRepository;

        parent::__construct($eventResponse);
    }

    /**
     * This method processes the Stripe webhook.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function processEvent(): EventResponse
    {
        $eventDTO = $this->getEventDTO();
        $order = $this->getOrderByEvent($eventDTO);

        $this->eventResponse->setWebhookEventSourceType(WebhookEventSource::WOO_ORDER_REFUND());


        if (! $order instanceof WC_Order) {
            return $this->eventResponse
                ->setWebhookEventRequestStatus(WebhookEventRequestStatus::RECORD_NOT_FOUND())
                ->ensureResponse();
        }

        // Check whether order processed with own payment method, if not then exit.
        if (Constants::GATEWAY_ID !== $order->get_payment_method()) {
            return $this->eventResponse
                ->setWebhookEventRequestStatus(WebhookEventRequestStatus::UNPROCESSABLE())
                ->ensureResponse();
        }

        $refundEventModel = RefundEventDTO::fromEvent($eventDTO);
        $refundFromEvent = $this->getOrderRefund($order, $refundEventModel->getRefundId());
        $orderRefund = $this->processOrderRefund($order, $refundEventModel);

        $webhookEventSourceId = $orderRefund
            ? $orderRefund->get_id()
            : $refundFromEvent->get_id(); // fallback refund is from event.

        $webhookEventRequestStatus = WebhookEventRequestStatus::UNPROCESSABLE();

        if ($orderRefund) {
            $webhookEventRequestStatus = $orderRefund->get_id()
                ? WebhookEventRequestStatus::SUCCEEDED()
                : WebhookEventRequestStatus::RECORD_DELETED();
        }

        return $this->eventResponse
            ->setWebhookEventSourceId($webhookEventSourceId)
            ->setWebhookEventRequestStatus($webhookEventRequestStatus)
            ->ensureResponse();
    }

    /**
     * This method processes the Stripe webhook.
     *
     * @since 1.0.0
     */
    abstract protected function processOrderRefund(
        WC_Order $order,
        RefundEventDTO $event,
        WC_Order_Refund $refund = null
    ): ?WC_Order_Refund;

    /**
     * This function returns the order that matches the event.
     *
     * @since 1.0.0
     */
    protected function getOrderByEvent(EventDTO $event): ?WC_Order
    {
        return $this->orderRepository->getOrderByPaymentIntentId($event->getPaymentIntentId());
    }

    /**
     * This method checks whether the refund is already processed.
     *
     * @since 1.0.0
     */
    protected function getOrderRefund(WC_Order $order, string $refundId): ?WC_Order_Refund
    {
        $refunds = $order->get_refunds();

        if (empty($refunds)) {
            return null;
        }

        foreach ($refunds as $refund) {
            if ($this->orderRefundRepository->getRefundId($refund) === $refundId) {
                return $refund;
            }
        }

        return null;
    }
}
