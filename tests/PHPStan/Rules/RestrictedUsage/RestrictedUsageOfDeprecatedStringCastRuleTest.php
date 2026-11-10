<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<RestrictedUsageOfDeprecatedStringCastRule>
 */
class RestrictedUsageOfDeprecatedStringCastRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RestrictedUsageOfDeprecatedStringCastRule(
			self::getContainer()->getExtensionsCollection(RestrictedMethodUsageExtension::class),
			self::createReflectionProvider(),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/restricted-to-string.php'], [
			[
				'Cannot call __toString',
				11,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug15681(): void
	{
		$this->analyse([__DIR__ . '/data/bug-15681.php'], [
			[
				'Cannot call __toString',
				67,
			],
			[
				'Cannot call __toString',
				68,
			],
			[
				'Cannot call __toString',
				145,
			],
			[
				'Cannot call __toString',
				146,
			],
			[
				'Cannot call __toString',
				147,
			],
			[
				'Cannot call __toString',
				159,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/restricted-usage.neon',
			...parent::getAdditionalConfigFiles(),
		];
	}

}
