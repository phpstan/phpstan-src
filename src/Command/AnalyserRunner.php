<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Closure;
use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Parallel\ParallelAnalyser;
use PHPStan\Parallel\Scheduler;
use PHPStan\Process\CpuCoreCounter;
use PHPStan\ShouldNotHappenException;
use React\EventLoop\StreamSelectLoop;
use Symfony\Component\Console\Input\InputInterface;
use function count;
use function function_exists;
use function is_file;
use function memory_get_peak_usage;

class AnalyserRunner
{

	public function __construct(
		private Scheduler $scheduler,
		private Analyser $analyser,
		private ParallelAnalyser $parallelAnalyser,
		private CpuCoreCounter $cpuCoreCounter,
	)
	{
	}

	/**
	 * @param string[] $files
	 * @param string[] $allAnalysedFiles
	 * @param Closure(string $file): void|null $preFileCallback
	 * @param Closure(int ): void|null $postFileCallback
	 */
	public function runAnalyser(
		array $files,
		array $allAnalysedFiles,
		?Closure $preFileCallback,
		?Closure $postFileCallback,
		bool $debug,
		bool $allowParallel,
		?string $projectConfigFile,
		InputInterface $input,
	): AnalyserResult
	{
		$filesCount = count($files);
		if ($filesCount === 0) {
			return new AnalyserResult([], [], [], [], [], false, memory_get_peak_usage(true));
		}

		$schedule = $this->scheduler->scheduleWork($this->cpuCoreCounter->getNumberOfCpuCores(), $files);
		$mainScript = null;
		if (isset($_SERVER['argv'][0]) && is_file($_SERVER['argv'][0])) {
			$mainScript = $_SERVER['argv'][0];
		}

		if (
			!$debug
			&& $allowParallel
			&& function_exists('proc_open')
			&& $mainScript !== null
			&& $schedule->getNumberOfProcesses() > 0
		) {
			$loop = new StreamSelectLoop();
			$result = null;
			$promise = $this->parallelAnalyser->analyse($loop, $schedule, $mainScript, $postFileCallback, $projectConfigFile, $input, null);
			$promise->then(static function (AnalyserResult $tmp) use (&$result): void {
				$result = $tmp;
			});
			$loop->run();
			if ($result === null) {
				throw new ShouldNotHappenException();
			}
			return $result;
		}

		return $this->analyser->analyse(
			$files,
			$preFileCallback,
			$postFileCallback,
			$debug,
			$allAnalysedFiles,
		);
	}

}
