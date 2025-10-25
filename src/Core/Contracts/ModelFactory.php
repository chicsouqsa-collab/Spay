<?php

/**
 * This class is contract to create a factory class for a modal
 *
 * @package StellarPay\Core\Contracts
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Contracts;

use StellarPay\Vendors\Faker\Factory;
use StellarPay\Vendors\Faker\Generator;

/**
 * @template M
 */
abstract class ModelFactory extends \StellarPay\Vendors\StellarWP\Models\ModelFactory
{
    /**
     * @since 1.0.0
     */
    protected Generator $faker;

    /**
     * @inerhitDoc
     * @since 1.0.0
     *
     * @return void
     */
    public function __construct(string $model)
    {
        parent::__construct($model);
        $this->faker = $this->withFaker();
    }

    /**
     * Get a new Faker instance.
     *
     * @since 1.0.0
     */
    protected function withFaker(): Generator
    {
        return Factory::create();
    }
}
