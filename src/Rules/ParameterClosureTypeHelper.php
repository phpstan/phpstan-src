<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\FunctionParameterClosureTypeExtension;
use PHPStan\Type\MethodParameterClosureTypeExtension;
use PHPStan\Type\StaticMethodParameterClosureTypeExtension;
use PHPStan\Type\Type;

/**
 * Asks the parameter closure type extensions for the type of a closure passed to a parameter,
 * the same way ArgumentsHandler does when it analyses the closure body.
 */
#[AutowiredService]
final class ParameterClosureTypeHelper
{

	/**
	 * @param ExtensionsCollection<FunctionParameterClosureTypeExtension> $functionParameterClosureTypeExtensions
	 * @param ExtensionsCollection<MethodParameterClosureTypeExtension> $methodParameterClosureTypeExtensions
	 * @param ExtensionsCollection<StaticMethodParameterClosureTypeExtension> $staticMethodParameterClosureTypeExtensions
	 */
	public function __construct(
		#[AutowiredExtensions(of: FunctionParameterClosureTypeExtension::class)]
		private ExtensionsCollection $functionParameterClosureTypeExtensions,
		#[AutowiredExtensions(of: MethodParameterClosureTypeExtension::class)]
		private ExtensionsCollection $methodParameterClosureTypeExtensions,
		#[AutowiredExtensions(of: StaticMethodParameterClosureTypeExtension::class)]
		private ExtensionsCollection $staticMethodParameterClosureTypeExtensions,
	)
	{
	}

	public function getParameterType(
		FuncCall|MethodCall|StaticCall|New_ $callLike,
		FunctionReflection|MethodReflection $calleeReflection,
		ParameterReflection $parameter,
		Scope&DependencyTracker $scope,
	): ?Type
	{
		if ($callLike instanceof FuncCall && $calleeReflection instanceof FunctionReflection) {
			foreach ($this->functionParameterClosureTypeExtensions->getAll() as $extension) {
				if ($extension->isFunctionSupported($calleeReflection, $parameter)) {
					return $extension->getTypeFromFunctionCall($calleeReflection, $callLike, $parameter, $scope);
				}
			}
		} elseif ($calleeReflection instanceof MethodReflection) {
			if ($callLike instanceof StaticCall) {
				foreach ($this->staticMethodParameterClosureTypeExtensions->getAll() as $extension) {
					if ($extension->isStaticMethodSupported($calleeReflection, $parameter)) {
						return $extension->getTypeFromStaticMethodCall($calleeReflection, $callLike, $parameter, $scope);
					}
				}
			} elseif ($callLike instanceof New_ && $callLike->class instanceof Name) {
				$staticCall = new StaticCall(
					$callLike->class,
					new Identifier('__construct'),
					$callLike->getArgs(),
				);
				foreach ($this->staticMethodParameterClosureTypeExtensions->getAll() as $extension) {
					if ($extension->isStaticMethodSupported($calleeReflection, $parameter)) {
						return $extension->getTypeFromStaticMethodCall($calleeReflection, $staticCall, $parameter, $scope);
					}
				}
			} elseif ($callLike instanceof MethodCall) {
				foreach ($this->methodParameterClosureTypeExtensions->getAll() as $extension) {
					if ($extension->isMethodSupported($calleeReflection, $parameter)) {
						return $extension->getTypeFromMethodCall($calleeReflection, $callLike, $parameter, $scope);
					}
				}
			}
		}

		return null;
	}

}
