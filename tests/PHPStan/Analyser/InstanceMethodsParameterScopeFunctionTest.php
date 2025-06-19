<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<InstanceMethodsParameterScopeFunctionRule>
 */
class InstanceMethodsParameterScopeFunctionTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InstanceMethodsParameterScopeFunctionRule();
	}

	protected function shouldNarrowMethodScopeFromConstructor(): bool
	{
		return true;
	}

	#[RequiresPhp('>= 8.0')]
	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/instance-methods-parameter-scope.php'], [
			[
				'Name DateTime found in function scope null',
				12,
			],
			[
				'Name Baz\Waldo found in function scope null',
				16,
			],
		]);
	}

}
