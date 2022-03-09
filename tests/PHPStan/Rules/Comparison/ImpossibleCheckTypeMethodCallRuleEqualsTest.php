<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ImpossibleCheckTypeMethodCallRule>
 */
class ImpossibleCheckTypeMethodCallRuleEqualsTest extends RuleTestCase
{

	public function getRule(): Rule
	{
		return new ImpossibleCheckTypeMethodCallRule(
			new ImpossibleCheckTypeHelper(
				$this->createReflectionProvider(),
				$this->getTypeSpecifier(),
				[],
				true,
			),
			true,
			true,
		);
	}

	public function testRule(): void
	{
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
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/impossible-check-type-method-call-equals.neon',
		];
	}

}
