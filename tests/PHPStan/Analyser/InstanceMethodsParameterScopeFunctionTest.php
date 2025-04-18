<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

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

	public function testRule(): void
	{
		if (PHP_VERSION_ID < 80000) {
			$this->markTestSkipped('Test requires PHP 8.0.');
		}

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
