<?php declare(strict_types = 1);

namespace PHPStan\Node\Property;

use PhpParser\Node\Expr\PropertyFetch;
use PHPStan\Analyser\Scope;

class PropertyWrite
{

	private PropertyFetch $fetch;

	private Scope $scope;

	public function __construct(PropertyFetch $fetch, Scope $scope)
	{
		$this->fetch = $fetch;
		$this->scope = $scope;
	}

	public function getFetch(): PropertyFetch
	{
		return $this->fetch;
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

}
