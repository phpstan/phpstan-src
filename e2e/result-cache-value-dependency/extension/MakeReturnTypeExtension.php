<?php declare(strict_types = 1);

namespace ResultCacheE2EValueDependency;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Scalar\String_;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;

/**
 * make('ClassName') returns an instance of the class named in the string.
 */
final class MakeReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'ResultCacheE2EValueDependency\make';
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

		$scope->trackClassDependency($arg->value);
		if (!$this->reflectionProvider->hasClass($arg->value)) {
			return null;
		}

		return new ObjectType($arg->value);
	}

}
