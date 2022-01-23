<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\BenevolentUnionType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\FloatType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

class PowFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'pow';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$defaultReturnType = new BenevolentUnionType([
			new FloatType(),
			new IntegerType(),
		]);
		if (count($functionCall->getArgs()) < 2) {
			return $defaultReturnType;
		}

		$firstArgType = $scope->getType($functionCall->getArgs()[0]->value);
		$secondArgType = $scope->getType($functionCall->getArgs()[1]->value);
		if ($firstArgType instanceof MixedType || $secondArgType instanceof MixedType) {
			return $defaultReturnType;
		}

		$object = new ObjectWithoutClassType();
		if (
			!$object->isSuperTypeOf($firstArgType)->no()
			|| !$object->isSuperTypeOf($secondArgType)->no()
		) {
			return TypeCombinator::union($firstArgType, $secondArgType);
		}

		return $defaultReturnType;
	}

}
