<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\ShouldNotHappenException;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<MissingCheckedExceptionInPropertyHookThrowsRule>
 */
class MissingCheckedExceptionInPropertyHookThrowsRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingCheckedExceptionInPropertyHookThrowsRule(
			new MissingCheckedExceptionInThrowsCheck(new DefaultExceptionTypeResolver(
				self::createReflectionProvider(),
				[],
				[ShouldNotHappenException::class],
				[],
				[],
			)),
		);
	}

	#[RequiresPhp('>= 8.4')]
	public function testRule(): void
	{
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
			[
				'Get hook for property MissingExceptionPropertyHookThrows\Foo::$n throws checked exception InvalidArgumentException but it\'s missing from the PHPDoc @throws tag.',
				43,
			],
		]);
	}

}
