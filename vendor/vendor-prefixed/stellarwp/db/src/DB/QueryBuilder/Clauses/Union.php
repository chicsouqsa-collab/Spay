<?php
/**
 * @license GPL-2.0
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\StellarWP\DB\QueryBuilder\Clauses;

use StellarPay\Vendors\StellarWP\DB\QueryBuilder\QueryBuilder;

/**
 * @since 1.0.0
 */
class Union {
	/**
	 * @var QueryBuilder
	 */
	public $builder;

	/**
	 * @var bool
	 */
	public $all = false;

	/**
	 * @param  QueryBuilder  $builder
	 * @param  bool  $all
	 */
	public function __construct( $builder, $all = false ) {
		$this->builder = $builder;
		$this->all     = $all;
	}
}
