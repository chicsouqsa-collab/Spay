<?php

/**
 * This class is responsible to provide logic to create or update the Stripe price for the WooCommerce order.
 *
 * @package StellarPay\Integrations\WooCommerce\Stripe\Services
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Integrations\WooCommerce\Stripe\Services;

use StellarPay\Core\Exceptions\BindingResolutionException;
use StellarPay\Integrations\WooCommerce\Stripe\Repositories\ProductRepository;
use StellarPay\Integrations\WooCommerce\Stripe\Strategies\PriceDataStrategy;
use StellarPay\PaymentGateways\Stripe\DataTransferObjects\PriceDTO;
use StellarPay\PaymentGateways\Stripe\Services\PriceService as BasePriceService;
use StellarPay\Subscriptions\Models\Subscription;
use WC_Order;
use WC_Order_Item_Product;
use WC_Product_Variation;

use function StellarPay\Core\container;
use function wc_get_product;

/**
 * @since 1.8.0 Add support for product variable
 * @since 1.0.0
 */
class PriceService
{
    /**
     * @since 1.0.0
     */
    protected BasePriceService $priceService;

    /**
     * @since 1.0.0
     */
    protected ProductService $productService;

    /**
     * @since 1.0.0
     */
    protected ProductRepository $productRepository;

    /**
     * @since 1.0.0
     */
    public function __construct(BasePriceService $priceService, ProductService $productService, ProductRepository $productRepository)
    {
        $this->priceService = $priceService;
        $this->productService = $productService;
        $this->productRepository = $productRepository;
    }

    /**
     * @since 1.0.0
     * @throws BindingResolutionException
     */
    public function create(Subscription $subscription, WC_Order $order, WC_Order_Item_Product $orderItem): string
    {

        $productVariation = null;
        $product = $orderItem->get_product();

        if ($product instanceof WC_Product_Variation) {
            $productVariation = $product;
            $product = wc_get_product($productVariation->get_parent_id());
        }

        $stripeProductId = $this->productService->createOrUpdate($product);

        $priceDataStrategy = container(PriceDataStrategy::class);

        $priceDataStrategy
            ->setSubscription($subscription)
            ->setOrder($order)
            ->setOrderItem($orderItem)
            ->setProduct($product)
            ->setStripeProductId($stripeProductId);

        if ($productVariation instanceof WC_Product_Variation) {
            $priceDataStrategy->setProductVariation($productVariation);
        }

        $priceDTO = PriceDTO::fromPriceDataStrategy($priceDataStrategy);

        $stripePriceId = $this->productRepository->getStripePriceId($product, $priceDTO);

        if (empty($stripePriceId)) {
            $stripePriceId = $this->priceService
                ->createPrice($priceDTO)
                ->getId();

            // Save the stripe price id in the product meta.
            // This will be reused when a customer subscribes to the same product with the same price.
            $this->productRepository->setStripePriceId($product, $priceDTO, $stripePriceId);
        }

        return $stripePriceId;
    }
}
