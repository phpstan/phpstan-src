<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<NewStaticRule>
 */
class NewStaticRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NewStaticRule(
			new PhpVersion(PHP_VERSION_ID),
		);
	}

	public function testRule(): void
	{
		$error = 'Unsafe usage of new static().';
		$tipText = 'See: https://phpstan.org/blog/solving-phpstan-error-unsafe-usage-of-new-static';
		$this->analyse([__DIR__ . '/data/new-static.php'], [
			[
				$error,
				10,
				$tipText,
			],
			[
				$error,
				25,
				$tipText,
			],
		]);
	}

	public function testRuleWithConsistentConstructor(): void
	{
		$this->analyse([__DIR__ . '/data/new-static-consistent-constructor.php'], []);
	}

	public function testBug9654(): void
	{
		$errors = [];
		if (PHP_VERSION_ID < 80000) {
			$errors[] = [
				'Unsafe usage of new static()',
				11,
				'See: https://phpstan.org/blog/solving-phpstan-error-unsafe-usage-of-new-static',
			];
			$errors[] = [
				'Unsafe usage of new static()',
				11,
				'See: https://phpstan.org/blog/solving-phpstan-error-unsafe-usage-of-new-static',
			];
		}

		$this->analyse([__DIR__ . '/data/bug-9654.php'], $errors);
	}

}
