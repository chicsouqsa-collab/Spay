<?php
/**
 * @license GPL-3.0-or-later
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\StellarWP\Models\Repositories\Contracts;

use StellarPay\Vendors\StellarWP\Models\Contracts\Model;

interface Insertable {
	/**
	 * Inserts a model record.
	 *
	 * @since 1.0.0
	 *
	 * @param Model $model
	 *
	 * @return Model
	 */
	public function insert( Model $model ) : Model;
}
