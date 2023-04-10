<?php declare(strict_types = 1);

namespace PHPStan\Rules\Generics;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<PropertyVarianceRule>
 */
class PropertyVarianceRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new PropertyVarianceRule(
			self::getContainer()->getByType(VarianceCheck::class),
			true,
		);
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/property-variance.php'], [
			[
				'Template type X is declared as covariant, but occurs in invariant position in property PropertyVariance\B::$a.',
				51,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in property PropertyVariance\B::$b.',
				54,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in property PropertyVariance\B::$c.',
				57,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in property PropertyVariance\B::$d.',
				60,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in property PropertyVariance\C::$a.',
				80,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in property PropertyVariance\C::$b.',
				83,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in property PropertyVariance\C::$c.',
				86,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in property PropertyVariance\C::$d.',
				89,
			],
		]);
	}

	public function testPromoted(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

		$this->analyse([__DIR__ . '/data/property-variance-promoted.php'], [
			[
				'Template type X is declared as covariant, but occurs in invariant position in property PropertyVariance\Promoted\B::$a.',
				58,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in property PropertyVariance\Promoted\B::$b.',
				59,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in property PropertyVariance\Promoted\B::$c.',
				60,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in property PropertyVariance\Promoted\B::$d.',
				61,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in property PropertyVariance\Promoted\C::$a.',
				84,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in property PropertyVariance\Promoted\C::$b.',
				85,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in property PropertyVariance\Promoted\C::$c.',
				86,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in property PropertyVariance\Promoted\C::$d.',
				87,
			],
		]);
	}

	public function testReadOnly(): void
	{
		if (PHP_VERSION_ID < 80100) {
			$this->markTestSkipped('Test requires PHP 8.1.');
		}

		$this->analyse([__DIR__ . '/data/property-variance-readonly.php'], [
			[
				'Template type X is declared as covariant, but occurs in contravariant position in property PropertyVariance\ReadOnly\B::$b.',
				45,
			],
			[
				'Template type X is declared as covariant, but occurs in invariant position in property PropertyVariance\ReadOnly\B::$d.',
				51,
			],
			[
				'Template type X is declared as contravariant, but occurs in covariant position in property PropertyVariance\ReadOnly\C::$a.',
				62,
			],
			[
				'Template type X is declared as contravariant, but occurs in covariant position in property PropertyVariance\ReadOnly\C::$c.',
				68,
			],
			[
				'Template type X is declared as contravariant, but occurs in invariant position in property PropertyVariance\ReadOnly\C::$d.',
				71,
			],
			[
				'Template type X is declared as contravariant, but occurs in covariant position in property PropertyVariance\ReadOnly\D::$a.',
				86,
			],
		]);
	}

	public function testBug9153(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9153.php'], []);
	}

}
