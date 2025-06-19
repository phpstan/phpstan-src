<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\FunctionReturnTypeCheck;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<ArrowFunctionReturnTypeRule>
 */
class ArrowFunctionReturnTypeRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new ArrowFunctionReturnTypeRule(new FunctionReturnTypeCheck(new RuleLevelHelper(
			self::createReflectionProvider(),
			true,
			false,
			true,
			false,
			false,
			false,
			true,
		)));
	}

	public function testRule(): void
	{
		$this->analyse([__DIR__ . '/data/arrow-functions-return-type.php'], [
			[
				'Anonymous function should return string but returns int.',
				12,
			],
			[
				'Anonymous function should return int but returns string.',
				14,
			],

		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testRuleNever(): void
	{
		$this->analyse([__DIR__ . '/data/arrow-function-never-return.php'], [
			[
				'Anonymous function should never return but return statement found.',
				12,
			],
		]);
	}

	public function testBug3261(): void
	{
		$this->analyse([__DIR__ . '/data/bug-3261.php'], []);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug8179(): void
	{
		$this->analyse([__DIR__ . '/data/bug-8179.php'], []);
	}

	public function testBugSpaceship(): void
	{
		$this->analyse([__DIR__ . '/data/bug-spaceship.php'], []);
	}

	public function testBugFunctionMethodConstants(): void
	{
		$this->analyse([__DIR__ . '/data/bug-anonymous-function-method-constant.php'], []);
	}

}
