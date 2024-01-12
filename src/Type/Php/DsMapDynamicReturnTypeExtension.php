<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
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

	public function getTypeFromMethodCall(MethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?Type
	{
		$argsCount = count($methodCall->getArgs());
		if ($argsCount > 1) {
			return null;
		}

		if ($argsCount === 0) {
			return null;
		}

		$mapType = $scope->getType($methodCall->var);
		if (!$mapType instanceof TypeWithClassName) {
			return null;
		}

		$mapAncestor = $mapType->getAncestorWithClassName('Ds\Map');
		if ($mapAncestor === null) {
			return null;
		}

		$mapAncestorClass = $mapAncestor->getClassReflection();
		if ($mapAncestorClass === null) {
			return null;
		}

		$valueType = $mapAncestorClass->getActiveTemplateTypeMap()->getType('TValue');
		if ($valueType === null) {
			return null;
		}

		return $valueType;
	}

}
