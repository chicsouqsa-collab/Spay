<?php

/**
 * Webhook Event Model.
 *
 * @package StellarPay\Webhook\Models
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Webhook\Models;

use StellarPay\Core\Constants;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Vendors\StellarWP\DB\DB;
use StellarPay\Vendors\StellarWP\Models\Contracts\ModelCrud;
use StellarPay\Vendors\StellarWP\Models\Model;
use StellarPay\Vendors\StellarWP\Models\ModelQueryBuilder;
use StellarPay\Webhook\Repositories\WebhookEventsRepository;
use StellarPay\Webhook\DataTransferObjects\WebhookEventQueryData;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Core\ValueObjects\WebhookEventSource;
use StellarPay\Core\ValueObjects\WebhookEventRequestStatus;
use StellarPay\Core\ValueObjects\WebhookEventType;
use StellarPay\Webhook\Factories\WebhookEventFactory;
use DateTimeInterface;

use function StellarPay\Core\container;

/**
 * @since 1.1.0
 *
 * @property int                       $id
 * @property string                    $eventId
 * @property WebhookEventType          $eventType
 * @property PaymentGatewayMode        $paymentGatewayMode
 * @property int                       $sourceId
 * @property WebhookEventSource        $sourceType
 * @property WebhookEventRequestStatus $requestStatus
 * @property DateTimeInterface         $createdAt
 * @property DateTimeInterface         $createdAtGmt
 * @property DateTimeInterface|null    $responseTime
 * @property DateTimeInterface|null    $responseTimeGmt
 * @property array                     $notes
 */
#[\AllowDynamicProperties]
class WebhookEvent extends Model implements ModelCrud
{
    /**
     * @inheritdoc
     */
    protected $properties = [
        'id' => 'int',
        'eventId' => 'string',
        'eventType' => WebhookEventType::class,
        'paymentGatewayMode' => PaymentGatewayMode::class,
        'sourceId' => 'int',
        'sourceType' => WebhookEventSource::class,
        'requestStatus' => WebhookEventRequestStatus::class,
        'createdAt' => DateTimeInterface::class,
        'createdAtGmt' => DateTimeInterface::class,
        'responseTime' => DateTimeInterface::class,
        'responseTimeGmt' => DateTimeInterface::class,
        'notes' => 'array'
    ];

    /**
     * @since 1.1.0
     */
    protected function getPropertyDefaults(): array
    {
        $dateCreated    = Temporal::getCurrentDateTime();
        $dateCreatedGmt = Temporal::getGMTDateTimeWithMilliseconds($dateCreated);

        return [
            'sourceId' => 0,
            'sourceType' => WebhookEventSource::GENERIC(),
            'createdAt' => $dateCreated,
            'createdAtGmt' => $dateCreatedGmt,
            'notes' => []
        ];
    }

    /**
     * @since 1.1.0
     */
    public static function getTableName(): string
    {
        $tableName = Constants::slugPrefixed('_webhook_events');

        return DB::prefix($tableName);
    }

    /**
     * @since 1.1.0
     */
    public static function getTableNameWithoutPrefix(): string
    {
        return Constants::slugPrefixed('_webhook_events');
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException|\Exception
     */
    public static function create(array $attributes)
    {
        $event = new static($attributes);

        container(WebhookEventsRepository::class)->insert($event);

        return $event;
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException|\Exception
     */
    public function save(): self
    {
        if (!$this->id) {
            return container(WebhookEventsRepository::class)->insert($this);
        } else {
            return container(WebhookEventsRepository::class)->update($this);
        }
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     * @throws \Exception
     */
    public function delete(): bool
    {
        return container(WebhookEventsRepository::class)->delete($this);
    }

    /**
     * @inheritDoc
     * @since 1.1.0
     *
     * @throws BindingResolutionException
     */
    public static function find($id): ?self
    {
        return container(WebhookEventsRepository::class)->getById($id);
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public static function findByEventId(string $eventId): ?self
    {
        return self::query()->where('event_id', $eventId)->get();
    }

    /**
     * @since 1.1.0
     */
    public static function fromQueryBuilderObject(object $object): self
    {
        return WebhookEventQueryData::fromObject($object)->toModel();
    }

    /**
     * @since 1.0.0
     */
    public static function factory(): WebhookEventFactory
    {
        return new WebhookEventFactory(static::class);
    }

    /**
     * @since 1.1.0
     * @return ModelQueryBuilder<WebhookEvent>
     * @throws BindingResolutionException
     */
    public static function query(): ModelQueryBuilder
    {
        return container(WebhookEventsRepository::class)->prepareQuery();
    }
}
