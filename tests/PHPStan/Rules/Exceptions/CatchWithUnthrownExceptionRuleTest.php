<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use Error;
use InvalidArgumentException;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<CatchWithUnthrownExceptionRule>
 */
class CatchWithUnthrownExceptionRuleTest extends RuleTestCase
{

	private bool $reportUncheckedExceptionDeadCatch = true;

	/** @var string[] */
	private array $uncheckedExceptionClasses = [];

	protected function getRule(): Rule
	{
		return new CatchWithUnthrownExceptionRule(new DefaultExceptionTypeResolver(
			$this->createReflectionProvider(),
			[],
			$this->uncheckedExceptionClasses,
			[],
			[],
		), $this->reportUncheckedExceptionDeadCatch);
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
			[
				'Dead catch - Exception is never thrown in the try block.',
				555,
			],
			[
				'Dead catch - InvalidArgumentException is never thrown in the try block.',
				629,
			],
			[
				'Dead catch - InvalidArgumentException is never thrown in the try block.',
				647,
			],
			[
				'Dead catch - InvalidArgumentException is never thrown in the try block.',
				741,
			],
			[
				'Dead catch - ArithmeticError is never thrown in the try block.',
				762,
			],
		]);
	}

	public function testRuleWithoutReportingUncheckedException(): void
	{
		$this->reportUncheckedExceptionDeadCatch = false;
		$this->uncheckedExceptionClasses = [
			InvalidArgumentException::class,
			Error::class,
		];

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
			[
				'Dead catch - Exception is never thrown in the try block.',
				555,
			],
		]);
	}

	public function testMultiCatch(): void
	{
		$this->analyse([__DIR__ . '/data/unthrown-exception-multi.php'], [
			[
				'Dead catch - LogicException is never thrown in the try block.',
				12,
			],
			[
				'Dead catch - OverflowException is never thrown in the try block.',
				36,
			],
			[
				'Dead catch - JsonException is never thrown in the try block.',
				58,
			],
			[
				'Dead catch - RuntimeException is never thrown in the try block.',
				120,
			],
			[
				'Dead catch - InvalidArgumentException is already caught above.',
				145,
			],
			[
				'Dead catch - InvalidArgumentException is already caught above.',
				156,
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

	public function testBug5866(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0');
		}

		$this->analyse([__DIR__ . '/data/bug-5866.php'], []);
	}

	public function testBug4814(): void
	{
		$this->analyse([__DIR__ . '/data/bug-4814.php'], [
			[
				'Dead catch - JsonException is never thrown in the try block.',
				16,
			],
		]);
	}

	public function testBug9066(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9066.php'], [
			[
				'Dead catch - OutOfBoundsException is never thrown in the try block.',
				28,
			],
		]);
	}

	public function testThrowExpression(): void
	{
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
		$this->analyse([__DIR__ . '/data/dead-catch-first-class-callables.php'], [
			[
				'Dead catch - InvalidArgumentException is never thrown in the try block.',
				29,
			],
		]);
	}

	public function testBug4852(): void
	{
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

	public function testBug6115(): void
	{
		if (PHP_VERSION_ID < 80000) {
			self::markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-6115.php'], [
			[
				'Dead catch - UnhandledMatchError is never thrown in the try block.',
				20,
			],
			[
				'Dead catch - UnhandledMatchError is never thrown in the try block.',
				28,
			],
		]);
	}

	public function testBug6262(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6262.php'], []);
	}

	public function testBug6256(): void
	{
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
		$this->analyse([__DIR__ . '/data/bug-6791.php'], [
			[
				'Dead catch - TypeError is never thrown in the try block.',
				22,
			],
			[
				'Dead catch - TypeError is never thrown in the try block.',
				34,
			],
			[
				'Dead catch - TypeError is never thrown in the try block.',
				38,
			],
		]);
	}

	public function testBug6786(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6786.php'], []);
	}

	public function testUnionTypeError(): void
	{
		if (PHP_VERSION_ID < 80000) {
			self::markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/union-type-error.php'], [
			[
				'Dead catch - TypeError is never thrown in the try block.',
				14,
			],
			[
				'Dead catch - TypeError is never thrown in the try block.',
				22,
			],
		]);
	}

	public function testBug6349(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6349.php'], [
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				29,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				33,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				44,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				48,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				106,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				110,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				121,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				125,
			],
			[
				// throw point not implemented yet, because there is no way to narrow float value by !== 0.0
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				139,
			],
			[
				// throw point not implemented yet, because there is no way to narrow float value by !== 0.0
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				143,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				172,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				176,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				187,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				191,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				249,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				253,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				264,
			],
			[
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				268,
			],
			[
				// throw point not implemented yet, because there is no way to narrow float value by !== 0.0
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				282,
			],
			[
				// throw point not implemented yet, because there is no way to narrow float value by !== 0.0
				'Dead catch - DivisionByZeroError is never thrown in the try block.',
				286,
			],
		]);
	}

	public function testMagicMethods(): void
	{
		$this->analyse([__DIR__ . '/data/dead-catch-magic-methods.php'], [
			[
				'Dead catch - Exception is never thrown in the try block.',
				22,
			],
			[
				'Dead catch - Exception is never thrown in the try block.',
				65,
			],
		]);
	}

	public function testBug9406(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9406.php'], []);
	}

	public function testBug5650(): void
	{
		$this->analyse([__DIR__ . '/data/bug-5650.php'], [
			[
				'Dead catch - RuntimeException is never thrown in the try block.',
				24,
			],
			[
				'Dead catch - RuntimeException is never thrown in the try block.',
				32,
			],
		]);
	}

	public function testBug9568(): void
	{
		if (PHP_VERSION_ID < 80000) {
			self::markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/bug-9568.php'], []);
	}

	public function testPropertyHooks(): void
	{
		if (PHP_VERSION_ID < 80400) {
			self::markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/unthrown-exception-property-hooks.php'], [
			[
				'Dead catch - UnthrownExceptionPropertyHooks\MyCustomException is never thrown in the try block.',
				27,
			],
			[
				'Dead catch - UnthrownExceptionPropertyHooks\SomeException is never thrown in the try block.',
				39,
			],
			[
				'Dead catch - UnthrownExceptionPropertyHooks\MyCustomException is never thrown in the try block.',
				53,
			],
			[
				'Dead catch - UnthrownExceptionPropertyHooks\SomeException is never thrown in the try block.',
				65,
			],
			[
				'Dead catch - UnthrownExceptionPropertyHooks\MyCustomException is never thrown in the try block.',
				107,
			],
			[
				'Dead catch - UnthrownExceptionPropertyHooks\MyCustomException is never thrown in the try block.',
				128,
			],
			[
				'Dead catch - UnthrownExceptionPropertyHooks\MyCustomException is never thrown in the try block.',
				154,
			],
			[
				'Dead catch - UnthrownExceptionPropertyHooks\MyCustomException is never thrown in the try block.',
				175,
			],
		]);
	}

}
