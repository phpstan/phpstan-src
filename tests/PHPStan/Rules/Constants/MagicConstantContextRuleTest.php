<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MagicConstantContextRule>
 */
class MagicConstantContextRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MagicConstantContextRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/magic-constant.php'], [
			[
				'Magic constant __CLASS__ is always empty when used outside a class.',
				5,
			],
			[
				'Magic constant __FUNCTION__ is always empty when used outside a function.',
				6,
			],
			[
				'Magic constant __METHOD__ is always empty when used outside a function.',
				7,
			],
			[
				'Magic constant __TRAIT__ is always empty when used outside a trait-using-class.',
				9,
			],
			[
				'Magic constant __TRAIT__ is always empty when used outside a trait-using-class.',
				17,
			],
			[
				'Magic constant __CLASS__ is always empty when used outside a class.',
				22,
			],
			[
				'Magic constant __TRAIT__ is always empty when used outside a trait-using-class.',
				26,
			],
			[
				'Magic constant __CLASS__ is always empty when used outside a class.',
				46,
			],
			[
				'Magic constant __TRAIT__ is always empty when used outside a trait-using-class.',
				51,
			],

		]);
	}

	public function testGlobalNamespace(): void
	{
		$this->analyse([__DIR__ . '/data/magic-constant-global-ns.php'], [
			[
				'Magic constant __CLASS__ is always empty when used outside a class.',
				5,
			],
			[
				'Magic constant __FUNCTION__ is always empty when used outside a function.',
				6,
			],
			[
				'Magic constant __METHOD__ is always empty when used outside a function.',
				7,
			],
			[
				'Magic constant __NAMESPACE__ is always empty when used in global namespace.',
				8,
			],
			[
				'Magic constant __TRAIT__ is always empty when used outside a trait-using-class.',
				9,
			],
			[
				'Magic constant __NAMESPACE__ is always empty when used in global namespace.',
				16,
			],
			[
				'Magic constant __TRAIT__ is always empty when used outside a trait-using-class.',
				17,
			],
			[
				'Magic constant __CLASS__ is always empty when used outside a class.',
				22,
			],
			[
				'Magic constant __NAMESPACE__ is always empty when used in global namespace.',
				25,
			],
			[
				'Magic constant __TRAIT__ is always empty when used outside a trait-using-class.',
				26,
			],
			[
				'Magic constant __NAMESPACE__ is always empty when used in global namespace.',
				34,
			],
			[
				'Magic constant __CLASS__ is always empty when used outside a class.',
				46,
			],
			[
				'Magic constant __NAMESPACE__ is always empty when used in global namespace.',
				48,
			],
			[
				'Magic constant __TRAIT__ is always empty when used outside a trait-using-class.',
				51,
			],
		]);
	}

}
