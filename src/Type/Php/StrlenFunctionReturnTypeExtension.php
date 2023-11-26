<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function strlen;

class StrlenFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'strlen';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) === 0) {
			return null;
		}

		$argType = $scope->getType($args[0]->value);

		if ($argType->isSuperTypeOf(new BooleanType())->yes()) {
			$constantScalars = TypeCombinator::remove($argType, new BooleanType())->getConstantScalarTypes();
			if (count($constantScalars) > 0) {
				$constantScalars[] = new ConstantBooleanType(true);
				$constantScalars[] = new ConstantBooleanType(false);
			}
		} else {
			$constantScalars = $argType->getConstantScalarTypes();
		}

		$min = null;
		$max = null;
		foreach ($constantScalars as $constantScalar) {
			$stringScalar = $constantScalar->toString();
			if (!($stringScalar instanceof ConstantStringType)) {
				$min = $max = null;
				break;
			}
			$len = strlen($stringScalar->getValue());

			if ($min === null) {
				$min = $len;
				$max = $len;
			}

			if ($len < $min) {
				$min = $len;
			}
			if ($len <= $max) {
				continue;
			}

			$max = $len;
		}

		// $max is always != null, when $min is != null
		if ($min !== null) {
			return IntegerRangeType::fromInterval($min, $max);
		}

		$bool = new BooleanType();
		if ($bool->isSuperTypeOf($argType)->yes()) {
			return IntegerRangeType::fromInterval(0, 1);
		}

		$isNonEmpty = $argType->isNonEmptyString();
		$numeric = TypeCombinator::union(new IntegerType(), new FloatType());
		if (
			$isNonEmpty->yes()
			|| $numeric->isSuperTypeOf($argType)->yes()
			|| TypeCombinator::remove($argType, $numeric)->isNonEmptyString()->yes()
		) {
			return IntegerRangeType::fromInterval(1, null);
		}

		if ($argType->isString()->yes() && $isNonEmpty->no()) {
			return new ConstantIntegerType(0);
		}

		return null;
	}

}
