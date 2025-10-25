<?php

/**
 * This class is responsible for registering routes.
 *
 * @package StellarPay\Core
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Core;

use StellarPay\Integrations\WooCommerce\Endpoints\MySubscriptionsEndpoint;

/**
 * Class RoutesRegisterer
 *
 * @since 1.8.0 Remove ".well-known" route rule.
 * @since 1.7.0
 */
class RoutesRegisterer
{
    /**
     * Get all routes to be registered.
     *
     * @since 1.7.0
     * @return array<string, array<int, array<string, string|int>>>
     */
    public function getRoutes(): array
    {
        // Developer note
        // Any changes to the data format should be reflected in the test class.
        // If we add only new entries to endpoints or rules with the same data format,
        // Then we do not need to update our tests.
        return [
            'endpoints' => [
                [
                    'slug' => MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG,
                    'args' => EP_ROOT | EP_PAGES,
                ],
            ],
            'rules' => [],
        ];
    }

    /**
     * Register routes dynamically.
     *
     * @since 1.7.0
     */
    public function __invoke(): void
    {
        $routes = $this->getRoutes();

        // Register endpoints
        foreach ($routes['endpoints'] as $endpoint) {
            add_rewrite_endpoint($endpoint['slug'], $endpoint['args']);
        }

        // Register rules
        foreach ($routes['rules'] as $rule) {
            add_rewrite_rule($rule['pattern'], $rule['query'], $rule['priority']);
        }
    }
}
