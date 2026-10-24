<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<InvalidPromotedPropertiesRule>
 */
class InvalidPromotedPropertiesRulePhpVersionRangeTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidPromotedPropertiesRule();
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testBug15425(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15425-range.php'], [
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				10,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/invalidPromotedPropertiesPhpVersionRange.neon',
		];
	}

}
