<?php

/**
 * This class responsible to provide logic to perform on subscriptions metadata.
 *
 * @package StellarPay\Subscriptions\Repositories
 * @since 1.1.0
 */

declare(strict_types=1);

namespace StellarPay\Subscriptions\Repositories;

use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidArgumentException;
use StellarPay\Core\Hooks;
use StellarPay\Subscriptions\DataTransferObjects\SubscriptionMetaDTO;
use StellarPay\Subscriptions\Models\Subscription;
use StellarPay\Vendors\StellarWP\DB\DB;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\QueryBuilder;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * @since 1.1.0
 */
class SubscriptionMetaRepository
{
    /**
     * @since 1.1.0
     */
    private array $requiredProperties = [
        'subscriptionId',
        'metaKey',
        'metaValue',
    ];

    /**
     * @since 1.1.0
     */
    private function getRenewalPaymentMethodIdKey(): string
    {
        return dbMetaKeyGenerator('new_payment_method_id_for_renewal', true);
    }

    /**
     * @since 1.1.0
     */
    public function getNewPaymentMethodForRenewal(int $subscriptionId): ?string
    {
        $metaData = $this->find($subscriptionId, $this->getRenewalPaymentMethodIdKey());

        if (!$metaData) {
            return null;
        }

        return $metaData->metaValue;
    }

    /**
     * @since 1.1.0
     */
    public function saveNewPaymentMethodForRenewal(int $subscriptionId, string $token): SubscriptionMetaDTO
    {
        $subscriptionMeta = SubscriptionMetaDTO::fromArray(
            [
                'subscription_id' => $subscriptionId,
                'meta_key' => $this->getRenewalPaymentMethodIdKey(),
                'meta_value' => $token, // phpcs:ignore WordPress.DB.SlowDBQuery
            ]
        );

        return $this->save($subscriptionMeta);
    }

    /**
     * Delete renewal payment method from subscription meta-table.
     *
     * @since 1.1.0
     * @throws Exception
     */
    public function deleteNewPaymentMethodForRenewal(Subscription $subscription): bool
    {
        return $this->delete($subscription, $this->getRenewalPaymentMethodIdKey());
    }

    /**
     * @since 1.1.0
     */
    public function has(int $subscriptionId, string $metaKey): bool
    {
        $metadata = $this->prepareQuery()
                ->where('subscription_id', $subscriptionId)
                ->where('meta_key', $metaKey)
                ->limit(1)
                ->count();

        return (bool) $metadata;
    }

    /**
     * @since 1.1.0
     */
    public function find(int $subscriptionId, string $metaKey): ?SubscriptionMetaDTO
    {
        $metadata = $this->prepareQuery()
                ->where('subscription_id', $subscriptionId)
                ->where('meta_key', $metaKey)
                ->get();

        if (! $metadata) {
            return null;
        }

        return SubscriptionMetaDTO::fromObject($metadata);
    }

    /**
     * @since 1.1.0
     */
    protected function getId(int $subscriptionId, string $metaKey): ?int
    {
        $metadata = $this->find($subscriptionId, $metaKey);

        if (! $metadata) {
            return null;
        }

        return $metadata->id;
    }

    /**
     * @since 1.1.0
     */
    public function get(int $subscriptionMetaId): ?SubscriptionMetaDTO
    {
        $metadata = $this->prepareQuery()
                ->where('id', $subscriptionMetaId)
                ->get();

        if (! $metadata) {
            return null;
        }

        return SubscriptionMetaDTO::fromObject($metadata);
    }

    /**
     * @since 1.1.0
     * @throws Exception
     */
    public function save(SubscriptionMetaDTO $subscriptionMeta): SubscriptionMetaDTO
    {
        if (!$this->has($subscriptionMeta->subscriptionId, $subscriptionMeta->metaKey)) {
            return $this->insert($subscriptionMeta);
        }

        $subscriptionMeta->id = $this->getId($subscriptionMeta->subscriptionId, $subscriptionMeta->metaKey);

        return $this->update($subscriptionMeta);
    }

    /**
     * @since 1.1.0
     * @throws Exception
     */
    public function insert(SubscriptionMetaDTO $subscriptionMeta): SubscriptionMetaDTO
    {
        $this->validate($subscriptionMeta);

        /**
         * Fires just before creating the subscription metadata.
         *
         * @since 1.1.0
         * @hook stellarpay_subscription_meta_creating
         * @param SubscriptionMetaDTO $subscriptionMetaData The subscription metadata to be created.
         */
        Hooks::doAction('stellarpay_subscription_meta_creating', $subscriptionMeta);

        DB::query('START TRANSACTION');

        try {
            $this->prepareQuery()
                ->insert(
                    $this->toArray($subscriptionMeta),
                );

            $subscriptionMetaId = DB::last_insert_id();
        } catch (\Exception $exception) {
            DB::query('ROLLBACK');

            throw new Exception('Failed creating a subscription metadata');
        }

        DB::query('COMMIT');

        $subscriptionMeta->id = $subscriptionMetaId;

        /**
         * Fires just after created the subscription metadata.
         *
         * @since 1.1.0
         * @hook stellarpay_subscription_meta_created
         * @param SubscriptionMetaDTO $subscriptionMetaData The subscription metadata.
         */
        Hooks::doAction('stellarpay_subscription_meta_created', $subscriptionMeta);

        return $subscriptionMeta;
    }

