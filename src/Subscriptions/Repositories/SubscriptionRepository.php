<?php

/**
 * This class responsible to provide logic to perform on subscriptions.
 *
 * @package StellarPay\Subscriptions\Repositories
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\Repositories;

use DateTime;
use DateTimeInterface;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidArgumentException;
use StellarPay\Core\Hooks;
use StellarPay\Core\Support\Enum;
use StellarPay\Core\Support\Facades\DateTime\Temporal;
use StellarPay\Core\ValueObjects\Money;
use StellarPay\Core\ValueObjects\SubscriptionStatus;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Vendors\StellarWP\DB\DB;
use StellarPay\Vendors\StellarWP\Models\ModelQueryBuilder;
use StellarPay\PaymentGateways\Stripe\Services\SubscriptionScheduleService;
use StellarPay\PaymentGateways\Stripe\Services\SubscriptionService;

use function StellarPay\Core\container;

/**
 * @since 1.0.0
 */
class SubscriptionRepository
{
    /**
     * @since 1.0.0
     *
     * @var string[]
     */
    private array $requiredSubscriptionProperties = [
        'customerId',
        'firstOrderId',
        'period',
        'frequency',
        'paymentGatewayMode',
        'source'
    ];

    /**
     * @since 1.0.0
     */
    public function getById(int $subscriptionId): ?Subscription
    {
        return $this->prepareQuery()
            ->where('ID', $subscriptionId)
            ->get();
    }

    /**
     * @since 1.0.0
     *
     * @return Subscription[]|array
     */
    public function getAll(array $args = []): ?array
    {
        $defaults = ['page' => 1, 'fields' => []];
        $args = array_merge($defaults, $args);
        $pageNumber = (int) $args['page'];

        $query = $this->prepareQuery()
            ->orderBy('ID', 'DESC');

        // Set offset.
        if (array_key_exists('perPage', $args) && $args['perPage']) {
            $limit = (int) $args['perPage'];
            $offset = $limit * ($pageNumber - 1);

            $query->offset($offset);
            $query->limit($limit);
        }

        if ($args['fields']) {
            $columnCount = count($args['fields']);
            $query->select(...$args['fields']);

            $result = DB::get_results($query->getSQL(), OBJECT);

            return 1 === $columnCount
                ? wp_list_pluck((array)$result, $args['fields'][0])
                : $result;
        }

        return $query->getAll();
    }

    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function insert(Subscription $subscription): Subscription
    {
        $this->validateSubscription($subscription);

        Hooks::doAction('stellarpay_subscription_creating', $subscription);

        $dateCreated = Temporal::withoutMicroseconds($subscription->createdAt ?? Temporal::getCurrentDateTime());
        $dateCreatedGmt = Temporal::getGMTDateTime($dateCreated);

        $dateUpdated = $subscription->updatedAt ?? $dateCreated;
        $dateUpdatedGmt = Temporal::getGMTDateTime($dateUpdated);

        DB::query('START TRANSACTION');

        try {
            $this->prepareQuery()
                ->insert(array_merge(
                    $this->toArray($subscription, true),
                    [
                        'created_at' => Temporal::getFormattedDateTime($dateCreated),
                        'created_at_gmt' => Temporal::getFormattedDateTime($dateCreatedGmt),
                        'updated_at' => Temporal::getFormattedDateTime($dateUpdated),
                        'updated_at_gmt' => Temporal::getFormattedDateTime($dateUpdatedGmt),
                    ]
                ));

            $subscriptionId = DB::last_insert_id();
        } catch (\Exception $exception) {
            DB::query('ROLLBACK');

            // @todo log errors

            throw new Exception('Failed creating a subscription');
        }

        DB::query('COMMIT');

        $subscription->id = $subscriptionId;

        $subscription->createdAt = $dateCreated;
        $subscription->createdAtGmt = $dateCreatedGmt;
        $subscription->updatedAt = $dateUpdated;
        $subscription->updatedAtGmt = $dateUpdatedGmt;

        Hooks::doAction('stellarpay_subscription_created', $subscription);

        return $subscription;
    }

