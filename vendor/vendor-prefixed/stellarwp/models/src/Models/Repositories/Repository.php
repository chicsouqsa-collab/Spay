<?php
/**
 * @license GPL-3.0-or-later
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\StellarWP\Models\Repositories;

use StellarPay\Vendors\StellarWP\Models\ModelQueryBuilder;

abstract class Repository {
	/**
	 * Prepare a query builder for the repository.
	 *
	 * @since 1.0.0
	 *
	 * @return ModelQueryBuilder
	 */
	abstract function prepareQuery() : ModelQueryBuilder;
}
