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
				'Variance annotation is only allowed for type parameters of classes and interfaces, but occurs in template type U in method MethodSignatureVariance\C::b().',
				16,
			],
			[
				'Variance annotation is only allowed for type parameters of classes and interfaces, but occurs in template type U in method MethodSignatureVariance\C::c().',
				22,
			],
		]);

		$this->analyse([__DIR__ . '/data/method-signature-variance-invariant.php'], []);

		$this->analyse([__DIR__ . '/data/method-signature-variance-covariant.php'], [
			[
				'Template type X is declared as covariant, but occurs in contravariant position in parameter a of method MethodSignatureVariance\Covariant\C::a().',
				35,
			],
			[
				'Template type X is declared as covariant, but occurs in contravariant position in parameter c of method MethodSignatureVariance\Covariant\C::a().',
				35,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in parameter e of method MethodSignatureVariance\Covariant\C::a().',
				35,
			],
			[
				'Template type X is declared as covariant, but occurs in contravariant position in parameter f of method MethodSignatureVariance\Covariant\C::a().',
				35,
			],
			[
				'Template type X is declared as covariant, but occurs in contravariant position in parameter h of method MethodSignatureVariance\Covariant\C::a().',
				35,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in parameter i of method MethodSignatureVariance\Covariant\C::a().',
				35,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in parameter j of method MethodSignatureVariance\Covariant\C::a().',
				35,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in parameter k of method MethodSignatureVariance\Covariant\C::a().',
				35,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in parameter l of method MethodSignatureVariance\Covariant\C::a().',
				35,
			],
			[
				'Template type X is declared as covariant, but occurs in contravariant position in return type of method MethodSignatureVariance\Covariant\C::c().',
				41,
			],
			[
				'Template type X is declared as covariant, but occurs in contravariant position in return type of method MethodSignatureVariance\Covariant\C::e().',
				47,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVariance\Covariant\C::f().',
				50,
			],
			[
				'Template type X is declared as covariant, but occurs in contravariant position in return type of method MethodSignatureVariance\Covariant\C::h().',
				56,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVariance\Covariant\C::j().',
				62,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVariance\Covariant\C::k().',
				65,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVariance\Covariant\C::l().',
				68,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in return type of method MethodSignatureVariance\Covariant\C::m().',
				71,
			],
		]);

		$this->analyse([__DIR__ . '/data/method-signature-variance-contravariant.php'], [
			[
				'Template type X is declared as contravariant, but occurs in covariant position in parameter b of method MethodSignatureVariance\Contravariant\C::a().',
				35,
			],
			[
				'Template type X is declared as contravariant, but occurs in covariant position in parameter d of method MethodSignatureVariance\Contravariant\C::a().',
				35,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in parameter e of method MethodSignatureVariance\Contravariant\C::a().',
				35,
			],
			[
				'Template type X is declared as contravariant, but occurs in covariant position in parameter g of method MethodSignatureVariance\Contravariant\C::a().',
				35,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in parameter i of method MethodSignatureVariance\Contravariant\C::a().',
				35,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in parameter j of method MethodSignatureVariance\Contravariant\C::a().',
				35,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in parameter k of method MethodSignatureVariance\Contravariant\C::a().',
				35,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in parameter l of method MethodSignatureVariance\Contravariant\C::a().',
				35,
			],
			[
				'Template type X is declared as contravariant, but occurs in covariant position in return type of method MethodSignatureVariance\Contravariant\C::b().',
				38,
			],
			[
				'Template type X is declared as contravariant, but occurs in covariant position in return type of method MethodSignatureVariance\Contravariant\C::d().',
				44,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVariance\Contravariant\C::f().',
				50,
			],
			[
				'Template type X is declared as contravariant, but occurs in covariant position in return type of method MethodSignatureVariance\Contravariant\C::g().',
				53,
			],
			[
				'Template type X is declared as contravariant, but occurs in covariant position in return type of method MethodSignatureVariance\Contravariant\C::i().',
				59,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVariance\Contravariant\C::j().',
				62,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVariance\Contravariant\C::k().',
				65,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVariance\Contravariant\C::l().',
				68,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in return type of method MethodSignatureVariance\Contravariant\C::m().',
				71,
			],
		]);

		$this->analyse([__DIR__ . '/data/method-signature-variance-static.php'], [
			[
				'Class template type X cannot be referenced in a static member in return type of method MethodSignatureVariance\Static\C::b().',
				18,
			],
			[
				'Class template type X cannot be referenced in a static member in return type of method MethodSignatureVariance\Static\C::c().',
				23,
			],
		]);

		$this->analyse([__DIR__ . '/data/method-signature-variance-constructor.php'], [
			[
				'Constructor is not allowed to define type parameters, but template type Y is defined in method MethodSignatureVariance\Constructor\D::__construct().',
				79,
			],
		]);
	}

}
