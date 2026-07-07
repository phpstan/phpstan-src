<?php declare(strict_types = 1);

namespace PureUnlessParameterPassedFunction;

/**
 * @param-out int $count
 * @pure-unless-parameter-passed $count
 */
function myReplace(string $subject, int &$count = 0): string
{
	$count = 1;

	return $subject;
}

/**
 * @param-out int $count
 * @phpstan-pure-unless-parameter-passed $count
 */
function myReplacePhpstanAlias(string $subject, int &$count = 0): string
{
	$count = 1;

	return $subject;
}

/**
 * @phpstan-pure
 */
function pureNotPassingByRef(string $s): string
{
	// $count is omitted, so myReplace() stays pure.
	return myReplace($s);
}

/**
 * @phpstan-pure
 */
function purePassingByRef(string $s): string
{
	// $count is passed, so myReplace() is possibly impure.
	$count = 0;

	return myReplace($s, $count);
}

/**
 * @phpstan-pure
 */
function pureNotPassingByRefAlias(string $s): string
{
	return myReplacePhpstanAlias($s);
}

/**
 * @phpstan-pure
 */
function purePassingByRefAlias(string $s): string
{
	$count = 0;

	return myReplacePhpstanAlias($s, $count);
}
