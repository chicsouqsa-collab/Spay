<?php

/**
 * This class is responsible for loading services for WooCommerce.
 *
 * @package StellarPay\Integrations\WooCommerce
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce;

use Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry;
use StellarPay\Core\Constants;
use StellarPay\Integrations\WooCommerce\Controllers\SyncSimpleProductQuickEditChanges;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Hooks;
use StellarPay\Integrations\WooCommerce\Actions\FlushPermalinkWhenTogglePaymentGateway;
use StellarPay\Integrations\WooCommerce\Cart\Block\Block;
use StellarPay\Integrations\WooCommerce\Cart\StoreApi\Cart;
use StellarPay\Integrations\WooCommerce\Cart\StoreApi\CartItem;
use StellarPay\Integrations\WooCommerce\Controllers\MyAccount\MySubscriptions;
use StellarPay\Integrations\WooCommerce\Controllers\SaveProductSetting;
use StellarPay\Integrations\WooCommerce\Controllers\SaveSimpleProductSettings;
use StellarPay\Integrations\WooCommerce\Emails\Actions\SentSubscriptionStatusChangedEmails;
use StellarPay\Integrations\WooCommerce\Emails\EmailCustomization\AddSubscriptionsDetailsToEmail;
use StellarPay\Integrations\WooCommerce\Emails\SubscriptionStatusChangedAdminEmail;
use StellarPay\Integrations\WooCommerce\Emails\SubscriptionStatusChangedSuccessfulCustomerEmail;
use StellarPay\Integrations\WooCommerce\Endpoints\MySubscriptionsEndpoint;
use StellarPay\Integrations\WooCommerce\ExtensionSupport\WooSubscriptions\RegisterValidations;
use StellarPay\Integrations\WooCommerce\Repositories\CustomerRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Actions\AcceptSubscriptionOrderWithZeroInitialAmount;
use StellarPay\Integrations\WooCommerce\Stripe\Actions\DisableOrderPaymentDuringAsyncConfirmation;
use StellarPay\Integrations\WooCommerce\Stripe\Actions\EditPaymentGatewaysAvailabilityOnCheckout;
use StellarPay\Integrations\WooCommerce\Stripe\Actions\RegistrationOnCheckoutWithSubscriptionProduct;
use StellarPay\Integrations\WooCommerce\Stripe\Controllers\DeletePaymentMethod;
use StellarPay\Integrations\WooCommerce\Stripe\Controllers\GetCartTotals;
use StellarPay\Integrations\WooCommerce\Stripe\Controllers\PrePaymentProcessLegacyCheckout;
use StellarPay\Integrations\WooCommerce\Stripe\Controllers\PrePaymentProcessor;
use StellarPay\Integrations\WooCommerce\Stripe\Controllers\RenderCardOnOrderReceipt;
use StellarPay\Integrations\WooCommerce\Stripe\Controllers\ReturnResultInJsonFormatForOrderPayPayment;
use StellarPay\Integrations\WooCommerce\Stripe\Controllers\StellarPaySubscriptionProcessor;
use StellarPay\Integrations\WooCommerce\Stripe\PaymentGateway;
use StellarPay\Integrations\WooCommerce\Stripe\PaymentGatewayCheckoutBlockSupport;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Services\CustomerService;
use StellarPay\Integrations\WooCommerce\Stripe\Services\PaymentIntentService;
use StellarPay\Integrations\WooCommerce\Stripe\Services\PriceService;
use StellarPay\Integrations\WooCommerce\Stripe\Services\ProductService;
use StellarPay\Integrations\WooCommerce\Stripe\Services\RefundService;
use StellarPay\Integrations\WooCommerce\Stripe\Traits\WooCommercePaymentGatewayUtilities;
use StellarPay\Integrations\WooCommerce\Stripe\Views\BadgesContainerForOrderEditPage;
use StellarPay\Integrations\WooCommerce\Stripe\Views\DisplaySubscriptionOrderBadge;
use StellarPay\Integrations\WooCommerce\Stripe\Views\DisplayTestModeBadge;
use StellarPay\Integrations\WooCommerce\Stripe\Views\EditRefundButtonTitle;
use StellarPay\Integrations\WooCommerce\Stripe\Views\EditPaymentGatewayDisplay;
use StellarPay\Integrations\WooCommerce\Stripe\Views\OrderEditPage as StripeOrderEditPage;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events\ChargeRefunded;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events\ChargeRefundUpdated;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events\ChargeUpdated;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events\CustomerSubscriptionCreated;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events\CustomerSubscriptionDeleted;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events\CustomerSubscriptionUpdated;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events\InvoicePaid;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events\InvoicePaymentFailed;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events\PaymentIntentCanceled;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events\PaymentIntentPaymentFailed;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events\PaymentIntentProcessing;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events\PaymentIntentSucceeded;
use StellarPay\Integrations\WooCommerce\Stripe\Webhook\Events\SubscriptionScheduleCanceled;
use StellarPay\Integrations\WooCommerce\ValueObjects\ProductType;
use StellarPay\Integrations\WooCommerce\Views\OrderRecurringTotals;
use StellarPay\Integrations\WooCommerce\Views\EditPriceHTML;
use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\RestApi\SubscriptionStatus;
use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\MySubscriptionsPage;
use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\UpdatePaymentMethodMySubscriptionPage;
use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\ViewMySubscriptionPage;
use StellarPay\Integrations\WooCommerce\Views\OrderEditPage\OrderEditPage;
use StellarPay\Integrations\WooCommerce\Views\ProductEditPage\ProductEditPage;
use StellarPay\Integrations\WooCommerce\Views\ProductEditPage\SubscriptionSalePriceNotice;
use StellarPay\Integrations\WooCommerce\Views\ProductEditPage\VariationSettingFields;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\RestApi\DetachCustomerPaymentMethod;
use StellarPay\Core\ValueObjects\WebhookEventType;
use StellarPay\Integrations\WooCommerce\Cart\FeeRecovery;
use StellarPay\Integrations\WooCommerce\Controllers\VariationBulkActions;
use StellarPay\Integrations\WooCommerce\Controllers\SaveProductVariationSettings;
use StellarPay\Integrations\WooCommerce\Controllers\SaveVariableProductSettings;
use StellarPay\Integrations\WooCommerce\Orders\Actions\AddOrderNoteOnScheduleStellarPaySubscriptionCancelation;
use StellarPay\Integrations\WooCommerce\Orders\Actions\AddOrderNoteOnStellarPaySubscriptionCanceled;
use StellarPay\Integrations\WooCommerce\Orders\Actions\AddOrderNoteOnStellarPaySubscriptionPaymentMethodUpdated;
use StellarPay\Integrations\WooCommerce\Orders\Actions\AddOrderNoteOnSubscriptionPausedAtPeriodEnd;
use StellarPay\Integrations\WooCommerce\Views\ClassicCheckoutAndCartGettextFilter;
use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\ViewOrder;
use StellarPay\Integrations\WooCommerce\Views\MyAccountPage\ViewOrders;
use StellarPay\PaymentGateways\Stripe\Webhook\WebhookRegisterer;
use StellarPay\PluginSetup\Environment;

use function get_current_user_id;
use function StellarPay\Core\container;
use function StellarPay\Core\dbOptionKeyGenerator;

/**
 * Class ServiceProvider
 *
 * @package StellarPay\Integrations\WooCommerce
 * @since 1.0.0
 */
