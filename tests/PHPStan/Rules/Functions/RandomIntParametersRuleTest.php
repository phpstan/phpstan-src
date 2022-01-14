<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<RandomIntParametersRule>
 */
class RandomIntParametersRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		return new RandomIntParametersRule($this->createReflectionProvider(), true);
	}

	public function testFile(): void
	{
		$this->analyse([__DIR__ . '/data/random-int.php'], [
			[
				'Parameter #1 $min (1) of function random_int expects lower number than parameter #2 $max (0).',
				8,
			],
			[
				'Parameter #1 $min (0) of function random_int expects lower number than parameter #2 $max (-1).',
				9,
			],
			[
				'Parameter #1 $min (0) of function random_int expects lower number than parameter #2 $max (int<-10, -1>).',
				11,
			],
			[
				'Parameter #1 $min (0) of function random_int expects lower number than parameter #2 $max (int<-10, 10>).',
				12,
			],
			[
				'Parameter #1 $min (int<1, 10>) of function random_int expects lower number than parameter #2 $max (0).',
				15,
			],
			[
				'Parameter #1 $min (int<-10, 10>) of function random_int expects lower number than parameter #2 $max (0).',
				16,
			],
			[
				'Parameter #1 $min (int<-5, 1>) of function random_int expects lower number than parameter #2 $max (int<0, 5>).',
				19,
			],
			[
				'Parameter #1 $min (int<-5, 0>) of function random_int expects lower number than parameter #2 $max (int<-1, 5>).',
				20,
			],
			[
				'Parameter #1 $min (int<0, 10>) of function random_int expects lower number than parameter #2 $max (int<0, 10>).',
				31,
			],
		]);
	}

	public function testBug6361(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6361.php'], []);
	}

}
