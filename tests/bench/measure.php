<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

/**
 * Analyses one benchmark from tests/bench/data in this process and tells CodSpeed to count
 * only that analysis: PHPStan boots, analyses Warmup.php so its code is loaded and compiled,
 * and only then starts the measurement. run.php runs this under CodSpeed's CPU simulation.
 *
 * Usage: php -d ffi.enable=1 tests/bench/measure.php <configuration file> <benchmark file> <peak memory output file>
 *
 * The INSTRUMENT_HOOKS_LIBRARY environment variable points to CodSpeed's instrument-hooks
 * library (https://github.com/CodSpeedHQ/instrument-hooks). Without it, the analysis runs
 * unmeasured, which run.php uses to fill the caches in tmpDir before the measured run.
 *
 * The peak memory is the highest memory usage during the benchmark's analysis, including what
 * the booted container and the warm-up keep allocated.
 */

use FFI;
use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\AnalyserResultFinalizer;
use PHPStan\Command\CommandHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use function basename;
use function count;
use function dirname;
use function error_reporting;
use function file_put_contents;
use function fwrite;
use function gc_disable;
use function getenv;
use function getmypid;
use function memory_get_peak_usage;
use function memory_reset_peak_usage;
use function realpath;
use function sprintf;
use const E_ALL;
use const E_DEPRECATED;
use const STDERR;

// Like bin/phpstan
error_reporting(E_ALL & ~E_DEPRECATED);
gc_disable();

$rootDir = dirname(__DIR__, 2);
require_once $rootDir . '/vendor/autoload.php';

if (!isset($argv) || count($argv) !== 4) {
	fwrite(STDERR, "Usage: php -d ffi.enable=1 tests/bench/measure.php <configuration file> <benchmark file> <peak memory output file>\n");
	exit(2);
}

[, $configFile, $benchmarkFile, $peakMemoryFile] = $argv;
$benchmarkFile = realpath($benchmarkFile);
$warmupFile = __DIR__ . '/Warmup.php';
if ($benchmarkFile === false) {
	fwrite(STDERR, "The benchmark file does not exist.\n");
	exit(2);
}

$hooks = null;
$hooksLibrary = getenv('INSTRUMENT_HOOKS_LIBRARY');
if ($hooksLibrary !== false && $hooksLibrary !== '') {
	$hooks = FFI::cdef('
		typedef struct InstrumentHooks InstrumentHooks;
		InstrumentHooks *instrument_hooks_init(void);
		bool instrument_hooks_is_instrumented(InstrumentHooks *);
		uint8_t instrument_hooks_start_benchmark(InstrumentHooks *);
		uint8_t instrument_hooks_stop_benchmark(InstrumentHooks *);
		uint8_t instrument_hooks_set_executed_benchmark(InstrumentHooks *, int32_t pid, const char *uri);
		uint8_t instrument_hooks_set_integration(InstrumentHooks *, const char *name, const char *version);
	', $hooksLibrary);
}

$output = new StreamOutput(STDERR);
$inceptionResult = CommandHelper::begin(
	new ArrayInput([]),
	$output,
	[$warmupFile, $benchmarkFile],
	'-1',
	null,
	[$rootDir],
	$configFile,
	null,
	null,
	false,
	true,
	null,
	null,
	false,
);
// Also tells the parser which files are analysed, the others are parsed without function bodies
$inceptionResult->getFiles();
$container = $inceptionResult->getContainer();
$analyser = $container->getByType(Analyser::class);
$finalizer = $container->getByType(AnalyserResultFinalizer::class);

$analyse = static function (string $file) use ($analyser, $finalizer): int {
	$result = $analyser->analyse([$file], null, null, true, [$file]);

	return count($finalizer->finalize($result, false, true)->getErrors());
};

// A different file than the benchmark: PHPStan memoizes types and reflection in memory,
// so analysing the benchmark twice would hide work from the measured run
$analyse($warmupFile);

$instance = null;
if ($hooks !== null) {
	$instance = $hooks->instrument_hooks_init();
	if ($instance === null || !$hooks->instrument_hooks_is_instrumented($instance)) {
		fwrite(STDERR, "Not running under CodSpeed's CPU simulation.\n");
		exit(1);
	}
	$hooks->instrument_hooks_set_integration($instance, 'phpstan-bench', '1.0.0');
}

memory_reset_peak_usage();
if ($hooks !== null && $hooks->instrument_hooks_start_benchmark($instance) !== 0) {
	fwrite(STDERR, "Cannot start the measurement.\n");
	exit(1);
}

$errors = $analyse($benchmarkFile);

if ($hooks !== null) {
	if ($hooks->instrument_hooks_stop_benchmark($instance) !== 0) {
		fwrite(STDERR, "Cannot stop the measurement.\n");
		exit(1);
	}
	// run.php finds the measured part of the profile by this name
	$hooks->instrument_hooks_set_executed_benchmark($instance, getmypid(), 'tests/bench/data/' . basename($benchmarkFile));
}

file_put_contents($peakMemoryFile, (string) memory_get_peak_usage());
fwrite(STDERR, sprintf("Analysed %s, %d errors.\n", $benchmarkFile, $errors));
