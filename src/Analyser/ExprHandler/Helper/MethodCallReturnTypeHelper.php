<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\ArgumentsNormalizer;
use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Type\DynamicReturnTypeExtensionRegistryProvider;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function substr;

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
		foreach ($typeWithMethod->getObjectClassNames() as $className) {
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
				}
			}
		}

		if (count($resolvedTypes) > 0) {
			return VoidToNullTypeTransformer::transform(TypeCombinator::union(...$resolvedTypes), $methodCall);
		}

		$returnType = $parametersAcceptor->getReturnType();
		$returnType = $this->narrowReturnTypeByAssertions($returnType, $methodReflection->getAsserts(), $normalizedMethodCall, $scope, $parametersAcceptor);

		return VoidToNullTypeTransformer::transform($returnType, $methodCall);
	}

	private function narrowReturnTypeByAssertions(
		Type $returnType,
		\PHPStan\Reflection\Assertions $assertions,
		MethodCall|Expr\StaticCall $call,
		MutatingScope $scope,
		ParametersAcceptor $parametersAcceptor,
	): Type
	{
		if (!$returnType->isBoolean()->yes() || $returnType->isTrue()->yes() || $returnType->isFalse()->yes()) {
			return $returnType;
		}

		$assertsIfFalse = $assertions->getAssertsIfFalse();
		$assertsIfTrue = $assertions->getAssertsIfTrue();

		if (count($assertsIfFalse) === 0 && count($assertsIfTrue) === 0) {
			return $returnType;
		}

		$argTypes = [];
		$parameters = $parametersAcceptor->getParameters();
		foreach ($call->getArgs() as $i => $arg) {
			$name = null;
			if ($arg->name !== null) {
				$name = $arg->name->toString();
			} elseif (isset($parameters[$i])) {
				$name = $parameters[$i]->getName();
			}
			if ($name !== null) {
				$argTypes[$name] = $scope->getType($arg->value);
			}
		}

		foreach ($assertsIfFalse as $assert) {
			$param = $assert->getParameter();
			if ($param->describe() !== $param->getParameterName()) {
				continue;
			}

			$paramName = substr($param->getParameterName(), 1);
			if (!isset($argTypes[$paramName])) {
				continue;
			}

			$actualType = $argTypes[$paramName];
			$assertedType = $assert->getType();

			if ($assert->isNegated()) {
				if ($assertedType->isSuperTypeOf($actualType)->yes()) {
					return new ConstantBooleanType(true);
				}
			} else {
				if ($assertedType->isSuperTypeOf($actualType)->no()) {
					return new ConstantBooleanType(true);
				}
			}
		}

		foreach ($assertsIfTrue as $assert) {
			$param = $assert->getParameter();
			if ($param->describe() !== $param->getParameterName()) {
				continue;
			}

			$paramName = substr($param->getParameterName(), 1);
			if (!isset($argTypes[$paramName])) {
				continue;
			}

			$actualType = $argTypes[$paramName];
			$assertedType = $assert->getType();

			if ($assert->isNegated()) {
				if ($assertedType->isSuperTypeOf($actualType)->yes()) {
					return new ConstantBooleanType(false);
				}
			} else {
				if ($assertedType->isSuperTypeOf($actualType)->no()) {
					return new ConstantBooleanType(false);
				}
			}
		}

		return $returnType;
	}

}
