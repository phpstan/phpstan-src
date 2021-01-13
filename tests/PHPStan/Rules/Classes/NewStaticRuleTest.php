<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;

/**
 * @extends \PHPStan\Testing\RuleTestCase<NewStaticRule>
 */
class NewStaticRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): Rule
	{
		return new NewStaticRule();
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

}
