<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends \PHPStan\Testing\RuleTestCase<FunctionSignatureVarianceRule>
 */
class FunctionSignatureVarianceRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$broker = $this->createReflectionProvider();
		return new FunctionSignatureVarianceRule(
			$broker,
			self::getContainer()->getByType(VarianceCheck::class)
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/function-signature-variance.php'], [
			[
				'Template type T is declared as covariant, but occurs in contravariant position in parameter a of function FunctionSignatureVariance\f().',
				20,
			],
			[
				'Template type T is declared as covariant, but occurs in invariant position in parameter b of function FunctionSignatureVariance\f().',
				20,
			],
			[
				'Template type T is declared as covariant, but occurs in contravariant position in parameter c of function FunctionSignatureVariance\f().',
				20,
			],
		]);
	}

}
