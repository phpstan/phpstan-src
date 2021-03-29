<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

class ThrowPoint
{

	private MutatingScope $scope;

	private Type $type;

	private bool $explicit;

	private bool $canContainAnyThrowable;

	private function __construct(MutatingScope $scope, Type $type, bool $explicit, bool $canContainAnyThrowable)
	{
		$this->scope = $scope;
		$this->type = $type;
		$this->explicit = $explicit;
		$this->canContainAnyThrowable = $canContainAnyThrowable;
	}

	public static function createExplicit(MutatingScope $scope, Type $type, bool $canContainAnyThrowable): self
	{
		return new self($scope, $type, true, $canContainAnyThrowable);
	}

	public static function createImplicit(MutatingScope $scope): self
	{
		return new self($scope, new ObjectType(\Throwable::class), false, true);
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
		return new self($this->scope, TypeCombinator::remove($this->type, $catchType), $this->explicit, $this->canContainAnyThrowable);
	}

}
