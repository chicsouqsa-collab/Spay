<?php

/**
 * This class is used access subscription metadata.
 *
 * @package StellarPay\Subscriptions\SubscriptionMetaDTO
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\DataTransferObjects;

/**
 * Class SubscriptionMetaDTO
 *
 * @since 1.1.0
 */
class SubscriptionMetaDTO
{
    /**
     * @since 1.1.0
     */
    public ?int $id;

    /**
     * @since 1.1.0
     */
    public int $subscriptionId;

    /**
     * @since 1.1.0
     */
    public string $metaKey;

    /**
     * @since 1.1.0
     */
    public string $metaValue;

    /**
     * @since 1.1.0
     */
    public static function fromObject(object $subscriptionMetaQueryObject): self
    {
        $self = new self();

        $self->id = $subscriptionMetaQueryObject->id ? absint($subscriptionMetaQueryObject->id) : null;
        $self->subscriptionId = absint($subscriptionMetaQueryObject->subscription_id);
        $self->metaKey = $subscriptionMetaQueryObject->meta_key;
        $self->metaValue = $subscriptionMetaQueryObject->meta_value;

        return $self;
    }

    /**
     * @since 1.1.0
     */
    public static function fromArray(array $subscriptionMeta): self
    {
        $self = new self();

        $self->id = $subscriptionMeta['id'] ?? null;
        $self->subscriptionId = $subscriptionMeta['subscription_id'];
        $self->metaKey = $subscriptionMeta['meta_key'];
        $self->metaValue = $subscriptionMeta['meta_value'];

        return $self;
    }
}
