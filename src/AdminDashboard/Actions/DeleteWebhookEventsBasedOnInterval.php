<?php

/**
 * This class used to remove webhook events data based on retention period admin settings.
 *
 * @package StellarPay\Webhook\Actions
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\Actions;

use StellarPay\AdminDashboard\Repositories\OptionsRepository;
use StellarPay\Webhook\Models\WebhookEvent;
use StellarPay\Webhook\Repositories\WebhookEventsRepository;

/**
 * @since 1.3.0
 */
class DeleteWebhookEventsBasedOnInterval
{
    /**
     * @since 1.3.0
     * @var OptionsRepository
     */
    protected OptionsRepository $optionsRepository;

    /**
     * @since 1.3.0
     * @var WebhookEventsRepository
     */
    protected WebhookEventsRepository $webhookEventsRepository;

    /**
     * @since 1.3.0
     */
    public function __construct(OptionsRepository $optionsRepository, WebhookEventsRepository $webhookEventsRepository)
    {
        $this->optionsRepository = $optionsRepository;
        $this->webhookEventsRepository = $webhookEventsRepository;
    }

    /**
     * @since 1.3.0
     */
    public function __invoke(): void
    {
        global $wpdb;

        $date = $this->optionsRepository->getWebhookEventsDataRetentionDate();

        if ($date instanceof \DateTime) {
            $tableName = WebhookEvent::getTableName();
            $formattedDate = $date->format('Y-m-d H:i:s');

            // phpcs:disable
            $wpdb->query(
                $wpdb->prepare(
                    "DELETE FROM $tableName WHERE created_at < %s",
                    $formattedDate
                )
            );
            // phpcs:enable
        }
    }
}
