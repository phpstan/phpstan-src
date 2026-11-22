<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use function in_array;
use function str_starts_with;
use function strlen;
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

	public const WRITES_THIS = 'writes-this';
	public const WRITES_ARGS = 'writes-args';
	public const WRITES_GLOBAL = 'writes-global';
	public const READS_GLOBAL = 'reads-global';
	public const NONDETERMINISTIC = 'nondeterministic';
	public const NONDETERMINISTIC_TIME = 'nondeterministic.time';
	public const NONDETERMINISTIC_RANDOM = 'nondeterministic.random';
	public const IO = 'io';
	public const IO_OUTPUT = 'io.output';
	public const IO_FILES = 'io.files';
	public const IO_NETWORK = 'io.network';
	public const IO_DB = 'io.db';
	public const IO_PROCESS = 'io.process';
	public const SLEEP = 'sleep';
	public const EXIT = 'exit';
	public const WARNS = 'warns';

	/**
	 * Named parts of global state. A call that writes one forgets only the
	 * remembered calls that read it, such as is_file($a) after clearstatcache().
	 */
	public const WRITES_GLOBAL_STAT_CACHE = 'writes-global.stat-cache';
	public const READS_GLOBAL_STAT_CACHE = 'reads-global.stat-cache';

	/**
	 * The settings of the mbstring extension, such as the internal encoding the
	 * mb_*() functions fall back to without an $encoding argument, and the
	 * position of mb_ereg_search().
	 */
	public const WRITES_GLOBAL_MBSTRING = 'writes-global.mbstring';
	public const READS_GLOBAL_MBSTRING = 'reads-global.mbstring';

	/**
	 * The last error of an extension, which its error getters read. Writing it
	 * is allowed in pure functions and makes a call on its own line useful.
	 */
	public const WRITES_GLOBAL_ERROR = 'writes-global.error';
	public const WRITES_GLOBAL_ERROR_JSON = 'writes-global.error.json';
	public const READS_GLOBAL_ERROR_JSON = 'reads-global.error.json';
	public const WRITES_GLOBAL_ERROR_PREG = 'writes-global.error.preg';
	public const READS_GLOBAL_ERROR_PREG = 'reads-global.error.preg';
	public const WRITES_GLOBAL_ERROR_OPENSSL = 'writes-global.error.openssl';
	public const READS_GLOBAL_ERROR_OPENSSL = 'reads-global.error.openssl';
	public const WRITES_GLOBAL_ERROR_INTL = 'writes-global.error.intl';
	public const READS_GLOBAL_ERROR_INTL = 'reads-global.error.intl';

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

		return $this->has(self::NONDETERMINISTIC)->negate();
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

		if (!in_array(self::WRITES_ARGS, $effects, true)) {
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
	 * The listed sub-effects of $effect without its prefix, such as
	 * ['stat-cache'] for writes-global.stat-cache: the named parts of the
	 * global state a call writes or reads. Null when $effect itself is listed,
	 * [] when neither is. Unlisted effects do not count.
	 *
	 * @return list<string>|null
	 */
	public function getListedSubEffects(string $effect): ?array
	{
		$subEffects = [];
		foreach ($this->effects as $listed) {
			$listed = self::baseName($listed);
			if ($listed === $effect) {
				return null;
			}
			if (!str_starts_with($listed, $effect . '.')) {
				continue;
			}

			$subEffects[] = substr($listed, strlen($effect) + 1);
		}

		return $subEffects;
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

		return in_array(self::WRITES_ARGS, $this->effects, true) ? TrinaryLogic::createYes() : $this->others;
	}

	/** The name of a listed effect without its parameters: writes-args for writes-args($a). */
	private static function baseName(string $listed): string
	{
		$position = strpos($listed, '(');

		return $position === false ? $listed : substr($listed, 0, $position);
	}

}
