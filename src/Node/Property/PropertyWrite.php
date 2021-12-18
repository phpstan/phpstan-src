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
	 */
	public function __construct(PropertyFetch|StaticPropertyFetch $fetch, Scope $scope)
	{
		$this->fetch = $fetch;
		$this->scope = $scope;
	}

	public function getFetch(): PropertyFetch|StaticPropertyFetch
	{
		return $this->fetch;
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

}
