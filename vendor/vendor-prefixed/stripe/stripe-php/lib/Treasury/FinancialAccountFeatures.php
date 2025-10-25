<?php

// File generated from our OpenAPI spec

namespace StellarPay\Vendors\Stripe\Treasury;

/**
 * Encodes whether a FinancialAccount has access to a particular Feature, with a <code>status</code> enum and associated <code>status_details</code>.
 * Stripe or the platform can control Features via the requested field.
 *
 * @property string $object String representing the object's type. Objects of the same type share the same value.
 * @property null|\StellarPay\Vendors\Stripe\StripeObject $card_issuing Toggle settings for enabling/disabling a feature
 * @property null|\StellarPay\Vendors\Stripe\StripeObject $deposit_insurance Toggle settings for enabling/disabling a feature
 * @property null|\StellarPay\Vendors\Stripe\StripeObject $financial_addresses Settings related to Financial Addresses features on a Financial Account
 * @property null|\StellarPay\Vendors\Stripe\StripeObject $inbound_transfers InboundTransfers contains inbound transfers features for a FinancialAccount.
 * @property null|\StellarPay\Vendors\Stripe\StripeObject $intra_stripe_flows Toggle settings for enabling/disabling a feature
 * @property null|\StellarPay\Vendors\Stripe\StripeObject $outbound_payments Settings related to Outbound Payments features on a Financial Account
 * @property null|\StellarPay\Vendors\Stripe\StripeObject $outbound_transfers OutboundTransfers contains outbound transfers features for a FinancialAccount.
 *
 * @license MIT
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */
class FinancialAccountFeatures extends \StellarPay\Vendors\Stripe\ApiResource
{
    const OBJECT_NAME = 'treasury.financial_account_features';
}
