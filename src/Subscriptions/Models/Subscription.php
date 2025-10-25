<?php

/**
 * This class represents the subscription model.
 *
 * @package StellarPay\Subscriptions\Models
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\Models;

use DateInterval;
use DateTime;
use DateTimeInterface;
use StellarPay\Core\Constants;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Support\Str;
use StellarPay\Core\ValueObjects\Money;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Core\ValueObjects\SubscriptionPeriod;
use StellarPay\Core\ValueObjects\SubscriptionSource;
use StellarPay\Core\ValueObjects\SubscriptionStatus;
use StellarPay\Subscriptions\DataTransferObjects\SubscriptionQueryData;
use StellarPay\Subscriptions\Factories\SubscriptionFactory;
use StellarPay\Subscriptions\Repositories\SubscriptionRepository;
use StellarPay\Vendors\Illuminate\Support\Collection;
use StellarPay\Vendors\StellarWP\DB\DB;
use StellarPay\Vendors\StellarWP\Models\Contracts\ModelCrud;
use StellarPay\Vendors\StellarWP\Models\Contracts\ModelHasFactory;
use StellarPay\Vendors\StellarWP\Models\Model;
use StellarPay\Vendors\StellarWP\Models\ModelQueryBuilder;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Integrations\Stripe\Client;
use StellarPay\Integrations\WooCommerce\Factories\ProductFactory;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\RenewalOrderRepository;
use StellarPay\Subscriptions\Repositories\SubscriptionMetaRepository;
use WC_Order;
use WC_Order_Item_Product;
use StellarPay\Integrations\WooCommerce\Models\ProductTypes\Contracts\Product as ProductModel;

use function StellarPay\Core\container;

/**
 * @since 1.8.0 Add support for initial amount, recurring amount and currency code.
 * @since 1.3.0 Add `getFormattedStatusLabel` and `canUpdate` method
 * @since 1.1.0 Add `saveRenewalPaymentMethod` method
 * @since 1.0.0
 *
 * @property int $id
 * @property int $customerId
 * @property int $firstOrderId
 * @property int $firstOrderItemId
 * @property SubscriptionPeriod $period
 * @property int $frequency
 * @property SubscriptionStatus $status
 * @property string|null $transactionId
 * @property int|null $billingTotal
 * @property int|null $billedCount
 * @property Money|null $initialAmount
 * @property Money|null $recurringAmount
 * @property string|null $currencyCode
 * @property PaymentGatewayMode $paymentGatewayMode
 * @property DateTime $createdAt
 * @property DateTime $createdAtGmt
 * @property DateTime|null $startedAt
 * @property DateTime|null $startedAtGmt
 * @property DateTime|null $endedAt
 * @property DateTime|null $endedAtGmt
 * @property DateTime|null $trialStartedAt
 * @property DateTime|null $trialStartedAtGmt
 * @property DateTime|null $trialEndedAt
 * @property DateTime|null $trialEndedAtGmt
 * @property DateTime|null $nextBillingAt
 * @property DateTime|null $nextBillingAtGmt
 * @property DateTime|null $updatedAt
 * @property DateTime|null $updatedAtGmt
 * @property DateTime|null $expiredAt
 * @property DateTime|null $expiredAtGmt
 * @property DateTime|null $canceledAt
 * @property DateTime|null $canceledAtGmt
 * @property DateTime|null $suspendedAt
 * @property DateTime|null $suspendedAtGmt
 * @property DateTime|null $resumedAt
 * @property DateTime|null $resumedAtGmt
 * @property SubscriptionSource $source
 * @property DateTime|null $expiresAt
 * @property DateTime|null $expiresAtGmt
 */
