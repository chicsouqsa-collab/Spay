<?php

/**
 * This class is responsible to provide logic to create or update the Stripe customer based for the WooCommerce order.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Services;

use StellarPay\Core\ArraySet;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Integrations\WooCommerce\Stripe\Decorators\OrderDecorator;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\CustomerRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Strategies\CustomerDataStrategy;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\CustomerDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\CustomerDTO as StripeResponseCustomerDTO;
use StellarPay\PaymentGateways\Stripe\Services\CustomerService as BaseCustomerService;
use WC_Order;
use WP_User;

use function StellarPay\Core\container;

/**
 * @since 1.0.0
 */
class CustomerService
{
    /**
     * @since 1.0.0
     */
    protected BaseCustomerService $customerService;

    /**
     * @since 1.0.0
     */
    protected ?OrderDecorator $orderDecorator;

    /**
     * @since 1.0.0
     */
    protected CustomerRepository $customerRepository;

    /**
     * @since 1.0.0
     */
    protected OrderRepository $orderRepository;

    /**
     * @since 1.0.0
     */
    public function __construct(
        BaseCustomerService $customerService,
        CustomerRepository $customerRepository,
        OrderRepository $orderRepository
    ) {
        $this->customerService = $customerService;
        $this->customerRepository = $customerRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @since 1.0.0
     * @throws StripeAPIException|BindingResolutionException
     */
    public function createOrUpdate(WC_Order $order): string
    {
        $this->orderDecorator = new OrderDecorator($order);

        if (! $this->orderDecorator->isRegisteredUser()) {
            return $this->getOrCreateCustomerForGuest($order);
        }

        return $this->getOrCreateCustomerForRegisteredUser($order, $this->orderDecorator->getRegisterUser());
    }

    /**
     * This function handles the customer creation or retrieval for guest users.
     *
     * @since 1.0.0
     *
     * @throws StripeAPIException|BindingResolutionException
     */
    private function getOrCreateCustomerForGuest(WC_Order $order): string
    {
        $customerEmail = $order->get_billing_email();
        $customerId = $this->customerRepository->getCustomerIdByGuestEmail(
            $customerEmail,
            $this->orderRepository->getPaymentGatewayMode($order)
        );

        if (!$customerId) {
            return $this->createCustomerForGuest($order)->getId();
        }

        // Save the customer id in the order meta.
        $this->orderRepository->setCustomerId($order, $customerId);
        $order->save();

        return $this->retrieveCustomerId($customerId, $order);
    }

    /**
     * This function handles the customer creation or retrieval for registered users.
     *
     * @since 1.0.0
     * @throws StripeAPIException|BindingResolutionException
     */
    private function getOrCreateCustomerForRegisteredUser(WC_Order $order, WP_User $user): string
    {
        $customerId = $this->customerRepository->getCustomerIdByUser($user, $this->orderRepository->getPaymentGatewayMode($order));

        if (! $customerId) {
            $customerId = $this->getCustomerIdByGuestEmailForRegisteredUser($user, $order);

            if (! $customerId) {
                return $this->createCustomerForRegisteredUser($order)->getId();
            }
        }

        $this->orderRepository->setCustomerId($order, $customerId);
        $order->save();

        return $this->retrieveCustomerId($customerId, $order);
    }

    /**
     * This function validates customer id and returns it.
     *
     * It creates the customer if the existing customer is not valid.
     *
     * @since 1.0.0
     * @throws StripeAPIException|BindingResolutionException
     */
    protected function retrieveCustomerId(string $customerId, WC_Order $order): string
    {
        $handleMissingCustomer = function () use ($order) {
            return $this->orderDecorator->isRegisteredUser()
                ? $this->createCustomerForRegisteredUser($order)->getId()
                : $this->createCustomerForGuest($order)->getId();
        };

        try {
            // Retrieve the customer.
            $customer = $this->customerService->getCustomer($customerId);

            // if the customer is deleted, create a new customer.
            if ($customer->isDeleted()) {
                return $handleMissingCustomer();
            }
        } catch (StripeAPIException $e) {
            // If the customer has a stripe id but is not valid with this account,
            // a resource_missing error will be thrown. In this case, we need to
            // create a new customer.
            if ($e->isResourceNotFound()) {
                return $handleMissingCustomer();
            }

            throw $e;
        }

        // Update the customer if the data has changed.
        $this->updateCustomer($order, $customer);

        return $customerId;
    }

    /**
     * This function creates a new the Stripe customer.
     *
     * It adds the customer id to the order meta.
     *
     * @since 1.0.0
     * @throws StripeAPIException|BindingResolutionException
     */
    protected function createCustomerForGuest(WC_Order $order): StripeResponseCustomerDTO
    {
        $customer = $this->createCustomer($order);

        // Save the customer id in the order meta.
        $this->orderRepository->setCustomerId($order, $customer->getId());
        $order->save();

        return $customer;
    }

    /**
     * This function should create a new customer.
     *
     * It adds the customer id to the user meta.
     *
     * @since 1.0.0
     * @throws StripeAPIException|BindingResolutionException
     */
    protected function createCustomerForRegisteredUser(WC_Order $order): StripeResponseCustomerDTO
    {
        $customer = $this->createCustomer($order);

        // Save the customer id in the user and order meta.
        $this->customerRepository->setCustomerIdByUser(
            $this->orderDecorator->getRegisterUser(),
            $customer->getId(),
            $this->orderRepository->getPaymentGatewayMode($order)
        );
        $this->orderRepository->setCustomerId($order, $customer->getId());

        $order->save();

        return $customer;
    }

    /**
     * @throws StripeAPIException|BindingResolutionException
     */
    protected function createCustomer(WC_Order $order): StripeResponseCustomerDTO
    {
        return $this->customerService->createCustomer($this->getCustomerDto($order));
    }

    /**
     * This function returns the customer id for register user if created as the guest.
     *
     * It also migrates the customer id from guest to register user if exists.
     *
     * @since 1.0.0
     */
    protected function getCustomerIdByGuestEmailForRegisteredUser(WP_User $user, WC_Order $order): ?string
    {
        $customerId = $this->customerRepository->getCustomerIdByGuestEmail(
            $user->user_email,
            $this->orderRepository->getPaymentGatewayMode($order)
        );

        if (! $customerId) {
            return $customerId;
        }

        // Move customer id to user metadata.
        $this->customerRepository->setCustomerIdByUser(
            $user,
            $customerId,
            $this->orderRepository->getPaymentGatewayMode($order)
        );

        return $customerId;
    }

    /**
     * This method updates the customer.
     *
     * @since 1.0.0
     * @throws StripeAPIException|BindingResolutionException
     */
    protected function updateCustomer(WC_Order $order, StripeResponseCustomerDTO $customer): ?StripeResponseCustomerDTO
    {
        $newCustomerDto = $this->getCustomerDto($order);
        $existingCustomerData = $customer->getStripeResponseAsArray();
        $newCustomerData = $newCustomerDto->toArray();

        $changedData = ArraySet::diffOnCommonKeys($newCustomerData, $existingCustomerData, true);
        $changedData = array_merge($changedData, array_diff_key($newCustomerData, $existingCustomerData));

        // If the payment intent data has changed, update the payment intent.
        if ($changedData) {
            return $this->customerService->updateCustomer($customer->getId(), $changedData);
        }

        return null;
    }

    /**
     * This method returns the customer data transfer object.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    protected function getCustomerDto(WC_Order $order): CustomerDto
    {
        $customerDataStrategy = container(CustomerDataStrategy::class);
        $customerDataStrategy->setOrder($order);
        return CustomerDTO::fromCustomerDataStrategy($customerDataStrategy);
    }
}
