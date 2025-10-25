<?php

/**
 * This class uses as contract for data transfer objects
 *
 * @since 1.2.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Contracts;

use StellarPay\Core\Exceptions\Primitives\InvalidPropertyException;
use StellarPay\Core\Support\Str;
use BadMethodCallException;

/**
 * @since 1.2.0
 */
abstract class DataTransferObjects
{
    /**
     * @since 1.2.0
     * @throws InvalidPropertyException
     */
    public function __get($name)
    {
        if (property_exists($this, $name)) {
            $fn = 'get' . Str::studly($name);
            return $this->$name;
        }

        throw new InvalidPropertyException(esc_html("Property $name does not exist"));
    }

    /**
     * Adds support for get{Property} methods.
     *
     * @since 1.2.0
     */
    public function __call($name, $arguments)
    {
        $exceptionMessage = "Method $name does not exist";

        if (strpos($name, 'get') !== 0) {
            throw new BadMethodCallException(esc_html($exceptionMessage));
        }

        $property = Str::lcfirst(Str::after($name, 'get'));

        if (empty($property)) {
            throw new BadMethodCallException(esc_html($exceptionMessage));
        }

        if (!property_exists($this, $property)) {
            throw new BadMethodCallException(esc_html($exceptionMessage));
        }

        return $this->$property;
    }
}
