<?php

/**
 * Responsible for providing Webhook Events data for Webhook Events List Page in the admin.
 *
 * @package StellarPay\AdminDashboard\RestApi
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\RestApi;

use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\ValueObjects\WebhookEventRequestStatus;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRefundRepository;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Webhook\Models\WebhookEvent;
use StellarPay\Webhook\Repositories\WebhookEventsRepository;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Integrations\Stripe\Client;
use StellarPay\RestApi\HandleMultipleApiRoutes;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * @since 1.1.0
 */
class WebhookEventsListPage extends ApiRoute
{
    use HandleMultipleApiRoutes {
        registerAllRoutes as register;
    }

    /**
     * @inheritdoc
     * @since 1.1.0
     */
    protected string $endpoint = 'webhook-events';

    /**
     * @since 1.1.0
     */
    protected WebhookEventsRepository $webhookEventsRepository;

    /**
     * @since 1.1.0
     */
    protected OrderRefundRepository $orderRefundRepository;

    /**
     * @since 1.1.0
     */
    public function __construct(WebhookEventsRepository $webhookEventsRepository, OrderRefundRepository $orderRefundRepository)
    {
        parent::__construct();

        $this->webhookEventsRepository = $webhookEventsRepository;
        $this->orderRefundRepository = $orderRefundRepository;
    }

    /**
     * @inheritdoc
     * @since 1.1.0
     */
    public function getRoutes(): array
    {
        return [
            'list' => [
                'method' => WP_REST_Server::READABLE,
                'args' => [
                    'pageNumber' => [
                        'required' => true,
                        'type' => 'integer',
                        'default' => 1,
                        'minimum' => 1,
                        'sanitize_callback' => 'absint'
                    ]
                ],
            ],
            'detail' => [
                'method' => WP_REST_Server::READABLE,
                'permission_callback' => [$this, 'permissionCheck'],
                'args' => [
                    'eventId' => [
                        'required' => true,
                        'type' => 'integer',
                    ]
                ],
            ]
        ];
    }

    /**
     * @since 1.1.0
     */
    public function permissionCheck(WP_REST_Request $request): bool
    {
        return parent::permissionCheck($request) && current_user_can('manage_options');
    }

    /**
     * @since 1.3.0 renamed from processRequest to getList
     * @since 1.1.0
     */
    public function getList(WP_REST_Request $request): WP_REST_Response
    {
        $pageNumber = $request->get_param('pageNumber');
        $webhookEventsPerPage = get_option('posts_per_page', 15);
        $webhookEvents = $this->webhookEventsRepository->getAll(['page' => $pageNumber, 'perPage' => $webhookEventsPerPage]);

        $result['page'] = $pageNumber;
        $result['perPage'] = $webhookEventsPerPage;
        $result['total'] = $this->webhookEventsRepository->totalCount();

        $result['events'] = [];

        foreach ($webhookEvents as $webhookEvent) {
            $result['events'][] = $this->prepareEventData($webhookEvent);
        }

        return rest_ensure_response($result);
    }

    /**
     * @since 1.3.0
     */
    public function getDetail(WP_REST_Request $request): WP_REST_Response
    {
        $eventId = $request->get_param('eventId');
        $webhookEvent = $this->webhookEventsRepository->getById($eventId);
        return rest_ensure_response($this->prepareEventData($webhookEvent));
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function prepareEventData(WebhookEvent $webhookEvent): array
    {
        $data = [
            'id' => $webhookEvent->id,
            'event_id' => $webhookEvent->eventId,
            'event_type' => $webhookEvent->eventType,
            'live_mode' => $webhookEvent->paymentGatewayMode->isLive(),
            'request_status' => $webhookEvent->requestStatus->getValue(),
            'source_id' => $webhookEvent->sourceId,
            'source_link' => $webhookEvent->sourceId,
            'source_type_label' => $webhookEvent->sourceType->label(),
            'created_at' => Temporal::getWPFormattedDateTime($webhookEvent->createdAt),
            'customer_email' => '', // This will be filled according to the source type with data filters.
            'stripe_dashboard_link' => Client::getStripeDashboardLink('events/' . $webhookEvent->eventId)
        ];

        if ($webhookEvent->sourceType->isWooOrder()) {
            $this->filterDataForWooOrderSourceType($data, $webhookEvent);
        } elseif ($webhookEvent->sourceType->isWooOrderRefund()) {
            $this->filterDataForWoocommerceOrderRefundSourceType($data, $webhookEvent);
        } elseif ($webhookEvent->sourceType->isStellarPaySubscription()) {
            $this->filterDataForStellarPaySubscriptionSourceType($data, $webhookEvent);
        }

        return $data;
    }

    /**
     * @since 1.1.0
     */
    public function filterDataForWooOrderSourceType(array &$data, WebhookEvent $webhookEvent): void
    {
        $order = wc_get_order($webhookEvent->sourceId);

        if ($order) {
            $data['source_id'] = $webhookEvent->sourceId;
            $data['customer_email'] = $order->get_billing_email();
            $data['customer_name'] = $order->get_formatted_billing_full_name();
            $data['customer_url'] = $order->get_user_id() ? get_edit_user_link($order->get_user_id()) : '#';
            $data['amount'] = $order->get_total();
            $data['source_link'] = $order->get_edit_order_url();
        }
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    private function filterDataForStellarPaySubscriptionSourceType(array &$data, WebhookEvent $webhookEvent): void
    {
        $subscription = Subscription::find($webhookEvent->sourceId);

        if ($subscription) {
            $order = wc_get_order($subscription->firstOrderId);
            $email = $order ? $order->get_billing_email() : '';
            $stripeDashboardLink = '';

            if ($subscription->transactionId) {
                $stripeDashboardLink = $subscription->getStripeDashboardLink();
            }

            $data['source_id'] = $webhookEvent->sourceId;
            $data['customer_email'] = $email;
            $data['customer_name'] = $order->get_formatted_billing_full_name();
            $data['customer_url'] = $order->get_user_id() ? get_edit_user_link($order->get_user_id()) : '#';
            $data['amount'] = $subscription->getLastOrderAmount()->getAmount();
            $data['source_link'] = $stripeDashboardLink;
        }
    }

    /**
     * @since 1.1.0
     */
    private function filterDataForWoocommerceOrderRefundSourceType(array &$data, WebhookEvent $webhookEvent): void
    {
        if (empty($webhookEvent->sourceId)) {
            return;
        }

        // Refund will be deleted when refund reverse from stripe dashboard.
        $order = wc_get_order($webhookEvent->sourceId);
        if (! $order) {
            $data['request_status'] = WebhookEventRequestStatus::RECORD_DELETED;
            return;
        }

        $parentOrder = wc_get_order($order->get_parent_id());

        if (!$parentOrder) {
            return;
        }

        $data['source_id'] = $parentOrder->get_id();
        $data['customer_email'] = $parentOrder->get_billing_email();
        $data['customer_name'] = $parentOrder->get_formatted_billing_full_name();
        $data['customer_url'] = $parentOrder->get_user_id() ? get_edit_user_link($parentOrder->get_user_id()) : '#';
        $data['amount'] = $this->orderRefundRepository->getRefundAmount($order)->getAmount();
        $data['source_link'] = $parentOrder->get_edit_order_url();
    }
}
