<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;
use ThrowsVoidMethod\MyException;
use UnhandledMatchError;

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
			self::createReflectionProvider(),
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
	 * @param string[] $checkedExceptionClasses
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[DataProvider('dataRule')]
	public function testRule(bool $missingCheckedExceptionInThrows, array $checkedExceptionClasses, array $errors): void
	{
		$this->missingCheckedExceptionInThrows = $missingCheckedExceptionInThrows;
		$this->checkedExceptionClasses = $checkedExceptionClasses;
		$this->analyse([__DIR__ . '/data/throws-void-method.php'], $errors);
	}

	#[RequiresPhp('>= 8.0')]
	public function testBug6910(): void
	{
		$this->missingCheckedExceptionInThrows = false;
		$this->checkedExceptionClasses = [UnhandledMatchError::class];
		$this->analyse([__DIR__ . '/data/bug-6910.php'], []);
	}

}
