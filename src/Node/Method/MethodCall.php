<?php declare(strict_types = 1);

namespace PHPStan\Node\Method;

use PhpParser\Node;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;

/** @api */
class MethodCall
{

	private Node\Expr\MethodCall|StaticCall|Array_ $node;

	private Scope $scope;

	public function __construct(Node\Expr\MethodCall|StaticCall|Array_ $node, Scope $scope)
	{
		$this->node = $node;
		$this->scope = $scope;
	}

	public function getNode(): Node\Expr\MethodCall|StaticCall|Array_
	{
		return $this->node;
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

}
