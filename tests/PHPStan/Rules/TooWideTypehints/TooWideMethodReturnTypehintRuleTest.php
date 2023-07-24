<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<TooWideMethodReturnTypehintRule>
 */
class TooWideMethodReturnTypehintRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new TooWideMethodReturnTypehintRule(true);
	}

	public function testPrivate(): void
	{
		$this->analyse([__DIR__ . '/data/tooWideMethodReturnType-private.php'], [
			[
				'Method TooWideMethodReturnType\Foo::bar() never returns string so it can be removed from the return type.',
				14,
			],
			[
				'Method TooWideMethodReturnType\Foo::baz() never returns null so it can be removed from the return type.',
				18,
			],
			[
				'Method TooWideMethodReturnType\Foo::dolor() never returns null so it can be removed from the return type.',
				34,
			],
			[
				'Method TooWideMethodReturnType\Foo::dolor2() never returns null so it can be removed from the return type.',
				48,
			],
			[
				'Method TooWideMethodReturnType\Foo::dolor4() never returns int so it can be removed from the return type.',
				66,
			],
			[
				'Method TooWideMethodReturnType\Foo::dolor6() never returns null so it can be removed from the return type.',
				86,
			],
		]);
	}

	public function testPublicProtected(): void
	{
		$this->analyse([__DIR__ . '/data/tooWideMethodReturnType-public-protected.php'], [
			[
				'Method TooWideMethodReturnType\Bar::bar() never returns string so it can be removed from the return type.',
				14,
			],
			[
				'Method TooWideMethodReturnType\Bar::baz() never returns null so it can be removed from the return type.',
				18,
			],
			[
				'Method TooWideMethodReturnType\Bazz::lorem() never returns string so it can be removed from the return type.',
				35,
			],
		]);
	}

	public function testPublicProtectedWithInheritance(): void
	{
		$this->analyse([__DIR__ . '/data/tooWideMethodReturnType-public-protected-inheritance.php'], [
			[
				'Method TooWideMethodReturnType\Baz::baz() never returns null so it can be removed from the return type.',
				27,
			],
			[
				'Method TooWideMethodReturnType\BarClass::doFoo() never returns null so it can be removed from the return type.',
				51,
			],
		]);
	}

	public function testBug5095(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5095.php'], [
			[
				'Method Bug5095\Parser::unaryOperatorFor() never returns \'not\' so it can be removed from the return type.',
				21,
			],
		]);
	}

	public function testBug6158(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->analyse([__DIR__ . '/data/bug-6158.php'], []);
	}

	public function testBug6175(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6175.php'], []);
	}

}
