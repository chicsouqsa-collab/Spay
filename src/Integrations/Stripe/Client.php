<?php

/**
 * Stripe HTTP Client.
 *
 * This class is responsible for interacting with the Stripe API.
 *
 * @package StellarPay/PaymentGateways/Stripe
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\Stripe;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\Stripe\Contracts\PaymentGatewayInterface;
use StellarPay\Integrations\Stripe\Traits\HandlesCharge;
use StellarPay\Integrations\Stripe\Traits\HandlesCustomer;
use StellarPay\Integrations\Stripe\Traits\HandlesInvoice;
use StellarPay\Integrations\Stripe\Traits\HandlesPaymentIntent;
use StellarPay\Integrations\Stripe\Traits\HandlesPaymentMethod;
use StellarPay\Integrations\Stripe\Traits\HandlesPrice;
use StellarPay\Integrations\Stripe\Traits\HandlesProduct;
use StellarPay\Integrations\Stripe\Traits\HandlesRefund;
use StellarPay\Integrations\Stripe\Traits\HandlesStripeAccount;
use StellarPay\Integrations\Stripe\Traits\HandlesSubscription;
use StellarPay\Integrations\Stripe\Traits\HandlesSubscriptionSchedule;
use StellarPay\Integrations\Stripe\Traits\HandlesWebhook;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\Repositories\SettingRepository;
use StellarPay\Vendors\Stripe\Service\FileService;
use StellarPay\Vendors\Stripe\StripeClient;

use function StellarPay\Core\container;

/**
 * Class Client
 *
 *  You cannot use the Stripe client before a connection is established.
 *  It will cause an error.
 *  You should check if the connection is established before using the client.
 *
 * @since 1.0.0
 */
class Client implements PaymentGatewayInterface
{
    use HandlesStripeAccount;
    use HandlesPaymentIntent;
    use HandlesCustomer;
    use HandlesRefund;
    use HandlesPaymentMethod;
    use HandlesWebhook;
    use HandlesProduct;
    use HandlesPrice;
    use HandlesSubscription;
    use HandlesSubscriptionSchedule;
    use HandlesInvoice;
    use HandlesCharge;

    /**
     * Stripe API version.
     *
     * @since 1.0.0
     */
    public const STRIPE_API_VERSION = '2024-06-20';

    /**
     * @since 1.0.0
     */
    private StripeClient $client;

    /**
     * @since 1.0.0
     */
    public function __construct($secretKey, $accountId)
    {
        $this->client = new StripeClient([
            'api_key' => $secretKey,
            'stripe_account' => $accountId,
            'stripe_version' => self::STRIPE_API_VERSION,
        ]);
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException|InvalidPropertyException|Exception
     */
    public static function getClient(PaymentGatewayMode $paymentGatewayMode): self
    {
        $account = container(AccountRepository::class)->getAccount($paymentGatewayMode);

        $secretKey = $account->getSecretKey();

        return new self($secretKey, $account->getAccountId());
    }

    /**
     * @todo: Temp function to get file from the Stripe API. Create a service for this
     * @since 1.0.0
     */
    public function getFile(): FileService
    {
        return $this->client->files;
    }

    /**
     * @since 1.0.0
     *
     * Note: Use this function in context where a Stripe account is connected.
     * @throws BindingResolutionException|\Exception
     */
    public static function getStripeDashboardLink(string $path = '', PaymentGatewayMode $paymentGatewayMode = null): string
    {
        $paymentGatewayMode = $paymentGatewayMode ?? container(SettingRepository::class)->getPaymentGatewayMode();
        $account = container(AccountRepository::class)->getAccount($paymentGatewayMode);
        $path = $path && 0 !== strpos($path, '/') ? '/' . $path : $path;

        return sprintf(
            'https://dashboard.stripe.com/b/%1$s%2$s%3$s',
            $account->getAccountId(),
            $paymentGatewayMode->isLive() ? '' : '/test',
            $path
        );
    }
}
