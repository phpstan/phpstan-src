<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/** @extends RuleTestCase<OverridingConstantRule> */
class OverridingConstantRuleConfigPhpTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new OverridingConstantRule(
			true,
			new OverrideAttributeOnConstantCheck(
				checkMissingOverrideConstantAttribute: null,
				checkMissingOverrideMethodAttribute: true,
			),
		);
	}

	#[RequiresPhp('>= 8.2.0')]
	public function testMissingOverrideAttributeNotCheckedOnPhpVersionRangeSpanning86(): void
	{
		$this->analyse([__DIR__ . '/data/constant-override-attr.php'], [
			[
				'Constant ConstantOverrideAttr\Bar::PRIVATE_FROM_PARENT has #[\Override] attribute but does not override any constant.',
				28,
			],
			[
				'Constant ConstantOverrideAttr\Bar::NOT_OVERRIDING has #[\Override] attribute but does not override any constant.',
				31,
			],
			[
				'Constant ConstantOverrideAttr\Baz::ALSO_NOT_OVERRIDING has #[\Override] attribute but does not override any constant.',
				41,
			],
			[
				'Constant ConstantOverrideAttr\BarInterface::NOT_OVERRIDING has #[\Override] attribute but does not override any constant.',
				50,
			],
			[
				'Constant ConstantOverrideAttr\UsesTraitWithoutParent::FROM_PARENT has #[\Override] attribute but does not override any constant.',
				56,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/constant-override-attr-php-version.neon',
		];
	}

}
