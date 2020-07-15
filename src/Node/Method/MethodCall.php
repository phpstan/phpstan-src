<?php declare(strict_types = 1);

namespace PHPStan\Node\Method;

use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;

class MethodCall
{

	/** @var \PhpParser\Node\Expr\MethodCall|StaticCall */
	private $node;

	private Scope $scope;

	/**
	 * @param \PhpParser\Node\Expr\MethodCall|StaticCall$node
	 * @param Scope $scope
	 */
	public function __construct($node, Scope $scope)
	{
		$this->node = $node;
		$this->scope = $scope;
	}

	/**
	 * @return \PhpParser\Node\Expr\MethodCall|StaticCall
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
