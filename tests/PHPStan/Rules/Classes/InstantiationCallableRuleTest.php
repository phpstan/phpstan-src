<?php declare(strict_types = 1);

namespace PHPStan\Rules\Classes;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<InstantiationCallableRule>
 */
class InstantiationCallableRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InstantiationCallableRule();
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/instantiation-callable.php'], [
			[
				'Cannot create callable from the new operator.',
				11,
			],
		]);
	}

	#[RequiresPhp('>= 8.6.0-dev')]
	public function testPartialFunctionApplication(): void
	{
		$this->analyse([__DIR__ . '/data/instantiation-partial-function-application.php'], [
			[
				'Cannot create callable from the new operator.',
				14,
			],
			[
				'Cannot create callable from the new operator.',
				15,
			],
		]);
	}

}
