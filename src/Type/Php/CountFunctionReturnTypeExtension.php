<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\Type;
use function count;
use function in_array;
use const COUNT_NORMAL;

#[AutowiredService]
final class CountFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['sizeof', 'count'], true);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 1) {
			return null;
		}

		$arrayType = $scope->getType($args[0]->value);
		if (!$this->isNormalCount($functionCall, $arrayType, $scope)->yes()) {
			if ($arrayType->isIterableAtLeastOnce()->yes()) {
				return IntegerRangeType::fromInterval(1, null);
			}
			return null;
		}

		return $scope->getType($args[0]->value)->getArraySize();
	}

	private function isNormalCount(FuncCall $countFuncCall, Type $countedType, Scope $scope): TrinaryLogic
	{
		if (count($countFuncCall->getArgs()) === 1) {
			$isNormalCount = TrinaryLogic::createYes();
		} else {
			$mode = $scope->getType($countFuncCall->getArgs()[1]->value);
			$isNormalCount = (new ConstantIntegerType(COUNT_NORMAL))->isSuperTypeOf($mode)->result->or($countedType->getIterableValueType()->isArray()->negate());
		}
		return $isNormalCount;
	}

}
