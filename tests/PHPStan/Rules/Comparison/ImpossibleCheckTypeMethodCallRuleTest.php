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

	public function getRule(): Rule
	{
		return new ImpossibleCheckTypeMethodCallRule(
			new ImpossibleCheckTypeHelper(
				$this->createReflectionProvider(),
				$this->getTypeSpecifier(),
				[],
				$this->treatPhpDocTypesAsCertain,
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
				'Call to method ImpossibleMethodCall\Foo::isSame() with *NEVER* and stdClass will always evaluate to false.',
				84,
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

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/impossible-check-type-method-call.neon',
		];
	}

}
