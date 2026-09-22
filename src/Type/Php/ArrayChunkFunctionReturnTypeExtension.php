<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use function count;

#[AutowiredService]
final class ArrayChunkFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_chunk';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) < 2) {
			return null;
		}

		$arrayType = $scope->getType($args[0]->value);
		if ($arrayType->isArray()->no()) {
			return $scope->getPhpVersion()->arrayFunctionsReturnNullWithNonArray()->no() ? new NeverType() : new NullType();
		}

		$lengthType = $scope->getType($args[1]->value);
		$negativeOrZero = IntegerRangeType::fromInterval(null, 0);
		if ($negativeOrZero->isSuperTypeOf($lengthType)->yes()) {
			return $scope->getPhpVersion()->throwsValueErrorForInternalFunctions()->yes() ? new NeverType() : new NullType();
		}

		$preserveKeysType = isset($args[2]) ? $scope->getType($args[2]->value) : new ConstantBooleanType(false);

		return $arrayType->chunkArray($lengthType, $preserveKeysType->isTrue());
	}

}
