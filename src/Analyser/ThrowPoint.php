<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Type\Type;

class ThrowPoint
{

	/** @var Node\Expr|Node\Stmt $node */
	private Node $node;

	private MutatingScope $scope;

	private Type $type;

	/**
	 * @param Node\Expr|Node\Stmt $node
	 * @param MutatingScope $scope
	 * @param Type $type
	 */
	public function __construct(Node $node, MutatingScope $scope, Type $type)
	{
		$this->node = $node;
		$this->scope = $scope;
		$this->type = $type;
	}

	/**
	 * @return Node\Expr|Node\Stmt
	 */
	public function getNode(): Node
	{
		return $this->node;
	}

	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

	public function getType(): Type
	{
		return $this->type;
	}

}
