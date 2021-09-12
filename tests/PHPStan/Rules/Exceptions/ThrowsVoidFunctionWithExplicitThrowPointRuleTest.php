<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ThrowsVoidFunctionWithExplicitThrowPointRule>
 */
class ThrowsVoidFunctionWithExplicitThrowPointRuleTest extends RuleTestCase
{

	/** @var bool */
	private $missingCheckedExceptionInThrows;

	/** @var string[] */
	private $checkedExceptionClasses;

	protected function getRule(): Rule
	{
		return new ThrowsVoidFunctionWithExplicitThrowPointRule(new DefaultExceptionTypeResolver(
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
						'Function ThrowsVoidFunction\foo() throws exception ThrowsVoidFunction\MyException but the PHPDoc contains @throws void.',
						15,
					],
				],
			],
			[
				false,
				[\ThrowsVoidFunction\MyException::class],
				[],
			],
		];
	}

	/**
	 * @dataProvider dataRule
	 * @param bool $missingCheckedExceptionInThrows
	 * @param string[] $checkedExceptionClasses
	 * @param mixed[] $errors
	 */
	public function testRule(bool $missingCheckedExceptionInThrows, array $checkedExceptionClasses, array $errors): void
	{
		$this->missingCheckedExceptionInThrows = $missingCheckedExceptionInThrows;
		$this->checkedExceptionClasses = $checkedExceptionClasses;
		$this->analyse([__DIR__ . '/data/throws-void-function.php'], $errors);
	}

}
