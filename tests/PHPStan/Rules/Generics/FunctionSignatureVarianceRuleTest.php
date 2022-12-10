<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<FunctionSignatureVarianceRule>
 */
class FunctionSignatureVarianceRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new FunctionSignatureVarianceRule(
			self::getContainer()->getByType(VarianceCheck::class),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/function-signature-variance.php'], [
			[
				'Variance annotation is only allowed for type parameters of classes and interfaces, but occurs in template type T in function FunctionSignatureVariance\f().',
				20,
			],
		]);
	}

}