#[\AllowDynamicProperties]
class Subscription extends Model implements ModelCrud, ModelHasFactory
{
    /**
     * @inheritdoc
     */
    protected $properties = [
        'id' => 'int',
        'customerId' => 'int',
        'firstOrderId' => 'int',
        'firstOrderItemId' => 'int',
        'period' => SubscriptionPeriod::class,
        'frequency' => 'int',
        'status' => SubscriptionStatus::class,
        'transactionId' => 'string',
        'billingTotal' => 'int',
        'billedCount' => 'int',
        'initialAmount' => Money::class,
        'recurringAmount' => Money::class,
        'currencyCode' => 'string',
        'paymentGatewayMode' => PaymentGatewayMode::class,
        'createdAt' => DateTimeInterface::class,
        'createdAtGmt' => DateTimeInterface::class,
        'startedAt' => DateTimeInterface::class,
        'startedAtGmt' => DateTimeInterface::class,
        'endedAt' => DateTimeInterface::class,
        'endedAtGmt' => DateTimeInterface::class,
        'trialStartedAt' => DateTimeInterface::class,
        'trialStartedAtGmt' => DateTimeInterface::class,
        'trialEndedAt' => DateTimeInterface::class,
        'trialEndedAtGmt' => DateTimeInterface::class,
        'nextBillingAt' => DateTimeInterface::class,
        'nextBillingAtGmt' => DateTimeInterface::class,
        'updatedAt' => DateTimeInterface::class,
        'updatedAtGmt' => DateTimeInterface::class,
        'expiredAt' => DateTimeInterface::class,
        'expiredAtGmt' => DateTimeInterface::class,
        'suspendedAt' => DateTimeInterface::class,
        'suspendedAtGmt' => DateTimeInterface::class,
        'canceledAt' => DateTimeInterface::class,
        'canceledAtGmt' => DateTimeInterface::class,
        'resumedAt' => DateTimeInterface::class,
        'resumedAtGmt' => DateTimeInterface::class,
        'source' => SubscriptionSource::class,
        'expiresAt' => DateTimeInterface::class,
        'expiresAtGmt' => DateTimeInterface::class,
    ];

    /**
     * @since 1.0.0
     */
    public static function getTableName(bool $withPrefix = true): string
    {
        $tableName = Constants::slugPrefixed('_subscriptions');

        return $withPrefix ? DB::prefix($tableName) : $tableName;
    }

    /**
     * @since 1.3.0
     */
    public function getCustomerId(): int
    {
        return $this->customerId;
    }

    /**
     * @since 1.0.0
     */
    public static function getMetaTableName(): string
    {
        return DB::prefix(self::getMetaTableNameWithoutDBPrefix());
    }

    /**
     * @since 1.1.0
     */
    public static function getMetaTableNameWithoutDBPrefix(): string
    {
        return Constants::slugPrefixed('_subscriptions_meta');
    }

    /**
     * @since 1.0.0
     */
    public function getPendingPaymentMessage(): string
    {
        $message = esc_html__('Ongoing until canceled', 'stellarpay');

        if ($this->billingTotal) {
            $billedCount = $this->billedCount ?? 0;
            $pendingPaymentCount = max(0, $this->billingTotal - $billedCount);
            $message = sprintf(
                '%1$s %2$s',
                $pendingPaymentCount,
                _n(
                    'payment remaining',
                    'payments remaining',
                    $pendingPaymentCount,
                    'stellarpay'
                )
            );
        }

        return $message;
    }

    /**
     * @since 1.0.0
     */
    public function getFormattedBillingPeriod(): string
    {
        $result = $this->period->getValue();

        if ($this->frequency > 1) {
            $result = "$this->frequency $result";
        }

        return $result;
    }

    /**
     * @since 1.0.0
     */
    public function hasEndDate(): bool
    {
        return null !== $this->billingTotal;
    }

    /**
     * @since 1.0.0
     */
    public function calculateEndDate(): ?DateTime
    {
        if (! $this->hasEndDate()) {
            return null;
        }

        $intervalString = sprintf(
            '+ %d %ss',
            $this->frequency * $this->billingTotal,
            $this->period
        );

        $interval = DateInterval::createFromDateString($intervalString);
        $endDate = clone $this->startedAt;
        $endDate->add($interval);

        return $endDate;
    }