class ServiceProvider implements \StellarPay\Core\Contracts\ServiceProvider
{
    use WooCommercePaymentGatewayUtilities;

    /**
     * @inheritDoc
     * @throws BindingResolutionException
     */
    public function register(): void
    {
        container()->singleton(OrderRepository::class);
        container()->singleton(DetachCustomerPaymentMethod::class);
        container()->singleton(CustomerService::class);
        container()->singleton(PaymentIntentService::class);
        container()->singleton(PriceService::class);
        container()->singleton(ProductService::class);
        container()->singleton(RefundService::class);
        container()->singleton(StellarPaySubscriptionProcessor::class);
    }

    /**
     * @inheritDoc
     * @throws BindingResolutionException
     */
    public function boot(): void
    {
        // If WooCommerce is not active, do not boot WooCommerce services.
        if (! container(Environment::class)->isWooCommerceActive()) {
            return;
        }

        add_action('before_woocommerce_init', [$this, 'declareFeatureCompatibility']);

        $this->registerWebhookProcessors();

        if (container(AccountRepository::class)->isLiveModeConnected()) {
            // Register a payment gateway.
            add_filter(
                'woocommerce_payment_gateways',
                static function (array $registeredGateways) {
                    $registeredGateways[] = PaymentGateway::class;

                    return $registeredGateways;
                }
            );

            // Register the Stripe payment gateway to WooCommerce checkout block.
            add_action('woocommerce_blocks_loaded', static function () {
                add_action(
                    'woocommerce_blocks_payment_method_type_registration',
                    static function (PaymentMethodRegistry $registerer) {
                        $registerer->register(container(PaymentGatewayCheckoutBlockSupport::class));
                    }
                );
            });

            $this->registerWooCommerceApiHooks();
        }
    }

