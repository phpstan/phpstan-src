<?php declare(strict_types = 1);

namespace PHPStan\Rules\Missing;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<MissingClosureNativeReturnTypehintRule>
 */
class MissingClosureNativeReturnTypehintRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MissingClosureNativeReturnTypehintRule(true);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/missing-closure-native-return-typehint.php'], [
			[
				'Anonymous function should have native return typehint "void".',
				10,
			],
			[
				'Anonymous function should have native return typehint "void".',
				13,
			],
			[
				'Anonymous function should have native return typehint "Generator".',
				16,
			],
			[
				'Mixing returning values with empty return statements - return null should be used here.',
				25,
			],
			[
				'Anonymous function should have native return typehint "?int".',
				23,
			],
			[
				'Anonymous function should have native return typehint "?int".',
				33,
			],
			[
				'Anonymous function sometimes return something but return statement at the end is missing.',
				40,
			],
			[
				'Anonymous function should have native return typehint "array".',
				46,
			],
		]);
	}

	public function testBug2682(): void
	{
		$this->analyse([__DIR__ . '/data/bug-2682.php'], [
			[
				'Anonymous function should have native return typehint "void".',
				9,
			],
		]);
	}

}
