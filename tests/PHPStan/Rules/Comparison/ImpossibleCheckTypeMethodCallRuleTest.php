<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ImpossibleCheckTypeMethodCallRule>
 */
class ImpossibleCheckTypeMethodCallRuleTest extends RuleTestCase
{

	private bool $treatPhpDocTypesAsCertain;

	private bool $reportAlwaysTrueInLastCondition = false;

	public function getRule(): Rule
	{
		return new ImpossibleCheckTypeMethodCallRule(
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
		$this->analyse([__DIR__ . '/data/impossible-method-call.php'], [
			[
				'Call to method PHPStan\Tests\AssertionClass::assertString() with string will always evaluate to true.',
				14,
			],
			[
				'Call to method PHPStan\Tests\AssertionClass::assertString() with int will always evaluate to false.',
				15,
			],
			[
				'Call to method PHPStan\Tests\AssertionClass::assertNotInt() with int will always evaluate to false.',
				30,
			],
			[
				'Call to method PHPStan\Tests\AssertionClass::assertNotInt() with string will always evaluate to true.',
				36,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isSame() with 1 and 1 will always evaluate to true.',
				60,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isSame() with 1 and 2 will always evaluate to false.',
				63,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isNotSame() with 1 and 1 will always evaluate to false.',
				66,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isNotSame() with 1 and 2 will always evaluate to true.',
				69,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isSame() with stdClass and stdClass will always evaluate to true.',
				78,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isNotSame() with stdClass and stdClass will always evaluate to false.',
				81,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isSame() with \'foo\' and \'foo\' will always evaluate to true.',
				101,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isNotSame() with \'foo\' and \'foo\' will always evaluate to false.',
				104,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isSame() with array{} and array{} will always evaluate to true.',
				113,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isNotSame() with array{} and array{} will always evaluate to false.',
				116,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isSame() with array{1, 3} and array{1, 3} will always evaluate to true.',
				119,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isNotSame() with array{1, 3} and array{1, 3} will always evaluate to false.',
				122,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isSame() with 1 and stdClass will always evaluate to false.',
				126,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isNotSame() with 1 and stdClass will always evaluate to true.',
				130,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isSame() with \'1\' and stdClass will always evaluate to false.',
				133,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isNotSame() with \'1\' and stdClass will always evaluate to true.',
				136,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isSame() with array{\'a\', \'b\'} and array{1, 2} will always evaluate to false.',
				139,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isNotSame() with array{\'a\', \'b\'} and array{1, 2} will always evaluate to true.',
				142,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isSame() with stdClass and \'1\' will always evaluate to false.',
				145,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isNotSame() with stdClass and \'1\' will always evaluate to true.',
				148,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isSame() with \'\' and \'\' will always evaluate to true.',
				174,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isNotSame() with \'\' and \'\' will always evaluate to false.',
				175,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isSame() with 1 and 1 will always evaluate to true.',
				191,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isNotSame() with 2 and 2 will always evaluate to false.',
				194,
			],
			[
				'Call to method ImpossibleMethodCall\ConditionalAlwaysTrue::isInt() with int will always evaluate to true.',
				208,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]);
	}

	public function testDoNotReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = false;
		$this->analyse([__DIR__ . '/data/impossible-method-call-not-phpdoc.php'], [
			[
				'Call to method PHPStan\Tests\AssertionClass::assertString() with string will always evaluate to true.',
				17,
			],
			[
				'Call to method PHPStan\Tests\AssertionClass::assertString() with string will always evaluate to true.',
				19,
			],
		]);
	}

	public function testReportPhpDoc(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/impossible-method-call-not-phpdoc.php'], [
			[
				'Call to method PHPStan\Tests\AssertionClass::assertString() with string will always evaluate to true.',
				17,
			],
			[
				'Call to method PHPStan\Tests\AssertionClass::assertString() with string will always evaluate to true.',
				18,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Call to method PHPStan\Tests\AssertionClass::assertString() with string will always evaluate to true.',
				19,
			],
		]);
	}

	public function testBug8169(): void
	{
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-8169.php'], [
			[
				'Call to method Bug8169\HelloWorld::assertString() with string will always evaluate to true.',
				19,
			],
			[
				'Call to method Bug8169\HelloWorld::assertString() with string will always evaluate to true.',
				26,
			],
			[
				'Call to method Bug8169\HelloWorld::assertString() with int will always evaluate to false.',
				33,
			],
		]);
	}

	public function dataReportAlwaysTrueInLastCondition(): iterable
	{
		yield [false, [
			[
				'Call to method PHPStan\Tests\AssertionClass::assertString() with string will always evaluate to true.',
				25,
				'Remove remaining cases below this one and this error will disappear too.',
			],
		]];
		yield [true, [
			[
				'Call to method PHPStan\Tests\AssertionClass::assertString() with string will always evaluate to true.',
				15,
			],
			[
				'Call to method PHPStan\Tests\AssertionClass::assertString() with string will always evaluate to true.',
				25,
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
		$this->analyse([__DIR__ . '/data/impossible-method-report-always-true-last-condition.php'], $expectedErrors);
	}

	public function testBugInstanceofStaticVsThis(): void
	{
		$message = 'Call to method BugInstanceofStaticVsThisImpossibleCheck\FooInterface::isNull() with int will always evaluate to false.';
		$tip = 'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.';
		$this->treatPhpDocTypesAsCertain = true;
		$this->analyse([__DIR__ . '/data/bug-instanceof-static-vs-this-impossible-check.php'], [
			[
				$message,
				17,
				$tip,
			],
			[
				$message,
				23,
				$tip,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/impossible-check-type-method-call.neon',
		];
	}

}
