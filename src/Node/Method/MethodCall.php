<?php declare(strict_types = 1);

namespace PHPStan\Node\Method;

use PHPStan\Analyser\Scope;

class MethodCall
{

	private \PhpParser\Node\Expr\MethodCall $node;

	private Scope $scope;

	public function __construct(\PhpParser\Node\Expr\MethodCall $node, Scope $scope)
	{
		$this->node = $node;
		$this->scope = $scope;
	}

	public function getNode(): \PhpParser\Node\Expr\MethodCall
	{
		return $this->node;
	}

	public function getScope(): Scope
	{
		return $this->scope;
	}

}
