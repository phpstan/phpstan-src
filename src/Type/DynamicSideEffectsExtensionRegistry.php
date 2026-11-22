<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ExtensionsCollection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\SideEffects;
use function strtolower;

/**
 * Asks the dynamic side effects extensions for the side effects of one call.
 * null means no extension describes it and the reflection's side effects apply.
 */
#[AutowiredService]
final class DynamicSideEffectsExtensionRegistry
{

	/** @var array<string, list<DynamicFunctionSideEffectsExtension>> */
	private array $functionExtensionsByName = [];

	/** @var array<string, list<DynamicMethodSideEffectsExtension>> */
	private array $methodExtensionsByName = [];

	/** @var array<string, list<DynamicStaticMethodSideEffectsExtension>> */
	private array $staticMethodExtensionsByName = [];

	/**
	 * @param ExtensionsCollection<DynamicFunctionSideEffectsExtension> $functionExtensions
	 * @param ExtensionsCollection<DynamicMethodSideEffectsExtension> $methodExtensions
	 * @param ExtensionsCollection<DynamicStaticMethodSideEffectsExtension> $staticMethodExtensions
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		#[AutowiredExtensions(of: DynamicFunctionSideEffectsExtension::class)]
		private ExtensionsCollection $functionExtensions,
		#[AutowiredExtensions(of: DynamicMethodSideEffectsExtension::class)]
		private ExtensionsCollection $methodExtensions,
		#[AutowiredExtensions(of: DynamicStaticMethodSideEffectsExtension::class)]
		private ExtensionsCollection $staticMethodExtensions,
	)
	{
	}

	public function getFunctionCallSideEffects(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?SideEffects
	{
		$name = $functionReflection->getName();
		if (!isset($this->functionExtensionsByName[$name])) {
			$supported = [];
			foreach ($this->functionExtensions->getAll() as $extension) {
				if (!$extension->isFunctionSupported($functionReflection)) {
					continue;
				}

				$supported[] = $extension;
			}
			$this->functionExtensionsByName[$name] = $supported;
		}

		foreach ($this->functionExtensionsByName[$name] as $extension) {
			$sideEffects = $extension->getSideEffectsFromFunctionCall($functionReflection, $functionCall, $scope);
			if ($sideEffects !== null) {
				return $sideEffects;
			}
		}

		return null;
	}

	public function getMethodCallSideEffects(ExtendedMethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?SideEffects
	{
		if ($this->methodExtensions->getAll() === []) {
			return null;
		}

		$key = strtolower($methodReflection->getDeclaringClass()->getName() . '::' . $methodReflection->getName());
		if (!isset($this->methodExtensionsByName[$key])) {
			$supported = [];
			foreach ($this->methodExtensions->getAll() as $extension) {
				if (!$this->isExtensionForClass($extension->getClass(), $methodReflection) || !$extension->isMethodSupported($methodReflection)) {
					continue;
				}

				$supported[] = $extension;
			}
			$this->methodExtensionsByName[$key] = $supported;
		}

		foreach ($this->methodExtensionsByName[$key] as $extension) {
			$sideEffects = $extension->getSideEffectsFromMethodCall($methodReflection, $methodCall, $scope);
			if ($sideEffects !== null) {
				return $sideEffects;
			}
		}

		return null;
	}

	public function getStaticCallSideEffects(ExtendedMethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?SideEffects
	{
		if ($this->staticMethodExtensions->getAll() === []) {
			return null;
		}

		$key = strtolower($methodReflection->getDeclaringClass()->getName() . '::' . $methodReflection->getName());
		if (!isset($this->staticMethodExtensionsByName[$key])) {
			$supported = [];
			foreach ($this->staticMethodExtensions->getAll() as $extension) {
				if (!$this->isExtensionForClass($extension->getClass(), $methodReflection) || !$extension->isStaticMethodSupported($methodReflection)) {
					continue;
				}

				$supported[] = $extension;
			}
			$this->staticMethodExtensionsByName[$key] = $supported;
		}

		foreach ($this->staticMethodExtensionsByName[$key] as $extension) {
			$sideEffects = $extension->getSideEffectsFromStaticMethodCall($methodReflection, $methodCall, $scope);
			if ($sideEffects !== null) {
				return $sideEffects;
			}
		}

		return null;
	}

	/** The extension's class is the declaring class of the method or one of its ancestors. */
	private function isExtensionForClass(string $extensionClass, ExtendedMethodReflection $methodReflection): bool
	{
		$extensionClass = strtolower($extensionClass);
		foreach (ExtensionClassHelper::getExtensionClassNames($this->reflectionProvider, $methodReflection->getDeclaringClass()->getName()) as $className) {
			if (strtolower($className) === $extensionClass) {
				return true;
			}
		}

		return false;
	}

}
