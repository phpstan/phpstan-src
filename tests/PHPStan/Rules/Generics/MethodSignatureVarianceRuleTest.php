<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MethodSignatureVarianceRule>
 */
class MethodSignatureVarianceRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MethodSignatureVarianceRule(
			self::getContainer()->getByType(VarianceCheck::class),
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/method-signature-variance.php'], [
			[
				'Template type T is declared as covariant, but occurs in contravariant position in parameter a of method MethodSignatureVariance\C::a().',
				25,
			],
			[
				'Template type T is declared as covariant, but occurs in invariant position in parameter b of method MethodSignatureVariance\C::a().',
				25,
			],
			[
				'Template type T is declared as covariant, but occurs in contravariant position in parameter c of method MethodSignatureVariance\C::a().',
				25,
			],
			[
				'Template type W is declared as covariant, but occurs in contravariant position in parameter d of method MethodSignatureVariance\C::a().',
				25,
			],
			[
				'Variance annotation is only allowed for type parameters of classes and interfaces, but occurs in template type U in in method MethodSignatureVariance\C::b().',
				35,
			],
		]);
	}

}
