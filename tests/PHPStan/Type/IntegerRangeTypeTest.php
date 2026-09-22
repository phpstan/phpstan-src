<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Testing\PHPStanTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

class IntegerRangeTypeTest extends PHPStanTestCase
{

	public static function dataCreateFromFloat(): iterable
	{
		// 2**63 is PHP_INT_MAX + 1, the smallest float greater than every int
		yield '2**63' => [
			9.2233720368547758E18,
			'int',
			'int',
			'*NEVER*',
			'*NEVER*',
		];

		// the largest float smaller than 2**63
		yield '2**63 - 1024' => [
			9.2233720368547748E18,
			'int<min, 9223372036854774783>',
			'int<min, 9223372036854774784>',
			'int<9223372036854774785, max>',
			'int<9223372036854774784, max>',
		];

		// -2**63 is exactly PHP_INT_MIN
		yield '-2**63' => [
			-9.2233720368547758E18,
			'*NEVER*',
			'-9223372036854775808',
			'int<-9223372036854775807, max>',
			'int',
		];

		// the largest float smaller than -2**63
		yield '-2**63 - 2048' => [
			-9.2233720368547779E18,
			'*NEVER*',
			'*NEVER*',
			'int',
			'int',
		];
	}

	#[DataProvider('dataCreateFromFloat')]
	public function testCreateFromFloat(
		float $value,
		string $expectedSmallerThan,
		string $expectedSmallerThanOrEqualTo,
		string $expectedGreaterThan,
		string $expectedGreaterThanOrEqualTo,
	): void
	{
		$this->assertSame([
			'createAllSmallerThan' => $expectedSmallerThan,
			'createAllSmallerThanOrEqualTo' => $expectedSmallerThanOrEqualTo,
			'createAllGreaterThan' => $expectedGreaterThan,
			'createAllGreaterThanOrEqualTo' => $expectedGreaterThanOrEqualTo,
		], [
			'createAllSmallerThan' => IntegerRangeType::createAllSmallerThan($value)->describe(VerbosityLevel::precise()),
			'createAllSmallerThanOrEqualTo' => IntegerRangeType::createAllSmallerThanOrEqualTo($value)->describe(VerbosityLevel::precise()),
			'createAllGreaterThan' => IntegerRangeType::createAllGreaterThan($value)->describe(VerbosityLevel::precise()),
			'createAllGreaterThanOrEqualTo' => IntegerRangeType::createAllGreaterThanOrEqualTo($value)->describe(VerbosityLevel::precise()),
		]);
	}

}
