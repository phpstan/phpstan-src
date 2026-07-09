<?php // lint >= 8.1

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
	// $count is passed, so myReplace() is impure (the flag is certain).
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

interface PureUnlessParameterPassedIntersectionA
{

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	public function m(string $s, int &$count = 0): string;

}

interface PureUnlessParameterPassedIntersectionB
{

	public function n(): string;

}

/**
 * @param PureUnlessParameterPassedIntersectionA&PureUnlessParameterPassedIntersectionB $obj
 * @phpstan-pure
 */
function pureIntersectionMethodOmittingCount($obj, string $s): string
{
	// $count is omitted here, so the call stays pure.
	return $obj->m($s);
}

/**
 * @param PureUnlessParameterPassedIntersectionA&PureUnlessParameterPassedIntersectionB $obj
 * @phpstan-pure
 */
function pureIntersectionMethodPassingCount($obj, string $s): string
{
	$count = 0;
	// $count is passed, so this is impure (the flag is certain).
	return $obj->m($s, $count);
}

class Replacer
{

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	public function replace(string $subject, int &$count = 0): string
	{
		$count = 1;

		return $subject;
	}

}

/**
 * @phpstan-pure
 */
function pureCallingMethodNotPassingByRef(Replacer $replacer, string $s): string
{
	// $count is omitted, so the call stays pure.
	return $replacer->replace($s);
}

/**
 * @phpstan-pure
 */
function pureCallingMethodPassingByRef(Replacer $replacer, string $s): string
{
	$count = 0;
	// $count is passed, so the call is impure (the flag is certain).
	return $replacer->replace($s, $count);
}

abstract class InheritedReplacerParent
{

	/**
	 * @param-out int $count
	 * @pure-unless-parameter-passed $count
	 */
	abstract public function replace(string $subject, int &$count = 0): string;

}

class InheritedReplacerChild extends InheritedReplacerParent
{

	public function replace(string $subject, int &$count = 0): string
	{
		$count = 1;

		return $subject;
	}

}

class InheritedReplacerRenamedChild extends InheritedReplacerParent
{

	public function replace(string $subject, int &$cnt = 0): string
	{
		$cnt = 1;

		return $subject;
	}

}

/**
 * @phpstan-pure
 */
function pureCallingInheritedMethodNotPassingByRef(InheritedReplacerChild $replacer, string $s): string
{
	// The child does not re-declare @pure-unless-parameter-passed; it is inherited
	// from the parent. $count is omitted here, so the call stays pure.
	return $replacer->replace($s);
}

/**
 * @phpstan-pure
 */
function pureCallingInheritedMethodPassingByRef(InheritedReplacerChild $replacer, string $s): string
{
	$count = 0;
	// The inherited @pure-unless-parameter-passed applies to the child, so passing
	// $count makes the call impure (the flag is certain).
	return $replacer->replace($s, $count);
}

/**
 * @phpstan-pure
 */
function pureCallingRenamedInheritedMethodNotPassingByRef(InheritedReplacerRenamedChild $replacer, string $s): string
{
	// The parent flags $count; the child renames it to $cnt. The inherited flag
	// still applies to the renamed parameter. It is omitted here, so this stays pure.
	return $replacer->replace($s);
}

/**
 * @phpstan-pure
 */
function pureCallingRenamedInheritedMethodPassingByRef(InheritedReplacerRenamedChild $replacer, string $s): string
{
	$cnt = 0;
	// The inherited flag applies to the renamed parameter, so passing it makes
	// the call impure (the flag is certain).
	return $replacer->replace($s, $cnt);
}

/**
 * @phpstan-pure
 */
function pureCallingFirstClassCallableOmittingCount(string $s): string
{
	$f = myReplace(...);
	// A first-class callable's purity is evaluated from its ParametersAcceptor
	// alone, without scope/args, so @pure-unless-parameter-passed cannot gate on
	// whether $count is actually passed at this call site; it stays possibly
	// impure even though $count is omitted here.
	return $f($s);
}

/**
 * @phpstan-pure
 */
function pureCallingFirstClassCallablePassingCount(string $s): string
{
	$count = 0;
	$f = myReplace(...);
	// The first-class callable is called with the flagged $count passed, so this
	// stays possibly impure.
	return $f($s, $count);
}

/**
 * @param-out int $count
 * @pure-unless-parameter-passed $count
 */
function nonOptionalUnlessParameterPassed(string $subject, int &$count): string
{
	// $count is not optional, so it is always passed and the tag can never keep
	// the function pure.
	$count = 1;

	return $subject;
}

/**
 * @param-out int $count
 * @pure-unless-parameter-passed $count
 */
function myReplaceVariadic(string $subject, int &$count = 0, string ...$extra): string
{
	$count = 1;

	return $subject;
}

/**
 * @phpstan-pure
 */
function pureVariadicOmittingCount(string $s): string
{
	// The signature has a trailing variadic, but the flagged $count is omitted, so
	// the call stays pure.
	return myReplaceVariadic($s);
}

/**
 * @phpstan-pure
 */
function pureVariadicPassingCount(string $s): string
{
	$count = 0;
	// $count is passed, so the call is impure regardless of the trailing variadic.
	return myReplaceVariadic($s, $count);
}

/**
 * @phpstan-pure
 */
function pureVariadicPassingCountWithExtra(string $s): string
{
	$count = 0;
	// $count is passed and the trailing variadic is also filled; the extra variadic
	// arguments do not affect the flagged parameter, so the call is impure.
	return myReplaceVariadic($s, $count, 'a', 'b');
}
