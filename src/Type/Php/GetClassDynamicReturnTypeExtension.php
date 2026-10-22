<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ClassStringType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function count;

#[AutowiredService]
final class GetClassDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'get_class';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): Type
	{
		$args = $functionCall->getArgs();

		if (count($args) === 0) {
			if ($scope->isInTrait()) {
				return new ClassStringType();
			}

			if ($scope->isInClass()) {
				return new ConstantStringType($scope->getClassReflection()->getName(), true);
			}

			// PHP 8 throws Error instead of returning false. Top-level code
			// might be included from a method and a closure bound to an object.
			$throwsValueError = $scope->getPhpVersion()->throwsValueErrorForInternalFunctions();
			$types = [];
			if (!$throwsValueError->no()) {
				if ($scope->getFunction() !== null && !$scope->isInAnonymousFunction()) {
					$types[] = new NeverType(true);
				} else {
					$types[] = new ClassStringType();
				}
			}

			if (!$throwsValueError->yes()) {
				$types[] = new ConstantBooleanType(false);
			}

			return TypeCombinator::union(...$types);
		}

		$argType = $scope->getType($args[0]->value);

		if ($scope->isInTrait() && TypeUtils::findThisType($argType) !== null) {
			return new ClassStringType();
		}

		return $argType->toGetClassResultType();
	}

}
