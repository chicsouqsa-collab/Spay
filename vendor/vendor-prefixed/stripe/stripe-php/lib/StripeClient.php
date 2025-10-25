<?php
/**
 * @license MIT
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\Stripe;

/**
 * Client used to send requests to Stripe's API.
 *
 * @property \StellarPay\Vendors\Stripe\Service\OAuthService $oauth
 * // The beginning of the section generated from our OpenAPI spec
 * @property \StellarPay\Vendors\Stripe\Service\AccountLinkService $accountLinks
 * @property \StellarPay\Vendors\Stripe\Service\AccountService $accounts
 * @property \StellarPay\Vendors\Stripe\Service\AccountSessionService $accountSessions
 * @property \StellarPay\Vendors\Stripe\Service\ApplePayDomainService $applePayDomains
 * @property \StellarPay\Vendors\Stripe\Service\ApplicationFeeService $applicationFees
 * @property \StellarPay\Vendors\Stripe\Service\Apps\AppsServiceFactory $apps
 * @property \StellarPay\Vendors\Stripe\Service\BalanceService $balance
 * @property \StellarPay\Vendors\Stripe\Service\BalanceTransactionService $balanceTransactions
 * @property \StellarPay\Vendors\Stripe\Service\Billing\BillingServiceFactory $billing
 * @property \StellarPay\Vendors\Stripe\Service\BillingPortal\BillingPortalServiceFactory $billingPortal
 * @property \StellarPay\Vendors\Stripe\Service\ChargeService $charges
 * @property \StellarPay\Vendors\Stripe\Service\Checkout\CheckoutServiceFactory $checkout
 * @property \StellarPay\Vendors\Stripe\Service\Climate\ClimateServiceFactory $climate
 * @property \StellarPay\Vendors\Stripe\Service\ConfirmationTokenService $confirmationTokens
 * @property \StellarPay\Vendors\Stripe\Service\CountrySpecService $countrySpecs
 * @property \StellarPay\Vendors\Stripe\Service\CouponService $coupons
 * @property \StellarPay\Vendors\Stripe\Service\CreditNoteService $creditNotes
 * @property \StellarPay\Vendors\Stripe\Service\CustomerService $customers
 * @property \StellarPay\Vendors\Stripe\Service\CustomerSessionService $customerSessions
 * @property \StellarPay\Vendors\Stripe\Service\DisputeService $disputes
 * @property \StellarPay\Vendors\Stripe\Service\Entitlements\EntitlementsServiceFactory $entitlements
 * @property \StellarPay\Vendors\Stripe\Service\EphemeralKeyService $ephemeralKeys
 * @property \StellarPay\Vendors\Stripe\Service\EventService $events
 * @property \StellarPay\Vendors\Stripe\Service\ExchangeRateService $exchangeRates
 * @property \StellarPay\Vendors\Stripe\Service\FileLinkService $fileLinks
 * @property \StellarPay\Vendors\Stripe\Service\FileService $files
 * @property \StellarPay\Vendors\Stripe\Service\FinancialConnections\FinancialConnectionsServiceFactory $financialConnections
 * @property \StellarPay\Vendors\Stripe\Service\Forwarding\ForwardingServiceFactory $forwarding
 * @property \StellarPay\Vendors\Stripe\Service\Identity\IdentityServiceFactory $identity
 * @property \StellarPay\Vendors\Stripe\Service\InvoiceItemService $invoiceItems
 * @property \StellarPay\Vendors\Stripe\Service\InvoiceRenderingTemplateService $invoiceRenderingTemplates
 * @property \StellarPay\Vendors\Stripe\Service\InvoiceService $invoices
 * @property \StellarPay\Vendors\Stripe\Service\Issuing\IssuingServiceFactory $issuing
 * @property \StellarPay\Vendors\Stripe\Service\MandateService $mandates
 * @property \StellarPay\Vendors\Stripe\Service\PaymentIntentService $paymentIntents
 * @property \StellarPay\Vendors\Stripe\Service\PaymentLinkService $paymentLinks
 * @property \StellarPay\Vendors\Stripe\Service\PaymentMethodConfigurationService $paymentMethodConfigurations
 * @property \StellarPay\Vendors\Stripe\Service\PaymentMethodDomainService $paymentMethodDomains
 * @property \StellarPay\Vendors\Stripe\Service\PaymentMethodService $paymentMethods
 * @property \StellarPay\Vendors\Stripe\Service\PayoutService $payouts
 * @property \StellarPay\Vendors\Stripe\Service\PlanService $plans
 * @property \StellarPay\Vendors\Stripe\Service\PriceService $prices
 * @property \StellarPay\Vendors\Stripe\Service\ProductService $products
 * @property \StellarPay\Vendors\Stripe\Service\PromotionCodeService $promotionCodes
 * @property \StellarPay\Vendors\Stripe\Service\QuoteService $quotes
 * @property \StellarPay\Vendors\Stripe\Service\Radar\RadarServiceFactory $radar
 * @property \StellarPay\Vendors\Stripe\Service\RefundService $refunds
 * @property \StellarPay\Vendors\Stripe\Service\Reporting\ReportingServiceFactory $reporting
 * @property \StellarPay\Vendors\Stripe\Service\ReviewService $reviews
 * @property \StellarPay\Vendors\Stripe\Service\SetupAttemptService $setupAttempts
 * @property \StellarPay\Vendors\Stripe\Service\SetupIntentService $setupIntents
 * @property \StellarPay\Vendors\Stripe\Service\ShippingRateService $shippingRates
 * @property \StellarPay\Vendors\Stripe\Service\Sigma\SigmaServiceFactory $sigma
 * @property \StellarPay\Vendors\Stripe\Service\SourceService $sources
 * @property \StellarPay\Vendors\Stripe\Service\SubscriptionItemService $subscriptionItems
 * @property \StellarPay\Vendors\Stripe\Service\SubscriptionService $subscriptions
 * @property \StellarPay\Vendors\Stripe\Service\SubscriptionScheduleService $subscriptionSchedules
 * @property \StellarPay\Vendors\Stripe\Service\Tax\TaxServiceFactory $tax
 * @property \StellarPay\Vendors\Stripe\Service\TaxCodeService $taxCodes
 * @property \StellarPay\Vendors\Stripe\Service\TaxIdService $taxIds
 * @property \StellarPay\Vendors\Stripe\Service\TaxRateService $taxRates
 * @property \StellarPay\Vendors\Stripe\Service\Terminal\TerminalServiceFactory $terminal
 * @property \StellarPay\Vendors\Stripe\Service\TestHelpers\TestHelpersServiceFactory $testHelpers
 * @property \StellarPay\Vendors\Stripe\Service\TokenService $tokens
 * @property \StellarPay\Vendors\Stripe\Service\TopupService $topups
 * @property \StellarPay\Vendors\Stripe\Service\TransferService $transfers
 * @property \StellarPay\Vendors\Stripe\Service\Treasury\TreasuryServiceFactory $treasury
 * @property \StellarPay\Vendors\Stripe\Service\V2\V2ServiceFactory $v2
 * @property \StellarPay\Vendors\Stripe\Service\WebhookEndpointService $webhookEndpoints
 * // The end of the section generated from our OpenAPI spec
 */
class StripeClient extends BaseStripeClient
{
    /**
     * @var \StellarPay\Vendors\Stripe\Service\CoreServiceFactory
     */
    private $coreServiceFactory;

    public function __get($name)
    {
        return $this->getService($name);
    }

    public function getService($name)
    {
        if (null === $this->coreServiceFactory) {
            $this->coreServiceFactory = new \StellarPay\Vendors\Stripe\Service\CoreServiceFactory($this);
        }

        return $this->coreServiceFactory->getService($name);
    }
}
