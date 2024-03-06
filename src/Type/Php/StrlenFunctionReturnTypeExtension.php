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
use function array_map;
use function array_unique;
use function count;
use function max;
use function min;
use function range;
use function sort;
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

		$lengths = [];
		foreach ($constantScalars as $constantScalar) {
			$stringScalar = $constantScalar->toString();
			if (!($stringScalar instanceof ConstantStringType)) {
				$lengths = [];
				break;
			}
			$length = strlen($stringScalar->getValue());
			$lengths[] = $length;
		}

		$isNonEmpty = $argType->isNonEmptyString();
		$numeric = TypeCombinator::union(new IntegerType(), new FloatType());
		$range = null;
		if (count($lengths) > 0) {
			$lengths = array_unique($lengths);
			sort($lengths);
			if ($lengths === range(min($lengths), max($lengths))) {
				$range = IntegerRangeType::fromInterval(min($lengths), max($lengths));
			} else {
				$range = TypeCombinator::union(...array_map(static fn ($l) => new ConstantIntegerType($l), $lengths));
			}
		} elseif ($argType->isBoolean()->yes()) {
			$range = IntegerRangeType::fromInterval(0, 1);
		} elseif (
			$isNonEmpty->yes()
			|| $numeric->isSuperTypeOf($argType)->yes()
			|| TypeCombinator::remove($argType, $numeric)->isNonEmptyString()->yes()
		) {
			$range = IntegerRangeType::fromInterval(1, null);
		} elseif ($argType->isString()->yes() && $isNonEmpty->no()) {
			$range = new ConstantIntegerType(0);
		}

		return $range;
	}

}
