<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\ResultCache\ResultCacheManager;
use PHPStan\Parallel\ParallelAnalyser;
use PHPStan\Parallel\Scheduler;
use PHPStan\PhpDoc\StubValidator;
use Symfony\Component\Console\Input\InputInterface;
use function file_exists;

class AnalyseApplication
{

	/** @var \PHPStan\Analyser\Analyser */
	private $analyser;

	/** @var \PHPStan\PhpDoc\StubValidator */
	private $stubValidator;

	/** @var ParallelAnalyser */
	private $parallelAnalyser;

	/** @var Scheduler */
	private $scheduler;

	/** @var \PHPStan\Analyser\ResultCache\ResultCacheManager */
	private $resultCacheManager;

	/** @var string */
	private $memoryLimitFile;

	public function __construct(
		Analyser $analyser,
		StubValidator $stubValidator,
		ParallelAnalyser $parallelAnalyser,
		Scheduler $scheduler,
		ResultCacheManager $resultCacheManager,
		string $memoryLimitFile
	)
	{
		$this->analyser = $analyser;
		$this->stubValidator = $stubValidator;
		$this->parallelAnalyser = $parallelAnalyser;
		$this->scheduler = $scheduler;
		$this->resultCacheManager = $resultCacheManager;
		$this->memoryLimitFile = $memoryLimitFile;
	}

	/**
	 * @param string[] $files
	 * @param bool $onlyFiles
	 * @param \PHPStan\Command\Output $stdOutput
	 * @param \PHPStan\Command\Output $errorOutput
	 * @param bool $defaultLevelUsed
	 * @param bool $debug
	 * @param string|null $projectConfigFile
	 * @return AnalysisResult
	 */
	public function analyse(
		array $files,
		bool $onlyFiles,
		Output $stdOutput,
		Output $errorOutput,
		bool $defaultLevelUsed,
		bool $debug,
		?string $projectConfigFile,
		InputInterface $input
	): AnalysisResult
	{
		$this->updateMemoryLimitFile();
		$stubErrors = $this->stubValidator->validate();

		register_shutdown_function(function (): void {
			$error = error_get_last();
			if ($error === null) {
				return;
			}
			if ($error['type'] !== E_ERROR) {
				return;
			}

			if (strpos($error['message'], 'Allowed memory size') !== false) {
				return;
			}

			@unlink($this->memoryLimitFile);
		});

		$resultCache = $this->resultCacheManager->restore($files, $debug);

		$analyserResult = $this->resultCacheManager->process($this->runAnalyser(
			$resultCache->getFilesToAnalyse(),
			$files,
			$onlyFiles || !$resultCache->isFullAnalysis(),
			$debug,
			$projectConfigFile,
			$stdOutput,
			$errorOutput,
			$input
		), $resultCache);

		$errors = array_merge($stubErrors, $analyserResult->getErrors());

		$fileSpecificErrors = [];
		$notFileSpecificErrors = [];
		$filesErrorsToCache = [];
		$warnings = [];
		foreach ($errors as $error) {
			if (is_string($error)) {
				$notFileSpecificErrors[] = $error;
			} else {
				if ($error->isWarning()) {
					$warnings[] = $error->getMessage();
					continue;
				}
				$fileSpecificErrors[] = $error;
				$filesErrorsToCache[$error->getFilePath()][] = $error;
			}
		}

		return new AnalysisResult(
			$fileSpecificErrors,
			$notFileSpecificErrors,
			$warnings,
			$defaultLevelUsed,
			$analyserResult->hasInferrablePropertyTypesFromConstructor(),
			$projectConfigFile
		);
	}

	/**
	 * @param string[] $files
	 * @param string[] $allAnalysedFiles
	 */
	private function runAnalyser(
		array $files,
		array $allAnalysedFiles,
		bool $onlyFiles,
		bool $debug,
		?string $projectConfigFile,
		Output $stdOutput,
		Output $errorOutput,
		InputInterface $input
	): AnalyserResult
	{
		$filesCount = count($files);
		$allAnalysedFilesCount = count($allAnalysedFiles);
		if ($filesCount === 0) {
			$errorOutput->getStyle()->progressStart($allAnalysedFilesCount);
			$errorOutput->getStyle()->progressAdvance($allAnalysedFilesCount);
			$errorOutput->getStyle()->progressFinish();
			return new AnalyserResult([], false, []);
		}

		/** @var bool $runningInParallel */
		$runningInParallel = false;

		if (!$debug) {
			$progressStarted = false;
			$fileOrder = 0;
			$preFileCallback = null;
			$postFileCallback = function (int $step) use ($errorOutput, &$progressStarted, $allAnalysedFilesCount, $filesCount, &$fileOrder, &$runningInParallel): void {
				if (!$progressStarted) {
					$errorOutput->getStyle()->progressStart($allAnalysedFilesCount);
					$errorOutput->getStyle()->progressAdvance($allAnalysedFilesCount - $filesCount);
					$progressStarted = true;
				}
				$errorOutput->getStyle()->progressAdvance($step);

				if ($runningInParallel) {
					return;
				}

				if ($fileOrder >= 100) {
					$this->updateMemoryLimitFile();
					$fileOrder = 0;
				}
				$fileOrder += $step;
			};
		} else {
			$preFileCallback = static function (string $file) use ($stdOutput): void {
				$stdOutput->writeLineFormatted($file);
			};
			$postFileCallback = null;
		}

		// todo what about hyperthreading? should I divide CPU cores by 2?
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
			$runningInParallel = true;
			$analyserResult = $this->parallelAnalyser->analyse($schedule, $mainScript, $onlyFiles, $postFileCallback, $projectConfigFile, $input);
		} else {
			$analyserResult = $this->analyser->analyse(
				$files,
				$onlyFiles,
				$preFileCallback,
				$postFileCallback,
				$debug,
				$allAnalysedFiles
			);
		}

		if (isset($progressStarted) && $progressStarted) {
			$errorOutput->getStyle()->progressFinish();
		}

		return $analyserResult;
	}

	private function updateMemoryLimitFile(): void
	{
		$bytes = memory_get_peak_usage(true);
		$megabytes = ceil($bytes / 1024 / 1024);
		file_put_contents($this->memoryLimitFile, sprintf('%d MB', $megabytes));
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
			$process = @popen('wmic cpu get NumberOfCores', 'rb');
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
