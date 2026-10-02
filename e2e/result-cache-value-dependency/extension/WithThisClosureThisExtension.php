<?php declare(strict_types = 1);

namespace ResultCacheE2EValueDependency;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\FunctionParameterClosureThisExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * $this in the closure passed to withThis() is the class in the "closureThis" parameter.
 */
final class WithThisClosureThisExtension implements FunctionParameterClosureThisExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return $functionReflection->getName() === 'ResultCacheE2EValueDependency\withThis';
	}

	/**
	 * @param Scope&DependencyTracker $scope
	 */
	public function getClosureThisTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		$scope->trackValueDependency(ParameterValueExtension::class, 'closureThis');
		$class = Container::getParameter('closureThis');

		return $class !== null ? new ObjectType($class) : null;
	}

}
