<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use ThrowsVoidMethod\MyException;
use UnhandledMatchError;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ThrowsVoidMethodWithExplicitThrowPointRule>
 */
class ThrowsVoidMethodWithExplicitThrowPointRuleTest extends RuleTestCase
{

	private bool $missingCheckedExceptionInThrows;

	/** @var string[] */
	private array $checkedExceptionClasses;

	protected function getRule(): Rule
	{
		return new ThrowsVoidMethodWithExplicitThrowPointRule(new DefaultExceptionTypeResolver(
			$this->createReflectionProvider(),
			[],
			[],
			[],
			$this->checkedExceptionClasses,
		), $this->missingCheckedExceptionInThrows);
	}

	public static function dataRule(): array
	{
		return [
			[
				true,
				[],
				[],
			],
			[
				false,
				['DifferentException'],
				[
					[
						'Method ThrowsVoidMethod\Foo::doFoo() throws exception ThrowsVoidMethod\MyException but the PHPDoc contains @throws void.',
						18,
					],
				],
			],
			[
				true,
				[MyException::class],
				[],
			],
			[
				true,
				['DifferentException'],
				[
					[
						'Method ThrowsVoidMethod\Foo::doFoo() throws exception ThrowsVoidMethod\MyException but the PHPDoc contains @throws void.',
						18,
					],
				],
			],
			[
				false,
				[],
				[
					[
						'Method ThrowsVoidMethod\Foo::doFoo() throws exception ThrowsVoidMethod\MyException but the PHPDoc contains @throws void.',
						18,
					],
				],
			],
			[
				false,
				[MyException::class],
				[
					[
						'Method ThrowsVoidMethod\Foo::doFoo() throws exception ThrowsVoidMethod\MyException but the PHPDoc contains @throws void.',
						18,
					],
				],
			],
		];
	}

	/**
	 * @dataProvider dataRule
	 * @param string[] $checkedExceptionClasses
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	public function testRule(bool $missingCheckedExceptionInThrows, array $checkedExceptionClasses, array $errors): void
	{
		$this->missingCheckedExceptionInThrows = $missingCheckedExceptionInThrows;
		$this->checkedExceptionClasses = $checkedExceptionClasses;
		$this->analyse([__DIR__ . '/data/throws-void-method.php'], $errors);
	}

	public function testBug6910(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}
		$this->missingCheckedExceptionInThrows = false;
		$this->checkedExceptionClasses = [UnhandledMatchError::class];
		$this->analyse([__DIR__ . '/data/bug-6910.php'], []);
	}

}
