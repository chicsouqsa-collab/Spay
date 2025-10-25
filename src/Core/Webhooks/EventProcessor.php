<?php

/**
 * Event processor for webhook events.
 *
 * This class is responsible to provide a contract for Stripe events processors.
 *
 * @package StellarPay\Core\Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Webhooks;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeWebhookEvents\EventDTO;

/**
 * Class EventProcessor
 *
 * @since 1.1.0 Move to core domain
 * @since 1.0.0
 */
abstract class EventProcessor
{
    /**
     * @since 1.1.0
     * @var EventDTO
     */
    private EventDTO $eventDTO;

    /**
     * @since 1.1.0
     * @var EventResponse
     */
    protected EventResponse $eventResponse;

    /**
     * @since 1.1.0
     * @param EventResponse $eventResponse
     */
    public function __construct(EventResponse $eventResponse)
    {
        $this->eventResponse = $eventResponse;
    }

    /**
     * This method processes the Stripe event.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function __invoke(EventDTO $eventDTO): EventResponse
    {
        $this->eventDTO = $eventDTO;
        $this->eventResponse->setWebhookEvent($this->getEventDTO());

        return $this->processEvent();
    }

    /**
     * @since 1.1.0
     *
     * @return EventResponse
     */
    abstract protected function processEvent(): EventResponse;

    /**
     * @since 1.1.0
     */
    public function getEventDTO(): EventDTO
    {
        return $this->eventDTO;
    }
}
