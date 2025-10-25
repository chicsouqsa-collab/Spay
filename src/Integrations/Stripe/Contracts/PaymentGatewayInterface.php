<?php

/**
 * Stripe payment gateway contract.
 *
 * This interface is responsible for defining the contract for the Stripe payment gateway.
 *
 * @package StellarPay\PaymentGateways\Stripe\Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe\Contracts;

use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\CustomerDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\PaymentIntentDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\RefundDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\PriceDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\ProductDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\SubscriptionDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\SubscriptionScheduleDTO;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\StripeRequests\SubscriptionResumeDTO;
use StellarPay\Vendors\Stripe\Account as StripeApiAccountModel;
use StellarPay\Vendors\Stripe\Balance;
use StellarPay\Vendors\Stripe\Collection;
use StellarPay\Vendors\Stripe\Customer as StripeCustomerModel;
use StellarPay\Vendors\Stripe\Dispute;
use StellarPay\Vendors\Stripe\File;
use StellarPay\Vendors\Stripe\PaymentIntent as StripeApiPaymentIntentModel;
use StellarPay\Vendors\Stripe\PaymentMethod;
use StellarPay\Vendors\Stripe\PaymentMethodDomain;
use StellarPay\Vendors\Stripe\Payout;
use StellarPay\Vendors\Stripe\Radar\EarlyFraudWarning;
use StellarPay\Vendors\Stripe\Refund as StripeRefundModel;
use StellarPay\Vendors\Stripe\SubscriptionSchedule;
use StellarPay\Vendors\Stripe\WebhookEndpoint;
use StellarPay\Vendors\Stripe\Price;
use StellarPay\Vendors\Stripe\Product;
use StellarPay\Vendors\Stripe\Subscription;
use StellarPay\Vendors\Stripe\Invoice;
use StellarPay\Vendors\Stripe\SearchResult;
use DateTime;

/**
 * Interface PaymentGatewayInterface
 *
 * @package StellarPay\PaymentGateways\Stripe\Contracts
 * @since 1.0.0
 */
interface PaymentGatewayInterface
{
    /**
     * This method retrieves the account details for a given stripe account id.
     *
     * @since 1.0.0
     *
     * @throws StripeAPIException
     */
    public function getAccount(string $stripeAccountId): StripeApiAccountModel;

    /**
     * Get balance for a Stripe account.
     *
     * @since 1.0.0
     */
    public function getBalance(): Balance;

    /**
     * @since 1.0.0
     * @return Collection<Payout>
     */
    public function getUpcomingPayout(): Collection;

    /**
     * @since 1.0.0
     * @return Collection<Dispute>
     */
    public function getDisputes(): Collection;

    /**
     * @since 1.0.0
     * @return Collection<EarlyFraudWarning>
     */
    public function getEarlyFraudWarnings(array $params = []): Collection;

    /**
     * This method retrieves the account logo image for a given image id.
     *
     * @since 1.0.0
     *
     * @throws StripeAPIException
     */
    public function getAccountFile(string $imageId): File;

    /**
     * This method adds a domain to Stripe.
     *
     * This used by following payment methods: Apple Pay, Google Pay, Link and PayPal
     *
     * @since 1.0.0
     */
    public function registerDomain(string $url, bool $enabled = true): PaymentMethodDomain;

    /**
     * This function uses to ver
     *
     * @since 1.0.0
     */
    public function validateDomain(string $paymentMethodDomainId): PaymentMethodDomain;

    /**
     * This method creates a payment intent given data.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function createPaymentIntent(PaymentIntentDTO $paymentIntent): StripeApiPaymentIntentModel;

    /**
     * This method gets a payment intent by payment intent id.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function getPaymentIntent(string $paymentIntentId, array $options = []): StripeApiPaymentIntentModel;

    /**
     * This method updates a payment intent by payment intent id.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function updatePaymentIntent(string $paymentIntentId, array $paymentIntent): StripeApiPaymentIntentModel;

    /**
     * This method retrieves the customer details for a given customer id.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function getCustomer(string $customerId): StripeCustomerModel;

    /**
     * This method creates a customer given data.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function createCustomer(CustomerDTO $customerData): StripeCustomerModel;

    /**
     * This method updates a customer by customer id.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function updateCustomer(string $customerId, array $customerData): StripeCustomerModel;

    /**
     * This method creates a refund payment.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function createRefund(RefundDTO $refundDTO): StripeRefundModel;

    /**
     * @since 1.0.0
     */
    public function getLatestRefundByPaymentIntentId(string $paymentIntentId): ?StripeRefundModel;

