<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<MissingCheckedExceptionInPropertyHookThrowsRule>
 */
class MissingCheckedExceptionInPropertyHookThrowsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingCheckedExceptionInPropertyHookThrowsRule(
			new MissingCheckedExceptionInThrowsCheck(new DefaultExceptionTypeResolver(
				$this->createReflectionProvider(),
				[],
				[ShouldNotHappenException::class],
				[],
				[],
			)),
		);
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/missing-exception-property-hook-throws.php'], [
			[
				'Get hook for property MissingExceptionPropertyHookThrows\Foo::$k throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag.',
				25,
			],
			[
				'Set hook for property MissingExceptionPropertyHookThrows\Foo::$l throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag.',
				32,
			],
			[
				'Get hook for property MissingExceptionPropertyHookThrows\Foo::$m throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag.',
				38,
			],
		]);
	}

}
