<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use ThrowsVoidFunction\MyException;

/**
 * @extends RuleTestCase<ThrowsVoidFunctionWithExplicitThrowPointRule>
 */
class ThrowsVoidFunctionWithExplicitThrowPointRuleTest extends RuleTestCase
{

	private bool $missingCheckedExceptionInThrows;

	/** @var string[] */
	private array $checkedExceptionClasses;

	protected function getRule(): Rule
	{
		return new ThrowsVoidFunctionWithExplicitThrowPointRule(new DefaultExceptionTypeResolver(
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
						'Function ThrowsVoidFunction\foo() throws exception ThrowsVoidFunction\MyException but the PHPDoc contains @throws void.',
						15,
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
						'Function ThrowsVoidFunction\foo() throws exception ThrowsVoidFunction\MyException but the PHPDoc contains @throws void.',
						15,
					],
				],
			],
			[
				false,
				[],
				[
					[
						'Function ThrowsVoidFunction\foo() throws exception ThrowsVoidFunction\MyException but the PHPDoc contains @throws void.',
						15,
					],
				],
			],
			[
				false,
				[MyException::class],
				[
					[
						'Function ThrowsVoidFunction\foo() throws exception ThrowsVoidFunction\MyException but the PHPDoc contains @throws void.',
						15,
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
		$this->analyse([__DIR__ . '/data/throws-void-function.php'], $errors);
	}

}