    /**
     * @since 1.0.0
     * @throws Exception
     * @throws BindingResolutionException
     */
    public function update(Subscription $subscription): Subscription
    {
        $this->validateSubscription($subscription);

        /**
         * Fires just before updating the subscription.
         *
         * @since 1.0.0
         * @hook stellarpay_subscription_updating
         * @param Subscription $subscription The subscription to be updated.
         */
        Hooks::doAction('stellarpay_subscription_updating', $subscription);

        $hasStatusChanged = $this->hasStatusChanged($subscription);

        if ($hasStatusChanged) {
            /**
             * Fires just before changing the subscription status.
             *
             * @since 1.0.0
             * @hook stellarpay_subscription_status_changing
             * @param Subscription $subscription The subscription to be updated.
             */
            Hooks::doAction('stellarpay_subscription_status_changing', $subscription);

            // Reset suspended and resumed dates when the status is changed to other than "paused".
            if (! $subscription->status->isPaused()) {
                $subscription->suspendedAt = null;
                $subscription->suspendedAtGmt = null;
                $subscription->resumedAt = null;
                $subscription->resumedAtGmt = null;
            }
        }

        $dateUpdated = Temporal::withoutMicroseconds(Temporal::getCurrentDateTime());
        $dateUpdatedGmt = Temporal::getGMTDateTime($dateUpdated);

        DB::query('START TRANSACTION');

        try {
            $this->prepareQuery()
                ->where('ID', $subscription->id)
                ->update(array_merge(
                    $this->toArray($subscription),
                    [
                        'updated_at' => Temporal::getFormattedDateTime($dateUpdated),
                        'updated_at_gmt' => Temporal::getFormattedDateTime($dateUpdatedGmt),
                    ]
                ));
        } catch (\Exception $exception) {
            DB::query('ROLLBACK');

            // @todo Log errors.

            throw new Exception('Failed updating a subscription');
        }

        $subscription->updatedAt = $dateUpdated;
        $subscription->updatedAtGmt = $dateUpdatedGmt;

        DB::query('COMMIT');

        $subscription = Subscription ::find($subscription->id);

        /**
         * Fires just after updated the subscription.
         *
         * @since 1.0.0
         * @hook stellarpay_subscription_updated
         * @param Subscription $subscription The subscription.
         */
        Hooks::doAction('stellarpay_subscription_updated', $subscription);

        if ($hasStatusChanged) {
            /**
             * Fires after the subscription status changed.
             *
             * @since 1.0.0
             * @hook stellarpay_subscription_status_changed
             * @param Subscription $subscription The subscription.
             */
            Hooks::doAction('stellarpay_subscription_status_changed', $subscription);
        }

        return $subscription;
    }

    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function delete(Subscription $subscription): bool
    {
        DB::query('START TRANSACTION');

        /**
         * Fires just before deleting the subscription.
         *
         * @since 1.0.0
         * @hook stellarpay_subscription_deleting
         * @param Subscription $subscription The subscription to be deleted.
         */
        Hooks::doAction('stellarpay_subscription_deleting', $subscription);

        try {
            // Remove subscription meta data when deletes it.
            container(SubscriptionMetaRepository::class)
                ->deleteAll($subscription);

            $this->prepareQuery()
                ->where('id', $subscription->id)
                ->delete();
        } catch (\Exception $exception) {
            DB::query('ROLLBACK');

            // @todo Log errors.

            throw new Exception('Failed deleting a subscription');
        }

        DB::query('COMMIT');

        /**
         * Fires just after deleted the subscription.
         *
         * @since 1.0.0
         * @hook stellarpay_subscription_deleted
         * @param Subscription $subscription The subscription.
         */
        Hooks::doAction('stellarpay_subscription_deleted', $subscription);

        return true;
    }

    /**
     * @since 1.0.0
     * @throws Exception|BindingResolutionException
     */
    public function cancel(Subscription $subscription): bool
    {
        DB::query('START TRANSACTION');

        /**
         * Fires just before canceling a subscription.
         *
         * @since 1.0.0
         * @hook stellarpay_subscription_canceling
         * @param Subscription $subscription The subscription to be canceled.
         */
        Hooks::doAction('stellarpay_subscription_canceling', $subscription);

        $dateCanceled = Temporal::withoutMicroseconds(Temporal::getCurrentDateTime());
        $dateCanceledGmt = Temporal::getGMTDateTime($dateCanceled);

        $subscription->status = SubscriptionStatus::CANCELED();
        $subscription->canceledAt = $dateCanceled;
        $subscription->canceledAtGmt = $dateCanceledGmt;
        $subscription->endedAt = $dateCanceled;
        $subscription->endedAtGmt = $dateCanceledGmt;
        $subscription->expiresAt = null;
        $subscription->expiresAtGmt = null;

        $this->update($subscription);

        /**
         * Fires after a subscription is canceled.
         *
         * @since 1.0.0
         * @hook stellarpay_subscription_canceled
         * @param Subscription $subscription The canceled subscription.
         */
        Hooks::doAction('stellarpay_subscription_canceled', $subscription);

        return true;
    }

