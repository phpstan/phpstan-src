<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class ThrowPoint
{

	private MutatingScope $scope;

	private Type $type;

	/** @var Node\Expr|Node\Stmt */
	private Node $node;

	private bool $explicit;

	private bool $canContainAnyThrowable;

	/**
	 * @param MutatingScope $scope
	 * @param Type $type
	 * @param Node\Expr|Node\Stmt $node
	 * @param bool $explicit
	 * @param bool $canContainAnyThrowable
	 */
	private function __construct(
		MutatingScope $scope,
		Type $type,
		Node $node,
		bool $explicit,
		bool $canContainAnyThrowable
	)
	{
		$this->scope = $scope;
		$this->type = $type;
		$this->node = $node;
		$this->explicit = $explicit;
		$this->canContainAnyThrowable = $canContainAnyThrowable;
	}

	/**
	 * @param MutatingScope $scope
	 * @param Type $type
	 * @param Node\Expr|Node\Stmt $node
	 * @param bool $canContainAnyThrowable
	 * @return self
	 */
	public static function createExplicit(MutatingScope $scope, Type $type, Node $node, bool $canContainAnyThrowable): self
	{
		return new self($scope, $type, $node, true, $canContainAnyThrowable);
	}

	/**
	 * @param MutatingScope $scope
	 * @param Node\Expr|Node\Stmt $node
	 * @return self
	 */
	public static function createImplicit(MutatingScope $scope, Node $node): self
	{
		return new self($scope, new ObjectType(\Throwable::class), $node, false, true);
	}

	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	/**
	 * @return Node\Expr|Node\Stmt
	 */
	public function getNode()
	{
		return $this->node;
	}

	public function isExplicit(): bool
	{
		return $this->explicit;
	}

	public function canContainAnyThrowable(): bool
	{
		return $this->canContainAnyThrowable;
	}

	public function subtractCatchType(Type $catchType): self
	{
		return new self($this->scope, TypeCombinator::remove($this->type, $catchType), $this->node, $this->explicit, $this->canContainAnyThrowable);
	}

}
