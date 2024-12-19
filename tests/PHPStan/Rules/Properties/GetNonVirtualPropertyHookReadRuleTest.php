<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<GetNonVirtualPropertyHookReadRule>
 */
class GetNonVirtualPropertyHookReadRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new GetNonVirtualPropertyHookReadRule();
	}

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80400) {
			$this->markTestSkipped('Test requires PHP 8.4.');
		}

		$this->analyse([__DIR__ . '/data/get-non-virtual-property-hook-read.php'], [
			[
				'Get hook for non-virtual property GetNonVirtualPropertyHookRead\Foo::$k does not read its value.',
				24,
			],
			[
				'Get hook for non-virtual property GetNonVirtualPropertyHookRead\Foo::$l does not read its value.',
				30,
			],
		]);
	}

}
