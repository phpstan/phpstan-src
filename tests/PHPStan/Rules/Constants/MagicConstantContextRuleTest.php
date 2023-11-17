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
				'Magic constant __CLASS__ is always empty outside a class.',
				5,
			],
			[
				'Magic constant __FUNCTION__ is always empty outside a function.',
				6,
			],
			[
				'Magic constant __METHOD__ is always empty outside a function.',
				7,
			],
			[
				'Magic constant __TRAIT__ is always empty outside a trait.',
				9,
			],
			[
				'Magic constant __TRAIT__ is always empty outside a trait.',
				17,
			],
			[
				'Magic constant __CLASS__ is always empty outside a class.',
				22,
			],
			[
				'Magic constant __TRAIT__ is always empty outside a trait.',
				26,
			],
			[
				'Magic constant __CLASS__ is always empty outside a class.',
				59,
			],
			[
				'Magic constant __TRAIT__ is always empty outside a trait.',
				64,
			],
			[
				'Magic constant __TRAIT__ is always empty outside a trait.',
				78,
			],
			[
				'Magic constant __METHOD__ is always empty outside a function.',
				91,
			],
			[
				'Magic constant __FUNCTION__ is always empty outside a function.',
				92,
			],
			[
				'Magic constant __TRAIT__ is always empty outside a trait.',
				93,
			],
			[
				'Magic constant __CLASS__ is always empty outside a class.',
				97,
			],
			[
				'Magic constant __TRAIT__ is always empty outside a trait.',
				101,
			],
			[
				'Magic constant __CLASS__ is always empty outside a class.',
				105,
			],
			[
				'Magic constant __TRAIT__ is always empty outside a trait.',
				109,
			],
			[
				'Magic constant __CLASS__ is always empty outside a class.',
				115,
			],
			[
				'Magic constant __TRAIT__ is always empty outside a trait.',
				120,
			],
			[
				'Magic constant __TRAIT__ is always empty outside a trait.',
				133,
			],
		]);
	}

	public function testGlobalNamespace(): void
	{
		$this->analyse([__DIR__ . '/data/magic-constant-global-ns.php'], [
			[
				'Magic constant __CLASS__ is always empty outside a class.',
				5,
			],
			[
				'Magic constant __FUNCTION__ is always empty outside a function.',
				6,
			],
			[
				'Magic constant __METHOD__ is always empty outside a function.',
				7,
			],
			[
				'Magic constant __NAMESPACE__ is always empty in global namespace.',
				8,
			],
			[
				'Magic constant __TRAIT__ is always empty outside a trait.',
				9,
			],
			[
				'Magic constant __NAMESPACE__ is always empty in global namespace.',
				16,
			],
			[
				'Magic constant __TRAIT__ is always empty outside a trait.',
				17,
			],
			[
				'Magic constant __CLASS__ is always empty outside a class.',
				22,
			],
			[
				'Magic constant __NAMESPACE__ is always empty in global namespace.',
				25,
			],
			[
				'Magic constant __TRAIT__ is always empty outside a trait.',
				26,
			],
			[
				'Magic constant __NAMESPACE__ is always empty in global namespace.',
				34,
			],
			[
				'Magic constant __CLASS__ is always empty outside a class.',
				46,
			],
			[
				'Magic constant __NAMESPACE__ is always empty in global namespace.',
				48,
			],
			[
				'Magic constant __TRAIT__ is always empty outside a trait.',
				51,
			],
		]);
	}

}
