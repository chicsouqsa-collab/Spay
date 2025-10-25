<?php

/**
 * This controller is used to handle requests from My Account > Subscriptions tab.
 *
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Controllers\MyAccount;

use StellarPay\Core\Contracts\Controller;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Facades\QueryVars;
use StellarPay\Core\Services\ModifierContextService;
use StellarPay\Core\ValueObjects\ModifierContextType;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\PaymentGateways\Stripe\Traits\StripeClientHelpers;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Core\Constants;
use StellarPay\Core\Traits\ControlControllerProcessFlowUtilities;
use Exception;
use StellarPay\Core\Traits\ClassInvokeHelpers;
use StellarPay\Core\ValueObjects\WebhookEventType;
use WC_Payment_Tokens;
use StellarPay\Integrations\WooCommerce\Stripe\Constants as StripeConstants;
use StellarPay\Integrations\WooCommerce\Stripe\Controllers\AddPaymentMethod;
use StellarPay\Integrations\WooCommerce\Endpoints\MySubscriptionsEndpoint;
use StellarPay\Integrations\WooCommerce\Stripe\Controllers\UpdatePaymentMethod;

/**
 * @since 1.1.0
 */
class MySubscriptions extends Controller
{
    use ControlControllerProcessFlowUtilities;
    use SubscriptionUtilities;
    use ClassInvokeHelpers;
    use StripeClientHelpers;

    /**
     * @since 1.1.0
     */
    public const CANCEL_SUBSCRIPTION_ACTION_NAME = 'cancel-subscription';

    /**
     * @since 1.1.0
     */
    public const UPDATE_SUBSCRIPTION_PAYMENT_METHOD_ACTION_NAME = 'update-subscription-payment-method';

    /**
     * @since 1.1.0
     * @throws Exception
     */
    public function __invoke(): void
    {
        global $wp;

        if (!is_account_page()) {
            return;
        }

        if (!$this->shouldInterceptRequest()) {
            return;
        }

        $action = $this->getAction();

        if (! $this->isValidRequest()) {
            $this->addErrorNotice($action);

            if (self::CANCEL_SUBSCRIPTION_ACTION_NAME === $action) {
                $subscriptionId = QueryVars::getInt(MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG);
                $this->redirectToSubscriptionPage($subscriptionId);
            }

            return;
        }

        if (!$subscription = MySubscriptionsEndpoint::getSubscriptionFromQueryVars()) {
            throw new Exception("Subscription not found");
        }

        $this->setStripeClientWithServices($subscription->paymentGatewayMode);

        switch ($action) {
            case self::CANCEL_SUBSCRIPTION_ACTION_NAME:
                $this->handleCancel($subscription);
                break;

            case self::UPDATE_SUBSCRIPTION_PAYMENT_METHOD_ACTION_NAME:
                $this->handleUpdatePaymentMethod($subscription);
                break;

            default:
                return;
        }
    }

    /**
     * @since 1.1.0
     */
    protected function getAction(): ?string
    {
        $action = $this->request->post('action');

        if (!$action) {
            $action = $this->request->get('action');
        }

        if (!$action) {
            return null;
        }

        return $action;
    }

    /**
     * @since 1.3.0 Use renamed function.
     * @since 1.1.0
     */
    protected function handleCancel(Subscription $subscription)
    {
        if (! MySubscriptionsEndpoint::isPage()) {
            return;
        }

        $action = self::CANCEL_SUBSCRIPTION_ACTION_NAME;

        if (get_current_user_id() !== $subscription->customerId) {
            $this->addErrorNotice($action);
            $this->redirectToSubscriptionPage($subscription->id);
        }

        try {
            $this->cancelStripeSubscription($subscription);
            $result = $subscription->cancel();

            if ($result) {
                $event = $subscription->isScheduleType() ? WebhookEventType::SUBSCRIPTION_SCHEDULE_CANCELED() : WebhookEventType::CUSTOMER_SUBSCRIPTION_DELETED();

                ModifierContextService::fromArray(
                    [
                        'eventType' => $event->getValue(),
                        'objectId' => $subscription->id,
                    ]
                )->storeContext(ModifierContextType::CUSTOMER());
            }

            $this->addSuccessNotice($action);
        } catch (Exception $e) {
            $this->addErrorNotice($action);
        } finally {
            $this->redirectToSubscriptionPage($subscription->id);
        }
    }

