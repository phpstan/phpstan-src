<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CallToNonExistentFunctionRule>
 */
class CallToNonExistentFunctionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
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

	public function testCallToRemovedFunctionsOnPhp8(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/removed-functions-from-php8.php'], [
			[
				'Function convert_cyr_string not found.',
				3,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function ezmlm_hash not found.',
				4,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function fgetss not found.',
				5,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function get_magic_quotes_gpc not found.',
				6,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function hebrevc not found.',
				7,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function imap_header not found.',
				8,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function ldap_control_paged_result not found.',
				9,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function ldap_control_paged_result_response not found.',
				10,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function restore_include_path not found.',
				11,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testCreateFunctionPhp8(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/create_function.php'], [
			[
				'Function create_function not found.',
				4,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testCreateFunctionPhp7(): void
	{
		if (PHP_VERSION_ID >= 80000) {
			$this->markTestSkipped('Test requires PHP 7.x.');
		}

		$this->analyse([__DIR__ . '/data/create_function.php'], []);
	}

	public function testBug3576(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3576.php'], [
			[
				'Function bug3576 not found.',
				14,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function bug3576 not found.',
				17,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function bug3576 not found.',
				26,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function bug3576 not found.',
				29,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function bug3576 not found.',
				38,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Function bug3576 not found.',
				41,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testBug7952(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7952.php'], []);
	}

	public function testBug8058(): void
	{
		if (PHP_VERSION_ID < 80200) {
			$this->markTestSkipped('Test requires PHP 8.2');
		}

		$this->analyse([__DIR__ . '/../Methods/data/bug-8058.php'], []);
	}

	public function testBug8058b(): void
	{
		if (PHP_VERSION_ID >= 80200) {
			$this->markTestSkipped('Test requires PHP before 8.2');
		}

		$this->analyse([__DIR__ . '/../Methods/data/bug-8058.php'], [
			[
				'Function mysqli_execute_query not found.',
				13,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testBug8205(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8205.php'], []);
	}

	public function testBug10003(): void
	{
		$this->analyse([__DIR__ . '/data/bug-10003.php'], [
			[
				'Call to function MongoDB\Driver\Monitoring\addSubscriber() with incorrect case: MONGODB\Driver\Monitoring\addSubscriber',
				10,
			],
			[
				'Call to function MongoDB\Driver\Monitoring\addSubscriber() with incorrect case: mongodb\driver\monitoring\addsubscriber',
				14,
			],
		]);
	}

}