    /**
     * Register WooCommerce API hooks.
     *
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function registerWooCommerceApiHooks(): void
    {
        // Manage payment gateway rendering in WooCommerce settings.
        Hooks::addAction(
            'woocommerce_admin_field_payment_gateways',
            EditPaymentGatewayDisplay::class,
            'makeChangeToDOMInWooPaymentGatewayList',
        );

        Hooks::addAction('woocommerce_update_options', FlushPermalinkWhenTogglePaymentGateway::class);

        if ($this->isPaymentGatewayActiveInWoocommerce()) {
            // Register rest api endpoint.
            Hooks::addAction('rest_api_init', SubscriptionStatus::class, 'register');

            // Register a feature that boots on admin_int.
            Hooks::addAction('admin_init', __CLASS__, 'adminInitBoot');

            // Register pre payment processor.
            Hooks::addAction(
                'woocommerce_rest_checkout_process_payment_with_context',
                PrePaymentProcessor::class,
            );
            Hooks::addAction(
                'woocommerce_checkout_order_processed',
                PrePaymentProcessLegacyCheckout::class,
                'handleOrderProcessing'
            );

            // Return response in JSON format for payments on the "order-pay" checkout page.
            // By default, WooCommerce redirects to the order-received page after successful payment,
            // otherwise stay on the same page.
            Hooks::addFilter(
                'woocommerce_payment_successful_result',
                ReturnResultInJsonFormatForOrderPayPayment::class,
                'successResponse',
                10,
                2
            );
            Hooks::addAction(
                'woocommerce_after_pay_action',
                ReturnResultInJsonFormatForOrderPayPayment::class,
                'errorResponse',
                10,
                2
            );
            Hooks::addAction(
                'woocommerce_before_pay_action',
                ReturnResultInJsonFormatForOrderPayPayment::class,
                'catchVoidReturn',
                20,
            );

            // Render payment method in receipt.
            Hooks::addFilter(
                'woocommerce_order_get_payment_method_title',
                RenderCardOnOrderReceipt::class,
                '__invoke',
                10,
                2
            );

            // Handle "delete payment method" request from customer.
            Hooks::addAction('wp', DeletePaymentMethod::class, '__invoke', 9);

            // Ajax request controller
            Hooks::addAction('wp_ajax_get_cart_totals', GetCartTotals::class);
            Hooks::addAction('wp_ajax_nopriv_get_cart_totals', GetCartTotals::class);

            // Ajax request controller
            Hooks::addAction('wp_ajax_get_cart_totals', GetCartTotals::class);
            Hooks::addAction('wp_ajax_nopriv_get_cart_totals', GetCartTotals::class);

            // Add root DOM element for the "View payment in Stripe" button in order details page.
            Hooks::addAction(
                'woocommerce_admin_order_data_after_payment_info',
                StripeOrderEditPage::class,
                'addPaymentDetailsReactDomRoot'
            );

            Hooks::addAction(
                'woocommerce_admin_order_data_after_payment_info',
                StripeOrderEditPage::class,
                'enqueueOrderScreenAdminScripts',
            );

            // Display label with saved test-mode only payment methods.
            Hooks::addAction(
                'woocommerce_after_account_payment_methods',
                DisplayTestModeBadge::class,
                'addTestModeLabelInCustomerPaymentTokenList'
            );

            Hooks::addFilter(
                'woocommerce_get_price_html',
                EditPriceHTML::class,
                '__invoke',
                99,
                2
            );

            Hooks::addAction('woocommerce_cart_totals_after_order_total', OrderRecurringTotals::class);
            Hooks::addAction('woocommerce_review_order_after_order_total', OrderRecurringTotals::class);
            Hooks::addAction('woocommerce_after_cart_item_name', OrderRecurringTotals::class, 'addSubscriptionSummaryToCartItemName');
            Hooks::addAction('wp_footer', OrderRecurringTotals::class, 'classicCheckoutStyle');
            Hooks::addAction('woocommerce_review_order_before_order_total', ClassicCheckoutAndCartGettextFilter::class);
            Hooks::addAction('woocommerce_cart_totals_before_order_total', ClassicCheckoutAndCartGettextFilter::class);

            // Subscription sale price notice.
            Hooks::addAction('woocommerce_product_meta_end', SubscriptionSalePriceNotice::class, 'invokeOnProductPage');
            Hooks::addAction('woocommerce_after_cart_contents', SubscriptionSalePriceNotice::class, 'invokeOnLegacyCartPage');
            Hooks::addAction('woocommerce_review_order_after_order_total', SubscriptionSalePriceNotice::class, 'invokeOnLegacyCheckoutPage');
            Hooks::addAction('wp_enqueue_scripts', SubscriptionSalePriceNotice::class, 'invokeOnBlockCartPage');
            Hooks::addAction('wp_enqueue_scripts', SubscriptionSalePriceNotice::class, 'invokeOnBlockCheckoutPage');

            Hooks::addAction(
                'woocommerce_blocks_loaded',
                Cart::class,
                'register'
            );

            Hooks::addAction(
                'woocommerce_blocks_loaded',
                CartItem::class,
                'register'
            );

            Hooks::addAction('wp_enqueue_scripts', Block::class);

            // Add the subscription information to the new order email.
            Hooks::addAction(
                'woocommerce_email_order_meta',
                AddSubscriptionsDetailsToEmail::class,
                '__invoke',
                20,
                5
            );

            // Register new emails
            add_filter('woocommerce_email_classes', function (array $emailClasses) {
                $emailClasses[dbOptionKeyGenerator('status-changed-email')] = new SubscriptionStatusChangedAdminEmail();
                $emailClasses[dbOptionKeyGenerator('status-changed-successful-email')] = new SubscriptionStatusChangedSuccessfulCustomerEmail();

                return $emailClasses;
            });

            // Accept zero amount orders.
            Hooks::addFilter('woocommerce_cart_needs_payment', AcceptSubscriptionOrderWithZeroInitialAmount::class, 'cartNeedsPayment', 99, 2);
            Hooks::addFilter('woocommerce_order_needs_payment', AcceptSubscriptionOrderWithZeroInitialAmount::class, 'zeroOrderValueNeedsPayment', 99, 2);

            Hooks::addFilter('woocommerce_available_payment_gateways', EditPaymentGatewaysAvailabilityOnCheckout::class, '__invoke', 99);

            Hooks::addAction('woocommerce_cart_calculate_fees', FeeRecovery::class);

            // Pause order payment and cancelation during async confirmation.
            Hooks::addFilter('woocommerce_order_needs_payment', DisableOrderPaymentDuringAsyncConfirmation::class, 'maybeOrderNeedsPayment', 1, 2);
            Hooks::addFilter('woocommerce_valid_order_statuses_for_cancel', DisableOrderPaymentDuringAsyncConfirmation::class, 'maybeCancelableOrder', 1, 2);

            $this->registerOnSubscriptionStatusChangedHooks();
            $this->registerOnSubscriptionPaymentMethodUpdatedHooks();
            $this->registerOnScheduleSubscriptionCancelationHooks();
            $this->registerMyOrdersHooks();
            $this->registerMySubscriptionsHooks();
            $this->wooCheckoutHooks();
        }
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    private function wooCheckoutHooks(): void
    {
        Hooks::addFilter(
            'woocommerce_checkout_registration_required',
            RegistrationOnCheckoutWithSubscriptionProduct::class,
            'requireRegistrationDuringCheckout'
        );

        Hooks::addFilter(
            'woocommerce_before_checkout_process',
            RegistrationOnCheckoutWithSubscriptionProduct::class,
            'forceRegistrationDuringCheckout'
        );

        Hooks::addFilter(
            'woocommerce_checkout_registration_enabled',
            RegistrationOnCheckoutWithSubscriptionProduct::class,
            'maybeEnableRegistration'
        );

        Hooks::addAction(
            'init',
            self::class,
            'registerWooCartValidations'
        );
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function adminInitBoot(): void
    {
        // Register a dom element to order a detail page which will be a container for badges.
        Hooks::addAction(
            'woocommerce_admin_order_data_after_payment_info',
            BadgesContainerForOrderEditPage::class
        );

        // Register the test mode label to order the row.
        Hooks::addAction(
            'manage_' . wc_get_page_screen_id('shop_order') . '_custom_column',
            DisplayTestModeBadge::class,
            'addToOrderStatusColumnInListTable',
            99,
            2
        );

        // Register the subscription labels to the order row.
        Hooks::addAction(
            'manage_' . wc_get_page_screen_id('shop_order') . '_custom_column',
            DisplaySubscriptionOrderBadge::class,
            'addToOrderNumberColumnInListTable',
            99,
            2
        );

        // Register the test mode label to order the detail page.
        Hooks::addAction(
            'woocommerce_admin_order_data_after_payment_info',
            DisplayTestModeBadge::class,
            'addToOrderDetailPage',
        );

        // Register the subscription labels to the order details page.
        Hooks::addAction(
            'woocommerce_admin_order_data_after_order_details',
            DisplaySubscriptionOrderBadge::class,
            'addToOrderDetailPage',
        );

        // Register the CSS for the order details page.
        Hooks::addAction(
            'admin_enqueue_scripts',
            DisplayTestModeBadge::class,
            'addWooAdminStylesheet',
        );

        // Edit product general setting for the StellarPay.
        Hooks::addAction('woocommerce_product_options_general_product_data', ProductEditPage::class);

        // Add product variation setting for the StellarPay.
        Hooks::addAction('woocommerce_variation_options_pricing', VariationSettingFields::class, '__invoke', 10, 3);

        // Save product setting when admin updates a simple product type.
        Hooks::addAction('woocommerce_product_quick_edit_save', SyncSimpleProductQuickEditChanges::class,);
        Hooks::addAction('woocommerce_process_product_meta_' . ProductType::SIMPLE, SaveProductSetting::class,);
        Hooks::addAction('woocommerce_process_product_meta_' . ProductType::SIMPLE, SaveSimpleProductSettings::class);

        // Save product setting when admin updates variation products type.
        Hooks::addAction('woocommerce_admin_process_variation_object', SaveProductVariationSettings::class, '__invoke', 50, 2);
        Hooks::addAction('wp_ajax_woocommerce_save_variations', SaveVariableProductSettings::class, '__invoke', 0);

        Hooks::addAction('woocommerce_bulk_edit_variations', VariationBulkActions::class, '__invoke', 50, 4);

        Hooks::addAction('admin_enqueue_scripts', EditRefundButtonTitle::class);

        Hooks::addFilter('woocommerce_before_order_itemmeta', OrderEditPage::class, '__invoke', 10, 3);
    }

    /**
     * @since 1.6.0 Register "payment_intent.processing" webhook event processor.
     * @since 1.3.0 Register "invoice.payment_failed" webhook event processor.
     * @since 1.1.0 Replace string webhook event ids by enum
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     */
    private function registerWebhookProcessors(): void
    {
        container(WebhookRegisterer::class)->registerEventHandlers([
            WebhookEventType::PAYMENT_INTENT_SUCCEEDED => PaymentIntentSucceeded::class,
            WebhookEventType::PAYMENT_INTENT_FAILED => PaymentIntentPaymentFailed::class,
            WebhookEventType::PAYMENT_INTENT_CANCELED => PaymentIntentCanceled::class,
            WebhookEventType::PAYMENT_INTENT_PROCESSING => PaymentIntentProcessing::class,
            WebhookEventType::CHARGE_REFUNDED => ChargeRefunded::class,
            WebhookEventType::CHARGE_UPDATED => ChargeUpdated::class,
            WebhookEventType::CHARGE_REFUND_UPDATED => ChargeRefundUpdated::class,
            WebhookEventType::CUSTOMER_SUBSCRIPTION_UPDATED => CustomerSubscriptionUpdated::class,
            WebhookEventType::CUSTOMER_SUBSCRIPTION_CREATED => CustomerSubscriptionCreated::class,
            WebhookEventType::CUSTOMER_SUBSCRIPTION_DELETED => CustomerSubscriptionDeleted::class,
            WebhookEventType::SUBSCRIPTION_SCHEDULE_CANCELED => SubscriptionScheduleCanceled::class,
            WebhookEventType::INVOICE_PAID => InvoicePaid::class,
            WebhookEventType::INVOICE_PAYMENT_FAILED => InvoicePaymentFailed::class,
        ]);
    }

