<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<IncompatibleRequireImplementsTypeClassRule>
 */
class IncompatibleRequireImplementsTypeClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new IncompatibleRequireImplementsTypeClassRule();
	}

	public function testRule(): void
	{
		$expectedErrors = [
			[
				'PHPDoc tag @phpstan-require-implements is only valid on trait.',
				40,
			],
			[
				'PHPDoc tag @phpstan-require-implements is only valid on trait.',
				45,
			],
		];

		$this->analyse([__DIR__ . '/data/incompatible-require-implements.php'], $expectedErrors);
	}

}
