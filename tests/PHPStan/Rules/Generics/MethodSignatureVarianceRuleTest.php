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

	public function testRuleInvariant(): void
	{
		$this->analyse([__DIR__ . '/data/method-signature-variance-invariant.php'], []);
	}

	public function testRuleContravariant(): void
	{
		$this->analyse([__DIR__ . '/data/method-signature-variance-contravariant.php'], [
			[
				'Template type K is declared as contravariant, but occurs in covariant position in parameter b of method MethodSignatureVarianceContravariant\A::a().',
				38,
			],
			[
				'Template type K is declared as contravariant, but occurs in covariant position in parameter d of method MethodSignatureVarianceContravariant\A::a().',
				38,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in parameter e of method MethodSignatureVarianceContravariant\A::a().',
				38,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in parameter f of method MethodSignatureVarianceContravariant\A::a().',
				38,
			],
			[
				'Template type K is declared as contravariant, but occurs in covariant position in parameter i of method MethodSignatureVarianceContravariant\A::a().',
				38,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in parameter j of method MethodSignatureVarianceContravariant\A::a().',
				38,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in parameter k of method MethodSignatureVarianceContravariant\A::a().',
				38,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in parameter l of method MethodSignatureVarianceContravariant\A::a().',
				38,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in parameter m of method MethodSignatureVarianceContravariant\A::a().',
				38,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in parameter n of method MethodSignatureVarianceContravariant\A::a().',
				38,
			],
			[
				'Template type K is declared as contravariant, but occurs in covariant position in return type of method MethodSignatureVarianceContravariant\A::a().',
				38,
			],
			[
				'Template type K is declared as contravariant, but occurs in covariant position in return type of method MethodSignatureVarianceContravariant\A::c().',
				44,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVarianceContravariant\A::e().',
				50,
			],
			[
				'Template type K is declared as contravariant, but occurs in covariant position in return type of method MethodSignatureVarianceContravariant\A::f().',
				53,
			],
			[
				'Template type K is declared as contravariant, but occurs in covariant position in return type of method MethodSignatureVarianceContravariant\A::g().',
				56,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVarianceContravariant\A::i().',
				62,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVarianceContravariant\A::j().',
				65,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVarianceContravariant\A::k().',
				68,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVarianceContravariant\A::l().',
				71,
			],
			[
				'Template type K is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVarianceContravariant\A::m().',
				74,
			],
		]);
	}

	public function testRuleCovariant(): void
	{
		$this->analyse([__DIR__ . '/data/method-signature-variance-covariant.php'], [
			[
				'Template type K is declared as covariant, but occurs in contravariant position in parameter a of method MethodSignatureVarianceCovariant\A::a().',
				38,
			],
			[
				'Template type K is declared as covariant, but occurs in contravariant position in parameter c of method MethodSignatureVarianceCovariant\A::a().',
				38,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in parameter e of method MethodSignatureVarianceCovariant\A::a().',
				38,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in parameter f of method MethodSignatureVarianceCovariant\A::a().',
				38,
			],
			[
				'Template type K is declared as covariant, but occurs in contravariant position in parameter g of method MethodSignatureVarianceCovariant\A::a().',
				38,
			],
			[
				'Template type K is declared as covariant, but occurs in contravariant position in parameter h of method MethodSignatureVarianceCovariant\A::a().',
				38,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in parameter j of method MethodSignatureVarianceCovariant\A::a().',
				38,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in parameter k of method MethodSignatureVarianceCovariant\A::a().',
				38,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in parameter l of method MethodSignatureVarianceCovariant\A::a().',
				38,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in parameter m of method MethodSignatureVarianceCovariant\A::a().',
				38,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in parameter n of method MethodSignatureVarianceCovariant\A::a().',
				38,
			],
			[
				'Template type K is declared as covariant, but occurs in contravariant position in return type of method MethodSignatureVarianceCovariant\A::b().',
				41,
			],
			[
				'Template type K is declared as covariant, but occurs in contravariant position in return type of method MethodSignatureVarianceCovariant\A::d().',
				47,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVarianceCovariant\A::e().',
				50,
			],
			[
				'Template type K is declared as covariant, but occurs in contravariant position in return type of method MethodSignatureVarianceCovariant\A::h().',
				59,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVarianceCovariant\A::i().',
				62,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVarianceCovariant\A::j().',
				65,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVarianceCovariant\A::k().',
				68,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVarianceCovariant\A::l().',
				71,
			],
			[
				'Template type K is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVarianceCovariant\A::m().',
				74,
			],
		]);
	}

}