    /**
     * @since 1.0.0
     */
    public function calculateNextBillingDate(): DateTime
    {
        $nextBillingNumber = $this->billedCount ?? 0;
        $nextBillingAt = $this->nextBillingAt ? clone $this->nextBillingAt : null;
        $resumedAt = $this->resumedAt ? clone $this->resumedAt : null;

        $intervalString = sprintf('+ %d %ss', $this->frequency, $this->period);
        $interval = DateInterval::createFromDateString($intervalString);

        // If the subscription is new or has no next billing date, add one period to the startedAt date.
        if (0 === $nextBillingNumber || ! $nextBillingAt) {
            $startedAt = clone $this->startedAt;
            $startedAt->add($interval);
            return $startedAt;
        }

        if ($resumedAt) {
            // If today is the equal to or more than the resumedAt date, add one period to the resumedAt date.
            if (Temporal::getCurrentDateTime() >= $resumedAt) {
                $resumedAt->add($interval);
                $this->resumedAt = null;
                $this->resumedAtGmt = null;
                return $resumedAt;
            } else {
                // Else, the customer has resumed the subscription before the resume date, but after the billing cycle.
                // So, add one period to today.
                $today = Temporal::getCurrentDateTime();
                $today->add($interval);
                $this->resumedAt = null;
                $this->resumedAtGmt = null;
                return $today;
            }
        }

        // If the subscription is not new or resumed, add one period to the nextBillingAt date.
        $nextBillingAt->add($interval);

        return $nextBillingAt;
    }

    /**
     * Get the next billing date by adding the interval to today.
     *
     * @since 1.9.0
     */
    public function calculateNextBillingDateFromToday(): DateTime
    {
        $intervalString = sprintf(
            '+ %d %ss',
            $this->frequency,
            $this->period
        );
        $interval = DateInterval::createFromDateString($intervalString);
        $currentDate = Temporal::getCurrentDateTime();
        $newNextBilling = clone $currentDate;
        $newNextBilling->add($interval);

        return $newNextBilling;
    }

    /**
     * @since 1.0.0
     */
    public function getFormattedNextBillingAt(): string
    {
        if (!is_null($this->billingTotal) && $this->billingTotal <= $this->billedCount) {
            return esc_html__('All payments completed', 'stellarpay');
        }

        if (!$this->billingTotal && $this->status->isCanceled()) {
            return esc_html__('Subscription ended', 'stellarpay');
        }

        if (!$this->nextBillingAt || $this->status->isCanceled() || $this->expiresAt) {
            return esc_html__('N/A', 'stellarpay');
        }

        return Temporal::getWPFormattedDate($this->nextBillingAt);
    }

    /**
     * @since 1.0.0
     */
    protected function canUpdateStatus(SubscriptionStatus $status): bool
    {
        return ! $this->status->equals($status);
    }

    /**
     * @since 1.4.0 Return bool whether to flag whether the subscription status updated.
     * @since 1.0.0
     *
     * @throws BindingResolutionException|Exception
     */
    public function updateStatus(SubscriptionStatus $status): bool
    {
        if ($this->canUpdateStatus($status)) {
            $this->status = $status;

            return $this->save()->status->equals($status);
        }

        return false;
    }

