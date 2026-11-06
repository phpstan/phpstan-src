<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<PropertyAssignRefRule>
 */
class PropertyAssignRefRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(PropertyAssignRefRule::class);
	}

	#[RequiresPhp('>= 8.4.0')]
	public function testRuleInPhpVersionRange(): void
	{
		$this->analyse([__DIR__ . '/data/property-assign-ref.php'], [
			[
				'Property PropertyAssignRef\\Foo::$foo with private visibility is assigned by reference.',
				25,
			],
			[
				'Property PropertyAssignRef\\Foo::$bar with protected(set) visibility is assigned by reference.',
				26,
			],
			[
				'Property PropertyAssignRef\\Baz::$a with protected visibility is assigned by reference.',
				41,
			],
			[
				'Property PropertyAssignRef\\Baz::$b with private visibility is assigned by reference.',
				42,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/../data/php-version-range.neon',
		];
	}

}
