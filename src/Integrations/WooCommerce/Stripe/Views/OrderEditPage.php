<?php

/**
 * This file is responsible for modifying the WooCommerce Order Details screen.
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Views;

use StellarPay\AdminDashboard\DataTransferObjects\DashboardDTO;
use StellarPay\Core\Constants;
use StellarPay\Core\EnqueueScript;
use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Core\Exceptions\Primitives\Exception;
use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\OrderRepository;
use WC_Order;

/**
 * Class OrderEditPage
 *
 * @since 1.3.0
 */
class OrderEditPage
{
    /**
     * @since 1.3.0
     */
    private OrderRepository $orderRepository;

    /**
     * @since 1.3.0
     */
    private DashboardDTO $dashboardDTO;

    /**
     * Class constructor.
     *
     * @since 1.3.0
     */
    public function __construct(
        OrderRepository $orderRepository,
        DashboardDTO $dashboardDTO
    ) {
        $this->orderRepository = $orderRepository;
        $this->dashboardDTO = $dashboardDTO;
    }

    /**
     * @param WC_Order $order
     *
     * @throws BindingResolutionException
     * @throws Exception
     * @throws InvalidPropertyException
     */
    public function enqueueOrderScreenAdminScripts(WC_Order $order): void
    {
        if (\StellarPay\Integrations\WooCommerce\Stripe\Constants::GATEWAY_ID !== $order->get_payment_method('edit')) {
            return;
        }

        if ($this->orderRepository->isTestOrder($order)) {
            return;
        }

        $prefix = Constants::PLUGIN_SLUG;

        $ordersScreenScript = (new EnqueueScript(
            $prefix . '-orders-screen',
            '/build/stellarpay-woocommerce-orders-screen.js'
        ));

        $data = $this->getDashboardData();
        $data['transactionID'] = $order->get_transaction_id();

        $ordersScreenScript
            ->loadInFooter()
            ->loadStyle(['wp-admin', 'wp-components'])
            ->registerLocalizeData('stellarPayDashboardData', $data)
            ->registerTranslations()
            ->enqueue();
    }

    /**
     * Add the React DOM root for the payment details.
     *
     * @since 1.3.0
     */
    public function addPaymentDetailsReactDomRoot()
    {
        echo '<div id="stellarpay-stripe-payment-details-root"></div>';
    }


    /**
     * Get the initial state data for hydrating the React UI.
     *
     * @since 1.0.0
     *
     * @throws BindingResolutionException|InvalidPropertyException|Exception
     */
    protected function getDashboardData(): array
    {
        $invokable = $this->dashboardDTO;
        return $invokable();
    }
}
