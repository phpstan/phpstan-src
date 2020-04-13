<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;

final class DsMapDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return 'Ds\Map';
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'get' || $methodReflection->getName() === 'remove';
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		$returnType = $methodReflection->getVariants()[0]->getReturnType();

		if (count($methodCall->args) > 1) {
			return ParametersAcceptorSelector::selectFromArgs(
				$scope,
				$methodCall->args,
				$methodReflection->getVariants()
			)->getReturnType();
		}

		if ($returnType instanceof UnionType) {
			$types = array_values(
				array_filter(
					$returnType->getTypes(),
					static function (Type $type): bool {
						return !$type instanceof TemplateType;
					}
				)
			);

			if (count($types) === 1) {
				return $types[0];
			}

			return TypeCombinator::union(...$types);
		}

		return $returnType;
	}

}
