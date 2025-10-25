<?php
/**
 * @license GPL-3.0-or-later
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\StellarWP\Models\Contracts;

use StellarPay\Vendors\StellarWP\Models\ModelQueryBuilder;

/**
 * @since 1.0.0
 */
interface ModelFromQueryBuilderObject {
	/**
	 * @since 1.0.0
	 *
	 * @param $object
	 *
	 * @return Model
	 */
	public static function fromQueryBuilderObject( $object );
}
