<?php declare(strict_types = 1);

namespace PHPStan\Rules\Floats;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\FloatRangeType;
use PHPStan\Type\FloatType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use const INF;
use const NAN;
use const PHP_INT_MIN;

/**
 * Decides whether the float part of a value can be a float that a conversion
 * or an encoder does not handle: NAN when converting to string, NAN, INF and
 * -INF when encoding or formatting, NAN and floats out of the int range when
 * converting to int.
 *
 * A value that is surely such a float is always reported. A value that only
 * can be one, which includes plain `float`, is reported with
 * checkNonFiniteFloats.
 */
#[AutowiredService]
final class NonFiniteFloatHelper
{

	public function __construct(
		#[AutowiredParameter]
		private bool $checkNonFiniteFloats,
	)
	{
	}

	public function canBeNan(Type $type): TrinaryLogic
	{
		return $this->canBe($type, new ConstantFloatType(NAN));
	}

	public function canBeNonFinite(Type $type): TrinaryLogic
	{
		return $this->canBe($type, TypeCombinator::union(
			new ConstantFloatType(NAN),
			new ConstantFloatType(INF),
			new ConstantFloatType(-INF),
		));
	}

	public function canBeOutOfIntRange(Type $type): TrinaryLogic
	{
		// (float) PHP_INT_MIN is -2**63 and still an int, 2**63 is not
		$intMin = (float) PHP_INT_MIN;

		return $this->canBe($type, TypeCombinator::union(
			new ConstantFloatType(NAN),
			FloatRangeType::createAllSmallerThan($intMin),
			FloatRangeType::createAllGreaterThanOrEqualTo(-$intMin),
		));
	}

	public function shouldReport(TrinaryLogic $canBe): bool
	{
		if ($canBe->yes()) {
			return true;
		}

		return $canBe->maybe() && $this->checkNonFiniteFloats;
	}

	/**
	 * Yes when every value of $type is one of $offending, Maybe when the float
	 * part of $type holds one of them. A mixed value is left to the mixed
	 * checks.
	 */
	private function canBe(Type $type, Type $offending): TrinaryLogic
	{
		if ($type instanceof MixedType) {
			return TrinaryLogic::createNo();
		}

		$floatPart = TypeCombinator::intersect($type, new FloatType());
		if ($floatPart instanceof NeverType) {
			return TrinaryLogic::createNo();
		}

		if (TypeCombinator::intersect($floatPart, $offending) instanceof NeverType) {
			return TrinaryLogic::createNo();
		}

		if ($offending->isSuperTypeOf($type)->yes()) {
			return TrinaryLogic::createYes();
		}

		return TrinaryLogic::createMaybe();
	}

}
