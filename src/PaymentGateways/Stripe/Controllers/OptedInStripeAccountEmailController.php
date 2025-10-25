<?php

/**
 * This class is responsible to set up a cron job based on ajax request.
 *
 * @package StellarPay\PaymentGateways\Stripe\Controllers
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Controllers;

use StellarPay\Core\Contracts\Controller;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\PaymentGateways\Stripe\Actions\OptInStripeAccountEmail;
use StellarPay\Vendors\StellarWP\Validation\Validator;

/**
 * @since 1.0.0
 */
class OptedInStripeAccountEmailController extends Controller
{
    /**
     * @since 1.0.0
     */
    private const NONCE_ACTION_NAME = 'stellarpay_opted_in_stripe_account_email';

    /**
     * @since 1.0.0
     * @throws Exception
     */
    public function __invoke(): void
    {
        check_ajax_referer(self::NONCE_ACTION_NAME);

        if (false === current_user_can('manage_options')) {
            throw new Exception('Unauthorized request.');
        }

        $validator = new Validator($this->getValidateRules(), $this->request->all());
        $safeValues = $validator->validated();


        if (empty($safeValues)) {
            throw new Exception('Invalid request.');
        }

        // Cron job should wait for 5 minutes.
        $timestamp = time() + ( 5 * MINUTE_IN_SECONDS );
        wp_schedule_single_event(
            $timestamp,
            OptInStripeAccountEmail::CRON_JOB_NAME,
            [ $safeValues['email'] ]
        );

        wp_send_json_success();
    }

    /**
     * @since 1.0.0
     * @return array[]
     */
    private function getValidateRules(): array
    {
        return [
            'email' => [ 'required', 'email']
        ];
    }

    /**
     * @since 1.0.0
     * @return string
     */
    public static function getRequestURL(): string
    {
        return  add_query_arg([
            'action' => OptInStripeAccountEmail::CRON_JOB_NAME,
            '_wpnonce' => wp_create_nonce(self::NONCE_ACTION_NAME),
        ], admin_url('admin-ajax.php')) ;
    }
}
