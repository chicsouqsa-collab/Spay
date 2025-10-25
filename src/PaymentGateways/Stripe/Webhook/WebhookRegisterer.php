<?php

/**
 * This class is responsible for registering the webhook events.
 *
 * @package StellarPay\PaymentGateways\Stripe\Webhook
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Webhook;

use StellarPay\Core\Webhooks\EventProcessor;
use StellarPay\Core\Exceptions\Primitives\InvalidArgumentException;

/**
 * Class WebhookRegisterer
 *
 * @since 1.0.0
 */
class WebhookRegisterer
{
    /**
     * @since 1.0.0
     */
    private array $events = [];

    /**
     * @since 1.0.0
     */
    public function registerEventHandler(string $event, string $processor): void
    {
        if (! is_subclass_of($processor, EventProcessor::class)) {
            throw new InvalidArgumentException('Listener must be a subclass of ' . EventProcessor::class);
        }

        $this->events[$event][] = $processor;
    }

    /**
     * @since 1.0.0
     */
    public function registerEventHandlers(array $eventsWithHandlers): void
    {
        foreach ($eventsWithHandlers as $event => $processor) {
            $this->registerEventHandler($event, $processor);
        }
    }

    /**
     * @since 1.0.0
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @since 1.0.0
     */
    public function getEventIds(): array
    {
        return array_keys($this->events);
    }
}
