<?php

/**
 * Woocommerce Stripe Payment Gateway.
 *
 * This class is responsible for managing the Stripe payment gateway for WooCommerce for legacy checkout page.
 *
 * @package StellarPay/Integrations/WooCommerce/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe;

use Exception;
use StellarPay\Core\EnqueueScript;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Request;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Integrations\Stripe\StripeErrorMessage;
use StellarPay\Integrations\WooCommerce\Endpoints\MySubscriptionsEndpoint;
use StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\RegisterSupport;
use StellarPay\Integrations\WooCommerce\Stripe\Actions\FilterPaymentTokensByPaymentGatewayMode;
use StellarPay\Integrations\WooCommerce\Stripe\Controllers\AddPaymentMethod;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Services\RefundService;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\HandlesStripeElementData;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\WooCommercePaymentToken;
use StellarPay\Integrations\WooCommerce\Stripe\ValueObjects\Money;
use StellarPay\Integrations\WooCommerce\Traits\MixedSubscriptionUtilities;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Controllers\PaymentProcessor;
use Automattic\WooCommerce\StoreApi\Payments\PaymentContext;
use StellarPay\Core\Hooks;
use StellarPay\Core\Facades\QueryVars;
use StellarPay\Integrations\WooCommerce\DataTransferObjects\ProcessPaymentResult;
use StellarPay\Integrations\WooCommerce\Stripe\Controllers\PrePaymentProcessLegacyCheckout;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Customer;
use WC_Payment_Gateway;
use WC_Payment_Token;
use WP_Error;
use StellarPay\Integrations\WooCommerce\FeatureSupport\ReactBasedPaymentSettings as RegisterSupportForReactBasedPaymentSettingsFeature;

use function StellarPay\Core\container;
use function StellarPay\Core\isRestAPIRequest;

/**
 * @since 1.8.0 Remove unused properties.
 * @since 1.7.0 Implement support to process subscription with WooCommerce Subscriptions.
 * @since 1.0.0
 */
class PaymentGateway extends WC_Payment_Gateway
{
    use HandlesStripeElementData;
    use WooCommercePaymentToken;
    use MixedSubscriptionUtilities;

    /**
     * @since 1.0.0
     */
    public $supports = [
        'products',
        'refunds',
        'tokenization'
    ];

    /**
    * @since 1.0.0
    */
    private AccountRepository $accountRepository;

    /**
     * @since 1.0.0
     */
    private Request $request;

    /**
     * @since 1.0.0
     */
    private SettingRepository $settingRepository;

    /**
     * @since 1.0.0
     */
    private OrderRepository $orderRepository;

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function __construct()
    {
        $this->request = container(Request::class);
        $this->accountRepository = container(AccountRepository::class);
        $this->orderRepository = container(OrderRepository::class);
        $this->settingRepository = container(SettingRepository::class);

        $this->id = Constants::GATEWAY_ID;

        // To control the payment method title in the WordPress backend, we intentionally didn't set "method_title".
        $this->title = $this->getTitle();

        $this->method_description = $this->getMethodDescription();
        $this->has_fields = true;

        $this->init_settings();

        // Reset property.
        // The parent class sets this property based on the settings.
        // But we want to set it additionally based on the account connection.
        $this->enabled = $this->is_available() ? 'yes' : 'no';

        // Register the WooCommerce subscription support.
        (new RegisterSupport($this))->registerSupport();

        // Register the React-based Payment settings page feature support.
        (new RegisterSupportForReactBasedPaymentSettingsFeature($this))->registerSupport();
    }

