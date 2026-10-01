<?php declare(strict_types = 1);

namespace ResultCacheE2EValueDependency;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

final class ServiceReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'ResultCacheE2EValueDependency\service';
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

		// the same value ServiceRule declares - recorded once
		$scope->trackValueDependency(HasServiceValueExtension::class, $arg->value);
		$class = Container::getService($arg->value);

		return $class !== null ? new ObjectType($class) : null;
	}

}
