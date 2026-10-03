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
	 * The method is marked impure, so its body is not checked for purity
	 * although it inherits @pure-unless-parameter-passed.
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
