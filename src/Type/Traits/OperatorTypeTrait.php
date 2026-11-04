<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Php\PhpVersion;
use PHPStan\Php\PhpVersionFactory;
use PHPStan\Type\LooseComparisonHelper;
use PHPStan\Type\OperatorTypeHelper;
use PHPStan\Type\Type;

trait OperatorTypeTrait
{

	public function exponentiate(Type $exponent): Type
	{
		return $this->arithmeticPower($exponent, new PhpVersion(PhpVersionFactory::MAX_PHP_VERSION));
	}

	public function arithmeticAdd(Type $right, PhpVersion $phpVersion): Type
	{
		return OperatorTypeHelper::add($this, $right, $phpVersion);
	}

	public function arithmeticSubtract(Type $right, PhpVersion $phpVersion): Type
	{
		return OperatorTypeHelper::subtract($this, $right, $phpVersion);
	}

	public function arithmeticMultiply(Type $right, PhpVersion $phpVersion): Type
	{
		return OperatorTypeHelper::multiply($this, $right, $phpVersion);
	}

	public function arithmeticDivide(Type $right, PhpVersion $phpVersion): Type
	{
		return OperatorTypeHelper::divide($this, $right, $phpVersion);
	}

	public function arithmeticIntDivide(Type $right, PhpVersion $phpVersion): Type
	{
		return OperatorTypeHelper::intDivide($this, $right, $phpVersion);
	}

	public function arithmeticModulo(Type $right, PhpVersion $phpVersion): Type
	{
		return OperatorTypeHelper::modulo($this, $right, $phpVersion);
	}

	public function arithmeticNegate(PhpVersion $phpVersion): Type
	{
		return OperatorTypeHelper::negate($this, $phpVersion);
	}

	public function bitwiseAnd(Type $right, PhpVersion $phpVersion): Type
	{
		return OperatorTypeHelper::bitwiseAnd($this, $right, $phpVersion);
	}

	public function bitwiseOr(Type $right, PhpVersion $phpVersion): Type
	{
		return OperatorTypeHelper::bitwiseOr($this, $right, $phpVersion);
	}

	public function bitwiseXor(Type $right, PhpVersion $phpVersion): Type
	{
		return OperatorTypeHelper::bitwiseXor($this, $right, $phpVersion);
	}

	public function bitwiseShiftLeft(Type $right, PhpVersion $phpVersion): Type
	{
		return OperatorTypeHelper::shiftLeft($this, $right, $phpVersion);
	}

	public function bitwiseShiftRight(Type $right, PhpVersion $phpVersion): Type
	{
		return OperatorTypeHelper::shiftRight($this, $right, $phpVersion);
	}

	public function getPossiblyLooselyEqualType(PhpVersion $phpVersion): Type
	{
		return LooseComparisonHelper::getPossiblyLooselyEqualType($this, $phpVersion);
	}

	public function getAlwaysLooselyEqualType(PhpVersion $phpVersion): Type
	{
		return LooseComparisonHelper::getAlwaysLooselyEqualType($this, $phpVersion);
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
