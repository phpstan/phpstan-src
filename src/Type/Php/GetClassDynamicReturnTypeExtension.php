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
use PHPStan\Type\IntersectionType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
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

			return new ConstantBooleanType(false);
		}

		$argType = $scope->getType($args[0]->value);

		if ($scope->isInTrait() && TypeUtils::findThisType($argType) !== null) {
			return new ClassStringType();
		}

		return TypeTraverser::map(
			$argType,
			static function (Type $type, callable $traverse): Type {
				if ($type instanceof UnionType || $type instanceof IntersectionType) {
					return $traverse($type);
				}

				$isObject = $type->isObject();
				if ($isObject->no()) {
					return new ConstantBooleanType(false);
				}

				$classStringType = $type->getClassStringType();
				if ($isObject->yes()) {
					return $classStringType;
				}

				return new UnionType([
					$classStringType,
					new ConstantBooleanType(false),
				]);
			},
		);
	}

}
