<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<EnumSanityRule>
 */
class EnumSanityRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new EnumSanityRule(
			self::getContainer()->getByType(InitializerExprTypeResolver::class),
		);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testDebugInfoPhpVersionRangeSpanning86(): void
	{
		$this->analyse([__DIR__ . '/data/enum-debug-info-php-versions.php'], [
			[
				'Enum EnumDebugInfoPhpVersions\UnsupportedInBranch contains magic method __debugInfo().',
				18,
			],
			[
				'Enum EnumDebugInfoPhpVersions\DependsOnPhpVersion contains magic method __debugInfo().',
				27,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/enum-debug-info-php-version.neon',
		];
	}

}
