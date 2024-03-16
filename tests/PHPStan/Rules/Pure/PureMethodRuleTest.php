<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

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
		]);
	}

}
