<?php declare(strict_types = 1);

namespace PHPStan\Rules\RestrictedUsage;

use PHPStan\DependencyInjection\LazyExtensionsCollection;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RestrictedUsageOfDeprecatedStringCastRule>
 */
class RestrictedUsageOfDeprecatedStringCastRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RestrictedUsageOfDeprecatedStringCastRule(
			new LazyExtensionsCollection(self::getContainer(), RestrictedMethodUsageExtension::class),
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

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/restricted-usage.neon',
			...parent::getAdditionalConfigFiles(),
		];
	}

}