    /**
     * @since 1.1.0
     * @throws Exception
     */
    public function update(SubscriptionMetaDTO $subscriptionMeta): SubscriptionMetaDTO
    {
        $this->validate($subscriptionMeta);

        /**
         * Fires just before updating the subscription metadata.
         *
         * @since 1.1.0
         * @hook stellarpay_subscription_meta_updating
         * @param SubscriptionMetaDTO $subscriptionMetaData The subscription metadata to be updated.
         */
        Hooks::doAction('stellarpay_subscription_meta_updating', $subscriptionMeta);

        DB::query('START TRANSACTION');

        try {
            $this->prepareQuery()
                ->where('ID', $subscriptionMeta->id)
                ->update(
                    $this->toArray($subscriptionMeta),
                );
        } catch (\Exception $exception) {
            DB::query('ROLLBACK');

            throw new Exception('Failed updating a subscription metadata');
        }

        DB::query('COMMIT');

        $subscriptionMeta = $this->get($subscriptionMeta->id);

        /**
         * Fires just after updated the subscription metadata.
         *
         * @since 1.1.0
         * @hook stellarpay_subscription_meta_updated
         * @param SubscriptionMetaDTO $subscriptionMetaData The subscription metadata.
         */
        Hooks::doAction('stellarpay_subscription_meta_updated', $subscriptionMeta);

        return $subscriptionMeta;
    }

    /**
     * @since 1.1.0
     * @throws Exception
     */
    public function delete(Subscription $subscription, string $metaKey): bool
    {
        DB::query('START TRANSACTION');

        /**
         * Fires just before deleting the subscription metadata.
         *
         * @since 1.1.0
         * @hook stellarpay_subscription_meta_deleting
         * @param Subscription $subscription The subscription to delete the metadata.
         * @param string $metaKey The meta-key to be deleted.
         */
        Hooks::doAction('stellarpay_subscription_meta_deleting', $subscription, $metaKey);

        try {
            $this->prepareQuery()
                ->where('subscription_id', $subscription->id)
                ->where('meta_key', $metaKey)
                ->delete();
        } catch (\Exception $exception) {
            DB::query('ROLLBACK');

            throw new Exception('Failed deleting a subscription metadata');
        }

        DB::query('COMMIT');

        /**
         * Fires just after deleted the subscription metadata.
         *
         * @since 1.1.0
         * @hook stellarpay_subscription_meta_deleted
         * @param Subscription $subscription The subscription.
         * @param string $metaKey The meta key deleted.
         */
        Hooks::doAction('stellarpay_subscription_meta_deleted', $subscription, $metaKey);

        return true;
    }

    /**
     * @since 1.2.0
     * @throws Exception
     */
    public function deleteAll(Subscription $subscription): bool
    {
        DB::query('START TRANSACTION');

        /**
         * Fires just before deleting all subscription metadata.
         *
         * @since 1.2.0
         * @hook stellarpay_all_subscription_meta_deleting
         * @param Subscription $subscription The subscription to delete all metadata.
         */
        Hooks::doAction('stellarpay_all_subscription_meta_deleting', $subscription);

        try {
            $this->prepareQuery()
                ->where('subscription_id', $subscription->id)
                ->delete();
        } catch (\Exception $exception) {
            DB::query('ROLLBACK');

            throw new Exception('Failed deleting all subscription metadata');
        }

        DB::query('COMMIT');

        /**
         * Fires just after deleted all subscription metadata.
         *
         * @since 1.2.0
         * @hook stellarpay_all_subscription_meta_deleted
         * @param Subscription $subscription The subscription.
         */
        Hooks::doAction('stellarpay_all_subscription_meta_deleted', $subscription);

        return true;
    }

    /**
     * @since 1.1.0
     */
    public function prepareQuery(): QueryBuilder
    {
        return DB::table(Subscription::getMetaTableNameWithoutDBPrefix());
    }

    /**
     * @since 1.1.0
     */
    private function validate(SubscriptionMetaDTO $subscriptionMeta): void
    {
        foreach ($this->requiredProperties as $key) {
            if (!isset($subscriptionMeta->$key)) {
                throw new InvalidArgumentException(esc_attr("'$key' is required to create subscription metadata"));
            }
        }
    }

    /**
     * @since 1.1.0
     */
    private function toArray(SubscriptionMetaDTO $subscriptionMeta): array
    {
        return [
            'id' => $subscriptionMeta->id,
            'subscription_id' => $subscriptionMeta->subscriptionId,
            'meta_key' => $subscriptionMeta->metaKey,
            'meta_value' => $subscriptionMeta->metaValue, // phpcs:ignore WordPress.DB.SlowDBQuery
        ];
    }
}
