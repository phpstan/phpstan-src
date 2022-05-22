<?php declare(strict_types = 1);

namespace PHPStan\Rules\DeadCode;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<UnusedPrivateMethodRule>
 */
class UnusedPrivateMethodRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new UnusedPrivateMethodRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unused-private-method.php'], [
			[
				'Method UnusedPrivateMethod\Foo::doFoo() is unused.',
				8,
			],
			[
				'Method UnusedPrivateMethod\Foo::doBar() is unused.',
				13,
			],
			[
				'Static method UnusedPrivateMethod\Foo::unusedStaticMethod() is unused.',
				44,
			],
			[
				'Method UnusedPrivateMethod\Bar::doBaz() is unused.',
				59,
			],
			[
				'Method UnusedPrivateMethod\Lorem::doBaz() is unused.',
				97,
			],
			[
				'Method UnusedPrivateMethod\UsingFooTrait::doBar() is unused.',
				133,
			],
		]);
	}

	public function testBug3630(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3630.php'], []);
	}

	public function testNullsafe(): void
	{
		$this->analyse([__DIR__ . '/data/nullsafe-unused-private-method.php'], []);
	}

	public function testFirstClassCallable(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/callable-unused-private-method.php'], []);
	}

	public function testEnums(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('This test needs PHP 8.1');
		}

		$this->analyse([__DIR__ . '/data/unused-private-method-enum.php'], [
			[
				'Method UnusedPrivateMethodEnunm\Foo::doBaz() is unused.',
				18,
			],
		]);
	}

}
