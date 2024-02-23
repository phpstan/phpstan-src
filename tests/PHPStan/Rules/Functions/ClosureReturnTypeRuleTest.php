<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ClosureReturnTypeRule>
 */
class ClosureReturnTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ClosureReturnTypeRule(new FunctionReturnTypeCheck(new RuleLevelHelper($this->createReflectionProvider(), true, false, true, false, false, true, false)));
	}

	public function testClosureReturnTypeRule(): void
	{
		$this->analyse([__DIR__ . '/data/closureReturnTypes.php'], [
			[
				'Anonymous function should return int but returns string.',
				21,
			],
			[
				'Anonymous function should return string but returns int.',
				28,
			],
			[
				'Anonymous function should return ClosureReturnTypes\Foo but returns ClosureReturnTypes\Bar.',
				35,
			],
			[
				'Anonymous function should return SomeOtherNamespace\Foo but returns ClosureReturnTypes\Foo.',
				39,
			],
			[
				'Anonymous function should return SomeOtherNamespace\Baz but returns ClosureReturnTypes\Foo.',
				46,
			],
			[
				'Anonymous function should return array{}|null but empty return statement found.',
				88,
			],
			[
				'Anonymous function should return string but returns int.',
				105,
			],
			[
				'Anonymous function should return string but returns int.',
				115,
			],
			[
				'Anonymous function should return string but returns int.',
				118,
			],
		]);
	}

	public function testClosureReturnTypeRulePhp70(): void
	{
		$this->analyse([__DIR__ . '/data/closureReturnTypes-7.0.php'], [
			[
				'Anonymous function should return int but empty return statement found.',
				4,
			],
			[
				'Anonymous function should return string but empty return statement found.',
				8,
			],
		]);
	}

	public function testClosureReturnTypePhp71Typehints(): void
	{
		$this->analyse([__DIR__ . '/data/closure-7.1ReturnTypes.php'], [
			[
				'Anonymous function should return int|null but returns string.',
				9,
			],
			[
				'Anonymous function should return iterable but returns string.',
				22,
			],
		]);
	}

	public function testBug3891(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3891.php'], []);
	}

	public function testBug6806(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6806.php'], []);
	}

	public function testBug4739(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4739.php'], []);
	}

	public function testBug4739b(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4739b.php'], []);
	}

	public function testBug5753(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5753.php'], []);
	}

	public function testBug6559(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6559.php'], []);
	}

	public function testBug6902(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6902.php'], []);
	}

	public function testBug7220(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7220.php'], []);
	}

	public function testBugFunctionMethodConstants(): void
	{
		if (PHP_VERSION_ID < 70400) {
			$this->markTestSkipped('Test requires PHP 7.4.');
		}

		$this->analyse([__DIR__ . '/data/bug-anonymous-function-method-constant.php'], []);
	}

}
