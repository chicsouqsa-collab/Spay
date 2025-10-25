<?php

/**
 * Enum to represent the status of a webhook event request.
 *
 * @package StellarPay\Webhook\ValueObjects
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Core\ValueObjects;

use StellarPay\Core\Support\Enum;

/**
 * @since 1.1.0
 *
 * @method static WebhookEventRequestStatus SUCCEEDED()
 * @method static WebhookEventRequestStatus FAILED()
 * @method static WebhookEventRequestStatus ERROR()
 * @method static WebhookEventRequestStatus RECORD_NOT_FOUND()
 * @method static WebhookEventRequestStatus RECORD_DELETED()
 * @method static WebhookEventRequestStatus UNPROCESSABLE()
 * @method bool isSucceeded()
 * @method bool isFailed()
 * @method bool isError()
 * @method bool isRecordNotFound()
 * @method bool isRecordDeleted()
 * @method bool isUnprocessable()
 */
class WebhookEventRequestStatus extends Enum
{
    /**
     * @since 1.1.0
     */
    public const SUCCEEDED = 'succeeded';

    /**
     * @since 1.1.0
     */
    public const FAILED = 'failed';

    /**
     * @since 1.1.0
     */
    public const RECORD_NOT_FOUND = 'record_not_found';

    /**
     * @since 1.1.0
     */
    public const RECORD_DELETED = 'record_deleted';

    /**
     * This represents that a webhook is received, but we can use process it because of unmatched context or state.
     *
     * @since 1.1.0
     */
    public const UNPROCESSABLE = 'unprocessable';

    /**
     * @since 1.1.0
     */
    public const ERROR = 'error';
}
