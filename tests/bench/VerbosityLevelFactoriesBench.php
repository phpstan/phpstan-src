<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\Type\VerbosityLevel;
use Tally\Attribute\Before;
use Tally\Attribute\Expected;

final class VerbosityLevelFactoriesBench
{

	private const CALLS = 1000;

	#[Before]
	public function boot(): void
	{
		VerbosityLevel::typeOnly();
		VerbosityLevel::value();
		VerbosityLevel::precise();
		VerbosityLevel::cache();
	}

	/**
	 * Every describe() call gets its level from one of these.
	 */
	#[Expected(instructions: '1.101M', cycles: '1.115M', memory: '0B')]
	public function benchFactories(): VerbosityLevel
	{
		$level = VerbosityLevel::typeOnly();
		for ($i = 0; $i < self::CALLS; $i++) {
			VerbosityLevel::typeOnly();
			VerbosityLevel::value();
			VerbosityLevel::precise();
			$level = VerbosityLevel::cache();
		}

		return $level;
	}

}
