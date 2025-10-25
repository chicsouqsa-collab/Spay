<?php

/**
 * This class is a controller for the "add payment method" request from customer.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Controllers
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Controllers;

use Exception;
use StellarPay\Core\Request;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\CustomerRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\FindMatchForPaymentMethod;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\WooCommercePaymentToken;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\CustomerDTO;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;
use StellarPay\PaymentGateways\Stripe\Services\CustomerService;
use StellarPay\PaymentGateways\Stripe\Services\PaymentMethodService;
use WC_Customer;
use WP_User;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeResponses\PaymentMethodDTO;
use StellarPay\Integrations\WooCommerce\Endpoints\MySubscriptionsEndpoint;

/**
 * @since 1.0.0
 */
class AddPaymentMethod
{
    use WooCommercePaymentToken;
    use FindMatchForPaymentMethod;

    /**
     * @since 1.0.0
     */
    protected SettingRepository $settingRepository;

    /**
     * @since 1.0.0
     */
    private PaymentMethodService $paymentMethodService;

    /**
     * @since 1.0.0
     */
    private CustomerService $customerService;

    /**
     * @since 1.0.0
     */
    private CustomerRepository $customerRepository;

    /**
     * @since 1.0.0
     */
    private Request $request;

    /**
     * @since 1.0.0
     */
    public function __construct(
        PaymentMethodService $paymentMethodService,
        CustomerService $customerService,
        CustomerRepository $customerRepository,
        Request $request,
        SettingRepository $settingRepository
    ) {
        $this->paymentMethodService = $paymentMethodService;
        $this->customerService = $customerService;
        $this->customerRepository = $customerRepository;
        $this->request = $request;
        $this->settingRepository = $settingRepository;
    }

    /**
     * @since 1.1.0 Update the return type to `PaymentMethodDTO`
     * @since 1.0.0
     * @throws Exception
     */
    public function __invoke(): ?PaymentMethodDTO
    {
        $userId = get_current_user_id();
        $user = wp_get_current_user();

        if (! $userId || ! ( $user instanceof WP_User)) {
            return null;
        }

        $customer = new WC_Customer($user->ID);

        $stripePaymentMethodId = $this->request->post('wc-' . Constants::GATEWAY_ID . '-payment-method-id');
        try {
            $paymentMethod = $this->addPaymentMethod($customer, $stripePaymentMethodId);
        } catch (Exception $e) {
            wc_add_notice($e->getMessage(), 'error');
            return null;
        }

        return $paymentMethod;
    }

    /**
     * @since 1.3.0
     * @throws Exception
     */
    public function addPaymentMethod(WC_Customer $customer, string $stripePaymentMethodId): ?PaymentMethodDTO
    {
        $user = get_user_by('id', $customer->get_id());
        $paymentMethod = $this->paymentMethodService->getPaymentMethod($stripePaymentMethodId);
        $stripeCustomerId = $this->getCustomerIdByUser($user);
        $stripeCustomer = $stripeCustomerId ? $this->customerService->getCustomer($stripeCustomerId) : null;

        if (! $paymentMethod->isCard()) {
            throw new Exception(esc_html__('Please correct your credit or debit card.', 'stellarpay'));
        }

        // Duplicate Stripe payment method check.
        if ($stripeCustomerId) {
            $stripePaymentMethod = $this->findMatchForPaymentMethodWithStripeCustomerId(
                $stripeCustomerId,
                $paymentMethod
            );

            if ($stripePaymentMethod && ! $paymentMethod->hasId($stripePaymentMethod->getId())) {
                $paymentMethod = $stripePaymentMethod;
            }
        }

        // If the payment method is already saved, then return failure.
        if ($this->isDuplicatePaymentMethodToken($user, $paymentMethod)) {
            throw new Exception(esc_html__('The card you entered is already saved in our system. Please use a different card.', 'stellarpay'));
        }

        // Create a new stripe customer:
        // - if it does not exist.
        // - If customer deleted.
        if (!$stripeCustomerId || ! $stripeCustomer || $stripeCustomer->isDeleted()) {
            $customerDTO = $this->getCustomerDTO($user, $customer);
            $stripeCustomerId = $this->customerService->createCustomer($customerDTO)->getId();
            $this->customerRepository->setCustomerIdByUser($user, $stripeCustomerId, $this->settingRepository->getPaymentGatewayMode());
        }

        // Attach a payment method to the customer.
        $this->paymentMethodService->attachPaymentMethodToCustomer(
            $paymentMethod->getId(),
            $stripeCustomerId
        );

        if (!$this->saveCardTypePaymentMethod($paymentMethod, $user->ID)) {
            throw new Exception(esc_html__('Unable to save the card.', 'stellarpay'));
        }

        return $paymentMethod;
    }

    /**
     * Get customer ID by WP user.
     *
     * @since 1.1.0
     */
    protected function getCustomerIdByUser(WP_User $user): string
    {
        if (!$subscription = MySubscriptionsEndpoint::getSubscriptionFromQueryVars()) {
            return $this->customerRepository->getCustomerIdByUser($user, $this->settingRepository->getPaymentGatewayMode());
        }

        return $this->customerRepository->getCustomerIdByUser($user, $subscription->paymentGatewayMode);
    }

    /**
     * @since 1.0.0
     */
    private function getCustomerDTO(WP_User $user, WC_Customer $customer): CustomerDTO
    {
        $firstName = $customer->get_billing_first_name('edit') ?: $customer->get_first_name('edit');
        $lastName = $customer->get_billing_last_name('edit') ?: $customer->get_last_name('edit');
        $fullName = trim($firstName . ' ' . $lastName);

        // Collect basic information to create stripe customer.
        // Eventually, these details will update when the customer processes order.
        $customerDTO = new CustomerDTO();
        $customerDTO->email = $customer->get_billing_email('edit') ?: $customer->get_email('edit');
        $customerDTO->name = $fullName;
        $customerDTO->dataFromStrategy = [
            'description' => sprintf(
                // translators: 1: Name, 2 Username.
                esc_html__('Name: %1$s, Username: %2$s', 'stellarpay'),
                $fullName,
                $user->user_login
            ),
            'metadata' => [
                'site_url' => esc_url(get_site_url()),
            ]
        ];

        return $customerDTO;
    }
}
