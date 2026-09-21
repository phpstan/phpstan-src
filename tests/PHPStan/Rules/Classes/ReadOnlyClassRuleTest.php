<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule as TRule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<ReadOnlyClassRule>
 */
class ReadOnlyClassRuleTest extends RuleTestCase
{

	protected function getRule(): TRule
	{
		return new ReadOnlyClassRule();
	}

	public function testRule(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80200) {
			$errors[] = [
				'Readonly classes are supported only on PHP 8.2 and later.',
				5,
			];
		}
		if (PHP_VERSION_ID < 80300) {
			$errors[] = [
				'Anonymous readonly classes are supported only on PHP 8.3 and later.',
				15,
			];
		}
		$this->analyse([__DIR__ . '/data/readonly-class.php'], $errors);
	}

	public function testConditionallyDeclaredClass(): void
	{
		$errors = [
			[
				'Readonly classes are supported only on PHP 8.2 and later.',
				12,
			],
		];
		if (PHP_VERSION_ID < 80200) {
			$errors[] = [
				'Readonly classes are supported only on PHP 8.2 and later.',
				17,
			];
		}
		$errors[] = [
			'Anonymous readonly classes are supported only on PHP 8.3 and later.',
			29,
		];
		if (PHP_VERSION_ID < 80300) {
			$errors[] = [
				'Anonymous readonly classes are supported only on PHP 8.3 and later.',
				33,
			];
		}

		$this->analyse([__DIR__ . '/data/readonly-class-php-versions.php'], $errors);
	}

}
