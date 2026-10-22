<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

#[AutowiredService]
final class ArrayFlipFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_flip';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) !== 1) {
			return null;
		}

		$arrayType = $scope->getType($args[0]->value);
		if ($arrayType->isArray()->no()) {
			if ($scope->getPhpVersion()->arrayFunctionsReturnNullWithNonArray()->no()) {
				return new NeverType();
			}

			return new NullType();
		}

		$flipped = $arrayType->flipArray();
		if ($arrayType->isIterableAtLeastOnce()->yes()) {
			return TypeCombinator::intersect($flipped, new NonEmptyArrayType());
		}
		return $flipped;
	}

}
