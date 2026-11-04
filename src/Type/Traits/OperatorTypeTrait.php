<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Php\PhpVersion;
use PHPStan\Php\PhpVersionFactory;
use PHPStan\Type\NumberSet;
use PHPStan\Type\OperatorTypeHelper;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\ValueSet;

trait OperatorTypeTrait
{

	public function exponentiate(Type $exponent): Type
	{
		return $this->arithmeticPower($exponent, new PhpVersion(PhpVersionFactory::MAX_PHP_VERSION));
	}

	public function toNumberSet(): ?NumberSet
	{
		return null;
	}

	public function toValueSet(): ValueSet
	{
		return ValueSet::createUnknown();
	}

	public function arithmeticAdd(Type $right, PhpVersion $phpVersion): Type
	{
		$leftValues = $this->toValueSet();
		$rightValues = $right->toValueSet();
		if (!$leftValues->isEmpty() && !$rightValues->isEmpty() && $this->isArray()->yes() && $right->isArray()->yes()) {
			return $this->arrayUnion($right, $phpVersion);
		}

		// strings, numbers, booleans and arrays added to each other give each of them,
		// as the operand types themselves to keep what else they tell
		if ($this->isArray()->maybe() && $right->isArray()->maybe()) {
			$leftIsPlusable = $leftValues->isPlusable();
			$rightIsPlusable = $rightValues->isPlusable();
			if ($leftIsPlusable && $rightIsPlusable) {
				return TypeCombinator::union($this, $right);
			}
			if ($leftIsPlusable && $rightValues->isUnknown()) {
				return $this;
			}
			if ($rightIsPlusable && $leftValues->isUnknown()) {
				return $right;
			}
		}

		return $leftValues->arithmeticAdd($rightValues, $phpVersion);
	}

	public function arithmeticSubtract(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->arithmeticSubtract($right->toValueSet(), $phpVersion);
	}

	public function arithmeticMultiply(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->arithmeticMultiply($right->toValueSet(), $phpVersion);
	}

	public function arithmeticDivide(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->arithmeticDivide($right->toValueSet(), $phpVersion);
	}

	public function arithmeticIntDivide(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toInteger()->toValueSet()->arithmeticIntDivide($right->toInteger()->toValueSet(), $phpVersion);
	}

	public function arithmeticModulo(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->arithmeticModulo($right->toValueSet(), $phpVersion);
	}

	public function arithmeticNegate(PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->arithmeticNegate($phpVersion);
	}

	public function bitwiseAnd(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->bitwiseAnd($right->toValueSet(), $phpVersion);
	}

	public function bitwiseOr(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->bitwiseOr($right->toValueSet(), $phpVersion);
	}

	public function bitwiseXor(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->bitwiseXor($right->toValueSet(), $phpVersion);
	}

	public function bitwiseShiftLeft(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->bitwiseShiftLeft($right->toValueSet(), $phpVersion);
	}

	public function bitwiseShiftRight(Type $right, PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->bitwiseShiftRight($right->toValueSet(), $phpVersion);
	}

	public function getPossiblyLooselyEqualType(PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->getPossiblyLooselyEqualType($phpVersion);
	}

	public function getAlwaysLooselyEqualType(PhpVersion $phpVersion): Type
	{
		return $this->toValueSet()->getAlwaysLooselyEqualType($phpVersion);
	}

	public function stringConcatenate(Type $right, PhpVersion $phpVersion): Type
	{
		return OperatorTypeHelper::concat($this, $right, $phpVersion);
	}

	public function arrayUnion(Type $right, PhpVersion $phpVersion): Type
	{
		return OperatorTypeHelper::arrayUnion($this, $right, $phpVersion);
	}

}
