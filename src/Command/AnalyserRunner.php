<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Closure;
use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Parallel\ParallelAnalyser;
use PHPStan\Parallel\Scheduler;
use PHPStan\Process\CpuCoreCounter;
use PHPStan\ShouldNotHappenException;
use React\EventLoop\StreamSelectLoop;
use Symfony\Component\Console\Input\InputInterface;
use function array_filter;
use function array_unshift;
use function array_values;
use function count;
use function filesize;
use function function_exists;
use function is_file;
use function memory_get_peak_usage;

#[AutowiredService]
final class AnalyserRunner
{

	public function __construct(
		private Scheduler $scheduler,
		private Analyser $analyser,
		private ParallelAnalyser $parallelAnalyser,
		private CpuCoreCounter $cpuCoreCounter,
		private BootstrapFilesRunner $bootstrapFilesRunner,
	)
	{
	}

	/**
	 * @param string[] $files
	 * @param string[] $allAnalysedFiles
	 * @param Closure(string $file): void|null $preFileCallback
	 * @param Closure(int, list<string>=): void|null $postFileCallback
	 * @throws InceptionNotSuccessfulException
	 */
	public function runAnalyser(
		array $files,
		array $allAnalysedFiles,
		?Closure $preFileCallback,
		?Closure $postFileCallback,
		bool $debug,
		bool $allowParallel,
		?string $projectConfigFile,
		?string $tmpFile,
		?string $insteadOfFile,
		InputInterface $input,
		Output $errorOutput,
	): AnalyserResult
	{
		$filesCount = count($files);
		if ($filesCount === 0) {
			return new AnalyserResult(
				unorderedErrors: [],
				filteredPhpErrors: [],
				allPhpErrors: [],
				locallyIgnoredErrors: [],
				linesToIgnore: [],
				unmatchedLineIgnores: [],
				internalErrors: [],
				collectedData: [],
				dependencies: [],
				usedTraitDependencies: [],
				packageDependencies: [],
				exportedNodes: [],
				reachedInternalErrorsCountLimit: false,
				peakMemoryUsageBytes: memory_get_peak_usage(true),
				processedFiles: [],
			);
		}

		if (!$debug && $allowParallel && function_exists('proc_open')) {
			$schedule = $this->scheduler->scheduleWork($this->cpuCoreCounter->getNumberOfCpuCores(), $files, static fn (string $file): int => (int) @filesize($file));

			$mainScript = null;
			if (isset($_SERVER['argv'][0]) && is_file($_SERVER['argv'][0])) {
				$mainScript = $_SERVER['argv'][0];
			}

			if ($mainScript !== null && $schedule->getNumberOfProcesses() > 0) {
				$loop = new StreamSelectLoop();
				$result = null;
				$promise = $this->parallelAnalyser->analyse($loop, $schedule, $allAnalysedFiles, $mainScript, $postFileCallback, $projectConfigFile, $tmpFile, $insteadOfFile, $input, null);
				$promise->then(static function (AnalyserResult $tmp) use (&$result): void {
					$result = $tmp;
				});
				$loop->run();
				if ($result === null) {
					throw new ShouldNotHappenException();
				}
				// the parallel analysis is over and no more workers fork - the
				// main thread runs the deferred bootstrapFiles now, before the
				// phases that may reflect analysed code (stub validation,
				// collector rules)
				$this->bootstrapFilesRunner->run($errorOutput, $debug);

				return $result;
			}
		}

		// every path below analyses in-process - including the fall-throughs
		// from the parallel branch above (no main script, zero-process
		// schedule) - so the main thread runs the deferred bootstrapFiles
		// first
		$this->bootstrapFilesRunner->run($errorOutput, $debug);

		return $this->analyser->analyse(
			$this->switchTmpFile($files, $insteadOfFile, $tmpFile),
			$preFileCallback,
			$postFileCallback,
			$debug,
			$this->switchTmpFile($allAnalysedFiles, $insteadOfFile, $tmpFile),
		);
	}

	/**
	 * @param string[] $analysedFiles
	 * @return string[]
	 */
	private function switchTmpFile(
		array $analysedFiles,
		?string $insteadOfFile,
		?string $tmpFile,
	): array
	{
		if ($insteadOfFile === null) {
			return $analysedFiles;
		}
		$analysedFiles = array_values(array_filter($analysedFiles, static fn (string $file): bool => $file !== $insteadOfFile));

		if ($tmpFile !== null) {
			array_unshift($analysedFiles, $tmpFile);
		}

		return $analysedFiles;
	}

}
