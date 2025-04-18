<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InstanceMethodsParameterRule>
 */
class InstanceMethodsParameterScopeTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new InstanceMethodsParameterRule();
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
				'Name DateTime found in method null',
				12,
			],
			[
				'Name Baz\Waldo found in method null',
				16,
			],
		]);
	}

}
