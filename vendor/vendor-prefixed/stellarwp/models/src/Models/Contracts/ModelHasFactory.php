<?php
/**
 * @license GPL-3.0-or-later
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\StellarWP\Models\Contracts;

use StellarPay\Vendors\StellarWP\Models\ModelFactory;

/**
 * @since 1.0.0
 */
interface ModelHasFactory {
	/**
	 * @since 1.0.0
	 *
	 * @return ModelFactory
	 */
	public static function factory();
}
