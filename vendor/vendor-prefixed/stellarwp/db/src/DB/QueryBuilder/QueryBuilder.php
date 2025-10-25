<?php
/**
 * @license GPL-2.0
 *
 * Modified by stellarwp using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace StellarPay\Vendors\StellarWP\DB\QueryBuilder;

use StellarPay\Vendors\StellarWP\DB\QueryBuilder\Concerns\Aggregate;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\Concerns\CRUD;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\Concerns\FromClause;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\Concerns\GroupByStatement;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\Concerns\HavingClause;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\Concerns\JoinClause;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\Concerns\LimitStatement;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\Concerns\MetaQuery;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\Concerns\OffsetStatement;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\Concerns\OrderByStatement;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\Concerns\SelectStatement;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\Concerns\TablePrefix;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\Concerns\UnionOperator;
use StellarPay\Vendors\StellarWP\DB\QueryBuilder\Concerns\WhereClause;

/**
 * @since 1.0.0
 */
class QueryBuilder {
	use Aggregate;
	use CRUD;
	use FromClause;
	use GroupByStatement;
	use HavingClause;
	use JoinClause;
	use LimitStatement;
	use MetaQuery;
	use OffsetStatement;
	use OrderByStatement;
	use SelectStatement;
	use TablePrefix;
	use UnionOperator;
	use WhereClause;

	/**
	 * @return string
	 */
	public function getSQL() {
		$sql = array_merge(
			$this->getSelectSQL(),
			$this->getFromSQL(),
			$this->getJoinSQL(),
			$this->getWhereSQL(),
			$this->getGroupBySQL(),
			$this->getHavingSQL(),
			$this->getOrderBySQL(),
			$this->getLimitSQL(),
			$this->getOffsetSQL(),
			$this->getUnionSQL()
		);

		// Trim double spaces added by DB::prepare
		return str_replace(
			[ '   ', '  ' ],
			' ',
			implode( ' ', $sql )
		);
	}
}