    /**
     * @since 1.8.0
     */
    protected function getMethodDescription(): string
    {
        return nl2br(esc_html__('StellarPay is the leading WooCommerce integration for Stripe, backed by active development and top-notch support. Beyond a payment gateway, it offers robust tools to enhance your eCommerce sales.

        With full integration with Stripe Elements, StellarPay provides seamless, secure, and customizable payment experiences. Our Stripe-certified developers ensure reliability, and as a Stripe verified partner, we deliver a trusted solution for all your payment processing needs.', 'stellarpay'));
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    private function getTitle(): string
    {
        $title = $this->settingRepository->getPaymentGatewayTitle();

        // Print a custom method title in the WooCommerce payment method list.
        if ('wc-settings' === $this->request->get('page')  && 'checkout' === $this->request->get('tab')) {
            $title = 'StellarPay - Stripe Payment Gateway Integration';
        }

        return $title;
    }

    /**
     * @since 1.0.0
     */
    public function is_available(): bool // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $available = parent::is_available();
        return $available && $this->accountRepository->isLiveModeConnected();
    }

    /**
     * This function processes payment for WooCommerce order.
     *
     * @since 1.7.0 Use "PaymentProcessor" controller to process payment.
     * @since 1.7.0 Add support to process subscription with WooCommerce Subscriptions with zero order value.
     * @since 1.0.0
     * @throws BindingResolutionException|StripeAPIException|\StellarPay\Core\Exceptions\Primitives\Exception
     */
    public function process_payment($orderId): ?array // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $order = wc_get_order($orderId);

        // Allow third party code to process the payment. Example Woo Subscriptions update payment method.
        $processPayment = Hooks::applyFilters('stellarpay_payment_process', null, $order);

        if ($processPayment instanceof ProcessPaymentResult) {
            return $processPayment->toArray();
        }

        // "Order Pay" Page Compatibility
        // Action hook (woocommerce_before_pay_action) triggers prior to data validation on the order-pay page.
        // For this reason, we need to set up stripe customer and payment method here.
        if (is_wc_endpoint_url('order-pay')) {
            $prePaymentProcessLegacyCheckout = container(PrePaymentProcessLegacyCheckout::class);
            $paymentContext = new PaymentContext();
            $paymentContext->set_order($order);

            $prePaymentProcessLegacyCheckout($paymentContext);
        }

        $invokable = container(PaymentProcessor::class);

        return $invokable
            ->withRedirectUrl($this->get_return_url($order))
            ->process($order)
            ->toArray();
    }

    /**
     * Show description and option to save cards, or payment method
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function payment_fields(): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $this->paymentMethodScripts();

        if (
            is_user_logged_in()
            && (is_checkout() || MySubscriptionsEndpoint::isSubscriptionUpdatePaymentMethodPage() || $this->isWooSubscriptionUpdatePaymentMethodPage())
            && $this->supports('tokenization')
        ) {
            $this->tokenization_script();

            if ($this->get_tokens()) {
                $this->saved_payment_methods();
            }

            $this->form();

            // We should not show the save payment method checkbox on the WooCommerce subscription update payment method page.
            $this->save_payment_method_checkbox();
        } else {
            $this->form();
        }
    }

    /**
     * @since 1.0.0
     * @return array|WC_Payment_Token[]
     * @throws BindingResolutionException
     */
    public function get_tokens(): array // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (! MySubscriptionsEndpoint::isSubscriptionUpdatePaymentMethodPage()) {
            $invokable = container(FilterPaymentTokensByPaymentGatewayMode::class);
            return $invokable(parent::get_tokens());
        }

        $subscription = MySubscriptionsEndpoint::getSubscriptionFromQueryVars();

        $invokable = container(FilterPaymentTokensByPaymentGatewayMode::class);

