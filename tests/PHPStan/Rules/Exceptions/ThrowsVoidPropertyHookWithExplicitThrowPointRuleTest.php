<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ThrowsVoidPropertyHookWithExplicitThrowPointRule>
 */
class ThrowsVoidPropertyHookWithExplicitThrowPointRuleTest extends RuleTestCase
{

	private bool $missingCheckedExceptionInThrows;

	/** @var string[] */
	private array $checkedExceptionClasses;

	protected function getRule(): Rule
	{
		return new ThrowsVoidPropertyHookWithExplicitThrowPointRule(new DefaultExceptionTypeResolver(
			$this->createReflectionProvider(),
			[],
			[],
			[],
			$this->checkedExceptionClasses,
		), $this->missingCheckedExceptionInThrows);
	}

	public function dataRule(): array
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
						'Get hook for property ThrowsVoidPropertyHook\Foo::$i throws exception ThrowsVoidPropertyHook\MyException but the PHPDoc contains @throws void.',
						18,
					],
				],
			],
			[
				true,
				['ThrowsVoidPropertyHook\\MyException'],
				[],
			],
			[
				true,
				['DifferentException'],
				[
					[
						'Get hook for property ThrowsVoidPropertyHook\Foo::$i throws exception ThrowsVoidPropertyHook\MyException but the PHPDoc contains @throws void.',
						18,
					],
				],
			],
			[
				false,
				[],
				[
					[
						'Get hook for property ThrowsVoidPropertyHook\Foo::$i throws exception ThrowsVoidPropertyHook\MyException but the PHPDoc contains @throws void.',
						18,
					],
				],
			],
			[
				false,
				['ThrowsVoidPropertyHook\\MyException'],
				[
					[
						'Get hook for property ThrowsVoidPropertyHook\Foo::$i throws exception ThrowsVoidPropertyHook\MyException but the PHPDoc contains @throws void.',
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
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->missingCheckedExceptionInThrows = $missingCheckedExceptionInThrows;
		$this->checkedExceptionClasses = $checkedExceptionClasses;
		$this->analyse([__DIR__ . '/data/throws-void-property-hook.php'], $errors);
	}

}
