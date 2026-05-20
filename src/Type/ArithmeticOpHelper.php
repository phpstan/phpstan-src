<?php declare(strict_types = 1);

namespace PHPStan\Type;

use function count;

/**
 * Shared implementation of the arithmetic/shift binary operators (+, -, *, /, %, <<, >>)
 * behind the polymorphic Type::plus()/minus()/multiply()/divide()/modulo()/shiftLeft()/shiftRight()
 * methods. The numeric folding, integer-range math and array-union logic lived in
 * InitializerExprTypeResolver before they were made polymorphic; this helper keeps them in a
 * single place so the leaf Type implementations stay one-liners.
 *
 * The GMP/BcMath operator-extension dispatch and NeverType wrapping stay in
 * InitializerExprTypeResolver (see getPowTypeFromTypes for the same split with exponentiate()).
 */
final class ArithmeticOpHelper
{

	/**
	 * Collapses a union of constant scalars into its base scalar categories. Used when a
	 * constant-folding cross-product would exceed InitializerExprTypeResolver::CALCULATE_SCALARS_LIMIT.
	 */
	public static function optimizeScalarType(Type $type): Type
	{
		$types = [];
		if ($type->isInteger()->yes()) {
			$types[] = new IntegerType();
		}
		if ($type->isString()->yes()) {
			$types[] = new StringType();
		}
		if ($type->isFloat()->yes()) {
			$types[] = new FloatType();
		}
		if ($type->isNull()->yes()) {
			$types[] = new NullType();
		}

		if (count($types) === 0) {
			return new ErrorType();
		}

		if (count($types) === 1) {
			return $types[0];
		}

		return new UnionType($types);
	}

	/** Preserves the explicit flag of an operand NeverType in the operation's result. */
	public static function getNeverType(Type $leftType, Type $rightType): Type
	{
		if ($leftType instanceof NeverType && $leftType->isExplicit()) {
			return $leftType;
		}
		if ($rightType instanceof NeverType && $rightType->isExplicit()) {
			return $rightType;
		}
		return new NeverType();
	}

}
