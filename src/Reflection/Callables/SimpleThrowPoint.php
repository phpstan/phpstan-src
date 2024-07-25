<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Callables;

use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Throwable;

final class SimpleThrowPoint
{

	private function __construct(
		private Type $type,
		private bool $explicit,
		private bool $canContainAnyThrowable,
	)
	{
	}

	public static function createExplicit(Type $type, bool $canContainAnyThrowable): self
	{
		return new self($type, true, $canContainAnyThrowable);
	}

	public static function createImplicit(): self
	{
		return new self(new ObjectType(Throwable::class), false, true);
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

}
