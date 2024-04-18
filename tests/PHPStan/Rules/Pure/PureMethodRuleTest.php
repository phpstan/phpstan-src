<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<PureMethodRule>
 */
class PureMethodRuleTest extends RuleTestCase
{

	public function getRule(): Rule
	{
		return new PureMethodRule(new FunctionPurityCheck());
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/pure-method.php'], [
			[
				'Method PureMethod\Foo::doFoo() is marked as pure but parameter $p is passed by reference.',
				11,
			],
			[
				'Impure echo in pure method PureMethod\Foo::doFoo().',
				13,
			],
			[
				'Method PureMethod\Foo::doFoo2() is marked as pure but returns void.',
				19,
			],
			[
				'Impure die in pure method PureMethod\Foo::doFoo2().',
				21,
			],
			[
				'Impure property assignment in pure method PureMethod\Foo::doFoo3().',
				29,
			],
			[
				'Impure call to method PureMethod\Foo::voidMethod() in pure method PureMethod\Foo::doFoo4().',
				71,
			],
			[
				'Impure call to method PureMethod\Foo::impureVoidMethod() in pure method PureMethod\Foo::doFoo4().',
				72,
			],
			[
				'Possibly impure call to method PureMethod\Foo::returningMethod() in pure method PureMethod\Foo::doFoo4().',
				73,
			],
			[
				'Impure call to method PureMethod\Foo::impureReturningMethod() in pure method PureMethod\Foo::doFoo4().',
				75,
			],
			[
				'Possibly impure call to unknown method in pure method PureMethod\Foo::doFoo4().',
				76,
			],
			[
				'Impure call to method PureMethod\Foo::voidMethod() in pure method PureMethod\Foo::doFoo5().',
				84,
			],
			[
				'Impure call to method PureMethod\Foo::impureVoidMethod() in pure method PureMethod\Foo::doFoo5().',
				85,
			],
			[
				'Possibly impure call to method PureMethod\Foo::returningMethod() in pure method PureMethod\Foo::doFoo5().',
				86,
			],
			[
				'Impure call to method PureMethod\Foo::impureReturningMethod() in pure method PureMethod\Foo::doFoo5().',
				88,
			],
			[
				'Possibly impure call to unknown method in pure method PureMethod\Foo::doFoo5().',
				89,
			],
			[
				'Impure instantiation of class PureMethod\ImpureConstructor in pure method PureMethod\TestConstructors::doFoo().',
				140,
			],
			[
				'Possibly impure instantiation of class PureMethod\PossiblyImpureConstructor in pure method PureMethod\TestConstructors::doFoo().',
				141,
			],
			[
				'Possibly impure instantiation of unknown class in pure method PureMethod\TestConstructors::doFoo().',
				142,
			],
			[
				'Method PureMethod\ActuallyPure::doFoo() is marked as impure but does not have any side effects.',
				153,
			],
			[
				'Impure echo in pure method PureMethod\ExtendingClass::pure().',
				183,
			],
			[
				'Method PureMethod\ExtendingClass::impure() is marked as impure but does not have any side effects.',
				187,
			],
			[
				'Method PureMethod\ClassWithVoidMethods::privateEmptyVoidFunction() returns void but does not have any side effects.',
				214,
			],
			[
				'Impure assign to superglobal variable in pure method PureMethod\ClassWithVoidMethods::purePostGetAssign().',
				230,
			],
			[
				'Impure assign to superglobal variable in pure method PureMethod\ClassWithVoidMethods::purePostGetAssign().',
				231,
			],
			[
				'Possibly impure call to method PureMethod\MaybePureMagicMethods::__toString() in pure method PureMethod\TestMagicMethods::doFoo().',
				295,
			],
			[
				'Impure call to method PureMethod\ImpureMagicMethods::__toString() in pure method PureMethod\TestMagicMethods::doFoo().',
				296,
			],
			[
				'Possibly impure call to a callable in pure method PureMethod\MaybeCallableFromUnion::doFoo().',
				330,
			],
			[
				'Possibly impure call to a callable in pure method PureMethod\MaybeCallableFromUnion::doFoo().',
				330,
			],
		]);
	}

	public function testPureConstructor(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/pure-constructor.php'], [
			[
				'Impure property assignment in pure method PureConstructor\Foo::__construct().',
				19,
			],
			[
				'Method PureConstructor\Bar::__construct() is marked as impure but does not have any side effects.',
				30,
			],
		]);
	}

}
