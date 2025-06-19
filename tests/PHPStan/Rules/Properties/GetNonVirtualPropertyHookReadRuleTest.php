<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<GetNonVirtualPropertyHookReadRule>
 */
class GetNonVirtualPropertyHookReadRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new GetNonVirtualPropertyHookReadRule();
	}

	#[RequiresPhp('>= 8.4')]
	public function testRule(): void
	{
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

	#[RequiresPhp('>= 8.4')]
	public function testAbstractProperty(): void
	{
		$this->analyse([__DIR__ . '/data/get-abstract-property-hook-read.php'], []);
	}

}
