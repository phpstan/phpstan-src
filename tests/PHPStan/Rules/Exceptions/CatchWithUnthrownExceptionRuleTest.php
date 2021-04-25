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
		$this->analyse([__DIR__ . '/data/bug-4814.php'], [
			[
				'Dead catch - JsonException is never thrown in the try block.',
				16,
			],
		]);
	}

}
