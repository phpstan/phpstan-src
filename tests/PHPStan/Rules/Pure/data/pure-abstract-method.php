<?php declare(strict_types = 1);

namespace PureAbstractMethod;

interface Foo
{

	/**
	 * @phpstan-pure
	 */
	public function pureByRef(int &$p): int;

	/**
	 * @phpstan-pure
	 */
	public function pureVoid(): void;

	/**
	 * @phpstan-pure
	 * @throws \Exception
	 */
	public function pureVoidThrowing(): void;

	/**
	 * @phpstan-pure
	 */
	public function pureFine(int $p): int;

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	public function requiredCount(string $subject, int &$count): string;

	/**
	 * @pure-unless-parameter-passed $flag
	 */
	public function byValueFlag(string $subject, bool $flag = false): string;

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	public function optionalCount(string $subject, int &$count = 0): string;

	/**
	 * @param pure-callable(int): int $cb
	 * @pure-unless-callable-is-impure $cb
	 */
	public function alreadyPureCallback(callable $cb): int;

	/**
	 * @param callable(int): int $cb
	 * @pure-unless-callable-is-impure $cb
	 */
	public function callback(callable $cb): int;

}

abstract class Bar
{

	/**
	 * @phpstan-pure
	 */
	abstract public function pureByRef(int &$p): int;

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	abstract public function requiredCount(string $subject, int &$count): string;

	/**
	 * A method with a body is left to PureMethodRule.
	 *
	 * @phpstan-pure
	 */
	public function withBody(int &$p): int
	{
		return $p;
	}

}
