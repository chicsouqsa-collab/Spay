<?php

/**
 * This class is a controller for the "update payment method" request from customer.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Controllers
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Controllers;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Request;
use StellarPay\Core\ValueObjects\ModifierContextType;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\UpdatePaymentMethodMySubscriptionPage;
use StellarPay\PaymentGateways\Stripe\Services\SubscriptionService;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Integrations\WooCommerce\Controllers\MyAccount\MySubscriptions;
use StellarPay\Integrations\WooCommerce\Utils\OrderNote;
use StellarPay\PaymentGateways\Stripe\Services\SubscriptionScheduleService;

/**
 * @since 1.1.0
 */
class UpdatePaymentMethod
{
    /**
     * @since 1.1.0
     */
    private SubscriptionService $subscriptionService;

    /**
     * @since 1.1.0
     */
    private SubscriptionScheduleService $subscriptionScheduleService;

    /**
     * @since 1.1.0
     */
    private Request $request;

    /**
     * @since 1.1.0
     */
    public function __construct(
        Request $request,
        SubscriptionService $subscriptionService,
        SubscriptionScheduleService $subscriptionScheduleService
    ) {
        $this->request = $request;
        $this->subscriptionService = $subscriptionService;
        $this->subscriptionScheduleService = $subscriptionScheduleService;
    }

    /**
     * Update the subscription payment method.
     *
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function __invoke(string $paymentMethodId): void
    {
        if (MySubscriptions::UPDATE_SUBSCRIPTION_PAYMENT_METHOD_ACTION_NAME !== $this->request->post('action')) {
            return;
        }

        $subscriptionId = absint($this->request->post('stellarpay_subscription_id'));
        $subscription = Subscription::find($subscriptionId);

        if (!$subscription) {
            wc_add_notice(esc_html__('Unable to update the payment method.', 'stellarpay'), 'error');
            return;
        }

        try {
            if ($subscription->isScheduleType()) {
                $this->subscriptionScheduleService->updatePaymentMethod($subscription->transactionId, $paymentMethodId);
            } else {
                $this->subscriptionService->updateSubscriptionPaymentMethod($subscription->transactionId, $paymentMethodId);
            }

            OrderNote::onSubscriptionPaymentMethodUpdate($subscription, $paymentMethodId, ModifierContextType::CUSTOMER());

            $subscription->saveNewPaymentMethodForRenewal($paymentMethodId);
        } catch (\Exception $e) {
            $errorMessage = esc_html__('Unable to update the payment method.', 'stellarpay');

            if ($e instanceof StripeAPIException) {
                $errorMessage .= ' ' . $e->getMessage();
            }

            wc_add_notice($errorMessage, 'error');

            // Redirect back to action page.
            wp_safe_redirect(UpdatePaymentMethodMySubscriptionPage::getActionURL($subscriptionId));
            exit();
        }
    }
}
