<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RequiresPhp;

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
						'Get hook for property ThrowsVoidPropertyHook\Foo::$i throws exception ThrowsVoidPropertyHook\MyException but the PHPDoc contains @throws void.',
						18,
					],
					[
						'Get hook for property ThrowsVoidPropertyHook\Foo::$j throws exception ThrowsVoidPropertyHook\MyException but the PHPDoc contains @throws void.',
						26,
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
					[
						'Get hook for property ThrowsVoidPropertyHook\Foo::$j throws exception ThrowsVoidPropertyHook\MyException but the PHPDoc contains @throws void.',
						26,
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
					[
						'Get hook for property ThrowsVoidPropertyHook\Foo::$j throws exception ThrowsVoidPropertyHook\MyException but the PHPDoc contains @throws void.',
						26,
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
					[
						'Get hook for property ThrowsVoidPropertyHook\Foo::$j throws exception ThrowsVoidPropertyHook\MyException but the PHPDoc contains @throws void.',
						26,
					],
				],
			],
		];
	}

	/**
	 * @param string[] $checkedExceptionClasses
	 * @param list<array{0: string, 1: int, 2?: string}> $errors
	 */
	#[RequiresPhp('>= 8.4')]
	#[DataProvider('dataRule')]
	public function testRule(bool $missingCheckedExceptionInThrows, array $checkedExceptionClasses, array $errors): void
	{
		$this->missingCheckedExceptionInThrows = $missingCheckedExceptionInThrows;
		$this->checkedExceptionClasses = $checkedExceptionClasses;
		$this->analyse([__DIR__ . '/data/throws-void-property-hook.php'], $errors);
	}

}
