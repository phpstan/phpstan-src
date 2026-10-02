<?php declare(strict_types = 1);

namespace ResultCacheE2EValueDependency;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * mayThrow() throws when the "throwing" parameter says so.
 */
final class MayThrowThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'ResultCacheE2EValueDependency\mayThrow';
	}

	/**
	 * @param Scope&DependencyTracker $scope
	 */
	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		$scope->trackValueDependency(ParameterValueExtension::class, 'throwing');

		return Container::getParameter('throwing') === 'yes' ? new ObjectType(\RuntimeException::class) : null;
	}

}
