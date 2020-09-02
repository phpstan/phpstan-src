<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Parallel\ParallelAnalyser;
use PHPStan\Parallel\Scheduler;
use Symfony\Component\Console\Input\InputInterface;

class AnalyserRunner
{

	private Scheduler $scheduler;

	private Analyser $analyser;

	private ParallelAnalyser $parallelAnalyser;

	public function __construct(
		Scheduler $scheduler,
		Analyser $analyser,
		ParallelAnalyser $parallelAnalyser
	)
	{
		$this->scheduler = $scheduler;
		$this->analyser = $analyser;
		$this->parallelAnalyser = $parallelAnalyser;
	}

	/**
	 * @param string[] $files
	 * @param string[] $allAnalysedFiles
	 * @param \Closure|null $preFileCallback
	 * @param \Closure|null $postFileCallback
	 * @param bool $debug
	 * @param string|null $projectConfigFile
	 * @param InputInterface $input
	 * @return AnalyserResult
	 * @throws \Throwable
	 */
	public function runAnalyser(
		array $files,
		array $allAnalysedFiles,
		?\Closure $preFileCallback,
		?\Closure $postFileCallback,
		bool $debug,
		?string $projectConfigFile,
		InputInterface $input
	): AnalyserResult
	{
		$filesCount = count($files);
		if ($filesCount === 0) {
			return new AnalyserResult([], [], [], [], false);
		}

		$schedule = $this->scheduler->scheduleWork($this->getNumberOfCpuCores(), $files);
		$mainScript = null;
		if (isset($_SERVER['argv'][0]) && file_exists($_SERVER['argv'][0])) {
			$mainScript = $_SERVER['argv'][0];
		}

		if (
			!$debug
			&& $mainScript !== null
			&& $schedule->getNumberOfProcesses() > 1
		) {
			return $this->parallelAnalyser->analyse($schedule, $mainScript, $postFileCallback, $projectConfigFile, $input);
		}

		return $this->analyser->analyse(
			$files,
			$preFileCallback,
			$postFileCallback,
			$debug,
			$allAnalysedFiles
		);
	}

	private function getNumberOfCpuCores(): int
	{
		// from brianium/paratest
		$cores = 2;
		if (is_file('/proc/cpuinfo')) {
			// Linux (and potentially Windows with linux sub systems)
			$cpuinfo = @file_get_contents('/proc/cpuinfo');
			if ($cpuinfo !== false) {
				preg_match_all('/^processor/m', $cpuinfo, $matches);
				return count($matches[0]);
			}
		}

		if (\DIRECTORY_SEPARATOR === '\\') {
			// Windows
			$process = @popen('wmic cpu get NumberOfLogicalProcessors', 'rb');
			if ($process !== false) {
				fgets($process);
				$cores = (int) fgets($process);
				pclose($process);
			}

			return $cores;
		}

		$process = @\popen('sysctl -n hw.ncpu', 'rb');
		if ($process !== false) {
			// *nix (Linux, BSD and Mac)
			$cores = (int) fgets($process);
			pclose($process);
		}

		return $cores;
	}

}
