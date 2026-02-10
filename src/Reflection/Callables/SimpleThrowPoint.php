<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Callables;

use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use Throwable;

/**
 * Represents a point where a callable may throw an exception.
 *
 * Used by CallableParametersAcceptor::getThrowPoints() to describe what exceptions
 * a closure or callable value may throw. This is a simplified version of the full
 * ThrowPoint used in the analyser — it carries just the exception type, whether the
 * throw was explicitly declared (@throws), and whether it could be any Throwable.
 *
 * Explicit throw points come from @throws annotations. Implicit throw points represent
 * the possibility that any function call could throw.
 */
final class SimpleThrowPoint
{

	private function __construct(
		private Type $type,
		private bool $explicit,
		private bool $canContainAnyThrowable,
	)
	{
	}

	/**
	 * Creates a throw point from an explicit @throws annotation.
	 *
	 * @param Type $type The exception type declared in @throws
	 * @param bool $canContainAnyThrowable Whether the type could match any Throwable
	 */
	public static function createExplicit(Type $type, bool $canContainAnyThrowable): self
	{
		return new self($type, true, $canContainAnyThrowable);
	}

	/**
	 * Creates an implicit throw point that could throw any Throwable.
	 *
	 * Used when no @throws annotation is present and the callable is not marked pure.
	 */
	public static function createImplicit(): self
	{
		return new self(new ObjectType(Throwable::class), false, true);
	}

	/** Returns the type of exceptions that may be thrown. */
	public function getType(): Type
	{
		return $this->type;
	}

	/** Whether this throw point comes from an explicit @throws annotation. */
	public function isExplicit(): bool
	{
		return $this->explicit;
	}

	/** Whether the exception type is broad enough to include any Throwable. */
	public function canContainAnyThrowable(): bool
	{
		return $this->canContainAnyThrowable;
	}

}
