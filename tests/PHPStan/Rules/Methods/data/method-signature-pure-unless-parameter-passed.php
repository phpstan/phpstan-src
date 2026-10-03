<?php declare(strict_types = 1);

namespace MethodSignaturePureUnlessParameterPassed;

interface PureUnlessParent
{

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	public function replace(string $subject, int &$count = 0): string;

}

class ImpureChild implements PureUnlessParent
{

	/**
	 * @phpstan-impure
	 */
	public function replace(string $subject, int &$count = 0): string
	{
		echo 'side effect';
		$count = 1;

		return $subject;
	}

}

class InheritingChild implements PureUnlessParent
{

	public function replace(string $subject, int &$count = 0): string
	{
		$count = 1;

		return $subject;
	}

}

class PureChild implements PureUnlessParent
{

	/**
	 * @phpstan-pure
	 */
	public function replace(string $subject, int &$count = 0): string
	{
		return $subject;
	}

}
