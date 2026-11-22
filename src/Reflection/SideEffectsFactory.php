<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use function array_filter;
use function array_values;
use function explode;
use function in_array;
use function ltrim;
use function preg_match;
use function trim;

final class SideEffectsFactory
{

	/**
	 * Derives the side effects of a function or method from the variants'
	 * parameters flagged as pure unless the callable is impure or unless they
	 * are passed, and from $effects when the function lists them. Without the
	 * list, when it is ['*'], or when it is empty but hasSideEffects() says
	 * Yes, such as for a method returning void, any effect is as possible as
	 * hasSideEffects() says. '*' next to effect names, such as
	 * ['*', 'writes-global.error.json'], names some effects of a function
	 * that can also have any other effect as much as hasSideEffects() says.
	 * A listed writes-args($a, $b) makes $a and $b written parameters. A
	 * written parameter passed by reference that is also read makes the
	 * result nondeterministic.
	 *
	 * @param list<string>|null $effects
	 */
	public static function createFromReflection(FunctionReflection|ExtendedMethodReflection $reflection, ?array $effects = null): SideEffects
	{
		if ($effects === ['*'] || ($effects === [] && $reflection->hasSideEffects()->yes())) {
			$effects = null;
		} elseif ($effects !== null && in_array('*', $effects, true)) {
			$named = array_values(array_filter($effects, static fn (string $effect): bool => $effect !== '*'));

			return SideEffects::create($named, $reflection->hasSideEffects());
		}

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

		$writtenParameters = self::mergeVariants($written);
		foreach ($effects ?? [] as $effect) {
			if (preg_match('~^writes-args\((.+)\)$~', $effect, $matches) !== 1) {
				continue;
			}

			foreach (explode(',', $matches[1]) as $parameterName) {
				$writtenParameters[ltrim(trim($parameterName), '$')] = TrinaryLogic::createYes();
			}
		}

		// a function whose effects are listed writes what it takes by reference
		if ($effects !== null) {
			foreach ($reflection->getVariants() as $variant) {
				foreach ($variant->getParameters() as $parameter) {
					if (!$parameter->passedByReference()->yes()) {
						continue;
					}

					$writtenParameters[$parameter->getName()] = TrinaryLogic::createYes();
				}
			}
		}

		$sideEffects = SideEffects::create(
			$effects ?? [],
			$effects !== null ? TrinaryLogic::createNo() : $reflection->hasSideEffects(),
			self::mergeVariants($called),
			$writtenParameters,
		);

		$readsWrittenArguments = false;
		foreach ($reflection->getVariants() as $variant) {
			foreach ($variant->getParameters() as $parameter) {
				if (
					!$parameter->passedByReference()->yes()
					|| $parameter->passedByReference()->createsNewVariable()
					|| !$sideEffects->writesParameter($parameter->getName())->yes()
				) {
					continue;
				}

				$readsWrittenArguments = true;
			}
		}

		if (!$readsWrittenArguments) {
			return $sideEffects;
		}

		return SideEffects::create(
			$sideEffects->getEffectNames(),
			$effects !== null ? TrinaryLogic::createNo() : $reflection->hasSideEffects(),
			$sideEffects->getCalledParameters(),
			$writtenParameters,
			true,
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
