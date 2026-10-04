<?php declare(strict_types = 1);

namespace PureUnlessImpureOverride;

interface Replacer
{

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	public function replace(string $subject, int &$count = 0): string;

	/**
	 * @param callable(string): string $cb
	 * @pure-unless-callable-is-impure $cb
	 */
	public function map(callable $cb, string $subject): string;

}

final class ImpureReplacer implements Replacer
{

	/**
	 * @phpstan-impure on the method replaces the inherited
	 * @pure-unless-parameter-passed, so the body is not checked for purity.
	 * MethodSignatureRule reports the override instead.
	 *
	 * @phpstan-impure
	 */
	public function replace(string $subject, int &$count = 0): string
	{
		echo $subject;
		$count = 1;

		return $subject;
	}

	/**
	 * @phpstan-impure
	 */
	public function map(callable $cb, string $subject): string
	{
		echo $subject;

		return $cb($subject);
	}

}

final class ImpureReplacerWithoutSideEffects implements Replacer
{

	/**
	 * @phpstan-impure
	 */
	public function replace(string $subject, int &$count = 0): string
	{
		return $subject;
	}

	/**
	 * @phpstan-impure
	 */
	public function map(callable $cb, string $subject): string
	{
		return $subject;
	}

}

final class InheritingReplacer implements Replacer
{

	public function replace(string $subject, int &$count = 0): string
	{
		echo $subject;
		$count = 1;

		return $subject;
	}

	public function map(callable $cb, string $subject): string
	{
		echo $subject;

		return $cb($subject);
	}

}

final class PureReplacer implements Replacer
{

	/**
	 * @phpstan-pure on the method replaces the inherited
	 * @pure-unless-parameter-passed, so writing to $count is not exempt.
	 *
	 * @phpstan-pure
	 */
	public function replace(string $subject, int &$count = 0): string
	{
		return $subject;
	}

	/**
	 * @phpstan-pure on the method replaces the inherited
	 * @pure-unless-callable-is-impure, so invoking $cb is not exempt.
	 *
	 * @phpstan-pure
	 */
	public function map(callable $cb, string $subject): string
	{
		return $cb($subject);
	}

}
