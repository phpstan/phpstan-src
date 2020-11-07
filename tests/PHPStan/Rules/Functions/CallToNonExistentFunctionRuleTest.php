<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

/**
 * @extends \PHPStan\Testing\RuleTestCase<CallToNonExistentFunctionRule>
 */
class CallToNonExistentFunctionRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new CallToNonExistentFunctionRule($this->createReflectionProvider(), true);
	}

	public function testEmptyFile(): void
	{
		$this->analyse([__DIR__ . '/data/empty.php'], []);
	}

	public function testCallToExistingFunction(): void
	{
		require_once __DIR__ . '/data/existing-function-definition.php';
		$this->analyse([__DIR__ . '/data/existing-function.php'], []);
	}

	public function testCallToNonexistentFunction(): void
	{
		$this->analyse([__DIR__ . '/data/nonexistent-function.php'], [
			[
				'Function foobarNonExistentFunction not found.',
				5,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',

			],
		]);
	}

	public function testCallToNonexistentNestedFunction(): void
	{
		$this->analyse([__DIR__ . '/data/nonexistent-nested-function.php'], [
			[
				'Function barNonExistentFunction not found.',
				5,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',

			],
		]);
	}

	public function testCallToIncorrectCaseFunctionName(): void
	{
		require_once __DIR__ . '/data/incorrect-function-case-definition.php';
		$this->analyse([__DIR__ . '/data/incorrect-function-case.php'], [
			[
				'Call to function IncorrectFunctionCase\fooBar() with incorrect case: foobar',
				5,
			],
			[
				'Call to function IncorrectFunctionCase\fooBar() with incorrect case: IncorrectFunctionCase\foobar',
				7,
			],
			[
				'Call to function htmlspecialchars() with incorrect case: htmlSpecialChars',
				10,
			],
		]);
	}

	public function testMatchExprAnalysis(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/match-expr-analysis.php'], [
			[
				'Function lorem not found.',
				10,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function ipsum not found.',
				11,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function dolor not found.',
				11,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function sit not found.',
				12,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

}
