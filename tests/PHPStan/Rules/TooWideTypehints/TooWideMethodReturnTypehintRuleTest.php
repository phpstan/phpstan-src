<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<TooWideMethodReturnTypehintRule>
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
				'Method TooWideMethodReturnType\Foo::bar() never returns string so it can be removed from the return typehint.',
				14,
			],
			[
				'Method TooWideMethodReturnType\Foo::baz() never returns null so it can be removed from the return typehint.',
				18,
			],
		]);
	}

	public function testPublicProtected(): void
	{
		$this->analyse([__DIR__ . '/data/tooWideMethodReturnType-public-protected.php'], [
			[
				'Method TooWideMethodReturnType\Bar::bar() never returns string so it can be removed from the return typehint.',
				14,
			],
			[
				'Method TooWideMethodReturnType\Bar::baz() never returns null so it can be removed from the return typehint.',
				18,
			],
			[
				'Method TooWideMethodReturnType\Bazz::lorem() never returns string so it can be removed from the return typehint.',
				35,
			],
		]);
	}

	public function testPublicProtectedWithInheritance(): void
	{
		$this->analyse([__DIR__ . '/data/tooWideMethodReturnType-public-protected-inheritance.php'], [
			[
				'Method TooWideMethodReturnType\Baz::baz() never returns null so it can be removed from the return typehint.',
				27,
			],
		]);
	}

	public function testPublicProtectedMethodCanBeCovariantOnPhp74(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}
		$this->analyse([__DIR__ . '/data/tooWideMethodReturnType-php74.php'], [
			[
				'Method TooWideMethodReturnType74\Bar::doFoo() never returns null so it can be removed from the return typehint.',
				15,
			],
		]);
	}

}
