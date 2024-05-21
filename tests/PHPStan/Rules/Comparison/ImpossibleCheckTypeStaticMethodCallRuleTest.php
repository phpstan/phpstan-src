<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ImpossibleCheckTypeStaticMethodCallRule>
 */
class ImpossibleCheckTypeStaticMethodCallRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	private bool $reportAlwaysTrueInLastCondition = false;

	public function getRule(): Rule
	{
		return new ImpossibleCheckTypeStaticMethodCallRule(
			new ImpossibleCheckTypeHelper(
				$this->createReflectionProvider(),
				$this->getTypeSpecifier(),
				[],
				$this->treatPhpDocTypesAsCertain,
				true,
			),
			true,
			$this->treatPhpDocTypesAsCertain,
			$this->reportAlwaysTrueInLastCondition,
		);
	}

	protected function shouldTreatPhpDocTypesAsCertain(): bool
	{
		return $this->treatPhpDocTypesAsCertain;
	}

	public function testRule(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/impossible-static-method-call.php'], [
			[
				'Call to static method PHPStan\Tests\AssertionClass::assertInt() with int will always evaluate to true.',
				13,
			],
			[
				'Call to static method PHPStan\Tests\AssertionClass::assertInt() with string will always evaluate to false.',
				14,
			],
			[
				'Call to static method PHPStan\Tests\AssertionClass::assertInt() with int will always evaluate to true.',
				31,
			],
			[
				'Call to static method PHPStan\Tests\AssertionClass::assertInt() with string will always evaluate to false.',
				32,
			],
			[
				'Call to static method PHPStan\Tests\AssertionClass::assertInt() with 1 and 2 will always evaluate to true.',
				33,
			],
			[
				'Call to static method PHPStan\Tests\AssertionClass::assertInt() with arguments 1, 2 and 3 will always evaluate to true.',
				34,
			],
			[
				'Call to static method ImpossibleStaticMethodCall\ConditionalAlwaysTrue::isInt() with int will always evaluate to true.',
				66,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	public function testDoNotReportPhpDocs(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/impossible-static-method-call-not-phpdoc.php'], [
			[
				'Call to static method PHPStan\Tests\AssertionClass::assertInt() with int will always evaluate to true.',
				16,
			],
			[
				'Call to static method PHPStan\Tests\AssertionClass::assertInt() with int will always evaluate to true.',
				18,
			],
		]);
	}

	public function testReportPhpDocs(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/impossible-static-method-call-not-phpdoc.php'], [
			[
				'Call to static method PHPStan\Tests\AssertionClass::assertInt() with int will always evaluate to true.',
				16,
			],
			[
				'Call to static method PHPStan\Tests\AssertionClass::assertInt() with int will always evaluate to true.',
				17,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Call to static method PHPStan\Tests\AssertionClass::assertInt() with int will always evaluate to true.',
				18,
			],
		]);
	}

	public function testAssertUnresolvedGeneric(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/assert-unresolved-generic.php'], []);
	}

	public function dataReportAlwaysTrueInLastCondition(): iterable
	{
		yield [false, [
			[
				'Call to static method PHPStan\Tests\AssertionClass::assertInt() with int will always evaluate to true.',
				23,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]];
		yield [true, [
			[
				'Call to static method PHPStan\Tests\AssertionClass::assertInt() with int will always evaluate to true.',
				14,
			],
			[
				'Call to static method PHPStan\Tests\AssertionClass::assertInt() with int will always evaluate to true.',
				23,
			],
		]];
	}

	/**
	 * @dataProvider dataReportAlwaysTrueInLastCondition
	 * @param list<array{0: string, 1: int, 2?: string}> $expectedErrors
	 */
	public function testReportAlwaysTrueInLastCondition(bool $reportAlwaysTrueInLastCondition, array $expectedErrors): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->reportAlwaysTrueInLastCondition = $reportAlwaysTrueInLastCondition;
		$this->analyse([__DIR__ . '/data/impossible-static-method-report-always-true-last-condition.php'], $expectedErrors);
	}

	public function testBugInstanceofStaticVsThis(): void
	{
		$message = 'Call to static method BugInstanceofStaticVsThisImpossibleCheck\FooInterface::isNull() with int will always evaluate to false.';
		$tip = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-instanceof-static-vs-this-impossible-check.php'], [
			[
				$message,
				18,
				$tip,
			],
			[
				$message,
				19,
				$tip,
			],
			[
				$message,
				24,
				$tip,
			],
			[
				$message,
				25,
				$tip,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/impossible-check-type-static-method-call.neon',
		];
	}

}
