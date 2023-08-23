<?php declare(strict_types = 1);

namespace PHPStan\Rules\Constants;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;
use function define;

/**
 * @extends RuleTestCase<ConstantRule>
 */
class ConstantRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ConstantRule([]);
	}

	public function testConstants(): void
	{
		define('FOO_CONSTANT', 'foo');
		define('Constants\\BAR_CONSTANT', 'bar');
		define('OtherConstants\\BAZ_CONSTANT', 'baz');
		$this->analyse([__DIR__ . '/data/constants.php'], [
			[
				'Constant NONEXISTENT_CONSTANT not found.',
				10,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
			[
				'Constant DEFINED_CONSTANT_IF not found.',
				21,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

	public function testCompilerHaltOffsetConstantFalseDetection(): void
	{
		$this->analyse([__DIR__ . '/data/compiler-halt-offset-const-defined.php'], []);
	}

	public function testCompilerHaltOffsetConstantIsUndefinedDetection(): void
	{
		$this->analyse([__DIR__ . '/data/compiler-halt-offset-const-not-defined.php'], [
			[
				'Constant __COMPILER_HALT_OFFSET__ not found.',
				3,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',

			],
		]);
	}

	public function testConstEquals(): void
	{
		$this->analyse([__DIR__ . '/data/const-equals.php'], []);
	}

	public function testConstEqualsNoNamespace(): void
	{
		$this->analyse([__DIR__ . '/data/const-equals-no-namespace.php'], []);
	}

	public function testDefinedScopeMerge(): void
	{
		$this->analyse([__DIR__ . '/data/defined-scope-merge.php'], [
			[
				'Constant TEST not found.',
				8,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],

			[
				'Constant TEST not found.',
				11,
				'Learn more at https://phpstan.org/user-guide/discovering-symbols',
			],
		]);
	}

}
