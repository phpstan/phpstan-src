<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

class RandomIntFunctionReturnTypeExtension implements \PHPStan\Type\DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'random_int';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if (count($functionCall->args) < 2) {
			return ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType();
		}

		$minType = $scope->getType($functionCall->args[0]->value)->toInteger();
		$maxType = $scope->getType($functionCall->args[1]->value)->toInteger();

		return $this->createRange($minType, $maxType);
	}

	private function createRange(Type $minType, Type $maxType): Type
	{
		$minValue = array_reduce($minType instanceof UnionType ? $minType->getTypes() : [$minType], static function (int $carry, Type $type): int {
			if ($type instanceof IntegerRangeType) {
				$value = $type->getMin();
			} elseif ($type instanceof ConstantIntegerType) {
				$value = $type->getValue();
			} else {
				$value = PHP_INT_MIN;
			}

			return min($value, $carry);
		}, PHP_INT_MAX);

		$maxValue = array_reduce($maxType instanceof UnionType ? $maxType->getTypes() : [$maxType], static function (int $carry, Type $type): int {
			if ($type instanceof IntegerRangeType) {
				$value = $type->getMax();
			} elseif ($type instanceof ConstantIntegerType) {
				$value = $type->getValue();
			} else {
				$value = PHP_INT_MAX;
			}

			return max($value, $carry);
		}, PHP_INT_MIN);

		return IntegerRangeType::fromInterval($minValue, $maxValue);
	}

}
