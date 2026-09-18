<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;

/**
 * @api
 */
final class ThrowPoint
{

	/**
	 * @param Node\Expr|Node\Stmt $node
	 */
	private function __construct(
		private Scope $scope,
		private Type $type,
		private Node $node,
		private bool $explicit,
		private bool $canContainAnyThrowable,
		private bool $fromThrowExpr = false,
	)
	{
	}

	/**
	 * @param Node\Expr|Node\Stmt $node
	 */
	public static function createExplicit(Scope $scope, Type $type, Node $node, bool $canContainAnyThrowable, bool $fromThrowExpr = false): self
	{
		return new self($scope, $type, $node, true, $canContainAnyThrowable, $fromThrowExpr);
	}

	/**
	 * @param Node\Expr|Node\Stmt $node
	 */
	public static function createImplicit(Scope $scope, Node $node, ?Type $type = null): self
	{
		return new self($scope, $type ?? new ObjectType(Throwable::class), $node, explicit: false, canContainAnyThrowable: true);
	}

	public function getScope(): Scope
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

	/**
	 * Whether the throw point comes from a `throw` written in the analysed code,
	 * as opposed to a throw inferred from what a called function or an operation
	 * can throw.
	 */
	public function isFromThrowExpr(): bool
	{
		return $this->fromThrowExpr;
	}

	public function subtractCatchType(Type $catchType): self
	{
		return new self($this->scope, TypeCombinator::remove($this->type, $catchType), $this->node, $this->explicit, $this->canContainAnyThrowable, $this->fromThrowExpr);
	}

}
