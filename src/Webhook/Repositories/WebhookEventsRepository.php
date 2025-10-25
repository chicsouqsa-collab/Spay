<?php

/**
 * This class is Responsible for providing logic to perform on Webhook Events.
 *
 * @package StellarPay\Webhook\Repositories
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Webhook\Repositories;

use StellarPay\Core\Exceptions\Primitives\InvalidArgumentException;
use StellarPay\Vendors\StellarWP\Models\ModelQueryBuilder;
use StellarPay\Webhook\Models\WebhookEvent;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\Hooks;
use StellarPay\Vendors\StellarWP\DB\DB;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\EventDTO;
use StellarPay\PaymentGateways\Stripe\RestApi\Webhook;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\Core\ValueObjects\WebhookEventRequestStatus;
use StellarPay\Core\ValueObjects\WebhookEventType;

/**
 * @since 1.1.0
 */
class WebhookEventsRepository
{
    use SubscriptionUtilities;

    /**
     * @since 1.1.0
     */
    private array $requiredSubscriptionProperties = [
        'eventId',
        'eventType',
        'paymentGatewayMode',
        'sourceId',
        'sourceType',
        'requestStatus',
        'createdAt',
        'createdAtGmt',
    ];

    /**
     * @since 1.1.0
     *
     * @return ModelQueryBuilder<WebhookEvent>
     */
    public function prepareQuery(): ModelQueryBuilder
    {
        $builder = new ModelQueryBuilder(WebhookEvent::class);

        return $builder->from(WebhookEvent::getTableNameWithoutPrefix());
    }

    /**
     * @since 1.1.0
     */
    public function insert(WebhookEvent $webhookEvent): WebhookEvent
    {
        $this->validateWebhookEvent($webhookEvent);

        /**
         * Fires just before creating the webhook event.
         *
         * @since 1.1.0
         * @hook stellarpay_webhook_event_creating
         * @param WebhookEvent $webhookEvent The webhook event.
         */
        Hooks::doAction('stellarpay_webhook_event_creating', $webhookEvent);

        DB::query('START TRANSACTION');

        try {
            $this->prepareQuery()
                ->insert($this->toArray($webhookEvent));
            $webhookEvent->id = DB::last_insert_id();
        } catch (\Exception $exception) {
            DB::query('ROLLBACK');
        }

        DB::query('COMMIT');

        /**
         * Fires just after updating the webhook event.
         *
         * @since 1.1.0
         * @hook stellarpay_webhook_event_creating
         * @param WebhookEvent $webhookEvent The webhook event.
         */
        Hooks::doAction('stellarpay_webhook_event_created', $webhookEvent);

        return $webhookEvent;
    }

    /**
     * @since 1.1.0
     *
     * @return WebhookEvent[]
     */
    public function getAll(array $args = []): array
    {
        $defaults = ['page' => 1];
        $args = array_merge($defaults, $args);
        $pageNumber = (int) $args['page'];

        $query = $this->prepareQuery()
            ->whereIn('event_type', WebhookEventType::toArray())
            ->orderBy('ID', 'DESC');

        // Set offset.
        if (array_key_exists('perPage', $args) && $args['perPage']) {
            $limit = (int) $args['perPage'];
            $offset = $limit * ($pageNumber - 1);

            $query->offset($offset);
            $query->limit($limit);
        }

        return $query->getAll() ?? [];
    }

    /**
     * @since 1.1.0
     * @throws \Exception
     */
    public function update(WebhookEvent $webhookEvent): WebhookEvent
    {
        $this->validateWebhookEvent($webhookEvent);

        /**
         * Fires just before updating the webhook event.
         *
         * @since 1.1.0
         * @hook stellarpay_webhook_event_updating
         * @param WebhookEvent $webhookEvent The webhook event to be updated.
         */
        Hooks::doAction('stellarpay_webhook_event_updating', $webhookEvent);

        DB::query('START TRANSACTION');

        try {
            $this->prepareQuery()
                ->where('ID', $webhookEvent->id)
                ->update($this->toArray($webhookEvent));
        } catch (\Exception $exception) {
            DB::query('ROLLBACK');
            // @todo Log errors.

            throw new \Exception('Failed updating a webhook event');
        }

        DB::query('COMMIT');

        /**
         * Fires just after updated the Webhook Event.
         *
         * @since 1.1.0
         * @hook stellarpay_webhook_event_updated
         * @param WebhookEvent $webhookEvent The webhook event.
         */
        Hooks::doAction('stellarpay_webhook_event_updated', $webhookEvent);

        return $webhookEvent;
    }

    /**
     * @since 1.1.0
     */
    public function validateWebhookEvent(WebhookEvent $webhookEvent): void
    {
        foreach ($this->requiredSubscriptionProperties as $key) {
            if (!isset($webhookEvent->$key)) {
                throw new InvalidArgumentException(esc_attr("'$key' is required to create/update webhook event"));
            }
        }
    }

    /**
     * @since 1.1.0
     */
    public static function createWebhookEventFromEventDTO(EventDTO $webhookEvent): WebhookEvent
    {
        return new WebhookEvent([
            'eventType' => WebhookEventType::from($webhookEvent->getType()),
            'eventId' => $webhookEvent->getId(),
            'paymentGatewayMode' => Webhook::$paymentGatewayMode,
            'requestStatus' => WebhookEventRequestStatus::SUCCEEDED(),
        ]);
    }

    /**
     * @since 1.1.0
     */
    public function toArray(WebhookEvent $webhookEvent): array
    {
        return [
            'id' => $webhookEvent->id,
            'event_type' => $webhookEvent->eventType->getValue(),
            'event_id' => $webhookEvent->eventId,
            'payment_gateway_mode' => $webhookEvent->paymentGatewayMode->getValue(),
            'source_id' => $webhookEvent->sourceId,
            'source_type' => $webhookEvent->sourceType->getValue(),
            'request_status' => $webhookEvent->requestStatus->getValue(),
            'created_at' => Temporal::getFormattedDateTimeWithMilliseconds($webhookEvent->createdAt),
            'created_at_gmt' => Temporal::getFormattedDateTimeWithMilliseconds($webhookEvent->createdAtGmt),
            'response_time' => $webhookEvent->responseTime ? Temporal::getFormattedDateTimeWithMilliseconds($webhookEvent->responseTime) : null,
            'response_time_gmt' => $webhookEvent->responseTimeGmt ? Temporal::getFormattedDateTimeWithMilliseconds($webhookEvent->responseTimeGmt) : null,
            'notes' => wp_json_encode($webhookEvent->notes)
        ];
    }

    /**
     * @since 1.1.0
     */
    public function getById(int $id): ?WebhookEvent
    {
        return $this->prepareQuery()
            ->where('id', $id)
            ->get();
    }

    /**
     * @since 1.1.0
     */
    public function totalCount(): int
    {
        return $this->prepareQuery()
            ->count();
    }
}
