<?php declare(strict_types = 1);

namespace PHPStan\Type;

/** @api */
class ErrorType extends MixedType
{

	/** @api */
	public function __construct()
	{
		parent::__construct();
	}

	public function describe(VerbosityLevel $level): string
	{
		return $level->handle(
			fn (): string => parent::describe($level),
			fn (): string => parent::describe($level),
			static fn (): string => '*ERROR*',
		);
	}

	public function getIterableKeyType(): Type
	{
		return new ErrorType();
	}

	public function getIterableValueType(): Type
	{
		return new ErrorType();
	}

	public function subtract(Type $type): Type
	{
		return new self();
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self;
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): Type
	{
		return new self();
	}

}
