<?php declare(strict_types = 1);

namespace PHPStan\Rules\Playground;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<MethodNeverRule>
 */
class MethodNeverRulePhp80Test extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new MethodNeverRule(new NeverRuleHelper());
	}

	public function testMagicMethods(): void
	{
		// "never" is not accepted as a magic method return type before PHP 8.1,
		// so the return type PHP mandates still wins.
		$this->analyse([__DIR__ . '/data/method-never-php80.php'], [
			[
				'Method MethodNeverPhp80\MagicMethods::__clone() always throws an exception, it should have return type "never".',
				8,
			],
			[
				'Method MethodNeverPhp80\MagicMethods::__toString() always throws an exception, it should have return type "never".',
				13,
			],
		]);
	}

	public static function getAdditionalConfigFiles(): array
	{
		return [
			__DIR__ . '/data/method-never-php-8.0.neon',
		];
	}

}
