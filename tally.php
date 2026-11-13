<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\Process\Restart\OpcacheSettings;
use Tally\Configuration;

return Configuration::create()
	->withPaths([__DIR__ . '/tests/bench/AnalysisBench.php'])
	// bin/phpstan restarts PHP with these OPcache settings, the benchmarks run without the restart
	->withIniEntries([...OpcacheSettings::getIniEntries(), 'memory_limit=-1'])
	// The slowest benchmark takes a few seconds natively and about 13 minutes under the simulation.
	// One stuck in a loop fails in the native run, one that regresses far beyond that fails as not
	// measured instead of holding up its shard.
	->withTimeLimits(30, 25 * 60);
