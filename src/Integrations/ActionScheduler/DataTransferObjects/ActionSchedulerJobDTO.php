<?php

/**
 * This class represents data required for action scheduler library functions.
 *
 * @package StellarPay\Integrations\ActionScheduler\DataTransferObjects
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\ActionScheduler\DataTransferObjects;

use StellarPay\Core\Contracts\DataTransferObjects;
use StellarPay\Core\Exceptions\Primitives\InvalidArgumentException;
use StellarPay\Core\Support\Facades\DateTime\Temporal;

/**
 * @since 1.2.0
 *
 * @method string getHookName()
 * @method array getArguments()
 * @method string getGroupName()
 * @method int getPriority()
 * @method bool getUnique()
 * @method int getTimestamp()
 * @method int getInterval()
 */
class ActionSchedulerJobDTO extends DataTransferObjects
{
    /**
     * @since 1.2.0
     */
    protected string $hookName;

    /**
     * @since 1.2.0
     */
    protected array $arguments;

    /**
     * @since 1.2.0
     */
    protected string $groupName;

    /**
     * @since 1.2.0
     */
    protected int $priority;

    /**
     * @since 1.2.0
     */
    protected bool $unique;

    /**
     * @since 1.2.0
     */
    protected int $timestamp;

    /**
     * @since 1.2.0
     */
    protected int $interval;

    /**
     * @since 1.2.0
     */
    public static function fromEventData(array $jobData): self
    {
        $self = new self();

        $self->validateEventData($jobData);

        $self->hookName = $jobData['hook-name'];
        $self->arguments = $jobData['arguments'] ?? [];
        $self->groupName = $jobData['group-name'] ?? '';
        $self->unique = $jobData['unique'] ?? false;
        $self->priority = $jobData['priority'] ?? 10;
        $self->timestamp = $jobData['timestamp'] ?? Temporal::getCurrentDateTime()->getTimestamp() + 5;
        $self->interval = $jobData['interval'] ?? MINUTE_IN_SECONDS * 15;

        return $self;
    }

    /**
     * @since 1.2.0
     */
    protected function validateEventData(array $eventData): void
    {
        $requiredFields = ['hook-name'];

        if (! array_intersect($requiredFields, array_keys($eventData))) {
            throw new InvalidArgumentException(sprintf(
                'Required fields are missing. Required fields: %s',
                esc_html(implode(', ', $requiredFields))
            ));
        }
    }
}
