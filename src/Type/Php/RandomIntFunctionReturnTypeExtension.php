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
		return in_array($functionReflection->getName(), ['random_int', 'rand'], true);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		if ($functionReflection->getName() === 'rand' && count($functionCall->args) === 0) {
			return IntegerRangeType::fromInterval(0, null);
		}

		if (count($functionCall->args) < 2) {
			return ParametersAcceptorSelector::selectFromArgs($scope, $functionCall->args, $functionReflection->getVariants())->getReturnType();
		}

		$minType = $scope->getType($functionCall->args[0]->value)->toInteger();
		$maxType = $scope->getType($functionCall->args[1]->value)->toInteger();

		return $this->createRange($minType, $maxType);
	}

	private function createRange(Type $minType, Type $maxType): Type
	{
		$minValues = array_map(
			static function (Type $type): ?int {
				if ($type instanceof IntegerRangeType) {
					return $type->getMin();
				}
				if ($type instanceof ConstantIntegerType) {
					return $type->getValue();
				}
				return null;
			},
			$minType instanceof UnionType ? $minType->getTypes() : [$minType]
		);

		$maxValues = array_map(
			static function (Type $type): ?int {
				if ($type instanceof IntegerRangeType) {
					return $type->getMax();
				}
				if ($type instanceof ConstantIntegerType) {
					return $type->getValue();
				}
				return null;
			},
			$maxType instanceof UnionType ? $maxType->getTypes() : [$maxType]
		);

		assert(count($minValues) > 0);
		assert(count($maxValues) > 0);

		return IntegerRangeType::fromInterval(
			in_array(null, $minValues, true) ? null : min($minValues),
			in_array(null, $maxValues, true) ? null : max($maxValues)
		);
	}

}
