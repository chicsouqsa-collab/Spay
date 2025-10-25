<?php

/**
 * Subscription Data Transfer Object.
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views\MyAccountPage;

use DateTime;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\PaymentMethodRepository;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Core\ValueObjects\SubscriptionStatus;
use WC_Order_Item_Product;
use WC_Order;
use StellarPay\Integrations\WooCommerce\Traits\SubscriptionUtilities;

use function StellarPay\Core\container;

/**
 * Class MySubscriptionDTO
 *
 * @since 1.6.0 add `isSubscriptionPayments`
 * @since 1.3.0 add `expiresAt` and `canUpdate`
 * @since 1.1.0 add `getPaymentMethodTitle` method
 * @since 1.0.0
 */
class MySubscriptionDTO
{
    use SubscriptionUtilities;

    /**
     * The subscription ID.
     *
     * @since 1.0.0
     */
    public int $id;

    /**
     * The customer ID.
     *
     * @since 1.1.0
     */
    public int $customerId;

    /**
     * The order item.
     *
     * @since 1.0.0
     */
    public ?WC_Order_Item_Product $orderItem;

    /**
     * The order item.
     *
     * @since 1.0.0
     */
    public ?WC_Order $order;

    /**
     * The subscription name.
     *
     * @since 1.0.0
     */
    public string $name;

    /**
     * The subscription status.
     *
     * @since 1.0.0
     */
    public SubscriptionStatus $status;

    /**
     * The status label.
     *
     * @since 1.3.0
     */
    public string $statusLabel;

    /**
     * The status color.
     *
     * @since 1.0.0
     */
    public string $statusColor;

    /**
     * The classes to use in the badge status.
     *
     * @since 1.0.0
     */
    public string $statusBadgeClasses;

    /**
     * The next billing date.
     *
     * @since 1.0.0
     */
    public string $nextBillingAt;

    /**
     * The total.
     *
     * @since 1.0.0
     */
    public string $total;

    /**
     * The total.
     *
     * @since 1.0.0
     */
    public ?int $totalPayments;

    /**
     * Is a test order?
     *
     * @since 1.0.0
     */
    public bool $isTestOrder;

    /**
     * The payment method title
     *
     * @since 1.0.0
     */
    public string $paymentMethodTitle;

    /**
     * The payment date.
     *
     * @since 1.0.0
     */
    public string $paymentDate;

    /**
     * Number of payments
     *
     * @since 1.0.0
     */
    public ?int $frequency;

    /**
     * @since 1.2.0
     */
    public bool $canCancel;

    /**
     * @since 1.3.0
     */
    public bool $canUpdate;

    /**
     * The expires at date.
     *
     * @since 1.3.0
     */
    public ?DateTime $expiresAt;

    /**
     * @since 1.6.0
     */
    public bool $isSubscriptionPayments;

    /**
     * Color badge classes for different statuses.
     *
     * @since 1.3.0
     */
    protected const COLOR_BADGE_CLASSES = [
        'green' => 'sp-text-green-800 sp-border-green-200 sp-bg-green-50',
        'orange' => 'sp-text-orange-700 sp-border-orange-200 sp-bg-orange-50',
        'gray' => 'sp-text-gray-700 sp-border-gray-300 sp-bg-gray-50',
    ];

    /**
     * Create a new MySubscriptionDTO instance from a Subscription.
     *
     * @since 1.1.0 Set the customerId property
     * @since 1.0.0
     */
    public static function fromSubscription(Subscription $subscription): MySubscriptionDTO
    {
        $self = new self();

        try {
            $orderItem = new WC_Order_Item_Product($subscription->firstOrderItemId);
        } catch (\Exception $e) {
            $orderItem = null;
        }

        $order = wc_get_order($orderItem->get_order_id());

        $self->id = $subscription->id;
        $self->customerId = $subscription->customerId;
        $self->orderItem = $orderItem;
        $self->order = $order;
        $self->name = $orderItem instanceof WC_Order_Item_Product ? $orderItem->get_name() : '';
        $self->expiresAt = $subscription->expiresAt;
        $self->status = $subscription->status;
        $self->statusLabel = $subscription->getFormattedStatusLabel();
        $self->statusColor = $self->getStatusColor($self->status);
        $self->statusBadgeClasses = $self->getBadgedClasses($self->statusColor);
        $self->nextBillingAt = $subscription->getFormattedNextBillingAt();
        $self->total = $self->getPriceHtml($orderItem, $subscription);
        $self->totalPayments = $subscription->billedCount;
        $self->isTestOrder = $orderItem instanceof WC_Order_Item_Product ? container(OrderRepository::class)->isTestOrder($orderItem->get_order()) : false;
        $self->paymentMethodTitle = $self->getPaymentMethodTitle($subscription);
        $self->paymentDate = $order && $order->get_date_completed() ? $order->get_date_completed()->format(get_option('date_format', 'F j, Y')) : '';
        $self->frequency = $subscription->billingTotal;
        $self->canCancel = $subscription->canCancel();
        $self->canUpdate = $subscription->canUpdate();
        $self->isSubscriptionPayments = $subscription->isSubscriptionPayments();

        return $self;
    }

    /**
     * Get the status color based on the subscription status
     *
     * @since 1.0.0
     */
    protected function getStatusColor(SubscriptionStatus $status): string
    {
        if ($this->expiresAt) {
            return 'gray';
        }

        switch (true) {
            case $status->isCompleted():
            case $status->isActive():
                return 'green';

            case (! is_order_received_page() && $status->isPending()):
            case $status->isSuspended():
                return 'orange';

            default:
                return 'gray';
        }
    }

    /**
     * Get the badge classes based on the status.
     *
     * @since 1.0.0
     */
    protected function getBadgedClasses(string $color): string
    {
        if ($this->expiresAt) {
            return self::COLOR_BADGE_CLASSES['gray'];
        }

        switch ($color) {
            case 'green':
                return self::COLOR_BADGE_CLASSES['green'];

            case 'orange':
                return self::COLOR_BADGE_CLASSES['orange'];

            default:
                return self::COLOR_BADGE_CLASSES['gray'];
        }
    }

    /**
     * @since 1.9.0 Use "getLastOrder" function
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    protected function getPaymentMethodTitle(Subscription $subscription): ?string
    {
        $paymentMethodToken = $subscription->getNewPaymentMethodForRenewal();

        if (!$paymentMethodToken) {
            $paymentMethodToken = $subscription->getLastPaymentMethod();
        }

        $order = $subscription->getLastOrder();

        try {
            return container(PaymentMethodRepository::class)->getPaymentMethodTitleForReceipt($paymentMethodToken, $order);
        } catch (\Exception $e) {
            return container(SettingRepository::class)->getPaymentGatewayTitle();
        }
    }
}
