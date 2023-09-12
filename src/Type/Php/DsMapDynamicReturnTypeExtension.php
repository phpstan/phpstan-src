<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeWithClassName;
use function count;
use function in_array;

final class DsMapDynamicReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return 'Ds\Map';
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return in_array($methodReflection->getName(), ['get', 'remove'], true);
	}

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): Type
	{
		$returnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$methodCall->getArgs(),
			$methodReflection->getVariants(),
		)->getReturnType();

		$argsCount = count($methodCall->getArgs());
		if ($argsCount > 1) {
			return $returnType;
		}

		if ($argsCount === 0) {
			return $returnType;
		}

		$mapType = $scope->getType($methodCall->var);
		if (!$mapType instanceof TypeWithClassName) {
			return $returnType;
		}

		$mapAncestor = $mapType->getAncestorWithClassName('Ds\Map');
		if ($mapAncestor === null) {
			return $returnType;
		}

		$mapAncestorClass = $mapAncestor->getClassReflection();
		if ($mapAncestorClass === null) {
			return $returnType;
		}

		$valueType = $mapAncestorClass->getActiveTemplateTypeMap()->getType('TValue');
		if ($valueType === null) {
			return $returnType;
		}

		return $valueType;
	}

}
