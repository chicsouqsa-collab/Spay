<?php

/**
 * This class responsible to provide view for deactivation modal.
 *
 * Deactivation modal displays when admin click on plugin deactivation link.
 *
 * @package StellarPay\PluginSetup\Views\DeactivationModal
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\PluginSetup\Views\DeactivationModal;

use StellarPay\Core\Contracts\View;

/**
 * @since 1.0.0
 */
class DeactivationModal extends View
{
    /**
     * @since 1.0.0
     */
    public function getHTML(): string
    {
        ob_start();
        ?>
        <div
            id="stellarpay-deactivation-modal"
            class="stellarpay-deactivation-modal"
            style="position:relative; z-index: 100001;"
        >
            <div class="sp-hidden sp-overflow-y-auto sp-overflow-x-hidden sp-fixed sp-top-0 sp-right-0 sp-left-0 sp-z-50 sp-justify-center sp-items-center sp-w-full md:sp-inset-0 h-[calc(100%-1rem)] sp-max-h-full sp-bg-gray-500 sp-bg-opacity-75">
                <div class="sp-relative sp-p-4 sp-w-full sp-max-w-2xl sp-max-h-full">
                    <div class="sp-relative sp-bg-white sp-shadow">
                        <!-- Modal header -->
                        <div class="sp-flex sp-items-center sp-justify-between sp-px-4 sp-bg-slate-50 sp-border-b sp-border-slate-300 sp-border-solid sp-border-t-0 sp-border-l-0 sp-border-r-0">
                            <h3 class="sp-text-xl sp-font-normal sp-text-gray-900">
                                <?php esc_html_e('StellarPay Deactivation', 'stellarpay'); ?>
                            </h3>
                        </div>
                        <!-- Modal body -->
                        <div class="sp-p-4 md:sp-p-5 sp-space-y-4">
                            <div class="sp-p-4 sp-bg-amber-50 sp-border sp-border-yellow-200 sp-border-solid">
                                <div class="sp-flex sp-items-center sp-mb-4">
                                    <label class="sp-text-sm sp-text-gray-800 sp-cursor-pointer">
                                        <input
                                            type="checkbox"
                                            name="delete_all_stellarpay_data"
                                            class="stellarpay-deactivation-modal__delete-all-data-field sp-w-4 sp-h-4"
                                        />
                                        <?php esc_html_e('Would you like to delete all StellarPay data?', 'stellarpay'); ?>
                                    </label>
                                </div>

                                <p class="sp-text-sm sp-text-gray-500 sp-mb-0">
                                    <?php
                                    esc_html_e(
                                        'By default, the StellarPay options and database entries are not deleted when you deactivate the plugin. Check this option if you are completely deleting StellarPay from your website and want those items removed. Note: This will permanently delete all StellarPay data from your database.',
                                        'stellarpay'
                                    );
                                    ?>
                                </p>
                            </div>
                        </div>
                        <!-- Modal footer -->
                        <div class="sp-flex sp-items-center sp-justify-between sp-p-4 sp-border-t sp-border-gray-200 sp-bg-slate-50 sp-border-slate-300 sp-border-solid sp-border-b-0 sp-border-l-0 sp-border-r-0">
                            <button type="button" class="button stellarpay-deactivation-modal__skip-deactivate-button">
                                <?php esc_html_e('Skip and Deactivate', 'stellarpay'); ?>
                            </button>
                            <div>
                                <button
                                    type="button"
                                    class="button button-secondary stellarpay-deactivation-modal__cancel-button"
                                >
                                    <?php esc_html_e('Cancel', 'stellarpay'); ?>
                                </button>
                                <button
                                    type="button"
                                    class="button button-primary sp-ml-3 stellarpay-deactivation-modal__submit-deactivate-button"
                                >
                                    <?php esc_html_e('Submit and Deactivate', 'stellarpay'); ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}
