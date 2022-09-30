<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<FunctionAssertRule>
 */
class FunctionAssertRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$initializerExprTypeResolver = self::getContainer()->getByType(InitializerExprTypeResolver::class);
		return new FunctionAssertRule(new AssertRuleHelper($initializerExprTypeResolver));
	}

	public function testRule(): void
	{
		require_once __DIR__ . '/data/function-assert.php';
		$this->analyse([__DIR__ . '/data/function-assert.php'], [
			[
				'Asserted type int for $i with type int does not narrow down the type.',
				8,
			],
			[
				'Asserted type int for $i with type int does not narrow down the type.',
				17,
			],
			[
				'Asserted type int for $i with type int does not narrow down the type.',
				26,
			],
			[
				'Asserted type int|string for $i with type int does not narrow down the type.',
				42,
			],
			[
				'Asserted type string for $i with type int can never happen.',
				49,
			],
			[
				'Assert references unknown parameter $j.',
				56,
			],
			[
				'Asserted negated type int for $i with type int can never happen.',
				63,
			],
			[
				'Asserted negated type string for $i with type int does not narrow down the type.',
				70,
			],
		]);
	}

}
