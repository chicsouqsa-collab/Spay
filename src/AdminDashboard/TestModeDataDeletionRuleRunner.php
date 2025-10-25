<?php

/**
 * This class is responsible for registering the jobs for test mode
 * data deletion scheduled actions.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard;

use StellarPay\AdminDashboard\Actions\Contracts\TestDataDeletionRule;
use StellarPay\AdminDashboard\Actions\DeleteTestModeCustomers;
use StellarPay\AdminDashboard\Actions\DeleteTestModeOrders;
use StellarPay\AdminDashboard\Actions\DeleteTestModePaymentMethods;
use StellarPay\AdminDashboard\Actions\DeleteTestModeSubscriptions;
use StellarPay\Core\Constants;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\ActionScheduler\ActionScheduler;
use StellarPay\Integrations\ActionScheduler\DataTransferObjects\ActionSchedulerJobDTO;

use function StellarPay\Core\container;

/**
  * @since 1.2.0
  *
  * @template T of TestDataDeletionRule
  */
class TestModeDataDeletionRuleRunner
{
     /**
      * @since 1.2.0
      * @throws BindingResolutionException
      */
    public function __invoke(): void
    {
        foreach ($this->getDeletionRules() as $deletionRule) {
            $deletionRuleInvokable = container($deletionRule);
            $deletionRuleInvokable();
        }
    }

    /**
     * @since 1.2.0
     * @return class-string<T>[]
     */
    public function getDeletionRules(): array
    {
        return [
            DeleteTestModeSubscriptions::class,
            DeleteTestModePaymentMethods::class,
            DeleteTestModeCustomers::class,
            DeleteTestModeOrders::class
        ];
    }

    /**
     * @since 1.2.0
     */
    private function getJob(): ActionSchedulerJobDTO
    {
        return ActionSchedulerJobDTO::fromEventData([
            'hook-name' => self::getActionSchedulerJobName(),
            'unique' => true
        ]);
    }

    /**
     * @since 1.2.0
     */
    public function scheduleJob(): void
    {
        ActionScheduler::scheduleAsyncAction($this->getJob());
    }

    /**
     * @since 1.2.0
     */
    public static function getActionSchedulerJobName(): string
    {
        return Constants::PLUGIN_SLUG . '_delete_test_mode_data';
    }
}
