<?php

/**
 * Handles the update of the payment method for WooCommerce subscriptions.
 *
 * @package StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Controllers;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\ValueObjects\ModifierContextType;
use StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\Utils\WooSubscriptionNote;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\PaymentMethodRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\WooCommercePaymentToken;
use StellarPay\PaymentGateways\Stripe\Services\PaymentMethodService;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\FindMatchForPaymentMethod;
use StellarPay\Integrations\WooCommerce\Stripe\Controllers\AddPaymentMethod;
use WC_Payment_Tokens;
use WC_Customer;
use WC_Payment_Token;
use WC_Order;
use StellarPay\Integrations\WooCommerce\DataTransferObjects\ProcessPaymentResult;
use StellarPay\Integrations\WooCommerce\ValueObjects\ProcessPaymentResultType;
use StellarPay\Core\Contracts\Controller;
use StellarPay\Core\Request;
use WC_Subscription;

use function StellarPay\Core\container;
use function StellarPay\Core\prefixedKey;

/**
 * @since 1.7.0
 */
class UpdatePaymentMethod extends Controller
{
    use WooCommercePaymentToken;
    use FindMatchForPaymentMethod;

    /**
     * @since 1.7.0
     */
    private ?OrderRepository $orderRepository = null;

    /**
     * @since 1.7.0
     */
    private ?PaymentMethodService $paymentMethodService = null;

    /**
     * @since 1.7.0
     */
    private ?PaymentMethodRepository $paymentMethodRepository = null;

    /**
     * @since 1.7.0
     */
    public function __construct(
        OrderRepository $orderRepository,
        PaymentMethodService $paymentMethodService,
        PaymentMethodRepository $paymentMethodRepository,
        Request $request
    ) {
        parent::__construct($request);
        $this->orderRepository = $orderRepository;
        $this->paymentMethodService = $paymentMethodService;
        $this->paymentMethodRepository = $paymentMethodRepository;
    }

    /**
     * @since 1.7.0
     */
    public function __invoke(?ProcessPaymentResult $processPayment, WC_Order $order): ?ProcessPaymentResult
    {
        // Check if the request is for updating the payment method.
        if (! $this->isSubscriptionPaymentMethodChangeRequest()) {
            return $processPayment;
        }

        // Check if the order is a subscription.
        $subscription = wcs_get_subscription($order->get_id());
        if (! $subscription) {
            return $processPayment;
        }

        $processPayment = $processPayment instanceof ProcessPaymentResult ? $processPayment : new ProcessPaymentResult();

        try {
            // Use saved payment method token as a new payment method.
            if ('new' !== $this->request->post('wc-stellarpay-stripe-payment-token')) {
                // Update the existing payment method.
                $tokenId = intval($this->request->post('wc-stellarpay-stripe-payment-token'));

                if (empty($tokenId)) {
                    $errorMessage = esc_html__('Invalid payment method.', 'stellarpay');
                    wc_add_notice($errorMessage, 'error');

                    return $processPayment
                        ->setError(prefixedKey('invalid-payment-method'), $errorMessage);
                }

                $paymentToken = $this->addExistingPaymentMethodToken($subscription, $tokenId);
            } else {
                // Add a new payment method.
                $paymentMethodId = $this->request->post('wc-stellarpay-stripe-payment-method-id');
                $paymentToken = $this->addNewPaymentMethodToken($subscription, $paymentMethodId);
            }

            // @todo: code cleanup
            // It does not seem to be necessary that customer will have many subscriptions which are difficult to update at once.
            // For now, we are update all subscriptions, but in future, we should consider updating all subscription payment method token asynchronously.
            $this->updateAllSubscriptionsPaymentMethod($paymentToken);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage();
            wc_add_notice($errorMessage, 'error');

            return $processPayment
                ->setError(prefixedKey('invalid-payment-method'), $errorMessage);
        }

        $myAccountUrl = get_permalink(wc_get_page_id('myaccount'));

        return $processPayment->setResult(ProcessPaymentResultType::SUCCESS())
               ->setRedirect($myAccountUrl);
    }

