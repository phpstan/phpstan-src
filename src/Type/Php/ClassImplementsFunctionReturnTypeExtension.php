<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;
use function in_array;

class ClassImplementsFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array(
			$functionReflection->getName(),
			['class_implements', 'class_uses', 'class_parents'],
			true,
		);
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		if (count($functionCall->getArgs()) < 1) {
			return null;
		}

		$firstArgType = $scope->getType($functionCall->getArgs()[0]->value);
		$autoload = !isset($functionCall->getArgs()[1])
			|| $scope->getType($functionCall->getArgs()[1]->value)->equals(new ConstantBooleanType(true));

		$isObject = (new ObjectWithoutClassType())->isSuperTypeOf($firstArgType);

		$objectOrClassString = (new UnionType([new ObjectWithoutClassType(), new ClassStringType()]));
		if (
			$autoload && $objectOrClassString->isSuperTypeOf($firstArgType)->yes()
			|| $isObject->yes()
		) {
			return TypeCombinator::remove(
				ParametersAcceptorSelector::selectSingle($functionReflection->getVariants())->getReturnType(),
				new ConstantBooleanType(false),
			);
		}

		if ($isObject->no() && $firstArgType->isClassStringType()->no()) {
			return new ConstantBooleanType(false);
		}

		return null;
	}

}
