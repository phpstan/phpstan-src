<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use Generator;
use PHPUnit\Framework\TestCase;

class TimeHelperTest extends TestCase
{

	/**
	 * @dataProvider dataHumaniseFractionalSeconds
	 */
	public function testHumaniseFractionalSeconds(float $fractionalSeconds, string $humanisedOutputExpected): void
	{
		$this->assertEquals($humanisedOutputExpected, TimeHelper::humaniseFractionalSeconds($fractionalSeconds));
	}

	public function dataHumaniseFractionalSeconds(): Generator
	{
		yield 'fractional seconds below one second' => [
			'fractionalSeconds' => 0.12345,
			'humanisedOutputExpected' => '0.123s',
		];

		yield 'fractional seconds below threshold' => [
			'fractionalSeconds' => 1.2345,
			'humanisedOutputExpected' => '1.235s',
		];

		yield 'seconds' => [
			'fractionalSeconds' => 12.345,
			'humanisedOutputExpected' => '12s',
		];

		yield 'full minute' => [
			'fractionalSeconds' => 60,
			'humanisedOutputExpected' => '1m',
		];

		yield 'minute with seconds' => [
			'fractionalSeconds' => 70,
			'humanisedOutputExpected' => '1m10s',
		];

		yield 'full hour' => [
			'fractionalSeconds' => 3600,
			'humanisedOutputExpected' => '1h',
		];

		yield 'hour full minute' => [
			'fractionalSeconds' => 3660,
			'humanisedOutputExpected' => '1h1m',
		];

		yield 'hour minute seconds' => [
			'fractionalSeconds' => 3665,
			'humanisedOutputExpected' => '1h1m5s',
		];
	}

}
