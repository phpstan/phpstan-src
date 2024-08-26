<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\Type\ParameterClosureTypeExtensionProvider;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;

class ParameterClosureTypeHelper
{

	public function __construct(private readonly ParameterClosureTypeExtensionProvider $parameterClosureTypeExtensionProvider)
	{
	}

	/**
	 * @param MethodReflection|FunctionReflection|null $calleeReflection
	 */
	public function getParameterTypeFromParameterClosureTypeExtension(CallLike $callLike, $calleeReflection, ParameterReflection $parameter, Scope $scope): ?Type
	{
		if ($callLike instanceof FuncCall && $calleeReflection instanceof FunctionReflection) {
			foreach ($this->parameterClosureTypeExtensionProvider->getFunctionParameterClosureTypeExtensions() as $functionParameterClosureTypeExtension) {
				if ($functionParameterClosureTypeExtension->isFunctionSupported($calleeReflection, $parameter)) {
					return $functionParameterClosureTypeExtension->getTypeFromFunctionCall($calleeReflection, $callLike, $parameter, $scope);
				}
			}
		} elseif ($calleeReflection instanceof MethodReflection) {
			if ($callLike instanceof StaticCall) {
				foreach ($this->parameterClosureTypeExtensionProvider->getStaticMethodParameterClosureTypeExtensions() as $staticMethodParameterClosureTypeExtension) {
					if ($staticMethodParameterClosureTypeExtension->isStaticMethodSupported($calleeReflection, $parameter)) {
						return $staticMethodParameterClosureTypeExtension->getTypeFromStaticMethodCall($calleeReflection, $callLike, $parameter, $scope);
					}
				}
			} elseif ($callLike instanceof MethodCall) {
				foreach ($this->parameterClosureTypeExtensionProvider->getMethodParameterClosureTypeExtensions() as $methodParameterClosureTypeExtension) {
					if ($methodParameterClosureTypeExtension->isMethodSupported($calleeReflection, $parameter)) {
						return $methodParameterClosureTypeExtension->getTypeFromMethodCall($calleeReflection, $callLike, $parameter, $scope);
					}
				}
			}
		}

		return null;
	}

}