    /**
     * This method retrieves a payment method given its id.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function getPaymentMethod(string $paymentMethodId): PaymentMethod;

    /**
     * This method retrieves all payment methods.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     * @return Collection<PaymentMethod>
     */
    public function getAllPaymentMethods(string $stripeCustomerId): Collection;

    /**
     * This method attaches a payment method to a customer.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function attachPaymentMethodToCustomer(string $paymentMethodId, string $customerId): PaymentMethod;

    /**
     * This method detaches a payment method.
     *
     * @since 1.0.0
     * @throws StripeAPIException
     */
    public function detachPaymentMethod(string $paymentMethodId): PaymentMethod;

    /**
     * This method creates a webhook.
     * This method creates a product given data.
     *
     * @since 1.0.0
     */
    public function createProduct(ProductDTO $productData): Product;

    /**
     * This method updates a product given its id.
     *
     * @since 1.0.0
     */
    public function updateProduct(string $productId, ProductDTO $productData): Product;

    /**
     * This method gets a subscription.
     *
     * @since 1.0.0
     */
    public function getSubscription(string $subscriptionId): Subscription;

    /**
     * This method creates a subscription given data.
     *
     * @since 1.0.0
     */
    public function createSubscription(SubscriptionDTO $subscriptionData): Subscription;

    /**
     * This method updates a subscription given its id.
     *
     * @since 1.0.0
     */
    public function updateSubscription(string $subscriptionId, SubscriptionDTO $subscriptionData): Subscription;

    /**
     * This method updates the subscription payment method given its id and a payment method id.
     *
     * @since 1.1.0
     */
    public function updateSubscriptionPaymentMethod(string $stripeSubscriptionId, string $paymentMethodId): Subscription;

    /**
     * This method resumes a subscription given its id.
     *
     * @since 1.9.0
     */
    public function resumeSubscription(SubscriptionResumeDTO $subscriptionResumeDTO): Subscription;

    /**
     * This method pauses a subscription given its id.
     *
     * @since 1.9.0
     */
    public function pauseSubscription(string $stripeSubscriptionId, DateTime $resumesAt): Subscription;

    /**
     * This method cancels a subscription given its id.
     *
     * @since 1.0.0
     */
    public function cancelSubscription(string $subscriptionId): Subscription;

    /**
     * This method cancels a subscription at period end given its id.
     *
     * @since 1.3.0
     */
    public function cancelAtPeriodEndSubscription(string $subscriptionId): Subscription;

    /**
     * This method creates a price given data.
     *
     * @since 1.0.0
     */
    public function createPrice(PriceDTO $priceData): Price;

    /**
     * @since 1.4.0
     */
    public function getSubscriptionSchedule(string $subscriptionScheduleId): SubscriptionSchedule;

    /**
     * @since 1.0.0
     */
    public function createSubscriptionSchedule(SubscriptionScheduleDTO $subscriptionScheduleDTO): SubscriptionSchedule;

    /**
     * @since 1.0.0
     */
    public function cancelSubscriptionSchedule(string $subscriptionScheduleId): SubscriptionSchedule;

    /**
     * @since 1.3.0
     */
    public function cancelAtPeriodEndSubscriptionSchedule(string $subscriptionScheduleId): SubscriptionSchedule;

    /**
     * @since 1.1.0
     */
    public function updateSubscriptionSchedulePaymentMethod(string $subscriptionScheduleId, string $paymentMethodId): SubscriptionSchedule;

    /**
     * @since 1.9.0
     */
    public function updateSubscriptionSchedule(string $subscriptionScheduleId, SubscriptionScheduleDTO $subscriptionScheduleDTO): SubscriptionSchedule;

    /**
     * This method retrieves the mode of the payment gateway.
     *
     * @since 1.0.0
     */
    public function createWebhook(array $params): WebhookEndpoint;

    /**
     * This method retrieves a webhook.
     *
     * @since 1.0.0
     */
    public function updateWebhook(string $webhookId, array $params): WebhookEndpoint;

    /**
     * This method deletes a webhook.
     *
     * @since 1.0.0
     */
    public function deleteWebhook(string $webhookId): bool;

    /**
     * This function returns a list of webhooks.
     *
     * @since 1.0.0
     */
    public function getAllWebhooks(): Collection;

    /**
     * @since 1.4.0
     */
    public function getUpcomingInvoiceForSubscription(string $subscriptionId): Invoice;

    /**
     * @since 1.4.0
     */
    public function getLastPaidInvoiceForSubscription(string $subscriptionId): ?Invoice;

    /**
     * @since 1.4.0
     */
    public function searchCharges(array $parameters): SearchResult;
}
