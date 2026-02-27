<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Php\PhpVersion;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use const PHP_VERSION_ID;

/**
 * @extends RuleTestCase<InheritedMethodCompatibilityRule>
 */
class InheritedMethodCompatibilityRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$phpVersion = new PhpVersion(PHP_VERSION_ID);

		return new InheritedMethodCompatibilityRule(
			$phpVersion,
			new MethodParameterComparisonHelper($phpVersion),
		);
	}

	public function testBug7388(): void
	{
		$this->analyse([__DIR__ . '/data/bug-7388.php'], [
			[
				'Method Bug7388\ParentFoo::bar() overrides method Bug7388\FooInterface::bar() but misses parameter #1 $i.',
				17,
			],
			[
				'Method Bug7388\ParentBaz::baz() overrides method Bug7388\BazInterface::baz() but misses parameter #2 $i.',
				35,
			],
		]);
	}

}
