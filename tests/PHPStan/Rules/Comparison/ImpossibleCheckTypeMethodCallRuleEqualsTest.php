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
				self::createReflectionProvider(),
				$this->getTypeSpecifier(),
				[],
				true,
			),
			true,
			false,
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
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
			],
			[
				'Call to method PHPStan\Tests\AssertionClass::assertNotInt() with string will always evaluate to true.',
				36,
				'Because the type is coming from a PHPDoc, you can turn off this check by setting <fg=cyan>treatPhpDocTypesAsCertain: false</> in your <fg=cyan>%configurationFile%</>.',
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
				'Call to method ImpossibleMethodCall\Foo::isSame() with array{\'a\', \'b\'} and array{1, 2} will always evaluate to false.',
				139,
			],
			[
				'Call to method ImpossibleMethodCall\Foo::isNotSame() with array{\'a\', \'b\'} and array{1, 2} will always evaluate to true.',
				142,
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

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/impossible-check-type-method-call-equals.neon',
		];
	}

}
