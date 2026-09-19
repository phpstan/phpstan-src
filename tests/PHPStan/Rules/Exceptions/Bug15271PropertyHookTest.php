<?php declare(strict_types = 1);

namespace PHPStan\Rules\Exceptions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<MissingCheckedExceptionInPropertyHookThrowsRule>
 */
class Bug15271PropertyHookTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingCheckedExceptionInPropertyHookThrowsRule(
			self::getContainer()->getByType(MissingCheckedExceptionInThrowsCheck::class),
		);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15271-property-hook.php'], [
			[
				'Get hook for property Bug15271PropertyHook\Foo::$k throws checked exception Exception but it\'s missing from the PHPDoc @throws tag.',
				24,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/bug-15271.neon',
		];
	}

}
