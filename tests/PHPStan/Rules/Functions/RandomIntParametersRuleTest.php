<?php declare(strict_types = 1);

namespace PHPStan\Rules\Functions;

/**
 * @extends \PHPStan\Testing\RuleTestCase<RandomIntParametersRule>
 */
class RandomIntParametersRuleTest extends \PHPStan\Testing\RuleTestCase
{

	protected function getRule(): \PHPStan\Rules\Rule
	{
		return new RandomIntParametersRule($this->createReflectionProvider());
	}

	public function testFile(): void
	{
		$this->analyse([__DIR__ . '/data/random-int.php'], [
			[
				'Cannot call random_int() when $min parameter (1) is greater than $max parameter (0).',
				8,
			],
			[
				'Cannot call random_int() when $min parameter (0) is greater than $max parameter (-1).',
				9,
			],
			[
				'Cannot call random_int() when $min parameter (0) is greater than $max parameter (int<-10, -1>).',
				11,
			],
			[
				'Cannot call random_int() when $min parameter (0) can be greater than $max parameter (int<-10, 10>).',
				12,
			],
			[
				'Cannot call random_int() when $min parameter (int<1, 10>) is greater than $max parameter (0).',
				15,
			],
			[
				'Cannot call random_int() when $min parameter (int<-10, 10>) can be greater than $max parameter (0).',
				16,
			],
			[
				'Cannot call random_int() when $min parameter (int<-5, 1>) can be greater than $max parameter (int<0, 5>).',
				19,
			],
			[
				'Cannot call random_int() when $min parameter (int<-5, 0>) can be greater than $max parameter (int<-1, 5>).',
				20,
			],
			[
				'Cannot call random_int() when $min parameter (int<0, 10>) can be greater than $max parameter (int<0, 10>).',
				31,
			],
		]);
	}

}
