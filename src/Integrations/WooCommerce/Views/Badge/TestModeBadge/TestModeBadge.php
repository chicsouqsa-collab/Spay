<?php

/**
 * This class is responsible for render the Test Mode badge.
 *
 * @package StellarPay\Integrations\WooCommerce\Views\Badge\TestModeBadge
 * @since 1.7.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Views\Badge\TestModeBadge;

use StellarPay\Core\Contracts\View;
use StellarPay\Core\EnqueueScript;
use StellarPay\Core\Exceptions\BindingResolutionException;

/**
 * @since 1.7.0
 */
class TestModeBadge extends View
{
    /**
     * @since 1.7.0
     */
    protected ?string $tip = null;

    /**
     * @since 1.7.0
     */
    protected bool $hasMarginLeft = false;

    /**
     * @since 1.7.0
     * @throws BindingResolutionException
     */
    public function enqueueAssets()
    {
        $scriptId = 'stellarpay-test-mode-badge';

        (new EnqueueScript($scriptId, "/build/$scriptId.js"))->loadStyle()->enqueueStyle();
    }

    /**
     * @since 1.7.0
     */
    public function getHTML(): string
    {

        $classes = "stellarpay-test-mode-badge__element";

        if ($this->hasMarginLeft) {
            $classes .= " stellarpay-test-mode-badge__element--ml";
        }

        ob_start();
        ?>
        <span class="stellarpay-test-mode-badge">
            <span
                class="<?php echo esc_attr($classes) ?>"
            >
                <?php echo esc_html__('Test Mode', 'stellarpay'); ?><?php $this->helpToolTip();?>
            </span>
        </span>
        <?php
        return ob_get_clean();
    }

    /**
     * @since 1.7.0
     */
    protected function helpToolTip()
    {
        if (!$this->tip) {
            return;
        }

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo wc_help_tip($this->tip);
    }

    /**
     * @since 1.7.0
     */
    public function withHelpToolTip(string $tip): self
    {
        $this->tip = $tip;

        return $this;
    }

    /**
     * @since 1.7.0
     */
    public function addMarginLeft(): self
    {
        $this->hasMarginLeft = true;

        return $this;
    }
}
