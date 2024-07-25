<?php declare(strict_types = 1);

namespace PHPStan\Node\Constant;

use PhpParser\Node\Expr\ClassConstFetch;
use PHPStan\Analyser\Scope;

/**
 * @api
 * @final
 */
class ClassConstantFetch
{

	public function __construct(private ClassConstFetch $node, private Scope $scope)
	{
	}

	public function getNode(): ClassConstFetch
	{
		return $this->node;
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

}
