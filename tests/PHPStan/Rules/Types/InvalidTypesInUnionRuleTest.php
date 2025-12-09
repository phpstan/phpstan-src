<?php declare(strict_types = 1);

namespace PHPStan\Rules\Types;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<InvalidTypesInUnionRule>
 */
class InvalidTypesInUnionRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidTypesInUnionRule();
	}

	public function testRuleOnUnionWithVoid(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-union-with-void.php'], [
			[
				'Type void cannot be part of a union type declaration.',
				11,
			],
			[
				'Type void cannot be part of a nullable type declaration.',
				15,
			],
		]);
	}

	#[RequiresPhp('>=8.0')]
	public function testRuleOnUnionWithMixed(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-union-with-mixed.php'], [
			[
				'Type mixed cannot be part of a nullable type declaration.',
				9,
			],
			[
				'Type mixed cannot be part of a union type declaration.',
				12,
			],
			[
				'Type mixed cannot be part of a union type declaration.',
				16,
			],
			[
				'Type mixed cannot be part of a union type declaration.',
				17,
			],
			[
				'Type mixed cannot be part of a union type declaration.',
				22,
			],
			[
				'Type mixed cannot be part of a nullable type declaration.',
				29,
			],
			[
				'Type mixed cannot be part of a nullable type declaration.',
				29,
			],
			[
				'Type mixed cannot be part of a nullable type declaration.',
				34,
			],
		]);
	}

	#[RequiresPhp('>=8.1')]
	public function testRuleOnUnionWithNever(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-union-with-never.php'], [
			[
				'Type never cannot be part of a nullable type declaration.',
				7,
			],
			[
				'Type never cannot be part of a union type declaration.',
				16,
			],
		]);
	}

}
