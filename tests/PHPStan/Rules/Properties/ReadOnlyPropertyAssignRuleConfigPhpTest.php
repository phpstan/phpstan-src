<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ReadOnlyPropertyAssignRule>
 */
class ReadOnlyPropertyAssignRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(ReadOnlyPropertyAssignRule::class);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testRuleInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/readonly-property-assign-php-version-range.php'], [
			[
				'Readonly property ReadonlyPropertyAssignPhpVersionRange\ParentClass::$a is assigned outside of its declaring class.',
				25,
			],
			[
				'Readonly property ReadonlyPropertyAssignPhpVersionRange\Foo::$a is already assigned.',
				44,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/php-version-range-81.neon',
		];
	}

}
