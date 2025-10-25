<?php

/**
 * This class is responsible to provide to access subscriptions data for subscription list page.
 *
 * @package StellarPay\Subscriptions\RestApi
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\RestApi;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Services\ModifierContextService;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\ValueObjects\ModifierContextType;
use StellarPay\Core\ValueObjects\SubscriptionCancelAt;
use StellarPay\Core\ValueObjects\RefundType;
use StellarPay\Core\ValueObjects\WebhookEventType;
use StellarPay\Integrations\WooCommerce\Stripe\Decorators\OrderItemProductDecorator;
use StellarPay\Integrations\WooCommerce\Stripe\Services\SubscriptionScheduleService;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Subscriptions\Repositories\SubscriptionRepository;
use StellarPay\RestApi\HandleMultipleApiRoutes;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\PaymentMethodRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Constants;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\Integrations\WooCommerce\Stripe\Actions\FilterPaymentTokensByPaymentGatewayMode;
use StellarPay\Integrations\WooCommerce\Stripe\Controllers\AddPaymentMethod;
use StellarPay\Vendors\Illuminate\Support\Collection;
use WC_Order;
use WC_Order_Item_Product;
use WC_Payment_Token;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use WC_Payment_Tokens;
use WC_Customer;
use DateTime;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\InstallmentSubscriptionProduct;
use StellarPay\Integrations\WooCommerce\Stripe\Services\SubscriptionService;
use StellarPay\Integrations\WooCommerce\Traits\OrderUtilities;

use function StellarPay\Core\container;

/**
 * @since 1.2.0 Moved from Subscriptions domain to AdminDashboard domain.
 * @since 1.0.0
 */
class SubscriptionsListPage extends ApiRoute
{
    use SubscriptionUtilities;
    use OrderUtilities;

    use HandleMultipleApiRoutes {
        registerAllRoutes as register;
    }

    /**
     * @since 1.0.0
     */
    protected string $endpoint = 'subscriptions';

    /**
     * @since 1.0.0
     */
    protected SubscriptionRepository $subscriptionRepository;

    /**
     * @since 1.2.0
     */
    protected PaymentMethodRepository $paymentMethodRepository;

    /**
     * @since 1.9.0
     */
    protected SubscriptionService $subscriptionService;

    /**
     * @since 1.9.0
     */
    protected SubscriptionScheduleService $subscriptionScheduleService;