    /**
     * Declares compatibility with the Woocommerce features.
     *
     * List of features:
     * - custom_order_tables
     *
     * @since 1.0.0
     */
    public function declareFeatureCompatibility(): void
    {
        if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
                'custom_order_tables',
                Constants::$PLUGIN_ROOT_FILE
            );
        }
    }

    /**
     * @since 1.7.0
     * @throws BindingResolutionException
     */
    public function registerWooCartValidations(): void
    {
        if (!Environment::isWooSubscriptionActive()) {
            return;
        }

        Hooks::addFilter(
            'woocommerce_add_to_cart_validation',
            RegisterValidations::class,
            'canAddToCart',
            50,
            4
        );

        Hooks::addAction(
            'woocommerce_checkout_process',
            RegisterValidations::class,
            'validateCheckout'
        );

        Hooks::addAction(
            'woocommerce_store_api_cart_errors',
            RegisterValidations::class,
            'validateCheckout'
        );
    }

    /**
     * @since 1.7.0
     * @throws BindingResolutionException
     */
    private function registerMySubscriptionsHooks(): void
    {
        Hooks::addAction('init', MySubscriptionsPage::class);
        Hooks::addAction('init', ViewMySubscriptionPage::class);
        Hooks::addAction('init', UpdatePaymentMethodMySubscriptionPage::class);
        Hooks::addAction('wp', MySubscriptions::class, '__invoke', 9);

        // @todo: code cleanup.
        // We also do not need to register above hooks, but they are couple with checkout and backend order details pages.
        // So we should refactor them to handle only my-subscriptions page, after refactoring, we can register hooks conditionally.
        $customerHasStellarPaySubscriptions = CustomerRepository::hasStellarPaySubscriptions(get_current_user_id());
        if ($customerHasStellarPaySubscriptions || ! Environment::isWooSubscriptionActive()) {
            // Load the assets for the my-subscriptions page.
            Hooks::addAction('wp_enqueue_scripts', MySubscriptionsEndpoint::class, 'enqueueAssets');

            // Add the endpoint to the WooCommerce account menu.
            Hooks::addFilter('woocommerce_account_menu_items', MySubscriptionsEndpoint::class, 'addMenuItem');

            // Make the menu item active when the user is on the my-subscriptions page.
            Hooks::addFilter('woocommerce_account_menu_item_classes', MySubscriptionsEndpoint::class, 'makeSubscriptionMenuItemActive', 15, 2);

            // Add the endpoint to the WooCommerce query vars.
            Hooks::addFilter('woocommerce_get_query_vars', MySubscriptionsEndpoint::class, 'addSubscriptionsSlugToWooCommerceQueryVars');
        }
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    private function registerOnSubscriptionStatusChangedHooks(): void
    {
        Hooks::addAction('stellarpay_subscription_status_changed', SentSubscriptionStatusChangedEmails::class);
        Hooks::addAction('stellarpay_subscription_canceled', AddOrderNoteOnStellarPaySubscriptionCanceled::class);
        Hooks::addAction('stellarpay_subscription_paused_at_period_end', AddOrderNoteOnSubscriptionPausedAtPeriodEnd::class);
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    private function registerOnSubscriptionPaymentMethodUpdatedHooks(): void
    {
        Hooks::addAction(
            'stellarpay_subscription_payment_method_updated',
            AddOrderNoteOnStellarPaySubscriptionPaymentMethodUpdated::class,
            '__invoke',
            10,
            2
        );
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    private function registerOnScheduleSubscriptionCancelationHooks(): void
    {
        Hooks::addAction(
            'stellarpay_subscription_scheduled_to_cancel_at_period_end',
            AddOrderNoteOnScheduleStellarPaySubscriptionCancelation::class,
        );
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    protected function registerMyOrdersHooks(): void
    {
        Hooks::addAction('woocommerce_my_account_my_orders_column_order-status', ViewOrders::class);
        Hooks::addAction('woocommerce_order_details_before_order_table', ViewOrder::class);
    }
}
