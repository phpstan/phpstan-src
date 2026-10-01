<?php declare(strict_types = 1);

namespace ResultCacheE2EValueDependency;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\Type;

final class ParameterReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'ResultCacheE2EValueDependency\parameter';
	}

	/**
	 * @param Scope&DependencyTracker $scope
	 */
	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$arg = $functionCall->getArgs()[0]->value ?? null;
		if (!$arg instanceof String_) {
			return null;
		}

		$scope->trackValueDependency(ParameterValueExtension::class, $arg->value);
		$value = Container::getParameter($arg->value);
		if ($value === null) {
			return null;
		}

		return is_numeric($value) ? new ConstantIntegerType((int) $value) : new ConstantStringType($value);
	}

}
