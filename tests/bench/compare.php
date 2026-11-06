<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

/**
 * Compares the metrics measured by run.php against tests/bench/expected.neon and fails when a
 * benchmark is slower than BenchmarkComparator::TOLERANCES allows or was not measured. Prints the report to the log,
 * and on GitHub Actions also writes it to the job summary and annotates the benchmark files.
 *
 * Usage: php tests/bench/compare.php <directory with run.php outputs> [<file to write all measured metrics to>]
 *
 * The written file has the format of expected.neon, so it can replace it after an intended change.
 */

use Nette\Neon\Neon;
use function count;
use function file_get_contents;
use function file_put_contents;
use function fwrite;
use function getenv;
use function glob;
use function is_file;
use function ksort;
use function sprintf;
use function stream_isatty;
use const FILE_APPEND;
use const SORT_STRING;
use const STDERR;
use const STDOUT;

require_once __DIR__ . '/../../vendor/autoload.php';

if (!isset($argv) || (count($argv) !== 2 && count($argv) !== 3)) {
	fwrite(STDERR, "Usage: php tests/bench/compare.php <directory with run.php outputs> [<file to write all measured metrics to>]\n");
	exit(2);
}

$expectedFile = __DIR__ . '/expected.neon';
$expected = is_file($expectedFile) ? Neon::decode((string) file_get_contents($expectedFile)) : [];

$actual = [];
foreach (glob($argv[1] . '/*.neon') ?: [] as $resultFile) {
	$actual += Neon::decode((string) file_get_contents($resultFile));
}
ksort($actual, SORT_STRING);

if (isset($argv[2])) {
	$header = "# Expected metrics of the benchmarks in data/, compared by compare.php with the tolerances in BenchmarkComparator.\n"
		. "# To update it, replace it with the benchmark-measured artifact of the Benchmarks workflow.\n\n";
	file_put_contents($argv[2], $header . Neon::encode($actual, true));
}

$results = BenchmarkComparator::compare($expected, $actual);

$onGitHub = getenv('GITHUB_ACTIONS') === 'true';
$runId = getenv('GITHUB_RUN_ID');
$repository = getenv('GITHUB_REPOSITORY');
$acceptCommand = sprintf(
	"gh run download %s%s -n benchmark-measured -D tmp/benchmark-measured\nmv tmp/benchmark-measured/expected.neon tests/bench/expected.neon",
	$runId !== false && $runId !== '' ? $runId : '<run id>',
	$repository !== false && $repository !== '' ? ' -R ' . $repository : '',
);

echo BenchmarkReport::renderTerminal($results, $acceptCommand, $onGitHub || stream_isatty(STDOUT));
echo "\n";
echo $onGitHub ? "::group::All benchmarks\n" : "All benchmarks\n\n";
echo BenchmarkReport::renderTerminalTable($results);
if ($onGitHub) {
	echo "::endgroup::\n";
	echo BenchmarkReport::renderAnnotations($results);
}

$stepSummaryFile = getenv('GITHUB_STEP_SUMMARY');
if ($stepSummaryFile !== false && $stepSummaryFile !== '') {
	file_put_contents($stepSummaryFile, BenchmarkReport::renderMarkdown($results, $acceptCommand), FILE_APPEND);
}

exit(BenchmarkComparator::fails($results) ? 1 : 0);
