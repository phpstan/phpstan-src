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

	private function __construct(MutatingScope $scope, Type $type, bool $explicit)
	{
		$this->scope = $scope;
		$this->type = $type;
		$this->explicit = $explicit;
	}

	public static function createExplicit(MutatingScope $scope, Type $type): self
	{
		return new self($scope, $type, true);
	}

	public static function createImplicit(MutatingScope $scope): self
	{
		return new self($scope, new ObjectType(\Throwable::class), false);
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

	public function subtractCatchType(Type $catchType): self
	{
		return new self($this->scope, TypeCombinator::remove($this->type, $catchType), $this->explicit);
	}

}
