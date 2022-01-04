<?php declare(strict_types = 1);

namespace PHPStan\Node\Property;

use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;

/** @api */
class PropertyRead
{

	private PropertyFetch|StaticPropertyFetch $fetch;

	/**
	 * PropertyWrite constructor.
	 *
	 * @param PropertyFetch|StaticPropertyFetch $fetch
	 */
	public function __construct($fetch, private Scope $scope)
	{
		$this->fetch = $fetch;
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
