<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InvalidPromotedPropertiesRule>
 */
class InvalidPromotedPropertiesRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InvalidPromotedPropertiesRule();
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$errors = [
				[
					'Promoted properties are supported only on PHP 8.0 and later.',
					8,
				],
				[
					'Promoted properties are supported only on PHP 8.0 and later.',
					10,
				],
				[
					'Promoted properties are supported only on PHP 8.0 and later.',
					17,
				],
				[
					'Promoted properties are supported only on PHP 8.0 and later.',
					21,
				],
				[
					'Promoted properties are supported only on PHP 8.0 and later.',
					23,
				],
				[
					'Promoted properties are supported only on PHP 8.0 and later.',
					31,
				],
				[
					'Promoted properties are supported only on PHP 8.0 and later.',
					38,
				],
				[
					'Promoted properties are supported only on PHP 8.0 and later.',
					45,
				],
			];
		} else {
			$errors = [
				[
					'Promoted properties can be in constructor only.',
					10,
				],
				[
					'Promoted properties can be in constructor only.',
					17,
				],
				[
					'Promoted properties can be in constructor only.',
					21,
				],
				[
					'Promoted properties can be in constructor only.',
					23,
				],
				[
					'Promoted properties are not allowed in abstract constructors.',
					31,
				],
				[
					'Promoted properties are not allowed in abstract constructors.',
					38,
				],
				[
					'Promoted property parameter $i can not be variadic.',
					45,
				],
			];
		}

		$this->analyse([__DIR__ . '/data/invalid-promoted-properties.php'], $errors);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testBug9577(): void
	{
		$this->analyse([__DIR__ . '/data/bug-9577.php'], []);
	}

	#[RequiresPhp('>= 8.1.0')]
	public function testHooks(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-hooked-properties.php'], [
			[
				'Promoted properties can be in constructor only.',
				9,
			],
		]);
	}

	#[RequiresPhp('>= 8.0.0')]
	public function testFinalProperty(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80500) {
			$errors = [
				[
					'Final promoted properties are supported only on PHP 8.5 and later.',
					8,
				],
			];
		}
		$this->analyse([__DIR__ . '/data/final-promoted-property.php'], $errors);
	}

	public function testConditionallyDeclaredClass(): void
	{
		$errors = [
			[
				'Promoted properties are supported only on PHP 8.0 and later.',
				20,
			],
		];
		if (PHP_VERSION_ID < 80000) {
			$errors[] = [
				'Promoted properties are supported only on PHP 8.0 and later.',
				30,
			];
		}

		$this->analyse([__DIR__ . '/data/promoted-properties-php-versions.php'], $errors);
	}

}
