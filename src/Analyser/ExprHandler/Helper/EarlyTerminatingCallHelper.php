<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Type;
use function array_key_exists;
use function array_merge;
use function in_array;
use function strtolower;

/**
 * Decides whether a method/function call is configured as early-terminating
 * (`parameters.earlyTerminatingMethodCalls` / `earlyTerminatingFunctionCalls`).
 * The call handlers use this to give such a call an explicit NeverType, from
 * which NodeScopeResolver derives the statement's exit point - so the engine no
 * longer reaches Scope::getType() to find early-terminating expressions.
 */
#[AutowiredService]
final class EarlyTerminatingCallHelper
{

	/** @var array<string, true> */
	private array $earlyTerminatingMethodNames;

	/**
	 * @param string[][] $earlyTerminatingMethodCalls className(string) => methods(string[])
	 * @param array<int, string> $earlyTerminatingFunctionCalls
	 */
	public function __construct(
		private ReflectionProvider $reflectionProvider,
		#[AutowiredParameter]
		private array $earlyTerminatingMethodCalls,
		#[AutowiredParameter]
		private array $earlyTerminatingFunctionCalls,
	)
	{
		$earlyTerminatingMethodNames = [];
		foreach ($this->earlyTerminatingMethodCalls as $methodNames) {
			foreach ($methodNames as $methodName) {
				$earlyTerminatingMethodNames[strtolower($methodName)] = true;
			}
		}
		$this->earlyTerminatingMethodNames = $earlyTerminatingMethodNames;
	}

	public function isEarlyTerminatingMethodCall(string $methodName, Type $calledOnType): bool
	{
		if (!array_key_exists(strtolower($methodName), $this->earlyTerminatingMethodNames)) {
			return false;
		}

		foreach ($calledOnType->getObjectClassNames() as $referencedClass) {
			if (!$this->reflectionProvider->hasClass($referencedClass)) {
				continue;
			}

			$classReflection = $this->reflectionProvider->getClass($referencedClass);
			foreach (array_merge([$referencedClass], $classReflection->getParentClassesNames(), $classReflection->getNativeReflection()->getInterfaceNames()) as $className) {
				if (!isset($this->earlyTerminatingMethodCalls[$className])) {
					continue;
				}

				if (in_array($methodName, $this->earlyTerminatingMethodCalls[$className], true)) {
					return true;
				}
			}
		}

		return false;
	}

	public function isEarlyTerminatingFunctionCall(string $functionName): bool
	{
		return in_array($functionName, $this->earlyTerminatingFunctionCalls, true);
	}

}
