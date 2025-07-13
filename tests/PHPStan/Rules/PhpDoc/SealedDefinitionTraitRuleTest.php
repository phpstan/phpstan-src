<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<SealedDefinitionTraitRule>
 */
class SealedDefinitionTraitRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$reflectionProvider = self::createReflectionProvider();

		return new SealedDefinitionTraitRule($reflectionProvider);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/incompatible-sealed.php'], [
			[
				'PHPDoc tag @phpstan-sealed is only valid on class or interface.',
				11,
			],
		]);
	}

}
