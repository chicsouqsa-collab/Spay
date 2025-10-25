<?php

/**
 * This class is responsible for removing payment methods in test mode.
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\AdminDashboard\Actions;

use StellarPay\AdminDashboard\Actions\Contracts\TestDataDeletionRule;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use WC_Payment_Tokens;

use function StellarPay\Core\dbMetaKeyGenerator;

/**
 * @since 1.2.0
 */
class DeleteTestModePaymentMethods implements TestDataDeletionRule
{
    /**
     * @since 1.2.0
     */
    public function __invoke(): void
    {
        while (true) {
            $paymentMethods = WC_Payment_Tokens::get_tokens(['limit' => 10,]);

            if (empty($paymentMethods)) {
                break;
            }

            foreach ($paymentMethods as $paymentMethod) {
                $mode = $paymentMethod->get_meta(dbMetaKeyGenerator('payment_method_mode', true));

                if (PaymentGatewayMode::TEST !== $mode) {
                    continue;
                }

                $paymentMethod->delete(true);
            }
        }
    }
}
