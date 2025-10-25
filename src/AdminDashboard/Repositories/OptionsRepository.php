<?php

/**
 * This class is responsible for managing plugin options.
 * It provides methods for getting, setting, and deleting options.
 *
 * @package StellarPay/AdminDashboard/Repositories
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\Repositories;

use StellarPay\Core\Constants;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\Exceptions\Primitives\RuntimeException;
use StellarPay\Core\ValueObjects\TimePeriod;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;

use function StellarPay\Core\container;

/**
 * OptionsRepository class.
 *
 * @package StellarPay\AdminDashboard\Repositories
 * @since 1.0.0
 */
class OptionsRepository
{
    /**
     * @since 1.0.0
     */
    public const OPTION_NAME_STRIPE_STATEMENT_DESCRIPTOR = 'stripe-payment-statement-descriptor';

    /**
     * @since 1.3.0
     */
    public const WEBHOOK_EVENTS_DATA_RETENTION_PERIOD = 'webhook-events-data-retention-period';

    /**
     * @since 1.0.0
     */
    public const PAYMENT_OPTION_TITLE_GATEWAY_TITLE = 'stripe-payment-gateway-title';

    /**
     * @since 1.6.0
     */
    public const FEE_RECOVERY = 'fee-recovery';

    /**
     * @since 1.6.0
     */
    public const PERCENTAGE_FEE_RECOVERY = 'percentage-fee-recovery';

    /**
     * @since 1.6.0
     */
    public const FLAT_AMOUNT_FEE_RECOVERY = 'flat-amount-fee-recovery';

    /**
     * @since 1.6.0
     */
    public const LINE_ITEM_DESCRIPTOR_FEE_RECOVERY = 'item-line-descriptor-fee-recovery';

    /**
     * This is the key used to store the plugin options in the database.
     *
     * @var string
     *
     * @since 1.0.0
     */
    private string $optionName = Constants::PLUGIN_SLUG . '_stripe_options';

    /**
     * Checks whether a value is set for the given option.
     *
     * @since 1.0.0
     */
    public function has(string $option): bool
    {
        $options = get_option($this->optionName);

        return isset($options[$option]);
    }

    /**
     * Get a single option, or all if no key is provided.
     *
     * @param string $key Option key.
     * @param mixed $default Default value.
     *
     * @since 1.0.0
     *
     * @return array|string|bool
     */
    public function get(string $key = '', $default = false)
    {
        $options = get_option($this->optionName);

        if (! $options || ! is_array($options)) {
            $options = [];
        }

        if ($key) {
            return $options[$key] ?? $default;
        }

        return $options;
    }

    /**
     * @since 1.6.0 Use TimePeriod value object.
     * @since 1.3.0
     *
     * @throws RuntimeException|InvalidPropertyException|BindingResolutionException|Exception
     */
    public function getWebhookEventsDataRetentionDate(): ?\DateTime
    {
        $savedTimePeriod = $this->getAll()[self::WEBHOOK_EVENTS_DATA_RETENTION_PERIOD];

        if (empty($savedTimePeriod)) {
            return null;
        }

        $timePeriod = TimePeriod::from($savedTimePeriod);

        return $timePeriod->getDateTime();
    }

    /**
     * Update a single option.
     *
     * @param string $key Option key.
     * @param mixed $value Option value.
     *
     * @since 1.0.0
     */
    public function set(string $key, $value): bool
    {
        $options = $this->get();
        $options[$key] = $value;

        return update_option($this->optionName, $options);
    }

    /**
     * Delete a single option.
     *
     * @param string $key Option key.
     *
     * @since 1.0.0
     */
    public function delete(string $key): ?bool
    {
        $options = $this->get();

        if (!isset($options[$key])) {
            return null;
        }

        unset($options[$key]);

        return update_option($this->optionName, $options);
    }

    /**
     * Get all options.
     *
     * @since 1.3.0 Add new setting
     * @since 1.0.0
     * @throws InvalidPropertyException|BindingResolutionException|Exception
     */
    public function getAll(): array
    {
        $defaultOptions = [
            'test-mode' => false,
            'whsec-local-key' => '',
            'whsec-local-key-enabled' => false,
            self::PAYMENT_OPTION_TITLE_GATEWAY_TITLE => esc_html__('Credit Card or Debit Card', 'stellarpay'),
            'payment-element-theme' => 'stripe',
            'payment-element-layout' => 'tabs',
            'payment-element-appearance' => null,
            self::OPTION_NAME_STRIPE_STATEMENT_DESCRIPTOR => '',
            'stripe-payment-statement-descriptor-enabled' => false,
            self::FEE_RECOVERY => false,
            self::WEBHOOK_EVENTS_DATA_RETENTION_PERIOD => TimePeriod::THREE_MONTH,
            self::PERCENTAGE_FEE_RECOVERY => '2.9',
            self::FLAT_AMOUNT_FEE_RECOVERY => '0.30',
            self::LINE_ITEM_DESCRIPTOR_FEE_RECOVERY => esc_html__('Processing Fee', 'stellarpay'),
        ];

        $account = container(AccountRepository::class);
        if ($account->isLiveModeConnected()) {
            $defaultOptions['stripe-payment-statement-descriptor'] = container(AccountRepository::class)->getAccount()->getStatementDescriptor();
        }

        // Get saved options and only select allowed options.
        $savedOptions = array_intersect_key(get_option($this->optionName, []), $defaultOptions);

        return array_merge($defaultOptions, $savedOptions);
    }

    /**
     * @since 1.6.0
     */
    public function isFeeRecoveryEnabled(): bool
    {
        return (bool) $this->get(self::FEE_RECOVERY);
    }

    /**
     * @since 1.6.0
     */
    public function getPercentageFeeRecovery(): float
    {
        $default = 0.0;
        $percentage = $this->get(self::PERCENTAGE_FEE_RECOVERY, 0.0);

        return is_numeric($percentage) && $percentage > 0 ? (float) $percentage : $default;
    }

    /**
     * @since 1.6.0
     */
    public function getFlatAmountFeeRecovery(): float
    {
        $default = 0.0;
        $flatAmount = $this->get(self::FLAT_AMOUNT_FEE_RECOVERY, 0.0);

        return is_numeric($flatAmount) && $flatAmount > 0 ? (float) $flatAmount : $default;
    }

    /**
     * @since 1.6.0
     */
    public function getLineItemDescriptorFeeRecovery(): string
    {
        $default = esc_html__('Processing Fee', 'stellarpay');
        $lineItemDescriptor = $this->get(self::LINE_ITEM_DESCRIPTOR_FEE_RECOVERY, $default);

        return empty($lineItemDescriptor) ? $default : $lineItemDescriptor;
    }
}