        return $invokable->setTokens(parent::get_tokens())
            ->setExcludeTokens([$subscription->getLastPaymentMethod()])
            ->setMode($subscription->paymentGatewayMode)
            ->getTokens();
    }

    /**
     * Output HTML for the save payment method checkbox.
     *
     * Note
     * This function is a copy of the parent function with the only
     * difference being the style attribute added to the checkbox to hide it on the first load.
     *
     *
     * @since 1.0.0
     */
    public function save_payment_method_checkbox(): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        // If we don't have tokens, then we don't need to hide the checkbox on the first load.
        if (! $this->get_tokens()) {
            parent::save_payment_method_checkbox();
            return;
        }

        ob_start();
        parent::save_payment_method_checkbox();
        $html = ob_get_clean();

        $searchString = 'class="form-row woocommerce-SavedPaymentMethods-saveNew"';
        $replaceString = "$searchString style=\"display: none;\"";

        echo str_replace($searchString, $replaceString, $html); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }

    /**
     * Output field name HTML
     *
     * Gateways which support tokenization do not require names - we don't want the data to post to the server.
     *
     * @since 1.0.0
     */
    protected function form(): void
    {
        ?>
        <input type="hidden" name="wc-<?php echo esc_attr($this->id); ?>-payment-method-id" value="">
        <fieldset
            id="wc-<?php echo esc_attr($this->id); ?>-element"
            class='wc-credit-card-form wc-payment-form'
            <?php
            // Hide the form on the first load if we have tokens and support tokenization.
            $canHideOnFirstLoad = $this->get_tokens() && $this->supports('tokenization');
            if ($canHideOnFirstLoad) {
                echo 'style="display: none;"';
            }
            ?>
        ></fieldset>
        <?php
    }

    /**
     * Enqueue the scripts on legacy checkout page.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    protected function paymentMethodScripts(): void
    {

        switch (true) {
            case is_add_payment_method_page():
                $scriptId = 'stripe-add-payment-method-integration';
                break;

            case $this->isWooSubscriptionUpdatePaymentMethodPage():
                $scriptId = 'woo-subscription-update-payment-method-integration';
                break;

            case MySubscriptionsEndpoint::isSubscriptionUpdatePaymentMethodPage():
                $scriptId = 'stripe-update-payment-method-integration';
                break;

            default:
                $scriptId = 'stripe-legacy-checkout-integration';
                break;
        }

        (new EnqueueScript($scriptId, "/build/$scriptId.js"))
            ->registerTranslations()
            ->loadInFooter()
            ->registerLocalizeData('stellarPayStripeData', $this->getPaymentMethodData())
            ->enqueue();
    }

    /**
     * This function returns the payment method data.
     *
     * Payment method data will be accessible on the client side.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    protected function getPaymentMethodData(): array
    {
        // Get the data from the cart.
        $cart = WC()->cart;
        $currency = strtolower(get_woocommerce_currency());
        $amount = Money::make((float)$cart->get_total('edit'), $currency);

        // Get the data from order if we are on the order-pay page.
        if (is_wc_endpoint_url('order-pay')) {
            $orderKey = $this->request->get('key', '');
            $validOrderKey = false !== strpos($orderKey, 'wc_order_');

            if ($validOrderKey) {
                $orderId = wc_get_order_id_by_order_key($orderKey);
                $order = wc_get_order($orderId);

                $amount = Money::make((float)$order->get_total('edit'), $currency);
            }
        }

        $data = [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'order' => [
                'amount' => $amount->getMinorAmount(),
                'currency' => $currency
            ]
        ];

        $data['stripeErrorMessages'] = StripeErrorMessage::getErrorMessageList();

        if ($stripeElementData = $this->getStripeElementData()) {
            $data = array_merge($data, $stripeElementData);
        }

        // Add customer information.
        // These details are primarily used to create a Stripe payment method when adding a new payment method.
        $userId = get_current_user_id();
        if ($userId && (is_add_payment_method_page() || MySubscriptionsEndpoint::isSubscriptionUpdatePaymentMethodPage())) {
            $user = wp_get_current_user();
            $customer = new WC_Customer($user->ID);
            $firstName = $customer->get_billing_first_name('edit') ?: $customer->get_first_name('edit');
            $lastName = $customer->get_billing_last_name('edit') ?: $customer->get_last_name('edit');
            $fullName = trim($firstName . ' ' . $lastName);

            $data['customer'] = [
                'billing' => [
                    'name' => $fullName,
                    'email' => $customer->get_billing_email('edit') ?: $customer->get_email('edit'),
                    'phone' => $customer->get_billing_phone('edit')
                ]
            ];
        }

        if ($this->isWooSubscriptionUpdatePaymentMethodPage()) {
            $orderId = QueryVars::getInt('order-pay');
            $order   = wc_get_order($orderId);

            if ($order) {
                $data['customer'] = [
                    'billing' => [
                        'name' => $order->get_formatted_billing_full_name(),
                        'email' => $order->get_billing_email(),
                        'phone' => $order->get_billing_phone(),
                        'address_1' => $order->get_billing_address_1(),
                        'address_2' => $order->get_billing_address_2(),
                        'city' => $order->get_billing_city(),
                        'state' => $order->get_billing_state(),
                        'postcode' => $order->get_billing_postcode(),
                        'country' => $order->get_billing_country(),
                    ]
                ];
            }
        }

        return $data;
    }


    /**
     * This function processes refund for WooCommerce order.
     *
     * @since 1.0.0
     *
     * @return bool|WP_Error
     */
    public function process_refund($orderId, $amount = null, $reason = '')  // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps,Generic.Files.LineLength.TooLong
    {
        $order = wc_get_order($orderId);
        $reason = $reason ?: ''; // $reason cannot nullable after this line.

        if (! $order) {
            return new WP_Error('refund_error', esc_html__('Order not found.', 'stellarpay'));
        }

        if (! $amount) {
            return new WP_Error('refund_error', esc_html__('Refund amount is required.', 'stellarpay'));
        }

        try {
            $this->getService(RefundService::class)->create($order, (float) $amount, $reason);

            return true;
        } catch (Exception $e) {
            return new WP_Error('refund_error', $e->getMessage());
        }
    }

    /**
     * This function returns the transaction URL.
     *
     * @todo this function should use payment mode to dynamically generate the URL.
     *
     * @since 1.0.0
     */
    public function get_transaction_url($order): string // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        return  sprintf(
            'https://dashboard.stripe.com%1$s/payments/%2$s',
            $this->orderRepository->isTestOrder($order) ? '/test' : '',
            $order->get_transaction_id()
        );
    }

    /**
     * This function validates the fields.
     *
     * @since 1.0.0
     */
    public function validate_fields(): bool // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $stripePaymentMethodId = $this->request->post('wc-' . $this->id . '-payment-method-id');
        $selectedPaymentMethodTokenId = absint($this->request->post('wc-' . $this->id . '-payment-token'));

        $valid = true;

        // Checkout block does order validation on the REST API endpoint.
        // We should get the payment method id and token from the request.
        if (isRestAPIRequest()) {
            $stripePaymentMethodId = $this->request->post('paymentmethodid');
            $selectedPaymentMethodTokenId = $this->request->post('token');
        }

        // Token or payment method id is required to process payment for the order.
        if (
            empty($selectedPaymentMethodTokenId)
            && (empty($stripePaymentMethodId) || false === strpos($stripePaymentMethodId, 'pm_'))
        ) {
            // Legacy Checkout Compatibility:: Set a notice if no payment method is selected.
            wc_add_notice(
                esc_html__('A valid payment method is necessary to proceed with the order. Please ensure it is provided.', 'stellarpay'),
                'error'
            );

            $valid = false;
        }

        return $valid;
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException|Exception
     */
    public function add_payment_method(): array // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $failureResult = parent::add_payment_method();
        $invokable = container(AddPaymentMethod::class);

        if (! $invokable()) {
            return $failureResult;
        }

        return [
            'result'   => 'success',
            'redirect' => $failureResult['redirect'],
        ];
    }

    /**
     * @since 1.0.0
     *
     * @template S
     *
     * @param class-string<S> $serviceClassName
     *
     * @return S
     * @throws BindingResolutionException
     */
    private function getService(string $serviceClassName)
    {
        static $cache = [];

        if (array_key_exists($serviceClassName, $cache)) {
            return $cache[$serviceClassName];
        }

        $cache[$serviceClassName] = container($serviceClassName);

        return $cache[$serviceClassName];
    }
}
