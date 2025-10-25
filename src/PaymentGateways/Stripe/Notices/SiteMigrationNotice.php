<?php

/**
 * @package StellarPay\PaymentGateways\Stripe\Actions
 * @since 1.3.0
 */

declare(strict_types=1);

namespace StellarPay\PaymentGateways\Stripe\Notices;

use StellarPay\Core\Constants;
use StellarPay\Core\Request;
use StellarPay\PluginSetup\Environment;
use StellarPay\PluginSetup\Migrations\StoreHomeUrlInOptionTable;

/**
 * @since 1.3.0
 */
class SiteMigrationNotice
{
    /**
     * @since 1.3.0
     */
    protected Request $request;

    /**
     * @since 1.3.0
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @since 1.3.0
     * @return string
     */
    public function id(): string
    {
        return 'stellarpay_stripe_site_migration_notice';
    }

    /**
     * @since 1.3.0
     */
    public function getContent(): string
    {
        $actionsURL = $this->getActionsURL();
        $previousHomeUrl = base64_decode(get_option(StoreHomeUrlInOptionTable::OPTION_NAME, ''));
        $currentHomeUrl = get_home_url();

        $messagePart = sprintf(
            /* translators: 1: Previous domain name 2: New domain name */
            __('StellarPay has detected that your site has been moved from <code>%1$s</code> to <code>%2$s</code>. Your Stripe webhook endpoint is still pointing to the old domain.', 'stellarpay'),
            esc_url_raw($previousHomeUrl),
            esc_url_raw($currentHomeUrl)
        );

        ob_start();
        ?>
        <div style="padding: 15px 0 20px 0">
            <p style="max-width: 950px;margin: 0;padding: 0 0 10px 0;">
                <strong><?php esc_html_e('Domain Change Detected', 'stellarpay'); ?>:</strong> <?php echo $messagePart; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                <a href="https://links.stellarwp.com/stellarpay/docs/site-migrations" class="components-external-link" target="_blank" rel="external noreferrer noopener" style="text-decoration: none">
                    <span class="components-external-link__contents"><?php esc_html_e('Learn More', 'stellarpay'); ?></span>
                    <span class="components-external-link__icon" aria-label="(opens in a new tab)">↗</span>
                </a>
            </p>
            <strong><?php esc_html_e('What would you like to do?', 'stellarpay'); ?></strong>
            <ul style="margin-block-start: 5px;list-style: disc;padding-left: 14px;">
                <li><?php esc_html_e('Migrate the webhook endpoint to the new domain (recommended)', 'stellarpay'); ?></li>
                <li><?php esc_html_e('Create an additional webhook endpoint for the new domain and keep the existing endpoint', 'stellarpay'); ?></li>
                <li><?php esc_html_e('Keep the existing webhook endpoint on the old domain', 'stellarpay'); ?></li>
            </ul>
            <div style="display: flex;align-items: center;column-gap: 12px;">
                <button class="button-primary button" style="display: flex;align-items: center;" onclick="window.location.href='<?php echo esc_url($actionsURL['migrate-to-new-domain']); ?>'">
                    <span style="background: url(<?php echo esc_url(Constants::$PLUGIN_URL . '/build/images/arrow-path.svg'); ?>) center no-repeat; width: 24px; height: 24px; padding-right: 2px;position: relative;top: 2px"></span>
                    <span><?php esc_html_e('Migrate to new domain', 'stellarpay');?></span>
                </button>
                <button class="button-secondary button" onclick="window.location.href='<?php echo esc_url($actionsURL['set-up-both-domain']); ?>'">
                    <?php esc_html_e('Set up for both domains', 'stellarpay');?>
                </button>
                <a href="<?php echo esc_url($actionsURL['keep-existing-setup']); ?>" style="margin-left: 12px; text-decoration: none">
                    <?php esc_html_e('Keep existing setup', 'stellarpay'); ?>
                </a>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * @since 1.3.0
     */
    public function shouldShowNotice(): bool
    {
        $action = $this->request->get($this->getActionKey(), null);
        $allowedActions = array_keys($this->getActionsURL());

        if ($action) {
            return ! in_array($action, $allowedActions);
        }

        return Environment::isWebsiteMigrated();
    }

    /**
     * @since 1.3.0
     */
    public function getActionsURL(): array
    {
        return [
            'migrate-to-new-domain' => add_query_arg([
                $this->getActionKey() => 'migrate-to-new-domain'
            ]),
            'set-up-both-domain' => add_query_arg([
                $this->getActionKey() => 'set-up-both-domain'
            ]),
            'keep-existing-setup' => add_query_arg([
                $this->getActionKey() => 'keep-existing-setup'
            ])
        ];
    }

    /**
     * @since 1.3.0
     */
    public function getActionKey(): string
    {
        return Constants::slugPrefixed('-action');
    }
}