    /**
     * @since 1.7.0
     * @throws BindingResolutionException|InvalidPropertyException|Exception
     */
    public function addExistingPaymentMethodToken(WC_Subscription $subscription, int $tokenId): WC_Payment_Token
    {
        $token = WC_Payment_Tokens::get($tokenId);

        if (! $token || ( Constants::GATEWAY_ID !== $token->get_gateway_id() )) {
            throw new Exception(esc_html__('Invalid payment method.', 'stellarpay'));
        }

        $this->changeSubscriptionPaymentMethodToken($subscription, $token);

        return $token;
    }

    /**
     * @since 1.7.0
     *
     * @throws BindingResolutionException|InvalidPropertyException|Exception|\Exception
     */
    public function addNewPaymentMethodToken(WC_Subscription $subscription, string $stripePaymentMethodId): WC_Payment_Token
    {
        $customer = new WC_Customer($subscription->get_customer_id());
        $paymentMethodToken = container(AddPaymentMethod::class)->addPaymentMethod($customer, $stripePaymentMethodId);

        if (!$paymentMethodToken) {
            throw new Exception(esc_html__('Invalid payment method.', 'stellarpay'));
        }

        // Get the saved payment method token from the database and use it.
        $paymentMethodToken = $this->paymentMethodRepository->findByStripePaymentMethodId($paymentMethodToken->getId());
        $this->changeSubscriptionPaymentMethodToken($subscription, $paymentMethodToken);

        return $paymentMethodToken;
    }

    /**
     * @since 1.7.0
     */
    private function isSubscriptionPaymentMethodChangeRequest(): bool
    {
        // This action hook is triggered when the payment method is changed via the WooCommerce subscription page.
        // Prior to this action hook, the Woocommerce subscription validated "wcs_change_payment_method",
        // Nonce which generates after subscription and customer capability check.
        // It means the request has been validated and can be processed.
        return (bool) did_action('woocommerce_subscription_change_payment_method_via_pay_shortcode');
    }

    /**
     * Set the payment method to the subscription.
     * When the renewal order is created, this payment method meta is automatically copied
     * to the renewal order.
     *
     * @since 1.7.0
     * @throws BindingResolutionException|InvalidPropertyException|Exception
     */
    public function changeSubscriptionPaymentMethodToken(WC_Subscription $subscription, WC_Payment_Token $token): void
    {
        $subscription->update_meta_data($this->orderRepository->getPaymentMethodIdKey(), $token->get_token());
        $subscription->set_requires_manual_renewal(false);
        $subscription->save();

        WooSubscriptionNote::onSubscriptionPaymentMethodUpdate(
            $subscription,
            $token->get_token(),
            current_user_can('manage_options') ? ModifierContextType::ADMIN() : ModifierContextType::CUSTOMER(),
        );
    }

    /**
     * @since 1.7.0
     * @throws BindingResolutionException|InvalidPropertyException|Exception
     */
    private function updateAllSubscriptionsPaymentMethod(WC_Payment_Token $token): void
    {
        // Check if the request is for updating all subscriptions.
        if (! $this->request->has('update_all_subscriptions_payment_method')) {
            return;
        }

        // Update all subscriptions.
        $allSubscriptions = wcs_get_users_subscriptions();
        foreach ($allSubscriptions as $subscription) {
            // Check if the subscription is valid.
            if (! $subscription instanceof WC_Subscription) {
                continue;
            }

            // Skip if there are no remaining payments or the subscription is not current.
            if ($subscription->get_time('next_payment') <= 0 || ! $subscription->has_status([ 'active', 'on-hold' ])) {
                continue;
            }

            // Check if the payment method is already set to the new token.
            if ($this->orderRepository->getPaymentMethodId($subscription) === $token->get_token()) {
                continue;
            }

            $this->changeSubscriptionPaymentMethodToken($subscription, $token);
        }
    }
}