    /**
     * @since 1.3.0
     * @throws Exception|BindingResolutionException
     */
    public function cancelAtPeriodEnd(Subscription $subscription, DateTime $canceledAt = null): bool
    {
        DB::query('START TRANSACTION');

        /**
         * Fires just before updating a subscription to be canceled at the period end.
         *
         * @since 1.3.0
         * @hook stellarpay_subscription_updating_to_cancel_at_period_end
         * @param Subscription $subscription The subscription to be canceled.
         */
        Hooks::doAction('stellarpay_subscription_updating_to_cancel_at_period_end', $subscription);

        $dateCanceled = $canceledAt ?? Temporal::withoutMicroseconds(Temporal::getCurrentDateTime());
        $dateCanceledGmt = Temporal::getGMTDateTime($dateCanceled);

        $subscription->canceledAt = $dateCanceled;
        $subscription->canceledAtGmt = $dateCanceledGmt;

        $expiresAt = $subscription->nextBillingAt;
        $expiresAtGmt = $subscription->nextBillingAtGmt;

        $subscription->endedAt = $expiresAt;
        $subscription->endedAtGmt = $expiresAtGmt;
        $subscription->expiresAt = $expiresAt;
        $subscription->expiresAtGmt = $expiresAtGmt;

        $this->update($subscription);

        /**
         * Fires after a subscription is updated to be canceled at the period end.
         *
         * @since 1.3.0
         * @hook stellarpay_subscription_updated_to_cancel_at_period_end
         * @param Subscription $subscription The canceled subscription.
         */
        Hooks::doAction('stellarpay_subscription_updated_to_cancel_at_period_end', $subscription);

        return true;
    }

    /**
     * @since 1.3.0
     * @throws Exception|BindingResolutionException
     */
    public function removeCancelAtPeriodEnd(Subscription $subscription): bool
    {
        DB::query('START TRANSACTION');

        /**
         * Fires just before updating a subscription to not be canceled at period end.
         *
         * @since 1.3.0
         * @hook stellarpay_subscription_removing_cancel_at_period_end
         * @param Subscription $subscription The subscription to be canceled.
         */
        Hooks::doAction('stellarpay_subscription_removing_cancel_at_period_end', $subscription);

        $subscription->canceledAt = null;
        $subscription->canceledAtGmt = null;
        $subscription->endedAt = null;
        $subscription->endedAtGmt = null;
        $subscription->expiresAt = null;
        $subscription->expiresAtGmt = null;

        $this->update($subscription);

        /**
         * Fires after a subscription is updated to not be canceled at period end.
         *
         * @since 1.3.0
         * @hook stellarpay_subscription_removed_cancel_at_period_end
         * @param Subscription $subscription The canceled subscription.
         */
        Hooks::doAction('stellarpay_subscription_removed_cancel_at_period_end', $subscription);

        return true;
    }

    /**
     * @since 1.0.0
     * @throws Exception
     * @throws BindingResolutionException
     */
    public function suspend(Subscription $subscription): bool
    {
        /**
         * Fires just before suspending a subscription.
         *
         * @since 1.0.0
         * @hook stellarpay_subscription_suspending
         * @param Subscription $subscription The subscription to be suspended.
         */
        Hooks::doAction('stellarpay_subscription_suspending', $subscription);

        $dateSuspended = Temporal::withoutMicroseconds(Temporal::getCurrentDateTime());
        $dateSuspendedGmt = Temporal::getGMTDateTime($dateSuspended);

        $subscription->status = SubscriptionStatus::SUSPENDED();
        $subscription->suspendedAt = $dateSuspended;
        $subscription->suspendedAtGmt = $dateSuspendedGmt;

        $this->update($subscription);

        /**
         * Fires after a subscription is suspended.
         *
         * @since 1.0.0
         * @hook stellarpay_subscription_suspended
         * @param Subscription $subscription The suspended subscription.
         */
        Hooks::doAction('stellarpay_subscription_suspended', $subscription);

        return true;
    }

