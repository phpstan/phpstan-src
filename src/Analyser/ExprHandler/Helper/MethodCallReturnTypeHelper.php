<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_unique;
use function count;
use function in_array;

#[AutowiredService]
final class MethodCallReturnTypeHelper
{

	public function __construct(
		private DynamicReturnTypeExtensionRegistryProvider $dynamicReturnTypeExtensionRegistryProvider,
	)
	{
	}

	public function methodCallReturnType(
		MutatingScope $scope,
		Type $typeWithMethod,
		string $methodName,
		MethodCall|Expr\StaticCall $methodCall,
	): ?Type
	{
		$typeWithMethod = $scope->filterTypeWithMethod($typeWithMethod, $methodName);
		if ($typeWithMethod === null) {
			return null;
		}

		$methodReflection = $typeWithMethod->getMethod($methodName, $scope);
		$parametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$methodCall->getArgs(),
			$methodReflection->getVariants(),
			$methodReflection->getNamedArgumentsVariants(),
		);
		if ($methodCall instanceof MethodCall) {
			$normalizedMethodCall = ArgumentsNormalizer::reorderMethodArguments($parametersAcceptor, $methodCall);
		} else {
			$normalizedMethodCall = ArgumentsNormalizer::reorderStaticCallArguments($parametersAcceptor, $methodCall);
		}
		if ($normalizedMethodCall === null) {
			return VoidToNullTypeTransformer::transform($parametersAcceptor->getReturnType(), $methodCall);
		}

		$resolvedTypes = [];
		$allClassNames = array_unique($typeWithMethod->getObjectClassNames());
		$handledClassNames = [];
		foreach ($allClassNames as $className) {
			if ($normalizedMethodCall instanceof MethodCall) {
				foreach ($this->dynamicReturnTypeExtensionRegistryProvider->getRegistry()->getDynamicMethodReturnTypeExtensionsForClass($className) as $dynamicMethodReturnTypeExtension) {
					if (!$dynamicMethodReturnTypeExtension->isMethodSupported($methodReflection)) {
						continue;
					}

					$resolvedType = $dynamicMethodReturnTypeExtension->getTypeFromMethodCall($methodReflection, $normalizedMethodCall, $scope);
					if ($resolvedType === null) {
						continue;
					}

					$resolvedTypes[] = $resolvedType;
					$handledClassNames[] = $className;
				}
			} else {
				foreach ($this->dynamicReturnTypeExtensionRegistryProvider->getRegistry()->getDynamicStaticMethodReturnTypeExtensionsForClass($className) as $dynamicStaticMethodReturnTypeExtension) {
					if (!$dynamicStaticMethodReturnTypeExtension->isStaticMethodSupported($methodReflection)) {
						continue;
					}

					$resolvedType = $dynamicStaticMethodReturnTypeExtension->getTypeFromStaticMethodCall(
						$methodReflection,
						$normalizedMethodCall,
						$scope,
					);
					if ($resolvedType === null) {
						continue;
					}

					$resolvedTypes[] = $resolvedType;
					$handledClassNames[] = $className;
				}
			}
		}

		if (count($resolvedTypes) > 0) {
			foreach ($allClassNames as $className) {
				if (in_array($className, $handledClassNames, true)) {
					continue;
				}
				$classType = new ObjectType($className);
				if (!$classType->hasMethod($methodName)->yes()) {
					continue;
				}
				$classMethod = $classType->getMethod($methodName, $scope);
				$classParametersAcceptor = ParametersAcceptorSelector::selectFromArgs(
					$scope,
					$methodCall->getArgs(),
					$classMethod->getVariants(),
					$classMethod->getNamedArgumentsVariants(),
				);
				$resolvedTypes[] = $classParametersAcceptor->getReturnType();
			}
			return VoidToNullTypeTransformer::transform(TypeCombinator::union(...$resolvedTypes), $methodCall);
		}

		return VoidToNullTypeTransformer::transform($parametersAcceptor->getReturnType(), $methodCall);
	}

}
