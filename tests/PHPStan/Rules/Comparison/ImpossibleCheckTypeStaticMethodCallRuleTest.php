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

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/impossible-check-type-static-method-call.neon',
		];
	}

}
