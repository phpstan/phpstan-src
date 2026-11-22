<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function str_starts_with;

/**
 * The side effects of a function or method, as returned by getSideEffects().
 *
 * A function has the named effects, such as io.output, and possibly any other
 * effect as much as $others says: No when the list is known to be complete,
 * Maybe when nothing is known, Yes when it has some effects that are not
 * named.
 *
 * The called parameters are those flagged as pure unless the callable is
 * impure: the call has the side effects of the callable passed there. The
 * written parameters are those flagged as pure unless the parameter is passed:
 * the call writes to the argument passed there.
 *
 * @api
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/SideEffects.cpp')]
final class SideEffects
{

	/**
	 * @param list<string> $effects
	 * @param array<string, TrinaryLogic> $calledParameters
	 * @param array<string, TrinaryLogic> $writtenParameters
	 */
	private function __construct(
		private array $effects,
		private TrinaryLogic $others,
		private array $calledParameters,
		private array $writtenParameters,
	)
	{
	}

	public static function none(): self
	{
		return new self([], TrinaryLogic::createNo(), [], []);
	}

	public static function all(): self
	{
		return new self([], TrinaryLogic::createYes(), [], []);
	}

	public static function unknown(): self
	{
		return new self([], TrinaryLogic::createMaybe(), [], []);
	}

	/**
	 * @param list<string> $effects
	 * @param array<string, TrinaryLogic> $calledParameters
	 * @param array<string, TrinaryLogic> $writtenParameters
	 */
	public static function create(array $effects, TrinaryLogic $others, array $calledParameters = [], array $writtenParameters = []): self
	{
		return new self($effects, $others, $calledParameters, $writtenParameters);
	}

	/** Maybe when only the called and written parameters can bring effects in. */
	public function hasAny(): TrinaryLogic
	{
		if ($this->effects !== []) {
			return TrinaryLogic::createYes();
		}

		if ($this->others->no() && ($this->calledParameters !== [] || $this->writtenParameters !== [])) {
			return TrinaryLogic::createMaybe();
		}

		return $this->others;
	}

	/**
	 * Whether the call has the effect, or one of its sub-effects: has('io') is
	 * Yes for io.files. A listed parent effect makes a sub-effect Maybe.
	 */
	public function has(string $effect): TrinaryLogic
	{
		$result = $this->others;
		foreach ($this->effects as $listed) {
			if ($listed === $effect || str_starts_with($listed, $effect . '.')) {
				return TrinaryLogic::createYes();
			}
			if (!str_starts_with($effect, $listed . '.')) {
				continue;
			}

			$result = $result->or(TrinaryLogic::createMaybe());
		}

		return $result;
	}

	/**
	 * Whether calling it again with the same arguments gives the same result.
	 * A result that depends on global state is deterministic until the state
	 * is written. The callables passed to the called parameters do not count:
	 * a call only knows its own effects.
	 */
	public function isDeterministic(): TrinaryLogic
	{
		return $this->has('nondeterministic')->negate();
	}

	/**
	 * The listed names, for messages, the reflection golden test, and telling
	 * a listed effect from one only possible through unlisted effects.
	 *
	 * @return list<string>
	 */
	public function getEffectNames(): array
	{
		return $this->effects;
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

	/**
	 * Whether the call writes to the argument passed to the parameter. A
	 * listed writes-args without parameters writes to every argument.
	 */
	public function writesParameter(string $parameterName): TrinaryLogic
	{
		return $this->writtenParameters[$parameterName] ?? $this->has('writes-args');
	}

}
