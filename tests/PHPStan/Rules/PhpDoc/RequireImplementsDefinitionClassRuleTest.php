<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<RequireImplementsDefinitionClassRule>
 */
class RequireImplementsDefinitionClassRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RequireImplementsDefinitionClassRule();
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-require-implements.php'], [
			[
				'PHPDoc tag @phpstan-require-implements is only valid on trait.',
				40,
			],
			[
				'PHPDoc tag @phpstan-require-implements is only valid on trait.',
				45,
			],
		]);
	}

}
