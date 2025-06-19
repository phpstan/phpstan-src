<?php declare(strict_types = 1);

namespace PHPStan\Rules\Pure;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<PureMethodRule>
 */
class PureMethodRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	public function getRule(): Rule
	{
		return new PureMethodRule(new FunctionPurityCheck());
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testRule(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
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
			[
				'Impure static property access in pure method PureMethod\StaticMethodAccessingStaticProperty::getA().',
				388,
			],
			[
				'Impure property assignment in pure method PureMethod\StaticMethodAssigningStaticProperty::getA().',
				409,
			],
		]);
	}

	#[RequiresPhp('>= 8.0')]
	public function testPureConstructor(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/pure-constructor.php'], [
			[
				'Impure static property access in pure method PureConstructor\Foo::__construct().',
				19,
			],
			[
				'Impure property assignment in pure method PureConstructor\Foo::__construct().',
				19,
			],
			[
				'Method PureConstructor\Bar::__construct() is marked as impure but does not have any side effects.',
				30,
			],
			[
				'Impure property assignment in pure method PureConstructor\AssignOtherThanThis::__construct().',
				49,
			],
		]);
	}

	public function testImpureAssignRef(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/impure-assign-ref.php'], [
			[
				'Possibly impure property assignment by reference in pure method ImpureAssignRef\HelloWorld::bar6().',
				49,
			],
		]);
	}

	#[DataProvider('dataBug11207')]
	public function testBug11207(bool $treatPhpDocTypesAsCertain): void
	{
		$this->treatPhpDocTypesAsCertain = $treatPhpDocTypesAsCertain;
		$this->analyse([__DIR__ . '/data/bug-11207.php'], []);
	}

	public static function dataBug11207(): array
	{
		return [
			[true],
			[false],
		];
	}

	public function testBug12048(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-12048.php'], []);
	}

	public function testBug12224(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-12224.php'], [
			['Method PHPStan\Rules\Pure\data\A::pureWithThrowsVoid() is marked as pure but returns void.', 47],
		]);
	}

	public function testBug12382(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-12382.php'], [
			[
				'Method Bug12382\FinalHelloWorld1::dummy() is marked as impure but does not have any side effects.',
				25,
			],
			[
				'Method Bug12382\FinalHelloWorld2::dummy() is marked as impure but does not have any side effects.',
				33,
			],
			[
				'Method Bug12382\FinalHelloWorld3::dummy() is marked as impure but does not have any side effects.',
				42,
			],
			[
				'Method Bug12382\FinalHelloWorld4::dummy() is marked as impure but does not have any side effects.',
				53,
			],
		]);
	}

}