    /**
     * @since 1.4.0
     * @throws Exception|BindingResolutionException
     */
    public function activate(Subscription $subscription): bool
    {
        $subscription->status = SubscriptionStatus::ACTIVE();

        $this->update($subscription);

        return true;
    }

    /**
     * @since 1.9.0
     * @throws Exception|BindingResolutionException
     */
    public function resume(Subscription $subscription): bool
    {
        $subscription->status = SubscriptionStatus::ACTIVE();
        $subscription->suspendedAt = null;
        $subscription->suspendedAtGmt = null;
        $subscription->resumedAt = null;
        $subscription->resumedAtGmt = null;

        $this->update($subscription);

        /**
         * Fires after a subscription is resumed.
         *
         * @since 1.9.0
         * @hook stellarpay_subscription_resumed
         *
         * @param Subscription $subscription The resumed subscription.
         */
        Hooks::doAction('stellarpay_subscription_resumed', $subscription);

        return true;
    }

    /**
     * @since 1.0.0
     */
    public function complete(Subscription $subscription): bool
    {
        /**
         * Fires just before completing a subscription.
         *
         * @since 1.0.0
         * @hook stellarpay_subscription_completing
         * @param Subscription $subscription The subscription to be suspended.
         */
        Hooks::doAction('stellarpay_subscription_completing', $subscription);

        $dateCompleted = Temporal::withoutMicroseconds(Temporal::getCurrentDateTime());
        $dateCompletedGmt = Temporal::getGMTDateTime($dateCompleted);

        $subscription->status = SubscriptionStatus::COMPLETED();
        $subscription->endedAt = $dateCompleted;
        $subscription->endedAtGmt = $dateCompletedGmt;

        $this->update($subscription);

        /**
         * Fires after a subscription is completed.
         *
         * @since 1.0.0
         * @hook stellarpay_subscription_completed
         * @param Subscription $subscription The completed subscription.
         */
        Hooks::doAction('stellarpay_subscription_completed', $subscription);

        return true;
    }

    /**
     * @since 1.0.0
     *
     * @return ModelQueryBuilder<Subscription>
     */
    public function prepareQuery(): ModelQueryBuilder
    {
        return (new ModelQueryBuilder(Subscription ::class))->from(Subscription::getTableName(false));
    }

    /**
     * @since 1.0.0
     */
    private function validateSubscription(Subscription $subscription): void
    {
        foreach ($this->requiredSubscriptionProperties as $key) {
            if (!isset($subscription->$key)) {
                throw new InvalidArgumentException(esc_attr("'$key' is required to create subscription"));
            }
        }
    }

    /**
     * @since 1.8.0 Add support for initial amount, recurring amount and currency code.
     * @since 1.0.0
     */
    private function toArray(Subscription $subscription, $force = false): array
    {
        $result = [];

        $tableColumnsMappedToModelProperties = [
            'id' => 'id',
            'customerId' => 'customer_id',
            'firstOrderId' => 'first_order_id',
            'firstOrderItemId' => 'first_order_item_id',
            'period' => 'period',
            'frequency' => 'frequency',
            'status' => 'status',
            'transactionId' => 'transaction_id',
            'billingTotal' => 'billing_total',
            'billedCount' => 'billed_count',
            'paymentGatewayMode' => 'payment_gateway_mode',
            'createdAt' => 'created_at',
            'createdAtGmt' => 'created_at_gmt',
            'startedAt' => 'started_at',
            'startedAtGmt' => 'started_at_gmt',
            'endedAt' => 'ended_at',
            'endedAtGmt' => 'ended_at_gmt',
            'trialStartedAt' => 'trial_started_at',
            'trialStartedAtGmt' => 'trial_started_at_gmt',
            'trialEndedAt' => 'trial_ended_at',
            'trialEndedAtGmt' => 'trial_ended_at_gmt',
            'nextBillingAt' => 'next_billing_at',
            'nextBillingAtGmt' => 'next_billing_at_gmt',
            'updatedAt' => 'updated_at',
            'updatedAtGmt' => 'updated_at_gmt',
            'expiredAt' => 'expired_at',
            'expiredAtGmt' => 'expired_at_gmt',
            'suspendedAt' => 'suspended_at',
            'suspendedAtGmt' => 'suspended_at_gmt',
            'canceledAt' => 'canceled_at',
            'canceledAtGmt' => 'canceled_at_gmt',
            'resumedAt' => 'resumed_at',
            'resumedAtGmt' => 'resumed_at_gmt',
            'source' => 'source',
            'expiresAt' => 'expires_at',
            'expiresAtGmt' => 'expires_at_gmt',
            'initialAmount' => 'initial_amount',
            'recurringAmount' => 'recurring_amount',
            'currencyCode' => 'currency_code',
        ];

        if (! $force && $subscription->isClean()) {
            return $result;
        }

        $changedValues = $subscription->isDirty()
            ? $subscription->getDirty()
            : $subscription->toArray();

        foreach ($changedValues as $key => $value) {
            $column = $tableColumnsMappedToModelProperties[$key];

            // Skip id column if set to null.
            if ('id' === $column && ! $value) {
                continue;
            }

            if ('currencyCode' === $key) {
                $result[$column] = strtoupper($value);
                continue;
            }

            if ($value instanceof Enum) {
                $result[$column] = $value->getValue();
                continue;
            }

            if ($value instanceof DateTimeInterface) {
                $result[$column] = Temporal::getFormattedDateTime($value);
                continue;
            }

            if ($value instanceof Money) {
                $result[$column] = $value->getMinorAmount();
                continue;
            }

            $result[$column] = $value;
        }

        return $result;
    }

