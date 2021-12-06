<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use ThrowsVoidMethod\MyException;

/**
 * @extends RuleTestCase<ThrowsVoidMethodWithExplicitThrowPointRule>
 */
class ThrowsVoidMethodWithExplicitThrowPointRuleTest extends RuleTestCase
{

	/** @var bool */
	private $missingCheckedExceptionInThrows;

	/** @var string[] */
	private $checkedExceptionClasses;

	protected function getRule(): Rule
	{
		return new ThrowsVoidMethodWithExplicitThrowPointRule(new DefaultExceptionTypeResolver(
			$this->createReflectionProvider(),
			[],
			[],
			[],
			$this->checkedExceptionClasses
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
	 * @param mixed[] $errors
	 */
	public function testRule(bool $missingCheckedExceptionInThrows, array $checkedExceptionClasses, array $errors): void
	{
		$this->missingCheckedExceptionInThrows = $missingCheckedExceptionInThrows;
		$this->checkedExceptionClasses = $checkedExceptionClasses;
		$this->analyse([__DIR__ . '/data/throws-void-method.php'], $errors);
	}

}
