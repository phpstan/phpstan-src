<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function abs;
use function count;
use function max;

class AbsFunctionDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'abs';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();

		if (!isset($args[0])) {
			return null;
		}

		$type = $scope->getType($args[0]->value);

		if ($type instanceof UnionType) {
			$ranges = [];

			foreach ($type->getTypes() as $unionType) {
				if (
					!$unionType instanceof ConstantIntegerType
					&& !$unionType instanceof IntegerRangeType
					&& !$unionType instanceof ConstantFloatType
				) {
					return null;
				}

				$absRange = $this->absType($unionType);

				foreach ($ranges as $index => $range) {
					if (!($range instanceof IntegerRangeType)) {
						continue;
					}

					$unionRange = $range->tryUnion($absRange);

					if ($unionRange !== null) {
						$ranges[$index] = $unionRange;

						continue 2;
					}
				}

				$ranges[] = $absRange;
			}

			if (count($ranges) === 1) {
				return $ranges[0];
			}

			return new UnionType($ranges);
		}

		if (
			$type instanceof ConstantIntegerType
			|| $type instanceof IntegerRangeType
			|| $type instanceof ConstantFloatType
		) {
			return $this->absType($type);
		}

		return null;
	}

	private function absType(ConstantIntegerType|IntegerRangeType|ConstantFloatType $type): Type
	{
		if ($type instanceof ConstantIntegerType) {
			return new ConstantIntegerType(abs($type->getValue()));
		}

		if ($type instanceof ConstantFloatType) {
			return new ConstantFloatType(abs($type->getValue()));
		}

		$min = $type->getMin();
		$max = $type->getMax();

		if ($min !== null && $min >= 0) {
			return IntegerRangeType::fromInterval($min, $max);
		}

		if ($max === null || $max >= 0) {
			$inversedMin = $min !== null ? $min * -1 : null;

			return IntegerRangeType::fromInterval(0, $inversedMin !== null && $max !== null ? max($inversedMin, $max) : null);
		}

		return IntegerRangeType::fromInterval($max * -1, $min !== null ? $min * -1 : null);
	}

}
