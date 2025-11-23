<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\ClassCaseSensitivityCheck;
use PHPStan\Rules\ClassForbiddenNameCheck;
use PHPStan\Rules\ClassNameCheck;
use PHPStan\Rules\Generics\GenericObjectTypeCheck;
use PHPStan\Rules\MissingTypehintCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MethodAssertRule>
 */
class MethodAssertRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();
		$container = self::getContainer();
		return new MethodAssertRule(new AssertRuleHelper(
			$reflectionProvider,
			new UnresolvableTypeHelper(),
			new ClassNameCheck(
				new ClassCaseSensitivityCheck($reflectionProvider, true),
				new ClassForbiddenNameCheck($container),
				$reflectionProvider,
				$container,
			),
			new MissingTypehintCheck(true, []),
			new GenericObjectTypeCheck(),
			true,
			true,
		));
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/method-assert.php';
		$this->analyse([__DIR__ . '/data/method-assert.php'], [
			[
				'Asserted type int for $i with type int does not narrow down the type.',
				10,
			],
			[
				'Asserted type int for $i with type int does not narrow down the type.',
				19,
			],
			[
				'Asserted type int for $i with type int does not narrow down the type.',
				28,
			],
			[
				'Asserted type int|string for $i with type int does not narrow down the type.',
				44,
			],
			[
				'Asserted type string for $i with type int can never happen.',
				51,
			],
			[
				'Assert references unknown parameter $j.',
				58,
			],
			[
				'Asserted negated type int for $i with type int can never happen.',
				65,
			],
			[
				'Asserted negated type string for $i with type int does not narrow down the type.',
				72,
			],
			[
				'PHPDoc tag @phpstan-assert for $this->fooProp contains unresolvable type.',
				94,
			],
			[
				'PHPDoc tag @phpstan-assert-if-true for $a contains unresolvable type.',
				94,
			],
			[
				'PHPDoc tag @phpstan-assert for $a contains unknown class MethodAssert\Nonexistent.',
				105,
			],
			[
				'PHPDoc tag @phpstan-assert for $b contains invalid type MethodAssert\FooTrait.',
				105,
			],
			[
				'Class MethodAssert\Foo referenced with incorrect case: MethodAssert\fOO.',
				105,
			],
			[
				'Assert references unknown $this->barProp.',
				105,
			],
			[
				'Assert references unknown parameter $this.',
				113,
			],
			[
				'PHPDoc tag @phpstan-assert for $m contains generic type Exception<int, float> but class Exception is not generic.',
				131,
			],
			[
				'Generic type MethodAssert\FooBar<mixed> in PHPDoc tag @phpstan-assert for $m does not specify all template types of class MethodAssert\FooBar: T, TT',
				138,
			],
			[
				'Type mixed in generic type MethodAssert\FooBar<mixed> in PHPDoc tag @phpstan-assert for $m is not subtype of template type T of int of class MethodAssert\FooBar.',
				138,
			],
			[
				'Generic type MethodAssert\FooBar<int> in PHPDoc tag @phpstan-assert for $m does not specify all template types of class MethodAssert\FooBar: T, TT',
				145,
			],
			[
				'Generic type MethodAssert\FooBar<int, string, float> in PHPDoc tag @phpstan-assert for $m specifies 3 template types, but class MethodAssert\FooBar supports only 2: T, TT',
				152,
			],
			[
				'PHPDoc tag @phpstan-assert for $m has no value type specified in iterable type array.',
				194,
				'See: https://phpstan.org/blog/solving-phpstan-no-value-type-specified-in-iterable-type',
			],
			[
				'PHPDoc tag @phpstan-assert for $m contains generic class MethodAssert\FooBar but does not specify its types: T, TT',
				202,
			],
			[
				'PHPDoc tag @phpstan-assert for $m has no signature specified for callable.',
				210,
			],
		]);
	}

	public function testBug10573(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10573.php'], []);
	}

	public function testBug10214(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10214.php'], []);
	}

	public function testBug10594(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10594.php'], []);
	}

	public function testBugIncompatibleAssertTypeWithMethodReturnType(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-assert-type-with-method-return-type.php'], [
			[
				'Asserted type non-empty-list<static(IncompatibleAssertTypeWithMethodReturnType\Foo)> for $this->getValues() with type list<int> can never happen.',
				19,
			],
		]);
	}

}
