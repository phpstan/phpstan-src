<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function in_array;
use function str_starts_with;
use function strpos;
use function substr;

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
 * written parameters are those flagged as pure unless the parameter is passed,
 * and those named by a listed writes-args($a, $b): the call writes to the
 * argument passed there.
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
		private bool $readsWrittenArguments,
	)
	{
	}

	public static function none(): self
	{
		return new self([], TrinaryLogic::createNo(), [], [], false);
	}

	public static function all(): self
	{
		return new self([], TrinaryLogic::createYes(), [], [], false);
	}

	public static function unknown(): self
	{
		return new self([], TrinaryLogic::createMaybe(), [], [], false);
	}

	/**
	 * $readsWrittenArguments says that a written parameter is passed by
	 * reference and also read, like array_shift()'s $array: the call changes
	 * what its next call returns.
	 *
	 * @param list<string> $effects
	 * @param array<string, TrinaryLogic> $calledParameters
	 * @param array<string, TrinaryLogic> $writtenParameters
	 */
	public static function create(array $effects, TrinaryLogic $others, array $calledParameters = [], array $writtenParameters = [], bool $readsWrittenArguments = false): self
	{
		return new self($effects, $others, $calledParameters, $writtenParameters, $readsWrittenArguments);
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
			$listed = self::baseName($listed);
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
	 * is written. A call that writes an argument it reads is not: the next call
	 * gets the written value. The callables passed to the called parameters do
	 * not count: a call only knows its own effects.
	 */
	public function isDeterministic(): TrinaryLogic
	{
		if ($this->readsWrittenArguments) {
			return TrinaryLogic::createNo();
		}

		return $this->has('nondeterministic')->negate();
	}

	/**
	 * Whether every effect of the call is one of $effects or a sub-effect of
	 * one: hasOnly(['nondeterministic']) is Yes for nondeterministic.time. A
	 * listed parent of one of them, or unlisted effects that are only possible,
	 * make it Maybe. The written parameters count as writes-args. The callables
	 * passed to the called parameters do not count: a call only knows its own
	 * effects.
	 *
	 * @param list<string> $effects
	 */
	public function hasOnly(array $effects): TrinaryLogic
	{
		if ($this->others->yes()) {
			return TrinaryLogic::createNo();
		}

		$result = $this->others->negate();
		foreach ($this->effects as $listed) {
			$listed = self::baseName($listed);
			$listedResult = TrinaryLogic::createNo();
			foreach ($effects as $effect) {
				if ($listed === $effect || str_starts_with($listed, $effect . '.')) {
					$listedResult = TrinaryLogic::createYes();
					break;
				}
				if (!str_starts_with($effect, $listed . '.')) {
					continue;
				}

				$listedResult = TrinaryLogic::createMaybe();
			}

			$result = $result->and($listedResult);
		}

		if (!in_array('writes-args', $effects, true)) {
			foreach ($this->writtenParameters as $written) {
				$result = $result->and($written->negate());
			}
		}

		return $result;
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
	 * listed writes-args without parameters writes to every argument;
	 * writes-args($a, $b) writes only to those, which are written parameters.
	 */
	public function writesParameter(string $parameterName): TrinaryLogic
	{
		if (isset($this->writtenParameters[$parameterName])) {
			return $this->writtenParameters[$parameterName];
		}

		return in_array('writes-args', $this->effects, true) ? TrinaryLogic::createYes() : $this->others;
	}

	/** The name of a listed effect without its parameters: writes-args for writes-args($a). */
	private static function baseName(string $listed): string
	{
		$position = strpos($listed, '(');

		return $position === false ? $listed : substr($listed, 0, $position);
	}

}
