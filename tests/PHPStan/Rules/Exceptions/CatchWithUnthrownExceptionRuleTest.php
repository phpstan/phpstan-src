<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

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
				375,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				380,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				398,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				432,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				437,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				485,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				532,
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

	public function testDeadCatch(): void
	{
		$this->analyse([__DIR__ . '/data/dead-catch.php'], [
			[
				'Dead catch - TypeError is already caught above.',
				27,
			],
		]);
	}

	public function testFirstClassCallables(): void
	{
		if (PHP_VERSION_ID < 80100) {
			self::markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/dead-catch-first-class-callables.php'], [
			[
				'Dead catch - InvalidArgumentException is never thrown in the try block.',
				29,
			],
		]);
	}

	public function testBug4852(): void
	{
		if (!self::$useStaticReflectionProvider) {
			$this->markTestSkipped('This test needs static reflection');
		}

		$this->analyse([__DIR__ . '/data/bug-4852.php'], [
			[
				'Dead catch - Exception is never thrown in the try block.',
				70,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				77,
			],
		]);
	}

	public function testBug5903(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5903.php'], [
			[
				'Dead catch - Throwable is never thrown in the try block.',
				47,
			],
			[
				'Dead catch - Throwable is never thrown in the try block.',
				54,
			],
		]);
	}

	public function testBug6262(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6262.php'], []);
	}

	public function testBug6256(): void
	{
		if (PHP_VERSION_ID < 70400) {
			self::markTestSkipped('Test requires PHP 7.4.');
		}

		$this->analyse([__DIR__ . '/data/bug-6256.php'], [
			[
				'Dead catch - TypeError is never thrown in the try block.',
				25,
			],
			[
				'Dead catch - TypeError is never thrown in the try block.',
				31,
			],
			[
				'Dead catch - TypeError is never thrown in the try block.',
				45,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				57,
			],
			[
				'Dead catch - Throwable is never thrown in the try block.',
				63,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				100,
			],
		]);
	}

	public function testBug6791(): void
	{
		if (PHP_VERSION_ID < 70400) {
			self::markTestSkipped('Test requires PHP 7.4.');
		}

		$this->analyse([__DIR__ . '/data/bug-6791.php'], [
			[
				'Dead catch - TypeError is never thrown in the try block.',
				17,
			],
			[
				'Dead catch - TypeError is never thrown in the try block.',
				29,
			],
			[
				'Dead catch - TypeError is never thrown in the try block.',
				33,
			],
		]);
	}

	public function testBug6786(): void
	{
		if (PHP_VERSION_ID < 70400) {
			self::markTestSkipped('Test requires PHP 7.4.');
		}

		$this->analyse([__DIR__ . '/data/bug-6786.php'], []);
	}

}
