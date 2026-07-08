<?php // lint >= 8.0

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

/**
 * @phpstan-pure
 */
function pureUnpackingArgs(string $s): string
{
	$args = [$s];
	// An unpacked argument list might supply $count, so this stays possibly impure.
	return myReplace(...$args);
}

/**
 * @phpstan-pure
 */
function pureNamedArgForOtherParameter(string $s): string
{
	// The named argument targets $subject, not the flagged $count, so this stays pure.
	return myReplace(subject: $s);
}

/**
 * @phpstan-pure
 */
function pureNamedArgForFlaggedParameter(string $s): string
{
	$count = 0;
	// The named argument explicitly targets the flagged $count parameter.
	return myReplace(subject: $s, count: $count);
}

class MyReplacerConstructor
{

	public string $s;

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	public function __construct(string $s, int &$count = 0)
	{
		$this->s = $s;
		$count = 1;
	}

}

/**
 * @phpstan-pure
 */
function pureConstructorNotPassingByRef(string $s): MyReplacerConstructor
{
	// $count is omitted, so instantiation stays pure.
	return new MyReplacerConstructor($s);
}

/**
 * @phpstan-pure
 */
function pureConstructorPassingByRef(string $s): MyReplacerConstructor
{
	$count = 0;
	// $count is passed, so instantiation is impure (the flag is certain).
	return new MyReplacerConstructor($s, $count);
}

interface PureUnlessParameterPassedA
{

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	public function m(string $s, int &$count = 0): string;

}

interface PureUnlessParameterPassedB
{

	public function m(string $s, int &$count = 0): string;

}

/**
 * @param PureUnlessParameterPassedA|PureUnlessParameterPassedB $obj
 * @phpstan-pure
 */
function pureUnionMethodOmittingCount($obj, string $s): string
{
	// The flag is Yes in A and absent (No) in B, so combineAcceptors() merges it to Maybe.
	// $count is omitted here, so the call stays pure regardless of the flag's certainty.
	return $obj->m($s);
}

/**
 * @param PureUnlessParameterPassedA|PureUnlessParameterPassedB $obj
 * @phpstan-pure
 */
function pureUnionMethodPassingCount($obj, string $s): string
{
	$count = 0;
	// $count is passed against the Maybe-flagged parameter, so this is possibly impure.
	return $obj->m($s, $count);
}
