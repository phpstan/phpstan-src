<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\AccessoryNumericStringType;
use PHPStan\Type\ErrorType;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\NullType;
use PHPStan\Type\StringAlwaysAcceptingObjectWithToStringType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

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

	public function doesArgumentTypeMatchPlaceholder(Type $argumentType, bool $strictPlaceholderTypes): bool
	{
		switch ($this->acceptingType) {
			case 'strict-int':
				return (new IntegerType())->accepts($argumentType, true)->yes();
			case 'int':
				return $strictPlaceholderTypes
					? (new IntegerType())->accepts($argumentType, true)->yes()
					: ! $argumentType->toInteger() instanceof ErrorType;
			case 'float':
				return $strictPlaceholderTypes
					? TypeCombinator::union(
						new FloatType(),
						// numeric-string is allowed for consistency with phpstan-strict-rules.
						new IntersectionType([new StringType(), new AccessoryNumericStringType()]),
					)->accepts($argumentType, true)->yes()
					: ! $argumentType->toFloat() instanceof ErrorType;
			case 'string':
			case 'mixed':
				// The function signature already limits the parameters to stringable types, so there's
				// no point in checking string again here.
				return !$strictPlaceholderTypes
					// Don't accept null or bool. It's likely to be a mistake.
					|| TypeCombinator::union(
						new StringAlwaysAcceptingObjectWithToStringType(),
						// float also accepts int.
						new FloatType(),
						// null is allowed for consistency with phpstan-strict-rules (e.g. $string . $null).
						new NullType(),
					)->accepts($argumentType, true)->yes();
			// Without this PHPStan with PHP 7.4 reports "...should return bool but return statement is missing."
			// Presumably, because promoted properties are turned into regular properties and the phpdoc isn't applied to the property.
			default:
				throw new ShouldNotHappenException('Unexpected type ' . $this->acceptingType);
		}
	}

}
