<?php declare(strict_types = 1);

namespace PHPStan\Node\Property;

use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;

/**
 * @api
 * @final
 */
class PropertyRead
{

	public function __construct(
		private PropertyFetch|StaticPropertyFetch $fetch,
		private Scope $scope,
	)
	{
	}

	/**
	 * @return PropertyFetch|StaticPropertyFetch
	 */
	public function getFetch()
	{
		return $this->fetch;
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

}
