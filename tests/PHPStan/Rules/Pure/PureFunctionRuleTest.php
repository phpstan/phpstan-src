<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<PureFunctionRule>
 */
class PureFunctionRuleTest extends RuleTestCase
{

	public function getRule(): Rule
	{
		return new PureFunctionRule(new FunctionPurityCheck());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/pure-function.php'], [
			[
				'Function PureFunction\doFoo() is marked as pure but parameter $p is passed by reference.',
				8,
			],
			[
				'Impure echo in pure function PureFunction\doFoo().',
				10,
			],
			[
				'Function PureFunction\doFoo2() is marked as pure but returns void.',
				16,
			],
			[
				'Impure exit in pure function PureFunction\doFoo2().',
				18,
			],
			[
				'Impure property assignment in pure function PureFunction\doFoo3().',
				26,
			],
			[
				'Possibly impure call to a callable in pure function PureFunction\testThese().',
				60,
			],
			[
				'Possibly impure call to a callable in pure function PureFunction\testThese().',
				61,
			],
			[
				'Impure call to function PureFunction\impureFunction() in pure function PureFunction\testThese().',
				63,
			],
			[
				'Impure call to function PureFunction\voidFunction() in pure function PureFunction\testThese().',
				64,
			],
			[
				'Possibly impure call to function PureFunction\possiblyImpureFunction() in pure function PureFunction\testThese().',
				65,
			],
			[
				'Possibly impure call to unknown function in pure function PureFunction\testThese().',
				66,
			],
			[
				'Function PureFunction\actuallyPure() is marked as impure but does not have any side effects.',
				72,
			],
			[
				'Function PureFunction\emptyVoidFunction() returns void but does not have any side effects.',
				84,
			],
			[
				'Impure access to superglobal variable in pure function PureFunction\pureButAccessSuperGlobal().',
				102,
			],
			[
				'Impure access to superglobal variable in pure function PureFunction\pureButAccessSuperGlobal().',
				103,
			],
			[
				'Impure access to superglobal variable in pure function PureFunction\pureButAccessSuperGlobal().',
				105,
			],
			[
				'Impure global variable in pure function PureFunction\functionWithGlobal().',
				118,
			],
			[
				'Impure static variable in pure function PureFunction\functionWithStaticVariable().',
				128,
			],
			[
				'Possibly impure call to a Closure in pure function PureFunction\callsClosures().',
				139,
			],
			[
				'Possibly impure call to a Closure in pure function PureFunction\callsClosures().',
				140,
			],
			[
				'Impure output between PHP opening and closing tags in pure function PureFunction\justContainsInlineHtml().',
				160,
			],
			[
				'Impure call to function array_push() in pure function PureFunction\bug13288().',
				171,
			],
			[
				'Impure call to function array_push() in pure function PureFunction\bug13288().',
				175,
			],
			[
				'Impure call to function array_push() in pure function PureFunction\bug13288().',
				182,
			],
			[
				'Impure exit in pure function PureFunction\bug13288b().',
				200,
			],
			[
				'Impure exit in pure function PureFunction\bug13288c().',
				217,
			],
			[
				'Impure exit in pure function PureFunction\bug13288d().',
				230,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testFirstClassCallable(): void
	{
		$this->analyse([__DIR__ . '/data/first-class-callable-pure-function.php'], [
			[
				'Impure call to method FirstClassCallablePureFunction\Foo::impureFunction() in pure function FirstClassCallablePureFunction\testThese().',
				61,
			],
			[
				'Impure call to method FirstClassCallablePureFunction\Foo::voidFunction() in pure function FirstClassCallablePureFunction\testThese().',
				64,
			],
			[
				'Impure call to function FirstClassCallablePureFunction\impureFunction() in pure function FirstClassCallablePureFunction\testThese().',
				70,
			],
			[
				'Impure call to function FirstClassCallablePureFunction\voidFunction() in pure function FirstClassCallablePureFunction\testThese().',
				73,
			],
			[
				'Impure call to function FirstClassCallablePureFunction\voidFunction() in pure function FirstClassCallablePureFunction\testThese().',
				75,
			],
			[
				'Impure call to function FirstClassCallablePureFunction\impureFunction() in pure function FirstClassCallablePureFunction\testThese().',
				81,
			],
			[
				'Impure call to function FirstClassCallablePureFunction\voidFunction() in pure function FirstClassCallablePureFunction\testThese().',
				84,
			],
			[
				'Impure call to method FirstClassCallablePureFunction\Foo::impureFunction() in pure function FirstClassCallablePureFunction\testThese().',
				90,
			],
			[
				'Impure call to method FirstClassCallablePureFunction\Foo::voidFunction() in pure function FirstClassCallablePureFunction\testThese().',
				93,
			],
			[
				'Possibly impure call to a callable in pure function FirstClassCallablePureFunction\callCallbackImmediately().',
				102,
			],
		]);
	}

	public function testBug11361(): void
	{
		$this->analyse([__DIR__ . '/data/bug-11361-pure.php'], [
			[
				'Impure call to a Closure with by-ref parameter in pure function Bug11361Pure\foo().',
				14,
			],
		]);
	}

	public function testBug12224(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12224.php'], [
			[
				'Function PHPStan\Rules\Pure\data\pureWithThrowsVoid() is marked as pure but returns void.',
				18,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug13201(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13201.php'], []);
	}

	public function testBug12119(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12119.php'], []);
	}

	public function testBug14504(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14504.php'], []);
	}

	public function testBug14511(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14511.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug14557(): void
	{
		$this->analyse([__DIR__ . '/data/bug-14557-function.php'], []);
	}

	public function testBug6574(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6574.php'], []);
	}

	public function testPureUnlessCallableIsImpure(): void
	{
		$this->analyse([__DIR__ . '/data/pure-unless-callable-is-impure.php'], [
			[
				'Impure call to function array_map() in pure function PureUnlessCallableIsImpureFunction\pureWithImpureCallback().',
				22,
			],
			[
				'Impure echo in pure function PureUnlessCallableIsImpureFunction\pureWithImpureCallback().',
				23,
			],
			[
				'Possibly impure call to a callable in pure function PureUnlessCallableIsImpureFunction\pureWithOpaqueCallback().',
				36,
			],
			[
				'Possibly impure call to function array_map() in pure function PureUnlessCallableIsImpureFunction\pureWithOpaqueCallback().',
				36,
			],
			[
				'Impure call to method PureUnlessCallableIsImpureFunction\Mapper::map() in pure function PureUnlessCallableIsImpureFunction\pureCallingMethodWithImpureCallback().',
				129,
			],
			[
				'Possibly impure call to method PureUnlessCallableIsImpureFunction\Mapper::map() in pure function PureUnlessCallableIsImpureFunction\pureCallingMethodWithOpaqueCallback().',
				143,
			],
			[
				'Possibly impure call to function array_map() in pure function PureUnlessCallableIsImpureFunction\pureWithMaybeNullCallback().',
				155,
			],
			[
				'Possibly impure call to function array_map() in pure function PureUnlessCallableIsImpureFunction\pureWithMaybeCallablePureCallback().',
				167,
			],
			[
				'Impure instantiation of class PureUnlessCallableIsImpureFunction\Baz in pure function PureUnlessCallableIsImpureFunction\pureInstantiatingWithImpureCallback().',
				200,
			],
			[
				'Possibly impure instantiation of class PureUnlessCallableIsImpureFunction\Baz in pure function PureUnlessCallableIsImpureFunction\pureInstantiatingWithOpaqueCallback().',
				213,
			],
			[
				'Possibly impure instantiation of class PureUnlessCallableIsImpureFunction\Baz in pure function PureUnlessCallableIsImpureFunction\pureInstantiatingWithUnionCallback().',
				238,
			],
			[
				'Impure echo in pure function PureUnlessCallableIsImpureFunction\pureUnlessCallableWithImpureStatementOutsideCallback().',
				253,
			],
			[
				'Function PureUnlessCallableIsImpureFunction\redundantPureUnlessCallableWithPureCallable() is marked @pure-unless-callable-is-impure for parameter $f, but $f is already a pure callable, so function PureUnlessCallableIsImpureFunction\redundantPureUnlessCallableWithPureCallable() can be marked @phpstan-pure instead.',
				268,
			],
			[
				'Possibly impure call to method PureUnlessCallableIsImpureFunction\InheritedMapperChild::map() in pure function PureUnlessCallableIsImpureFunction\pureCallingInheritedMethodWithOpaqueCallback().',
				374,
			],
			[
				'Possibly impure call to method PureUnlessCallableIsImpureFunction\InheritedMapperRenamedChild::map() in pure function PureUnlessCallableIsImpureFunction\pureCallingRenamedInheritedMethodWithOpaqueCallback().',
				399,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testPureUnlessCallableIsImpureNamedArgs(): void
	{
		$this->analyse([__DIR__ . '/data/pure-unless-callable-is-impure-named-arg.php'], [
			[
				'Impure call to function PureUnlessCallableIsImpureNamedArg\myMap() in pure function PureUnlessCallableIsImpureNamedArg\pureCallingUserlandWithImpureCallbackByName().',
				61,
			],
			[
				'Impure echo in pure function PureUnlessCallableIsImpureNamedArg\pureCallingUserlandWithImpureCallbackByName().',
				62,
			],
			[
				'Possibly impure call to a callable in pure function PureUnlessCallableIsImpureNamedArg\pureCallingUserlandWithOpaqueCallbackByName().',
				75,
			],
			[
				'Possibly impure call to function PureUnlessCallableIsImpureNamedArg\myMap() in pure function PureUnlessCallableIsImpureNamedArg\pureCallingUserlandWithOpaqueCallbackByName().',
				75,
			],
			[
				'Impure call to function array_map() in pure function PureUnlessCallableIsImpureNamedArg\pureWithImpureCallbackByName().',
				85,
			],
			[
				'Impure echo in pure function PureUnlessCallableIsImpureNamedArg\pureWithImpureCallbackByName().',
				86,
			],
			[
				'Possibly impure call to a callable in pure function PureUnlessCallableIsImpureNamedArg\pureWithOpaqueCallbackByName().',
				99,
			],
			[
				'Possibly impure call to function array_map() in pure function PureUnlessCallableIsImpureNamedArg\pureWithOpaqueCallbackByName().',
				99,
			],
			[
				'Impure call to function PureUnlessCallableIsImpureNamedArg\myMap() in pure function PureUnlessCallableIsImpureNamedArg\pureCallingUserlandWithImpureCallbackShuffled().',
				121,
			],
			[
				'Impure echo in pure function PureUnlessCallableIsImpureNamedArg\pureCallingUserlandWithImpureCallbackShuffled().',
				122,
			],
			[
				'Impure call to method PureUnlessCallableIsImpureNamedArg\Mapper::map() in pure function PureUnlessCallableIsImpureNamedArg\pureCallingMethodWithImpureCallbackByName().',
				134,
			],
		]);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testPureUnlessCallableIsImpurePhp84(): void
	{
		$this->analyse([__DIR__ . '/data/pure-unless-callable-is-impure-php84.php'], [
			[
				'Impure call to function array_any() in pure function PureUnlessCallableIsImpureFunctionPhp84\anyWithImpureCallback().',
				29,
			],
			[
				'Impure echo in pure function PureUnlessCallableIsImpureFunctionPhp84\anyWithImpureCallback().',
				30,
			],
			[
				'Impure call to function array_find() in pure function PureUnlessCallableIsImpureFunctionPhp84\findWithImpureCallback().',
				59,
			],
			[
				'Impure echo in pure function PureUnlessCallableIsImpureFunctionPhp84\findWithImpureCallback().',
				60,
			],
			[
				'Impure call to function array_find_key() in pure function PureUnlessCallableIsImpureFunctionPhp84\findKeyWithImpureCallback().',
				71,
			],
			[
				'Impure echo in pure function PureUnlessCallableIsImpureFunctionPhp84\findKeyWithImpureCallback().',
				72,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testPureUnlessParameterPassed(): void
	{
		$this->analyse([__DIR__ . '/data/pure-unless-parameter-passed.php'], [
			[
				'Impure call to function PureUnlessParameterPassedFunction\myReplace() in pure function PureUnlessParameterPassedFunction\purePassingByRef().',
				44,
			],
			[
				'Impure call to function PureUnlessParameterPassedFunction\myReplacePhpstanAlias() in pure function PureUnlessParameterPassedFunction\purePassingByRefAlias().',
				62,
			],
			[
				'Possibly impure call to function PureUnlessParameterPassedFunction\myReplace() in pure function PureUnlessParameterPassedFunction\pureUnpackingArgs().',
				72,
			],
			[
				'Impure call to function PureUnlessParameterPassedFunction\myReplace() in pure function PureUnlessParameterPassedFunction\pureNamedArgForFlaggedParameter().',
				91,
			],
			[
				'Impure instantiation of class PureUnlessParameterPassedFunction\MyReplacerConstructor in pure function PureUnlessParameterPassedFunction\pureConstructorPassingByRef().',
				127,
			],
			[
				'Possibly impure call to method PureUnlessParameterPassedFunction\PureUnlessParameterPassedA::m() in pure function PureUnlessParameterPassedFunction\pureUnionMethodPassingCount().',
				167,
			],
			[
				'Impure call to method PureUnlessParameterPassedFunction\PureUnlessParameterPassedIntersectionA::m() in pure function PureUnlessParameterPassedFunction\pureIntersectionMethodPassingCount().',
				206,
			],
			[
				'Impure call to method PureUnlessParameterPassedFunction\Replacer::replace() in pure function PureUnlessParameterPassedFunction\pureCallingMethodPassingByRef().',
				241,
			],
			[
				'Impure call to method PureUnlessParameterPassedFunction\InheritedReplacerChild::replace() in pure function PureUnlessParameterPassedFunction\pureCallingInheritedMethodPassingByRef().',
				297,
			],
			[
				'Impure call to method PureUnlessParameterPassedFunction\InheritedReplacerRenamedChild::replace() in pure function PureUnlessParameterPassedFunction\pureCallingRenamedInheritedMethodPassingByRef().',
				318,
			],
			[
				'Possibly impure call to function PureUnlessParameterPassedFunction\myReplace() in pure function PureUnlessParameterPassedFunction\pureCallingFirstClassCallableOmittingCount().',
				331,
			],
			[
				'Possibly impure call to function PureUnlessParameterPassedFunction\myReplace() in pure function PureUnlessParameterPassedFunction\pureCallingFirstClassCallablePassingCount().',
				343,
			],
		]);
	}

	public function testPureUnlessParameterPassedBuiltin(): void
	{
		$this->analyse([__DIR__ . '/data/pure-unless-parameter-passed-builtin.php'], [
			[
				'Impure call to function str_replace() in pure function PureUnlessParameterPassedBuiltin\pureStrReplaceWithCount().',
				22,
			],
			[
				'Impure call to function preg_match() in pure function PureUnlessParameterPassedBuiltin\purePregMatchWithMatches().',
				40,
			],
		]);
	}

}
