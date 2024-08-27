<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use function count;
use function in_array;

final class ClassImplementsFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
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
		$args = $functionCall->getArgs();
		if (count($args) < 1) {
			return null;
		}

		$firstArgType = $scope->getType($args[0]->value);
		$autoload = TrinaryLogic::createYes();
		if (isset($args[1])) {
			$autoload = $scope->getType($args[1]->value)->isTrue();
		}

		$isObject = $firstArgType->isObject();
		$variant = ParametersAcceptorSelector::selectFromArgs($scope, $args, $functionReflection->getVariants());
		if ($isObject->yes()) {
			return TypeCombinator::remove($variant->getReturnType(), new ConstantBooleanType(false));
		}
		$isClassStringOrObject = (new UnionType([new ObjectWithoutClassType(), new ClassStringType()]))->isSuperTypeOf($firstArgType);
		if ($isClassStringOrObject->yes()) {
			if ($autoload->yes()) {
				return TypeUtils::toBenevolentUnion($variant->getReturnType());
			}

			return $variant->getReturnType();
		}

		if ($firstArgType->isClassStringType()->no()) {
			return new ConstantBooleanType(false);
		}

		return null;
	}

}
