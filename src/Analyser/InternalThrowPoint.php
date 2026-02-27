<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use Throwable;

final class InternalThrowPoint
{

	/**
	 * @param Node\Expr|Node\Stmt $node
	 */
	private function __construct(
		private MutatingScope $scope,
		private Type $type,
		private Node $node,
		private bool $explicit,
		private bool $canContainAnyThrowable,
	)
	{
	}

	public function toPublic(): ThrowPoint
	{
		if ($this->explicit) {
			return ThrowPoint::createExplicit($this->scope, $this->type, $this->node, $this->canContainAnyThrowable);
		}

		return ThrowPoint::createImplicit($this->scope, $this->node);
	}

	/**
	 * @param Node\Expr|Node\Stmt $node
	 */
	public static function createExplicit(MutatingScope $scope, Type $type, Node $node, bool $canContainAnyThrowable): self
	{
		return new self($scope, $type, $node, true, $canContainAnyThrowable);
	}

	/**
	 * @param Node\Expr|Node\Stmt $node
	 */
	public static function createImplicit(MutatingScope $scope, Node $node): self
	{
		return new self($scope, new ObjectType(Throwable::class), $node, explicit: false, canContainAnyThrowable: true);
	}

	public static function createFromPublic(ThrowPoint $throwPoint): self
	{
		$scope = $throwPoint->getScope();
		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		return new self($scope, $throwPoint->getType(), $throwPoint->getNode(), $throwPoint->isExplicit(), $throwPoint->canContainAnyThrowable());
	}

	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

	public function getType(): Type
	{
		return $this->type;
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
