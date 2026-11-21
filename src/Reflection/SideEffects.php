<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use function array_filter;

/**
 * The side effects of a function or method, as returned by getSideEffects().
 *
 * hasAny() answers what hasSideEffects() answers. The called parameters are
 * those flagged as pure unless the callable is impure: the call has the side
 * effects of the callable passed there. The written parameters are those
 * flagged as pure unless the parameter is passed: the call writes to the
 * argument passed there.
 *
 * @api
 */
final class SideEffects
{

	/**
	 * @param array<string, TrinaryLogic> $calledParameters
	 * @param array<string, TrinaryLogic> $writtenParameters
	 */
	private function __construct(
		private TrinaryLogic $any,
		private array $calledParameters,
		private array $writtenParameters,
	)
	{
	}

	public static function none(): self
	{
		return new self(TrinaryLogic::createNo(), [], []);
	}

	public static function all(): self
	{
		return new self(TrinaryLogic::createYes(), [], []);
	}

	public static function unknown(): self
	{
		return new self(TrinaryLogic::createMaybe(), [], []);
	}

	/**
	 * Derives the side effects from hasSideEffects() and the conditional purity
	 * flags of the variants' parameters.
	 */
	public static function createFromReflection(FunctionReflection|ExtendedMethodReflection $reflection): self
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

		return new self($reflection->hasSideEffects(), self::mergeVariants($called), self::mergeVariants($written));
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

	public function hasAny(): TrinaryLogic
	{
		return $this->any;
	}

	/**
	 * @return array<string, TrinaryLogic>
	 */
	public function getCalledParameters(): array
	{
		return $this->calledParameters;
	}

	/**
	 * @return array<string, TrinaryLogic>
	 */
	public function getWrittenParameters(): array
	{
		return $this->writtenParameters;
	}

}
