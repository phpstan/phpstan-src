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
				'Template type K is declared as contravariant, but occurs in covariant position in parameter b of method MethodSignatureVariance\B::a().',
				94,
			],
			[
				'Template type K is declared as contravariant, but occurs in covariant position in parameter d of method MethodSignatureVariance\B::a().',
				94,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in parameter e of method MethodSignatureVariance\B::a().',
				94,
			],
			[
				'Template type K is declared as contravariant, but occurs in covariant position in parameter h of method MethodSignatureVariance\B::a().',
				94,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in parameter i of method MethodSignatureVariance\B::a().',
				94,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in parameter j of method MethodSignatureVariance\B::a().',
				94,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in parameter k of method MethodSignatureVariance\B::a().',
				94,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in parameter l of method MethodSignatureVariance\B::a().',
				94,
			],
			[
				'Template type K is declared as contravariant, but occurs in covariant position in return type of method MethodSignatureVariance\B::a().',
				94,
			],
			[
				'Template type K is declared as contravariant, but occurs in covariant position in return type of method MethodSignatureVariance\B::c().',
				100,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVariance\B::e().',
				106,
			],
			[
				'Template type K is declared as contravariant, but occurs in covariant position in return type of method MethodSignatureVariance\B::f().',
				109,
			],
			[
				'Template type K is declared as contravariant, but occurs in covariant position in return type of method MethodSignatureVariance\B::g().',
				112,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVariance\B::i().',
				118,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVariance\B::j().',
				121,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVariance\B::k().',
				124,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVariance\B::l().',
				127,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVariance\B::m().',
				130,
			],
			[
				'Template type K is declared as covariant, but occurs in contravariant position in parameter a of method MethodSignatureVariance\C::a().',
				152,
			],
			[
				'Template type K is declared as covariant, but occurs in contravariant position in parameter c of method MethodSignatureVariance\C::a().',
				152,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in parameter e of method MethodSignatureVariance\C::a().',
				152,
			],
			[
				'Template type K is declared as covariant, but occurs in contravariant position in parameter f of method MethodSignatureVariance\C::a().',
				152,
			],
			[
				'Template type K is declared as covariant, but occurs in contravariant position in parameter g of method MethodSignatureVariance\C::a().',
				152,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in parameter i of method MethodSignatureVariance\C::a().',
				152,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in parameter j of method MethodSignatureVariance\C::a().',
				152,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in parameter k of method MethodSignatureVariance\C::a().',
				152,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in parameter l of method MethodSignatureVariance\C::a().',
				152,
			],
			[
				'Template type K is declared as covariant, but occurs in contravariant position in return type of method MethodSignatureVariance\C::b().',
				155,
			],
			[
				'Template type K is declared as covariant, but occurs in contravariant position in return type of method MethodSignatureVariance\C::d().',
				161,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVariance\C::e().',
				164,
			],
			[
				'Template type K is declared as covariant, but occurs in contravariant position in return type of method MethodSignatureVariance\C::h().',
				173,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVariance\C::i().',
				176,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVariance\C::j().',
				179,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVariance\C::k().',
				182,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVariance\C::l().',
				185,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVariance\C::m().',
				188,
			],
		]);
	}

}
