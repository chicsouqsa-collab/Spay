<?php

/**
 * @package StellarPay\MigrationLog\ValueObjects
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\MigrationLog;

use StellarPay\Core\Support\Enum;

/**
 * @since 1.3.0 Add "missing" status
 * @since 1.2.0
 *
 * @method static MigrationLogStatus SUCCESS()
 * @method static MigrationLogStatus FAILED()
 * @method static MigrationLogStatus MISSING()
 * @method bool isSuccess()
 * @method bool isFailed()
 * @method bool isMissing()
 */
class MigrationLogStatus extends Enum
{
    /**
     * @since 1.2.0
     */
    public const SUCCESS = 'success';

    /**
     * @since 1.2.0
     */
    public const FAILED = 'failed';

    /**
     * @since 1.3.0
     */
    public const MISSING = 'missing';
}
