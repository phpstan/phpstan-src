<?php declare(strict_types = 1);

namespace PHPStan\Node\Property;

use PhpParser\Node\Expr\AssignRef;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Node\PropertyAssignNode;

/**
 * @api
 */
final class PropertyWrite
{

	public function __construct(private PropertyFetch|StaticPropertyFetch $fetch, private Scope $scope, private bool $promotedPropertyWrite, private ClassPropertyNode|PropertyAssignNode|AssignRef|null $originalNode = null)
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

	public function isPromotedPropertyWrite(): bool
	{
		return $this->promotedPropertyWrite;
	}

	public function getOriginalNode(): ClassPropertyNode|PropertyAssignNode|AssignRef|null
	{
		return $this->originalNode;
	}

}
