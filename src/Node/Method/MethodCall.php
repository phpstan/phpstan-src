<?php declare(strict_types = 1);

namespace PHPStan\Node\Method;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;

/**
 * @api
 * @final
 */
class MethodCall
{

	public function __construct(
		private Node\Expr\MethodCall|StaticCall|Array_ $node,
		private Scope $scope,
	)
	{
	}

	/**
	 * @return Node\Expr\MethodCall|StaticCall|Array_
	 */
	public function getNode()
	{
		return $this->node;
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

}
