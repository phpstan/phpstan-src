<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;

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
	 * For messages and the reflection golden test only, never for comparisons.
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

	/** Whether the call writes to the argument passed to the parameter. */
	public function writesParameter(string $parameterName): TrinaryLogic
	{
		return $this->writtenParameters[$parameterName] ?? $this->others;
	}

}
