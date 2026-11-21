<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use function array_filter;

final class SideEffectsFactory
{

	/**
	 * Derives the side effects of a function or method from the variants'
	 * parameters flagged as pure unless the callable is impure or unless they
	 * are passed, and from $effects when the function lists them. Without the
	 * list, any effect is as possible as hasSideEffects() says.
	 *
	 * @param list<string>|null $effects
	 */
	public static function createFromReflection(FunctionReflection|ExtendedMethodReflection $reflection, ?array $effects = null): SideEffects
	{
		$called = [];
		$written = [];
		foreach ($reflection->getVariants() as $variant) {
			$variantCalled = [];
			$variantWritten = [];
			foreach ($variant->getParameters() as $parameter) {
				$variantCalled[$parameter->getName()] = $parameter->isPureUnlessCallableIsImpureParameter();
				$variantWritten[$parameter->getName()] = $parameter->isPureUnlessParameterPassedParameter();
			}
			$called[] = $variantCalled;
			$written[] = $variantWritten;
		}

		return SideEffects::create(
			$effects ?? [],
			$effects !== null ? TrinaryLogic::createNo() : $reflection->hasSideEffects(),
			self::mergeVariants($called),
			self::mergeVariants($written),
		);
	}

	/**
	 * A parameter flagged differently by the variants that have it is flagged
	 * Maybe. A variant without the parameter does not count: it cannot pass
	 * anything to it.
	 *
	 * @param list<array<string, TrinaryLogic>> $variants
	 * @return array<string, TrinaryLogic>
	 */
	private static function mergeVariants(array $variants): array
	{
		$merged = [];
		foreach ($variants as $variant) {
			foreach ($variant as $name => $flag) {
				$merged[$name] = isset($merged[$name]) && !$merged[$name]->equals($flag) ? TrinaryLogic::createMaybe() : $flag;
			}
		}

		return array_filter($merged, static fn (TrinaryLogic $flag): bool => !$flag->no());
	}

}