    /**
     * @inerhitDoc
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     */
    public static function find($id): ?self
    {
        return container(SubscriptionRepository::class)->getById($id);
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public static function findByTransactionId(string $transactionId): ?self
    {
        return self::query()->where('transaction_id', $transactionId)->get();
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public static function findByFirstOrderId(int $firstOrderId): ?self
    {
        return self::query()->where('first_order_id', $firstOrderId)->limit(1)->get();
    }

    /**
     * @since 1.0.0
     *
     * @return array<self>|null
     * @throws BindingResolutionException
     */
    public static function findAllByFirstOrderId(int $firstOrderId): ?array
    {
        return self::query()->where('first_order_id', $firstOrderId)->getAll();
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public static function findByFirstOrderAndItemId(int $firstOrderId, int $firstOrderItemID): ?self
    {
        return self::query()
            ->where('first_order_id', $firstOrderId)
            ->where('first_order_item_id', $firstOrderItemID)
            ->get();
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException|Exception
     */
    public static function create(array $attributes): self
    {
        $subscription = new static($attributes);

        container(SubscriptionRepository::class)->insert($subscription);

        return $subscription;
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException|Exception
     * @throws \Exception
     */
    public function save(): self
    {
        if (!$this->id) {
            return container(SubscriptionRepository::class)->insert($this);
        } else {
            return container(SubscriptionRepository::class)->update($this);
        }
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function delete(): bool
    {
        return container(SubscriptionRepository::class)->delete($this);
    }

    /**
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     */
    public static function totalCount(): int
    {
        $count = self::query()->count('id');

        return $count ?: 0;
    }

    /**
     * @since 1.7.0
     * @throws BindingResolutionException
     */
    public static function hasSubscriptions(): bool
    {
        return self::totalCount() > 0;
    }

    /**
     * @since 1.7.0
     * @throws BindingResolutionException
     */
    public static function customerHasSubscriptions(int $customerId): bool
    {
        // Customer id should be absolute positive integer.
        if ($customerId <= 0) {
            return false;
        }

        return self::query()
            ->where('customer_id', $customerId)
            ->count('id') > 0;
    }

    /**
     * This function uses to cancel subscription.
     *
     * If the subscription is set to cancel at the period end and you want to cancel the subscription, do it forcefully.
     *
     * @since 1.3.0 Allow force cancelation. We should force to delete it if the subscription is set to cancel at a period.
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function cancel(bool $force = false): bool
    {
        $status = SubscriptionStatus::CANCELED();

        if ($force || $this->canCancel()) {
            return container(SubscriptionRepository::class)->cancel($this);
        }

        return $this->status->equals($status);
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function cancelAtPeriodEnd(DateTime $canceledAt = null): bool
    {
        return container(SubscriptionRepository::class)->cancelAtPeriodEnd($this, $canceledAt);
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function removeCancelAtPeriodEnd(): bool
    {
        return container(SubscriptionRepository::class)->removeCancelAtPeriodEnd($this);
    }

    /**
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function suspend(): bool
    {
        $status = SubscriptionStatus::SUSPENDED();

        if ($this->canUpdateStatus($status)) {
            return container(SubscriptionRepository::class)->suspend($this);
        }

        return $this->status->equals($status);
    }

    /**
     * @since 1.9.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function pause(): bool
    {
        $status = SubscriptionStatus::PAUSED();

        if ($this->canUpdateStatus($status) && $this->willPauseAtPeriodEnd()) {
            return container(SubscriptionRepository::class)->pause($this);
        }

        return $this->status->equals($status);
    }

    /**
     * @since 1.9.0
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function pauseAtPeriodEnd(DateTime $resumesAt): bool
    {
        $result = false;

        if ($this->canPause()) {
            $result = container(SubscriptionRepository::class)->pauseAtPeriodEnd($this, $resumesAt);
        }

        return $result;
    }

    /**
     * @since 1.4.0
     *
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function resume(): bool
    {
        $status = SubscriptionStatus::ACTIVE();

        if ($this->canResume()) {
            return container(SubscriptionRepository::class)->resume($this);
        }

        return $this->status->equals($status);
    }

    /**
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function complete(): bool
    {
        $status = SubscriptionStatus::COMPLETED();

        if ($this->canUpdateStatus($status)) {
            return container(SubscriptionRepository::class)->complete($this);
        }

        return $this->status->equals($status);
    }

    /**
     * @since 1.0.0
     * @return ModelQueryBuilder<Subscription>
     * @throws BindingResolutionException
     */
    public static function query(): ModelQueryBuilder
    {
        return container(SubscriptionRepository::class)->prepareQuery();
    }

    /**
     * @since 1.0.0
     */
    public static function fromQueryBuilderObject(object $object): self
    {
        return SubscriptionQueryData::fromObject($object)->toSubscription();
    }


    /**
     * @since 1.0.0
     */
    public static function factory(): SubscriptionFactory
    {
        return new SubscriptionFactory(static::class);
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function getStripeDashboardLink(): string
    {
        if (! $this->transactionId) {
            return '';
        }

        return $this->isScheduleType()
            ? Client::getStripeDashboardLink('subscription_schedules/' . $this->transactionId)
            : Client::getStripeDashboardLink('subscriptions/' . $this->transactionId);
    }

    /**
     * @since 1.9.0
     * @throws BindingResolutionException
     */
    public function getRenewalOrders(int $limit = 50, string $orderField = 'date', string $orderDirection = 'DESC'): Collection
    {
        $collection = Collection::make();

        if ($this->billedCount < 2) {
            return $collection;
        }

        $args = [
            'parent' => $this->firstOrderId,
            'orderby' => $orderField,
            'order' => $orderDirection,
            'limit' => $limit,
            'meta_key' => container(RenewalOrderRepository::class)->getRenewalSubscriptionIdKey(),
            'meta_value' => $this->id, // phpcs:ignore WordPress.DB.SlowDBQuery
        ];

        $renewalOrders = wc_get_orders($args);

        if (!$renewalOrders) {
            return $collection;
        }

        $collection->push(...$renewalOrders);

        return $collection;
    }

    /**
     * Get the last renewal order
     *
     * @since 1.9.0 Use collection return value
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function getLastRenewalOrder(): ?WC_Order
    {
        $renewalOrders = $this->getRenewalOrders(1);

        if ($renewalOrders->isEmpty()) {
            return null;
        }

        return $renewalOrders->first();
    }

    /**
     * @since 1.1.0
     * @return WC_Order
     * @throws BindingResolutionException
     */
    public function getLastOrder(): WC_Order
    {
        $lastOrder = $this->getLastRenewalOrder();
        if ($lastOrder) {
            return $lastOrder;
        }

        return wc_get_order($this->firstOrderId);
    }

    /**
     * @since 1.9.0 Use ::getLastOrder function
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function getLastOrderAmount(): Money
    {
        $order = $this->getLastOrder();

        return Money::make((float) $order->get_total(), $order->get_currency());
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function getLastPaymentMethod(): string
    {
        $paymentToken = $this->getNewPaymentMethodForRenewal();

        if ($paymentToken) {
            return $paymentToken;
        }

        $order = $this->getLastOrder();

        return container(OrderRepository::class)->getPaymentMethodId($order);
    }

    /**
     * Whether is a subscription schedule type
     *
     * @since 1.1.0
     */
    public function isScheduleType(): bool
    {
        return $this->transactionId
               && Str::contains($this->transactionId, 'sub_sched_');
    }

    /**
     * @since 1.9.0
     */
    public function willPauseAtPeriodEnd(): bool
    {
        return $this->status->isActive() && $this->suspendedAt;
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function saveNewPaymentMethodForRenewal(string $token): void
    {
        container(SubscriptionMetaRepository::class)->saveNewPaymentMethodForRenewal($this->id, $token);
    }

    /**
     * @since 1.1.0
     * @throws BindingResolutionException
     */
    public function getNewPaymentMethodForRenewal(): ?string
    {
        return container(SubscriptionMetaRepository::class)->getNewPaymentMethodForRenewal($this->id);
    }

    /**
     * @since 1.1.0
     * @throws Exception
     * @throws BindingResolutionException
     */
    public function deleteNewPaymentMethodForRenewal(): bool
    {
        return container(SubscriptionMetaRepository::class)->deleteNewPaymentMethodForRenewal($this);
    }

    /**
     * @since 1.2.0
     * @return array<string>
     */
    private function getCancellableSubscriptionStatuses(): array
    {
        return [
            SubscriptionStatus::ACTIVE,
            SubscriptionStatus::PENDING,
            SubscriptionStatus::SUSPENDED,
            SubscriptionStatus::PROCESSING
        ];
    }

    /**
     * @since 1.6.0
     */
    public function getSubscriptionProduct(): ?ProductModel
    {
        try {
            $orderItem = new WC_Order_Item_Product($this->firstOrderItemId);
            $subscriptionProduct = ProductFactory::makeFromProduct($orderItem->get_product());
        } catch (\Exception $e) {
            $subscriptionProduct = null;
        }

        return $subscriptionProduct;
    }

    /**
     * @since 1.6.0
     */
    public function isSubscriptionPayments(): bool
    {
        $subscriptionProduct = $this->getSubscriptionProduct();

        if (!$subscriptionProduct) {
            return false;
        }

        return $subscriptionProduct->getProductType()->isSubscriptionPayments();
    }

    /**
     * @since 1.6.0
     */
    protected function hasCancellableStatus(): bool
    {
        return in_array($this->status->getValue(), $this->getCancellableSubscriptionStatuses());
    }

    /**
     * @since 1.9.0
     */
    public function hasRenewalDatePassed(): bool
    {
        return $this->nextBillingAt < Temporal::getCurrentDateTime();
    }

    /**
     * @since 1.6.0 Refactor function
     * @since 1.3.0 Check `expiresAt`
     * @since 1.2.0
     */
    public function canCancel(): bool
    {
        if ($this->expiresAt) {
            return false;
        }

        return $this->hasCancellableStatus();
    }

    /**
     * Check if the subscription can be updated e.g.
     * update the payment method, pause, etc.
     *
     * @since 1.3.0
     */
    public function canUpdate(): bool
    {
        if ($this->expiresAt) {
            return false;
        }

        return !$this->status->isCanceled() && !$this->status->isCompleted();
    }

    /**
     * @since 1.3.0
     * @throws Exception
     */
    public function getFormattedStatusLabel(): string
    {
        if ($this->expiresAt) {
            return sprintf(
                /* translators: 1: Date */
                esc_html__('Cancels at %s', 'stellarpay'),
                $this->expiresAt->format(get_option('date_format', 'F j, Y'))
            );
        }

        if ($this->suspendedAt && $this->status->isActive()) {
            return sprintf(
                /* translators: 1: Date */
                esc_html__('Pauses on %s', 'stellarpay'),
                $this->suspendedAt->format(get_option('date_format', 'F j, Y'))
            );
        }

        return $this->status->label();
    }

    /**
     * @since 1.3.0
     */
    public function canUpdatePaymentMethod(): bool
    {
        $notAllowedStatuses = [
            SubscriptionStatus::COMPLETED,
            SubscriptionStatus::CANCELED,
            SubscriptionStatus::EXPIRED,
            SubscriptionStatus::SUSPENDED,
            SubscriptionStatus::ABANDONED,
        ];

        return ! in_array($this->status->getValue(), $notAllowedStatuses, true);
    }

    /**
     * @since 1.3.0
     * @throws BindingResolutionException
     */
    public function updatePaymentMethod(string $token): void
    {
        if (! $this->canUpdatePaymentMethod()) {
            return;
        }

        container(SubscriptionRepository::class)->updatePaymentMethod($this, $token);
    }

    /**
     * @since 1.9.0
     */
    public function canPause(): bool
    {
        // Cannot pause installment payments.
        if ($this->hasEndDate()) {
            return false;
        }

        return ($this->status->isActive() || $this->status->isPastDue() || $this->status->isFailing()) && ! $this->suspendedAt;
    }

    /**
     * @since 1.9.0
     */
    public function canResume(): bool
    {
        // Cannot pause installment payments.
        if ($this->hasEndDate()) {
            return false;
        }

        return $this->status->isPaused() || ($this->status->isActive() && $this->suspendedAt);
    }
}
