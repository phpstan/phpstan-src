<?php declare(strict_types = 1);

namespace PHPStan\Node\Property;

use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;

/** @api */
class PropertyWrite
{

	private PropertyFetch|StaticPropertyFetch $fetch;

	private Scope $scope;

	/**
	 * PropertyWrite constructor.
	 *
	 * @param PropertyFetch|StaticPropertyFetch $fetch
	 */
	public function __construct($fetch, Scope $scope)
	{
		$this->fetch = $fetch;
		$this->scope = $scope;
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
