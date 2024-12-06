<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;

/**
 * Asks the registered dynamic parameter type extensions for the type a call site
 * really passes to a parameter, overriding the declared one.
 *
 * NodeScopeResolver consults it before walking an argument, so the override also
 * reaches closures nested in array arguments; FunctionCallParametersCheck
 * consults it so the argument type check judges against the same type.
 */
#[AutowiredService]
final class DynamicParameterTypeResolver
{

	/**
	 * @param ExtensionsCollection<DynamicFunctionParameterTypeExtension> $functionParameterTypeExtensions
	 * @param ExtensionsCollection<DynamicMethodParameterTypeExtension> $methodParameterTypeExtensions
	 * @param ExtensionsCollection<DynamicStaticMethodParameterTypeExtension> $staticMethodParameterTypeExtensions
	 */
	public function __construct(
		#[AutowiredExtensions(of: DynamicFunctionParameterTypeExtension::class)]
		private readonly ExtensionsCollection $functionParameterTypeExtensions,
		#[AutowiredExtensions(of: DynamicMethodParameterTypeExtension::class)]
		private readonly ExtensionsCollection $methodParameterTypeExtensions,
		#[AutowiredExtensions(of: DynamicStaticMethodParameterTypeExtension::class)]
		private readonly ExtensionsCollection $staticMethodParameterTypeExtensions,
	)
	{
	}

	public function resolve(
		CallLike $callLike,
		MethodReflection|FunctionReflection|null $calleeReflection,
		ParameterReflection $parameter,
		Scope $scope,
	): ?Type
	{
		if ($calleeReflection === null) {
			return null;
		}

		if ($callLike instanceof FuncCall && $calleeReflection instanceof FunctionReflection) {
			foreach ($this->functionParameterTypeExtensions->getAll() as $extension) {
				if (!$extension->isFunctionSupported($calleeReflection, $parameter)) {
					continue;
				}
				$type = $extension->getTypeFromFunctionCall($calleeReflection, $callLike, $parameter, $scope);
				if ($type !== null) {
					return $type;
				}
			}

			return null;
		}

		if ($callLike instanceof MethodCall && $calleeReflection instanceof MethodReflection) {
			foreach ($this->methodParameterTypeExtensions->getAll() as $extension) {
				if (!$extension->isMethodSupported($calleeReflection, $parameter)) {
					continue;
				}
				$type = $extension->getTypeFromMethodCall($calleeReflection, $callLike, $parameter, $scope);
				if ($type !== null) {
					return $type;
				}
			}

			return null;
		}

		if (!$calleeReflection instanceof MethodReflection) {
			return null;
		}

		if ($callLike instanceof StaticCall) {
			$staticCall = $callLike;
		} elseif ($callLike instanceof New_ && $callLike->class instanceof Name) {
			// constructors are described by static method extensions
			$staticCall = new StaticCall($callLike->class, new Identifier('__construct'), $callLike->getArgs());
		} else {
			return null;
		}

		foreach ($this->staticMethodParameterTypeExtensions->getAll() as $extension) {
			if (!$extension->isStaticMethodSupported($calleeReflection, $parameter)) {
				continue;
			}
			$type = $extension->getTypeFromStaticMethodCall($calleeReflection, $staticCall, $parameter, $scope);
			if ($type !== null) {
				return $type;
			}
		}

		return null;
	}

}
