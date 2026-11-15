<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<OverridingConstantRule>
 */
class OverridingConstantRuleConfigPhpTest extends RuleTestCase
{

	private ?bool $checkMissingOverrideConstantAttribute = null;

	protected function getRule(): Rule
	{
		return new OverridingConstantRule(
			true,
			new OverrideAttributeOnClassConstantCheck(
				$this->checkMissingOverrideConstantAttribute,
				true,
				true,
			),
		);
	}

	public function testDefaultReportsOnlyWhenEveryPhpVersionSupportsAttribute(): void
	{
		$this->analyse([__DIR__ . '/data/constant-missing-override-attr-php-versions.php'], [
			[
				'Constant ConstantMissingOverrideAttrPhpVersions\SupportedInBranch::FOO overrides constant ConstantMissingOverrideAttrPhpVersions\Foo::FOO but is missing the #[\Override] attribute.',
				16,
			],
		]);
	}

	public function testEnabledReportsOnEveryPhpVersion(): void
	{
		$this->checkMissingOverrideConstantAttribute = true;
		$this->analyse([__DIR__ . '/data/constant-missing-override-attr-php-versions.php'], [
			[
				'Constant ConstantMissingOverrideAttrPhpVersions\SupportedInBranch::FOO overrides constant ConstantMissingOverrideAttrPhpVersions\Foo::FOO but is missing the #[\Override] attribute.',
				16,
			],
			[
				'Constant ConstantMissingOverrideAttrPhpVersions\DependsOnPhpVersion::FOO overrides constant ConstantMissingOverrideAttrPhpVersions\Foo::FOO but is missing the #[\Override] attribute.',
				24,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/constant-missing-override-attr-php-versions.neon',
		];
	}

}
