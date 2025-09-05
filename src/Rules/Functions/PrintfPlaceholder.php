<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;

final class PrintfPlaceholder
{

	/** @phpstan-param 'strict-int'|'int'|'float'|'string'|'mixed' $acceptingType */
	public function __construct(
		public readonly string $label,
		public readonly int $parameterIndex,
		public readonly int $placeholderNumber,
		public readonly string $acceptingType,
	)
	{
	}

	public function doesArgumentTypeMatchPlaceholder(Type $argumentType): bool
	{
		switch ($this->acceptingType) {
			case 'strict-int':
				return (new IntegerType())->accepts($argumentType, true)->yes();
			case 'int':
				return ! $argumentType->toInteger() instanceof ErrorType;
			case 'float':
				return ! $argumentType->toFloat() instanceof ErrorType;
			// The function signature already limits the parameters to stringable types, so there's
			// no point in checking string again here.
			case 'string':
			case 'mixed':
				return true;
			// Without this PHPStan with PHP 7.4 reports "...should return bool but return statement is missing."
			// Presumably, because promoted properties are turned into regular properties and the phpdoc isn't applied to the property.
			default:
				throw new ShouldNotHappenException('Unexpected type ' . $this->acceptingType);
		}
	}

}
