<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;

/** @api */
class ImpurePoint
{

	/**
	 * @param Node\Expr|Node\Stmt $node
	 */
	public function __construct(
		private MutatingScope $scope,
		private Node $node,
		private bool $certain,
	)
	{
	}

	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

	/**
	 * @return Node\Expr|Node\Stmt
	 */
	public function getNode()
	{
		return $this->node;
	}

	public function isCertain(): bool
	{
		return $this->certain;
	}

}
