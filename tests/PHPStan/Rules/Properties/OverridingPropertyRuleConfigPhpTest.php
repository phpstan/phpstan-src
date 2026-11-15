<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<OverridingPropertyRule>
 */
class OverridingPropertyRuleConfigPhpTest extends RuleTestCase
{

	private ?bool $checkMissingOverridePropertyAttribute = null;

	protected function getRule(): Rule
	{
		return new OverridingPropertyRule(
			self::getContainer()->getByType(PhpVersion::class),
			true,
			true,
			$this->checkMissingOverridePropertyAttribute,
			true,
		);
	}

	public function testDefaultReportsOnlyWhenEveryPhpVersionSupportsAttribute(): void
	{
		$this->analyse([__DIR__ . '/data/property-missing-override-attr-php-versions.php'], [
			[
				'Property PropertyMissingOverrideAttrPhpVersions\SupportedInBranch::$foo overrides property PropertyMissingOverrideAttrPhpVersions\Foo::$foo but is missing the #[\Override] attribute.',
				16,
			],
		]);
	}

	public function testEnabledReportsOnEveryPhpVersion(): void
	{
		$this->checkMissingOverridePropertyAttribute = true;
		$this->analyse([__DIR__ . '/data/property-missing-override-attr-php-versions.php'], [
			[
				'Property PropertyMissingOverrideAttrPhpVersions\SupportedInBranch::$foo overrides property PropertyMissingOverrideAttrPhpVersions\Foo::$foo but is missing the #[\Override] attribute.',
				16,
			],
			[
				'Property PropertyMissingOverrideAttrPhpVersions\DependsOnPhpVersion::$foo overrides property PropertyMissingOverrideAttrPhpVersions\Foo::$foo but is missing the #[\Override] attribute.',
				24,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/property-missing-override-attr-php-versions.neon',
		];
	}

}
