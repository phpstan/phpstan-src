<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeScope;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function array_filter;
use function array_values;
use function count;

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
		$returnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$methodCall->getArgs(),
			$methodReflection->getVariants(),
		)->getReturnType();

		if (count($methodCall->getArgs()) > 1) {
			return $returnType;
		}

		if ($returnType instanceof UnionType) {
			$types = array_values(
				array_filter(
					$returnType->getTypes(),
					static function (Type $type): bool {
						if (
							$type instanceof TemplateType
							&& $type->getName() === 'TDefault'
							&& (
								$type->getScope()->equals(TemplateTypeScope::createWithMethod('Ds\Map', 'get'))
								|| $type->getScope()->equals(TemplateTypeScope::createWithMethod('Ds\Map', 'remove'))
							)
						) {
							return false;
						}

						return true;
					},
				),
			);

			if (count($types) === 1) {
				return $types[0];
			}

			if (count($types) === 0) {
				return $returnType;
			}

			return TypeCombinator::union(...$types);
		}

		return $returnType;
	}

}