    /**
     * @since 1.1.0
     */
    protected function addSuccessNotice(string $action): void
    {
        switch ($action) {
            case self::CANCEL_SUBSCRIPTION_ACTION_NAME:
                $message = esc_html__('Your subscription was canceled.', 'stellarpay');
                break;

            case self::UPDATE_SUBSCRIPTION_PAYMENT_METHOD_ACTION_NAME:
                $message = esc_html__('Payment method successfully updated.', 'stellarpay');
                break;

            default:
                return;
        }

        wc_add_notice(esc_html($message));
    }

    /**
     * @since 1.1.0
     */
    protected function addErrorNotice(string $action): void
    {
        switch ($action) {
            case self::CANCEL_SUBSCRIPTION_ACTION_NAME:
                $message = esc_html__('We are sorry, but we could not cancel your subscription. Please try again shortly.', 'stellarpay');
                break;

            case self::UPDATE_SUBSCRIPTION_PAYMENT_METHOD_ACTION_NAME:
                $message = esc_html__('Unable to update the payment method.', 'stellarpay');
                break;

            default:
                return;
        }

        wc_add_notice(esc_html($message), 'error');
    }

    /**
     * @since 1.1.0
     */
    protected function redirectToSubscriptionPage($subscriptionId): void
    {
        $this->redirectTo(wc_get_endpoint_url(MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG) . $subscriptionId);
    }

    /**
     * Handle subscription update payment method submit.
     *
     * @since 1.1.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    protected function handleUpdatePaymentMethod(Subscription $subscription): void
    {
        $subscriptionId = $subscription->id;
        $action = self::UPDATE_SUBSCRIPTION_PAYMENT_METHOD_ACTION_NAME;

        if (empty($this->request->post('wc-' . StripeConstants::GATEWAY_ID . '-payment-method-id'))) {
            $token = WC_Payment_Tokens::get($this->request->post('wc-' . StripeConstants::GATEWAY_ID . '-payment-token'));

            if (!$token) {
                $this->addErrorNotice($action);

                return;
            }

            $this->invokeClass(UpdatePaymentMethod::class, $token->get_token());

            $this->addSuccessNotice($action);

            wp_safe_redirect(MySubscriptionsEndpoint::getSubscriptionURL($subscriptionId));
            exit;
        }

        $paymentMethod = $this->invokeClassAndReturn(AddPaymentMethod::class);

        if (!$paymentMethod) {
            $this->addErrorNotice($action);

            return;
        }

        $this->invokeClass(UpdatePaymentMethod::class, $paymentMethod->getId());

        $this->addSuccessNotice($action);

        wp_safe_redirect(MySubscriptionsEndpoint::getSubscriptionURL($subscriptionId));
        exit;
    }

    /**
     * Check if the request should be intercepted.
     *
     * @since 1.1.0
     */
    protected function shouldInterceptRequest(): bool
    {
        global $wp;

        $userId = get_current_user_id();
        $subscriptionId = absint($wp->query_vars[MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG] ?? 0);
        $nonce = $this->getNonce();

        return $subscriptionId && $nonce && $userId;
    }

    /**
     * Check if the request is valid by checking nonce.
     *
     * @since 1.1.0
     */
    protected function isValidRequest()
    {
        $subscriptionId = QueryVars::getInt(MySubscriptionsEndpoint::MY_SUBSCRIPTIONS_SLUG);

        return wp_verify_nonce(
            $this->getNonce(),
            MySubscriptionsEndpoint::getNonceAction($this->getAction(), $subscriptionId)
        );
    }

    /**
     * Get nonce from request.
     *
     * @since 1.1.0
     */
    protected function getNonce(): ?string
    {
        $postedData = $this->getRequestData();

        return $postedData[Constants::NONCE_NAME] ?? null;
    }
}
