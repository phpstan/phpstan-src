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
				'Cannot call random_int() with $min parameter (1) greater than $max parameter (0).',
				8,
			],
			[
				'Cannot call random_int() with $min parameter (0) greater than $max parameter (-1).',
				9,
			],
			[
				'Cannot call random_int() with $max parameter (int<-10, -1>) less than $min parameter (0).',
				11,
			],
			[
				'Cannot call random_int() when $max parameter (int<-10, 10>) can be less than $min parameter (0).',
				12,
			],
			[
				'Cannot call random_int() with $min parameter (int<1, 10>) greater than $max parameter (0).',
				15,
			],
			[
				'Cannot call random_int() when $min parameter (int<-10, 10>) can be greater than $max parameter (0).',
				16,
			],
			[
				'Cannot call random_int() with intersecting $min (int<-5, 1>) and $max (int<0, 5>) parameters.',
				19,
			],
			[
				'Cannot call random_int() with intersecting $min (int<-5, 0>) and $max (int<-1, 5>) parameters.',
				20,
			],
		]);
	}

}
