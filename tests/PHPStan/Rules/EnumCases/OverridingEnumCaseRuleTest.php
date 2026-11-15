<?php declare(strict_types = 1);

namespace PHPStan\Rules\EnumCases;

use PHPStan\Rules\Constants\OverrideAttributeOnClassConstantCheck;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<OverridingEnumCaseRule>
 */
class OverridingEnumCaseRuleTest extends RuleTestCase
{

	private bool $checkMissingOverrideConstantAttribute = false;

	protected function getRule(): Rule
	{
		return new OverridingEnumCaseRule(
			true,
			new OverrideAttributeOnClassConstantCheck(
				$this->checkMissingOverrideConstantAttribute,
				null,
				false,
			),
		);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testRule(): void
	{
		$this->checkMissingOverrideConstantAttribute = true;
		$this->analyse([__DIR__ . '/data/enum-case-override-attr.php'], [
			[
				'Enum case EnumCaseOverrideAttr\WithCase::J overrides constant EnumCaseOverrideAttr\I::J but is missing the #[\Override] attribute.',
				19,
			],
			[
				'Enum case EnumCaseOverrideAttr\ViaParentInterface::K has #[\Override] attribute but does not override any constant.',
				26,
			],
			[
				'Enum case EnumCaseOverrideAttr\NoInterface::C has #[\Override] attribute but does not override any constant.',
				32,
			],
			[
				'Enum case EnumCaseOverrideAttr\OverridesFinal::F overrides final constant EnumCaseOverrideAttr\WithFinal::F.',
				44,
			],
			[
				'Enum case EnumCaseOverrideAttr\PhpDocTypedEnum::INT is not compatible with type int of constant EnumCaseOverrideAttr\PhpDocTyped::INT.',
				66,
			],
			[
				'Enum case EnumCaseOverrideAttr\IntBackedPhpDocTypedEnum::INT is not compatible with type int of constant EnumCaseOverrideAttr\PhpDocTyped::INT.',
				76,
			],
		]);
	}

	#[RequiresPhp('>= 8.3.0')]
	public function testNativeTypes(): void
	{
		$this->analyse([__DIR__ . '/data/enum-case-override-native-types.php'], [
			[
				'Enum case EnumCaseOverrideNativeTypes\Foo::INT is not compatible with native type int of constant EnumCaseOverrideNativeTypes\NativeTyped::INT.',
				23,
			],
			[
				'Enum case EnumCaseOverrideNativeTypes\Foo::UNIT_ENUM is not compatible with native type UnitEnum of constant EnumCaseOverrideNativeTypes\NativeTyped::UNIT_ENUM.',
				24,
			],
			[
				'Enum case EnumCaseOverrideNativeTypes\Foo::MIXED is not compatible with native type mixed of constant EnumCaseOverrideNativeTypes\NativeTyped::MIXED.',
				25,
			],
		]);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testFixOverrideAttribute(): void
	{
		$this->checkMissingOverrideConstantAttribute = true;
		$this->fix(__DIR__ . '/data/enum-case-override-attr-fix.php', __DIR__ . '/data/enum-case-override-attr-fix.php.fixed');
	}

}
