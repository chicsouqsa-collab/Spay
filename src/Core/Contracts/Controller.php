<?php

/**
 * Controller Contract.
 *
 * This file provides contract for controller.
 *
 * @since 1.0.0
 */

declare(strict_types=1);

namespace StellarPay\Core\Contracts;

use StellarPay\Core\Request;

/**
 * Class Controller.
 *
 * @since 1.0.0
 */
abstract class Controller
{
    /**
     * @since 1.0.0
     */
    protected Request $request;

    /**
     * Class constructor.
     *
     * @since 1.0.0
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * This function returns the sanitized data from the request.
     *
     * @since 1.0.0
     */
    protected function getRequestData(): ?array
    {
        return $this->request->sanitize($this->request->all());
    }
}