    /**
     * Check if the subscription status has changed.
     *
     * @since 1.0.0
     */
    protected function hasStatusChanged(Subscription $subscription): bool
    {
        return $subscription->isDirty('status')
               && $subscription->status->getValue() !== $subscription->getOriginal('status')->getValue();
    }

    /**
     * Change the payment method for the subscription.
     *
     * @since 1.3.0.
     * @throws BindingResolutionException
     */
    public function updatePaymentMethod(Subscription $subscription, string $token): void
    {
        /**
         * Fires before the payment method is updated for the subscription.
         *
         * @since 1.3.0
         * @hook stellarpay_subscription_payment_method_updating
         * @param Subscription $subscription The subscription.
         */
        Hooks::doAction('stellarpay_subscription_payment_method_updating', $subscription, $token);

        if ($subscription->isScheduleType()) {
            container(SubscriptionScheduleService::class)->updatePaymentMethod($subscription->transactionId, $token);
        } else {
            container(SubscriptionService::class)->updateSubscriptionPaymentMethod($subscription->transactionId, $token);
        }

        $subscription->saveNewPaymentMethodForRenewal($token);

        /**
         * Fires after the payment method is updated for the subscription.
         *
         * @since 1.3.0
         * @hook stellarpay_subscription_payment_method_updated
         * @param Subscription $subscription The subscription.
         */
        Hooks::doAction('stellarpay_subscription_payment_method_updated', $subscription, $token);
    }

    /**
     * @since 1.9.0
     * @throws Exception|BindingResolutionException
     */
    public function pause(Subscription $subscription): bool
    {
        $subscription->status = SubscriptionStatus::PAUSED();
        $subscription->suspendedAt = Temporal::withoutMicroseconds(Temporal::getCurrentDateTime());
        $subscription->suspendedAtGmt = Temporal::getGMTDateTime(Temporal::getCurrentDateTime());

        $this->update($subscription);

        return true;
    }

    /**
     * @since 1.9.0
     * @throws Exception|BindingResolutionException
     */
    public function pauseAtPeriodEnd(Subscription $subscription, DateTime $resumesAt): bool
    {
        DB::query('START TRANSACTION');

        /**
         * Fires just before updating a subscription to be paused at period end.
         *
         * @since 1.9.0
         * @hook stellarpay_subscription_updating_to_pause_at_period_end
         * @param Subscription $subscription The subscription to be paused.
         */
        Hooks::doAction('stellarpay_subscription_pause_at_period_end', $subscription);

        $subscription->suspendedAt    = $subscription->nextBillingAt;
        $subscription->suspendedAtGmt = Temporal::getGMTDateTime($subscription->nextBillingAt);
        $subscription->resumedAt      = $resumesAt;
        $subscription->resumedAtGmt   = Temporal::getGMTDateTime($resumesAt);

        $this->update($subscription);

        /**
         * Fires after a subscription is paused at period end.
         *
         * @since 1.9.0
         * @hook stellarpay_subscription_paused_at_period_end
         * @param Subscription $subscription The paused subscription.
         */
        Hooks::doAction('stellarpay_subscription_paused_at_period_end', $subscription);

        return true;
    }
}
