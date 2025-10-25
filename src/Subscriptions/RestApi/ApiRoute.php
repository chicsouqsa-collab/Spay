<?php

/**
 * This class is a contract for Api route.
 *
 * @package StellarPay\Subscriptions\RestApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\RestApi;

use StellarPay\Core\Constants;

/**
 * @since 1.0.0
 */
abstract class ApiRoute extends \StellarPay\RestApi\Endpoints\ApiRoute
{
    /**
     * @inheritdoc
     * @since 1.0.0
     */
    protected string $namespace = Constants::PLUGIN_SLUG . '/v1/subscriptions';
}
