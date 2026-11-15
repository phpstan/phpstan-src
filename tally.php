<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use PHPStan\Process\Restart\OpcacheSettings;
use Tally\Configuration;

return Configuration::create()
	->withPaths([__DIR__ . '/tests/bench'])
	// bin/phpstan restarts PHP with these OPcache settings, the benchmarks run without the restart.
	// bin/phpstan also calls gc_disable(): with the cycle collector on, what the setup allocated
	// decides whether a collection falls into the counted call, and it never runs in an analysis.
	->withIniEntries([...OpcacheSettings::getIniEntries(), 'memory_limit=-1', 'zend.enable_gc=0'])
	// The slowest benchmark takes a few seconds natively and about 13 minutes under the simulation.
	// One stuck in a loop fails in the native run, one that regresses far beyond that fails as not
	// measured instead of holding up its shard.
	->withTimeLimits(30, 25 * 60)
	// tally accept writes #[ExpectedFor(dataSet: '…')] like the benchmarks do
	->withNamedArguments(true);
