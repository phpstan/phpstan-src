<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<CallMethodsRule>
 */
class CallMethodsRuleClosureThisSubclassTest extends RuleTestCase
{

	public static function getAdditionalConfigFiles(): array
	{
		return [__DIR__ . '/closure-this-subclass.neon'];
	}

	protected function getRule(): Rule
	{
		return self::getContainer()->getByType(CallMethodsRule::class);
	}

	public function testClosureThisSubclassScope(): void
	{
		$this->analyse([__DIR__ . '/data/closure-this-subclass.php'], [
			[
				'Call to private method privateHelper() of class ClosureThisSubclass\\BaseTestCase.',
				31,
			],
			[
				'Call to private method privateHelper() of class ClosureThisSubclass\\BaseTestCase.',
				37,
			],
			[
				'Call to protected method protectedHelper() of class ClosureThisSubclass\\BaseTestCase.',
				49,
			],
			[
				'Call to private method privateHelper() of class ClosureThisSubclass\\BaseTestCase.',
				50,
			],
		]);
	}

}
