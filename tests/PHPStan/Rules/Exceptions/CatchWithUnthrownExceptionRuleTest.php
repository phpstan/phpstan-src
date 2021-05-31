<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CatchWithUnthrownExceptionRule>
 */
class CatchWithUnthrownExceptionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new CatchWithUnthrownExceptionRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/unthrown-exception.php'], [
			[
				'Dead catch - Throwable is never thrown in the try block.',
				12,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				21,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				38,
			],
			[
				'Dead catch - RuntimeException is never thrown in the try block.',
				49,
			],
			[
				'Dead catch - Throwable is never thrown in the try block.',
				71,
			],
			[
				'Dead catch - InvalidArgumentException is never thrown in the try block.',
				84,
			],
			[
				'Dead catch - DomainException is never thrown in the try block.',
				117,
			],
			[
				'Dead catch - Throwable is never thrown in the try block.',
				119,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				171,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				180,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				224,
			],
			[
				'Dead catch - ArithmeticError is never thrown in the try block.',
				260,
			],
			[
				'Dead catch - ArithmeticError is never thrown in the try block.',
				279,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				312,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				344,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				376,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				408,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				456,
			],
		]);
	}

	public function testBug4806(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4806.php'], [
			[
				'Dead catch - ArgumentCountError is never thrown in the try block.',
				65,
			],
			[
				'Dead catch - Throwable is never thrown in the try block.',
				119,
			],
		]);
	}

	public function testBug4805(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4805.php'], [
			[
				'Dead catch - OutOfBoundsException is never thrown in the try block.',
				44,
			],
			[
				'Dead catch - OutOfBoundsException is never thrown in the try block.',
				66,
			],
		]);
	}

	public function testBug4863(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4863.php'], []);
	}

	public function testBug4814(): void
	{
		if (PHP_VERSION_ID < 70300) {
			$this->markTestSkipped('Test requires PHP 7.3.');
		}

		$this->analyse([__DIR__ . '/data/bug-4814.php'], [
			[
				'Dead catch - JsonException is never thrown in the try block.',
				16,
			],
		]);
	}

	public function testThrowExpression(): void
	{
		if (PHP_VERSION_ID < 80000 && !self::$useStaticReflectionProvider) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->analyse([__DIR__ . '/data/dead-catch-throw-expr.php'], [
			[
				'Dead catch - InvalidArgumentException is never thrown in the try block.',
				17,
			],
		]);
	}

}