    /**
     * @since 1.0.0
     */
    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        PaymentMethodRepository $paymentMethodRepository,
        SubscriptionService $subscriptionService,
        SubscriptionScheduleService $subscriptionScheduleService
    ) {
        parent::__construct();

        $this->subscriptionRepository = $subscriptionRepository;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->subscriptionService = $subscriptionService;
        $this->subscriptionScheduleService = $subscriptionScheduleService;
    }

    /**
     * This function returns an array of route arguments.
     *
     * @since 1.2.0
     */
    public function getRoutes(): array
    {
        return [
            'list' => [
                'method' => WP_REST_Server::READABLE,
                'mainCallback' => 'getSubscriptionListPage',
                'args' => [
                    'pageNumber' => [
                        'required' => true,
                        'type' => 'integer',
                        'default' => 1,
                        'minimum' => 1,
                    ]
                ],
            ],
            'detail' => [
                'method' => WP_REST_Server::READABLE,
                'mainCallback' => 'getSingleSubscription',
                'args' => [
                    'subscriptionId' => [
                        'required' => true,
                        'type' => 'integer',
                    ]
                ],
            ],
            'cancel' => [
                'method' => WP_REST_Server::EDITABLE,
                'mainCallback' => 'cancelSubscription',
                'args' => [
                    'subscriptionId' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                    'cancelAt' => [
                        'required' => false,
                        'type' => 'string',
                        'default' => SubscriptionCancelAt::IMMEDIATELY,
                        'validate_callback' => [SubscriptionCancelAt::class, 'isValid']
                    ],
                    'refund' => [
                        'required' => false,
                        'type' => 'string',
                        'default' => RefundType::NO_REFUND,
                        'validate_callback' => [RefundType::class, 'isValid']
                    ]
                ],
            ],
            'pause' => [
                'method' => WP_REST_Server::EDITABLE,
                'mainCallback' => 'pauseSubscription',
                'args' => [
                    'subscriptionId' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                    'resumesAt' => [
                        'required' => false,
                        'type' => 'date-time',
                    ]
                ],
            ],
            'resume' => [
                'method' => WP_REST_Server::EDITABLE,
                'mainCallback' => 'resumeSubscription',
                'args' => [
                    'subscriptionId' => [
                        'required' => true,
                        'type' => 'integer',
                    ]
                ],
            ],
            'get-payment-methods' => [
                'method' => WP_REST_Server::READABLE,
                'mainCallback' => 'getPaymentMethods',
                'args' => [
                    'subscriptionId' => [
                        'required' => true,
                        'type' => 'integer',
                    ]
                ],
            ],
            'update-existing-payment-method' => [
                'method' => WP_REST_Server::EDITABLE,
                'mainCallback' => 'updateExistingPaymentMethod',
                'args' => [
                    'subscriptionId' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                    'paymentMethodId' => [
                        'required' => true,
                        'type' => 'integer',
                    ]
                ],
            ],
            'add-new-payment-method' => [
                'method' => WP_REST_Server::EDITABLE,
                'mainCallback' => 'addNewPaymentMethod',
                'args' => [
                    'subscriptionId' => [
                        'required' => true,
                        'type' => 'integer',
                    ],
                    'paymentMethodToken' => [
                        'required' => true,
                        'type' => 'string',
                    ]
                ],
            ],
        ];
    }

    /**
     * @since 1.2.0
     */
    public function processRequest(WP_REST_Request $request): WP_REST_Response
    {
        $routes = $this->getRoutes();
        $urlEnd = basename($request->get_route());

        /* @var WP_REST_Response $result Response. */
        $invokable = $routes[$urlEnd]['mainCallback'];

        return $this->$invokable($request);
    }

    /**
     * @since 1.2.0
     */
    public function permissionCheck(WP_REST_Request $request): bool
    {
        return parent::permissionCheck($request) && current_user_can('manage_options');
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException|Exception
     */
    public function getSubscriptionListPage(WP_REST_Request $request): WP_REST_Response
    {
        $pageNumber = $request->get_param('pageNumber');
        $subscriptionPerPage = $this->getPerPageLimit();
        $subscriptions = $this->subscriptionRepository->getAll(
            ['page' => $pageNumber, 'perPage' => $subscriptionPerPage]
        );

        $result['page'] = $pageNumber;
        $result['perPage'] = $subscriptionPerPage;
        $result['total'] = Subscription::totalCount();

        $result['subscriptions'] = [];

        foreach ($subscriptions as $subscription) {
            $subscriptionData = $this->prepareSubscriptionData($subscription);

            if (! $subscriptionData) {
                continue;
            }

            $result['subscriptions'][] = $subscriptionData;
        }

        return rest_ensure_response($result);
    }

    /**
     * @since 1.2.0
     * @throws BindingResolutionException|Exception
     */
    public function getSingleSubscription(WP_REST_Request $request): WP_REST_Response
    {
        $subscriptionId = $request->get_param('subscriptionId');
        $subscription = $this->subscriptionRepository->getById($subscriptionId);

        if (! $subscription) {
            return rest_ensure_response(null);
        }

        $result = $this->prepareSubscriptionData($subscription);

        if (! $result) {
            return rest_ensure_response(null);
        }

        $result['child_orders'] = $this->prepareRenewalOrders($subscription, $subscription->getRenewalOrders())->toArray();

        $result['prorated_amount'] = $subscription->canCancel()
            ? $this->getSubscriptionProratedAmount($subscription)->getAmount()
            : null;

        return rest_ensure_response($result);
    }

    /**
     * @since 1.4.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    private function prepareSubscriptionDataById(int $subscriptionId): ?array
    {
        $subscription = $this->subscriptionRepository->getById($subscriptionId);

        if (! $subscription) {
            return null;
        }

        return $this->prepareSubscriptionData($subscription);
    }

    /**
     * @since 1.4.0 Rename array key "product" to "product_name"
     * @since 1.2.0
     * @throws BindingResolutionException|Exception
     */
    private function prepareSubscriptionData(Subscription $subscription): ?array
    {
        $order = wc_get_order($subscription->firstOrderId);
        $orderItem = $order ? $order->get_item($subscription->firstOrderItemId) : null;

        if (
            ! ($order instanceof WC_Order)
            || ! ($orderItem instanceof WC_Order_Item_Product)
        ) {
            return null;
        }

        $orderItemProductDecorator = new OrderItemProductDecorator($orderItem, $order);

        $customerName = trim($order->get_billing_first_name() . ' ' . $order->get_billing_last_name());
        $productName = $orderItem->get_name('edit');
        $subscriptionProduct = $subscription->getSubscriptionProduct();
        $lastPaymentMethod = $subscription->getLastPaymentMethod();
        $paymentMethodTitle = '';
        $lastOrder = $subscription->getLastOrder();

        if ($lastPaymentMethod) {
            $paymentMethodTitle = $this->paymentMethodRepository->getPaymentMethodTitleForReceipt(
                $subscription->getLastPaymentMethod(),
                $order
            );
        }

        return [
            'id' => $subscription->id,
            'order_id' => $subscription->firstOrderId,
            'amount' => $orderItemProductDecorator->getSubscriptionAmount()->getAmount(),
            'formatted_period' => $this->getFormattedBillingPeriod($subscription),
            'recurring_period' => $this->getRecurringPeriod($subscription),
            'payment_status' => $subscription->getPendingPaymentMessage(),
            'status' => $subscription->status->getValue(),
            'status_label' => $subscription->getFormattedStatusLabel(),
            'customer_name' => $customerName,
            'customer_email' => $order->get_billing_email('edit'),
            'customer_phone' => $order->get_billing_phone(),
            'product_name' => $productName,
            'start_date' => $subscription->startedAt ? Temporal::getWPFormattedDate(
                $subscription->startedAt
            ) : '',
            'canceled_at' => $subscription->canceledAt ? Temporal::getWPFormattedDate(
                $subscription->canceledAt
            ) : '',
            'next_payment' => $subscription->getFormattedNextBillingAt(),
            'next_payment_timestamp' => $subscription->nextBillingAt ? $subscription->nextBillingAt->getTimestamp() : null,
            'live_mode' => $subscription->paymentGatewayMode->isLive(),
            'stripe_dashboard_link' => $subscription->getStripeDashboardLink(),
            'stripe_subscription_id' => $subscription->transactionId,
            'payment_method' => $paymentMethodTitle,
            'edit_order_url' => esc_url($order->get_edit_order_url()),
            'last_order_amount' => $subscription->getLastOrderAmount()->getAmount(),
            'last_order_date' => $subscription->getLastOrder()->get_date_created()->getTimestamp(),
            'last_order_discount' => $lastOrder->get_total_discount(),
            'can_cancel' => $subscription->canCancel(),
            'can_pause' => $subscription->canPause(),
            'can_resume' => $subscription->canResume(),
            'can_update_payment_method' => $subscription->canUpdatePaymentMethod(),
            'expires_at' => $subscription->expiresAt ? Temporal::getWPFormattedDate(
                $subscription->expiresAt
            ) : '',
            'paused_at' => $subscription->suspendedAt ? Temporal::getWPFormattedDate(
                $subscription->suspendedAt
            ) : '',
            'resumed_at' => $subscription->resumedAt ? Temporal::getWPFormattedDate(
                $subscription->resumedAt
            ) : '',
            'type' => $subscriptionProduct->getProductType(),
            'number_of_installments' => ($subscriptionProduct instanceof InstallmentSubscriptionProduct) ? $subscriptionProduct->getNumberOfPayments('edit') : null,
            'billed_count' => $subscription->billedCount,
            'frequency' => $subscription->frequency,
            'period' => $subscription->period->getValue(),
        ];
    }

    /**
     * @since 1.2.0
     */
    public function getRecurringPeriod(Subscription $subscription): ?string
    {
        $period = $subscription->period->getValue();
        $frequency = $subscription->frequency;

        if (1 === $frequency) {
            if ($subscription->period->isDAY()) {
                return _x('Daily', 'Recurring period', 'stellarpay');
            } elseif ($subscription->period->isWEEK()) {
                return _x('Weekly', 'Recurring period', 'stellarpay');
            } elseif ($subscription->period->isMONTH()) {
                return _x('Monthly', 'Recurring period', 'stellarpay');
            } elseif ($subscription->period->isYEAR()) {
                return _x('Yearly', 'Recurring period', 'stellarpay');
            }
        } else {
            return sprintf(
            // translators: %1$s is the frequency, %2$s is the period. Example: `every 3 months`.
                _x('Every %1$s %2$s(s)', 'Recurring period', 'stellarpay'),
                $frequency,
                $period
            );
        }

        return '';
    }

    /**
     * @since 1.2.0
     */
    public function cancelSubscription(WP_REST_Request $request): WP_REST_Response
    {
        $subscriptionId = $request->get_param('subscriptionId');
        $subscription = $this->subscriptionRepository->getById($subscriptionId);
        $errorResponse = new WP_REST_Response(
            [
                'message' => 'Error message.',
                'code' => 'cannot_cancel_subscription',
            ],
            500
        );

        if (! $subscription) {
            return $errorResponse;
        }

        if (! $subscription->canCancel()) {
            $errorResponse->set_data([
                'message' => esc_html__('Subscription is not cancellable.', 'stellarpay'),
            ]);

            return $errorResponse;
        }

        $cancelAt = $request->get_param('cancelAt');

        try {
            switch (SubscriptionCancelAt::from($cancelAt)->getValue()) {
                case SubscriptionCancelAt::IMMEDIATELY:
                    $refundType = RefundType::from($request->get_param('refund'));

                    if ($refundType->isLastPayment() || $refundType->isProratedAmount()) {
                        $this->refundSubscription($subscription, $refundType);
                    }


                    $message = esc_html__('Subscription has been canceled.', 'stellarpay');

                    if (! $subscription->transactionId) {
                        $result = $subscription->cancel();
                    } else {
                        $result = $this->cancelStripeSubscription($subscription);
                        $event = $subscription->isScheduleType()
                            ? WebhookEventType::SUBSCRIPTION_SCHEDULE_CANCELED()
                            : WebhookEventType::CUSTOMER_SUBSCRIPTION_DELETED();

                        ModifierContextService::fromArray(
                            [
                                'eventType' => $event->getValue(),
                                'objectId' => $subscription->id,
                            ]
                        )->storeContext(ModifierContextType::ADMIN());
                    }

                    break;

                case SubscriptionCancelAt::END_OF_THE_CURRENT_PERIOD:
                    $message = esc_html__(
                        'Subscription is scheduled to be canceled at the end of the current period.',
                        'stellarpay'
                    );
                    $result = $this->cancelAtPeriodEndStripeSubscription($subscription);

                    break;

                default:
                    $result = false;
                    $message = '';
                    break;
            }
        } catch (\Exception $e) {
            $errorResponse->set_data([
                'message' => $e->getMessage(),
            ]);

            return $errorResponse;
        }

        if (! $result) {
            return $errorResponse;
        }

        return rest_ensure_response(
            [
                'message' => $message,
            ]
        );
    }

    /**
     * @since 1.2.0
     */
    private function getPerPageLimit(): int
    {
        return (int)get_option('posts_per_page', 15);
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException
     */
    private function getPaymentMethods(WP_REST_Request $request): WP_REST_Response
    {
        $subscriptionId = $request->get_param('subscriptionId');
        $subscription = $this->subscriptionRepository->getById($subscriptionId);

        if (! $subscription) {
            return rest_ensure_response([]);
        }

        $savedPaymentMethods = [];
        $customerId = $subscription->getCustomerId();
        $tokens = WC_Payment_Tokens::get_customer_tokens($customerId, Constants::GATEWAY_ID);
        $invokable = container(FilterPaymentTokensByPaymentGatewayMode::class)
            ->setExcludeTokens([$subscription->getLastPaymentMethod()]);
        $tokens = $invokable($tokens);

        foreach ($tokens as $token) {
            $savedPaymentMethods[] = [
                'id' => $token->get_id(),
                'label' => $token->get_display_name(),
                'is_default' => $token->get_token() === $subscription->getNewPaymentMethodForRenewal(),
            ];
        }

        return rest_ensure_response($savedPaymentMethods);
    }

    /**
     * Attach an existing payment method to a subscription.
     *
     * @since 1.3.0
     * @throws BindingResolutionException
     */
    public function updateExistingPaymentMethod(WP_REST_Request $request): WP_REST_Response
    {
        $errorResponse = new WP_REST_Response([], 500);

        $paymentMethodId = absint($request->get_param('paymentMethodId'));

        try {
            $subscription = $this->validateAndGetSubscription($request);
        } catch (Exception $e) {
            $errorResponse->set_data([
                'message' => $e->getMessage(),
            ]);

            return $errorResponse;
        }

        $token = WC_Payment_Tokens::get($paymentMethodId);

        if (
            ! $token instanceof WC_Payment_Token
            || Constants::GATEWAY_ID !== $token->get_gateway_id()
            || $token->get_user_id('edit') !== $subscription->getCustomerId()
        ) {
            $errorResponse->set_data([
                'message' => esc_html__('Cannot update payment method for this subscription.', 'stellarpay'),
            ]);

            return $errorResponse;
        }

        try {
            $subscription->updatePaymentMethod($token->get_token());

            return rest_ensure_response([
                'message' => esc_html__('Payment method has been updated successfully.', 'stellarpay'),
            ]);
        } catch (\Exception $e) {
            $errorMessage = esc_html__('Unable to update the payment method.', 'stellarpay');

            if ($e instanceof StripeAPIException) {
                $errorMessage .= ' ' . $e->getMessage();
            }

            $errorResponse->set_data([
                'message' => $errorMessage,
            ]);

            return $errorResponse;
        }
    }

    /**
     * Attach a new payment method to a subscription.
     *
     * @since 1.3.0
     * @throws BindingResolutionException
     * @throws \Exception
     */
    public function addNewPaymentMethod(WP_REST_Request $request): WP_REST_Response
    {
        $paymentMethodToken = $request->get_param('paymentMethodToken');
        $errorResponse = new WP_REST_Response([], 500);

        try {
            $subscription = $this->validateAndGetSubscription($request);
        } catch (Exception $e) {
            $errorResponse->set_data([
                'message' => $e->getMessage(),
            ]);

            return $errorResponse;
        }

        $customerId = $subscription->getCustomerId();
        $customer = new WC_Customer($customerId);
        $paymentMethod = container(AddPaymentMethod::class);

        try {
            $newPaymentMethod = $paymentMethod->addPaymentMethod($customer, $paymentMethodToken);

            if (! $newPaymentMethod) {
                $errorResponse->set_data([
                    'message' => esc_html__('Unable to update the payment method.', 'stellarpay'),
                ]);

                return $errorResponse;
            }

            $subscription->updatePaymentMethod($newPaymentMethod->getId());

            return rest_ensure_response([
                'message' => esc_html__('Payment method has been updated successfully.', 'stellarpay'),
            ]);
        } catch (\Exception $e) {
            $errorResponse->set_data([
                'message' => $e->getMessage(),
            ]);

            return $errorResponse;
        }
    }

    /**
     * @since 1.3.0
     * @throws Exception|BindingResolutionException
     */
    private function validateAndGetSubscription(\WP_REST_Request $request): Subscription
    {
        $subscriptionId = absint($request->get_param('subscriptionId'));
        $subscription = Subscription::find($subscriptionId);

        if (! $subscription) {
            throw new Exception(esc_html__('Subscription not found.', 'stellarpay'));
        }

        if (empty($subscription->transactionId) || ! $subscription->canUpdatePaymentMethod()) {
            throw new Exception(esc_html__('You can not update payment method for this subscription.', 'stellarpay'));
        }

        return $subscription;
    }

    /**
     * Pause a subscription.
     *
     * @since 1.9.0
     * @throws \Exception
     */
    private function pauseSubscription(WP_REST_Request $request): WP_REST_Response
    {
        $errorResponse = new WP_REST_Response([], 500);
        $subscriptionId = $request->get_param('subscriptionId');
        $subscription = $this->subscriptionRepository->getById($subscriptionId);
        $resumesAt = $request->get_param('resumesAt');

        if (! $subscription) {
            $errorResponse->set_data([
                'message' => esc_html__('Subscription not found.', 'stellarpay'),
            ]);

            return $errorResponse;
        }

        if (!$resumesAt) {
            $errorResponse->set_data([
                'message' => esc_html__('Invalid resume date.', 'stellarpay'),
            ]);

            return $errorResponse;
        }

        if ($subscription->hasEndDate()) {
            $errorResponse->set_data([
                'message' => esc_html__('Installment payments cannot be paused.', 'stellarpay'),
            ]);

            return $errorResponse;
        }

        if (! $subscription->canPause()) {
            $errorResponse->set_data([
                'message' => esc_html__('Subscription is not pauseable.', 'stellarpay'),
            ]);

            return $errorResponse;
        }

        $resumesAt = new DateTime($resumesAt);

        try {
            $result = $subscription->isScheduleType()
                ? $this->subscriptionScheduleService->pause($subscription, $resumesAt)
                : $this->subscriptionService->pause($subscription, $resumesAt);
        } catch (\Exception $e) {
            $errorResponse->set_data([
                'message' => $e->getMessage(),
            ]);

            return $errorResponse;
        }

        if (! $result) {
            $errorResponse->set_data([
                'message' => esc_html__('Unable to pause the subscription.', 'stellarpay'),
            ]);

            return $errorResponse;
        }

        ModifierContextService::fromArray(
            [
                'eventType' => WebhookEventType::CUSTOMER_SUBSCRIPTION_UPDATED()->getValue(),
                'objectId' => $subscription->id,
            ]
        )->storeContext(ModifierContextType::ADMIN());

        return rest_ensure_response([
            'message' => esc_html__('Subscription has been paused.', 'stellarpay'),
        ]);
    }

    /**
     * Resume a subscription.
     *
     * @since 1.9.0
     */
    private function resumeSubscription(WP_REST_Request $request): WP_REST_Response
    {
        $errorResponse = new WP_REST_Response([], 500);
        $subscriptionId = $request->get_param('subscriptionId');
        $subscription = $this->subscriptionRepository->getById($subscriptionId);

        if (! $subscription) {
            $errorResponse->set_data([
                'message' => esc_html__('Subscription not found.', 'stellarpay'),
            ]);

            return $errorResponse;
        }

        if ($subscription->getSubscriptionProduct() instanceof InstallmentSubscriptionProduct) {
            $errorResponse->set_data([
                'message' => esc_html__('Installment payments cannot be resumed.', 'stellarpay'),
            ]);

            return $errorResponse;
        }

        if (! $subscription->canResume()) {
            $errorResponse->set_data([
                'message' => esc_html__('Subscription is not resumable.', 'stellarpay'),
            ]);

            return $errorResponse;
        }

        try {
            $result = $subscription->isScheduleType()
                ? $this->subscriptionScheduleService->resume($subscription)
                : $this->subscriptionService->resume($subscription);
        } catch (\Exception $e) {
            $errorResponse->set_data([
                'message' => $e->getMessage(),
            ]);

            return $errorResponse;
        }

        if (! $result) {
            $errorResponse->set_data([
                'message' => esc_html__('Unable to resume the subscription.', 'stellarpay'),
            ]);

            return $errorResponse;
        }

        ModifierContextService::fromArray(
            [
                'eventType' => WebhookEventType::CUSTOMER_SUBSCRIPTION_UPDATED()->getValue(),
                'objectId' => $subscription->id,
            ]
        )->storeContext(ModifierContextType::ADMIN());

        return rest_ensure_response([
            'message' => esc_html__('Subscription has been resumed.', 'stellarpay'),
        ]);
    }

    /**
     * Prepare the Renewal Orders data to be returned in the API.
     *
     * @since 1.9.0
     */
    private function prepareRenewalOrders(Subscription $subscription, Collection $renewalOrders): Collection
    {
        $firstOrder = wc_get_order($subscription->firstOrderId);

        $renewalOrders = $renewalOrders->map(function (WC_Order $renewalOrder) {
            return [
                'order_id' => $renewalOrder->get_id(),
                'total' => $renewalOrder->get_total(),
                'date' => Temporal::getWPFormattedDate($renewalOrder->get_date_created()),
                'status' => $renewalOrder->get_status(),
                'status_label' => $this->getOrderStatusLabel($renewalOrder),
                'edit_order_url' => esc_url($renewalOrder->get_edit_order_url()),
            ];
        });

        $renewalOrders->add([
            'order_id' => $firstOrder->get_id(),
            'total' => $firstOrder->get_total(),
            'date' => Temporal::getWPFormattedDate($firstOrder->get_date_created()),
            'status' => $firstOrder->get_status(),
            'status_label' => $this->getOrderStatusLabel($firstOrder),
            'edit_order_url' => esc_url($firstOrder->get_edit_order_url()),
        ]);

        return $renewalOrders;
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public static function isCancelationRequest(): bool
    {
        return self::isRequest('cancel');
    }

    /**
     * @since 1.8.0
     * @throws BindingResolutionException
     */
    public static function isEditPaymentMethodRequest(): bool
    {
        return self::isRequest('update-existing-payment-method')
               || self::isRequest('add-new-payment-method');
    }

    /**
     * @since 1.9.0
     * @throws BindingResolutionException
     */
    public static function isPauseRequest(): bool
    {
        return self::isRequest('pause');
    }
}
