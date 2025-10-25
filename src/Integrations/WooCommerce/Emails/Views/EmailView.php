<?php

/**
 * This class is a contract that used for email view.
 *
 * @package StellarPay\Integrations\WooCommerce\Emails\Views
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Emails\Views;

use StellarPay\Subscriptions\Models\Subscription;
use WC_Email;
use WC_Order;

/**
 * @since 1.0.0
 */
abstract class EmailView
{
    /**
     * @since 1.0.0
     */
    protected WC_Order $order;

    /**
     * @since 1.0.0S
     */
    protected Subscription $subscription;

    /**
     * @since 1.0.0
     */
    protected bool $plainText = false;

    /**
     * @since 1.0.0
     */
    protected string $additionalContent;

    /**
     * @since 1.0.0
     */
    protected string $emailHeading;

    /**
     * @since 1.0.0
     */
    protected bool $isAdminEmail;

    /**
     * @since 1.0.0
     */
    protected WC_Email $email;

    /**
     * @since 1.0.0
     */
    protected string $storeName;

    /**
     * @since 1.0.0
     */
    public function __construct(WC_Order $order, Subscription $subscription)
    {
        $this->order = $order;
        $this->subscription = $subscription;
    }

    /**
     * @since 1.0.0
     */
    public function getContent(): string
    {
        return $this->plainText ? $this->getPlainTextContent() : $this->getHTMLContent();
    }

    /**
     * @since 1.0.0
     */
    abstract protected function getPlainTextContent(): string;

    /**
     * @since 1.0.0
     */
    abstract protected function getHTMLContent(): string;

    /**
     * @since 1.0.0
     */
    public function setEmail(WC_Email $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @since 1.0.0
     *
     * @return $this
     */
    public function setPlainText(bool $plainText): self
    {
        $this->plainText =  $plainText;

        return $this;
    }

    /**
     * @since 1.0.0
     */
    public function setIsAdminEmail(bool $isAdminEmail): self
    {
        $this->isAdminEmail = $isAdminEmail;

        return $this;
    }

    /**
     * @since 1.0.0
     */
    public function setAdditionalContent(string $content): self
    {
        $this->additionalContent = $content;

        return $this;
    }

    /**
     * @since 1.0.0
     */
    public function setEmailHeading(string $emailHeading): self
    {
        $this->emailHeading = $emailHeading;

        return $this;
    }

    /**
     * @since 1.0.0
     */
    public function setStoreName(string $storeName): self
    {
        $this->storeName = $storeName;

        return $this;
    }

    /**
     * @since 1.4.0
     */
    protected function doActionWoocommerceEmailOrderMeta(): void
    {
        do_action('woocommerce_email_order_meta', $this->order, $this->isAdminEmail, $this->plainText, $this->email, $this->subscription);
    }
}
