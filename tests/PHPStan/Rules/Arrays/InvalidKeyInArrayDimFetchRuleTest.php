<?php declare(strict_types = 1);

namespace PHPStan\Rules\Arrays;

use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleLevelHelper;
use PHPStan\Testing\RuleTestCase;
use PHPUnit\Framework\Attributes\RequiresPhp;

/**
 * @extends RuleTestCase<InvalidKeyInArrayDimFetchRule>
 */
class InvalidKeyInArrayDimFetchRuleTest extends RuleTestCase
{

	protected function getRule(): Rule
	{
		$ruleLevelHelper = new RuleLevelHelper(self::createReflectionProvider(), true, false, true, true, true, false, true);
		return new InvalidKeyInArrayDimFetchRule($ruleLevelHelper, true);
	}

	public function testInvalidKey(): void
	{
		$this->analyse([__DIR__ . '/data/invalid-key-array-dim-fetch.php'], [
			[
				'Invalid array key type DateTimeImmutable.',
				7,
			],
			[
				'Invalid array key type array.',
				8,
			],
			[
				'Possibly invalid array key type stdClass|string.',
				24,
			],
			[
				'Invalid array key type DateTimeImmutable.',
				31,
			],
			[
				'Possibly invalid array key type mixed.',
				41,
			],
			[
				'Invalid array key type DateTimeImmutable.',
				45,
			],
			[
				'Invalid array key type DateTimeImmutable.',
				46,
			],
			[
				'Invalid array key type DateTimeImmutable.',
				47,
			],
			[
				'Invalid array key type stdClass.',
				47,
			],
			[
				'Invalid array key type DateTimeImmutable.',
				48,
			],
		]);
	}

	#[RequiresPhp('>= 8.1')]
	public function testBug6315(): void
	{
		$this->analyse([__DIR__ . '/data/bug-6315.php'], [
			[
				'Invalid array key type Bug6315\FooEnum::A.',
				18,
			],
			[
				'Invalid array key type Bug6315\FooEnum::A.',
				19,
			],
			[
				'Invalid array key type Bug6315\FooEnum::A.',
				20,
			],
			[
				'Invalid array key type Bug6315\FooEnum::B.',
				21,
			],
			[
				'Invalid array key type Bug6315\FooEnum::A.',
				21,
			],
			[
				'Invalid array key type Bug6315\FooEnum::A.',
				22,
			],
		]);
	}

	public function testBug13135(): void
	{
		$this->analyse([__DIR__ . '/data/bug-13135.php'], [
			[
				'Possibly invalid array key type Tk of mixed.',
				15,
			],
		]);
	}

	public function testBug12273(): void
	{
		$this->analyse([__DIR__ . '/data/bug-12273.php'], [
			[
				'Possibly invalid array key type mixed.',
				16,
			],
		]);
	}

}
