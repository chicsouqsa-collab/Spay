<?php

/**
 * This class is responsible for registering the services for the Stripe payment gateway.
 *
 * @package StellarPay\PaymentGateways\Stripe\Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Services;

use ReflectionClass;
use ReflectionException;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\Stripe\Client;

use function StellarPay\Core\container;

/**
 * Class ServiceRegisterer
 *
 * @since 1.0.0
 */
class ServiceRegisterer
{
    /**
     * @since 1.0.0
     */
    private array $services = [
        AccountService::class,
        ChargeService::class,
        CustomerService::class,
        InvoiceService::class,
        PaymentIntentService::class,
        PaymentMethodService::class,
        PriceService::class,
        ProductService::class,
        RefundService::class,
        SubscriptionService::class,
        SubscriptionScheduleService::class,
        WebhookService::class
    ];

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        foreach ($this->services as $service) {
            container()->singleton($service, function () use ($service) {
                $stripe = container(Client::class);
                $dependencies = $this->resolveServiceDependencies($service);

                return $dependencies
                    ? new $service($stripe, ...$dependencies)
                    : new $service($stripe);
            });
        }
    }

    /**
     * Resolve the dependencies of the service excluding the Stripe client,
     *  which is already resolved and to be provided separately.
     *
     * @since 1.0.0
     *
     * @return array The dependencies of the service
     * @throws ReflectionException|BindingResolutionException
     */
    private function resolveServiceDependencies(string $service): array
    {
        $reflectionClass = new ReflectionClass($service);
        $parameters = $reflectionClass->getConstructor()->getParameters();

        if (count($parameters) <= 1) {
            return [];
        }

        array_shift($parameters); // Stripe client is always the first parameter

        $dependencies = [];
        foreach ($parameters as $parameter) {
            $dependencies[] = container($parameter->getType()->getName()); // @phpstan-ignore-line
        }
        return $dependencies;
    }
}
