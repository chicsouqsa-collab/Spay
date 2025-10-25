<?php

/**
 * OnBoardingRedirectController
 *
 * This class is responsible for handling the onboarding redirect from Stripe.
 *
 * @package StellarPay\PaymentGateways\Stripe\Controllers
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Controllers;

use StellarPay\Core\Contracts\Controller;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Request;
use StellarPay\Core\Traits\ControlControllerProcessFlowUtilities;
use StellarPay\Core\ValueObjects\PaymentGatewayMode;
use StellarPay\Integrations\StellarCommerce\Client as StellarCommerceClient;
use StellarPay\Integrations\Stripe\Client;
use StellarPay\Integrations\Stripe\Exceptions\StripeAPIException;
use StellarPay\PaymentGateways\Stripe\Actions\CreateAndValidatePaymentMethodDomain;
use StellarPay\PaymentGateways\Stripe\Actions\RemoveStripeAccountConnection;
use StellarPay\PaymentGateways\Stripe\Actions\SaveConnectedAccount;
use StellarPay\PaymentGateways\Stripe\Actions\CreateWebhook;
use StellarPay\PaymentGateways\Stripe\Repositories\AccountRepository;
use StellarPay\PaymentGateways\Stripe\Services\ServiceRegisterer;
use Throwable;
use TypeError;

use function StellarPay\Core\container;
use function StellarPay\Core\isWebsiteOnline;

/**
 * Class OnBoardingRedirectController
 *
 * @since 1.0.0
 */
class OnBoardingRedirectController extends Controller
{
    use ControlControllerProcessFlowUtilities;

    /**
     * @since 1.1.0
     */
    protected AccountRepository $accountRepository;

    /**
     * @since 1.1.0 add second param to constructor
     */
    public function __construct(Request $request, AccountRepository $accountRepository)
    {
        parent::__construct($request);

        $this->accountRepository = $accountRepository;
    }

    /**
     * @since 1.1.0 Handle exception to prevent fatal error. Refactor code to make testable.
     * @since 1.0.0
     *
     * @throws BindingResolutionException
     */
    public function __invoke(): void
    {
        $requestData = $this->request->sanitize($this->request->all());
        $isTestAccessToken = strpos($requestData['stripe_access_token'] ?? '', 'sk_test_') !== false;

        $paymentGatewayMode = $isTestAccessToken
            ? PaymentGatewayMode::test()
            : PaymentGatewayMode::live();

        $nonceName = container(StellarCommerceClient::class)
            ->getRedirectUrlNonceActionName($paymentGatewayMode);

        // Authorization, nonce validation, and data validation.
        if (
            ! $this->request->hasPermission('manage_options')
            || ! $this->hasValidData($requestData) // phpcs:ignore
            || ! $this->request->hasValidNonce($nonceName)
        ) {
            if ($stripeErrorCode = $this->request->get('stripe_connect_error_code')) {
                $redirectURL = add_query_arg(
                    [
                        'connected' => 0,
                        'mode' => $this->request->get('mode'),
                        'stripe-error-code' => $stripeErrorCode
                    ],
                    admin_url('admin.php?page=stellarpay')
                );
                $this->redirectTo("$redirectURL#/onboarding-incomplete");
            }

            return;
        }

        // Load stripe client by current access token and account id.
        // This allows stripe service class to access the right client for api request.
        $stripeClient = new Client($requestData['stripe_access_token'], $requestData['stripe_account_id']);
        container()->singleton(Client::class, function () use ($stripeClient) {
                return $stripeClient;
        });

        // Stripe services register with a globally available Stripe client.
        // We need to re-register all stripe services, to a correct Stripe client.
        // So, whenever a container resolves dependencies for class, Stripe services will use a correct client.
        container(ServiceRegisterer::class)->register();

        $incompleteOnboardingPageURL = admin_url("admin.php?page=stellarpay&connected=0&mode={$paymentGatewayMode}#/onboarding-incomplete");
        try {
            $saveConnectedAccount = container(SaveConnectedAccount::class);
            $isAccountSaved = $saveConnectedAccount($requestData, $paymentGatewayMode);

            if ($isAccountSaved) {
                if (isWebsiteOnline()) {
                    $saveWebhook = container(CreateWebhook::class);
                    $saveWebhook($paymentGatewayMode);

                    $accountRepository = container(AccountRepository::class);
                    if (
                        $paymentGatewayMode->isLive()
                        || ! $accountRepository->isBothModesUseSameStripeAccount()
                    ) {
                        $createAndValidatePaymentMethodDomain = container(CreateAndValidatePaymentMethodDomain::class);
                        $createAndValidatePaymentMethodDomain($paymentGatewayMode);
                    }
                }

                // Redirect to the correct page based on the paymentGatewayMode.
                if ($paymentGatewayMode->isTest()) {
                    $this->redirectTo(admin_url('admin.php?page=stellarpay&connected=1#/settings/development'));
                } else {
                    $this->redirectTo(admin_url("admin.php?page=stellarpay&connected=1#/onboarding-complete"));
                }
            } else {
                $this->redirectTo($incompleteOnboardingPageURL);
            }
        } catch (Throwable $exception) {
            $this->saveError($exception);

            // Remove an account on failure.
            try {
                $removeStripeAccountConnection = container(RemoveStripeAccountConnection::class);
                $removeStripeAccountConnection($paymentGatewayMode);
            } catch (\Exception $e) {
            }

            $this->redirectTo($incompleteOnboardingPageURL);
        }
    }

    /**
     * @since 1.0.0
     */
    private function hasValidData(array $data): bool
    {
        return ! empty($data['stripe_account_id'])
               && ! empty($data['stripe_publishable_key'])
               && ! empty($data['stripe_access_token']);
    }

    /**
     * @since 1.1.0
     */
    private function saveError(Throwable $exception)
    {
        $errorMessage = '';

        if ($exception instanceof StripeAPIException) {
            $errorMessage = $exception->getMessage();
        }

        // TypeError will occur when required information is missing in a Stripe Account object, which generally happens if an account is incomplete.
        if ($exception instanceof TypeError) {
            $errorMessage = esc_html__('Your Stripe profile is not ready to accept payments. Please complete your profile and try again.', 'stellarpay');
        }

        if ($errorMessage) {
            $this->accountRepository->saveOnboardingError($errorMessage);
        }
    }
}
