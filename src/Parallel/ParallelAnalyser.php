<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoredErrorHelper;
use PHPStan\Command\AnalyseCommand;
use React\ChildProcess\Process;
use React\EventLoop\StreamSelectLoop;
use Symfony\Component\Console\Input\InputInterface;
use function escapeshellarg;

class ParallelAnalyser
{

	/** @var IgnoredErrorHelper */
	private $ignoredErrorHelper;

	/** @var int */
	private $internalErrorsCountLimit;

	public function __construct(
		IgnoredErrorHelper $ignoredErrorHelper,
		int $internalErrorsCountLimit
	)
	{
		$this->ignoredErrorHelper = $ignoredErrorHelper;
		$this->internalErrorsCountLimit = $internalErrorsCountLimit;
	}

	/**
	 * @param Schedule $schedule
	 * @param string $mainScript
	 * @param bool $onlyFiles
	 * @param \Closure(int): void|null $postFileCallback
	 * @param string|null $projectConfigFile
	 * @return array{errors: (string[]|\PHPStan\Analyser\Error[]), hasInferrablePropertyTypesFromConstructor: bool}
	 */
	public function analyse(
		Schedule $schedule,
		string $mainScript,
		bool $onlyFiles,
		?\Closure $postFileCallback,
		?string $projectConfigFile,
		InputInterface $input
	): array
	{
		$ignoredErrorHelperResult = $this->ignoredErrorHelper->initialize();
		if (count($ignoredErrorHelperResult->getErrors()) > 0) {
			return [
				'errors' => $ignoredErrorHelperResult->getErrors(),
				'hasInferrablePropertyTypesFromConstructor' => false,
			];
		}

		$jobs = array_reverse($schedule->getJobs());
		$loop = new StreamSelectLoop();

		$processes = [];
		$numberOfProcesses = $schedule->getNumberOfProcesses();
		$errors = [];
		$internalErrors = [];
		$hasInferrablePropertyTypesFromConstructor = false;

		$command = $this->getWorkerCommand(
			$mainScript,
			$projectConfigFile,
			$input
		);

		$internalErrorsCount = 0;
		$reachedInternalErrorsCountLimit = false;

		$handleError = static function (\Throwable $error) use ($loop, &$internalErrors, &$internalErrorsCount, &$reachedInternalErrorsCountLimit): void {
			$internalErrors[] = sprintf('Internal error: ' . $error->getMessage());
			$internalErrorsCount++;
			$reachedInternalErrorsCountLimit = true;
			$loop->stop();
		};

		for ($i = 0; $i < $numberOfProcesses; $i++) {
			if (count($jobs) === 0) {
				break;
			}

			$process = new Process($command);
			$process->start($loop);
			$processStdIn = new Encoder($process->stdin);
			$processStdIn->on('error', $handleError);
			$processStdOut = new Decoder($process->stdout, true, 512, 0, 4 * 1024 * 1024);
			$processStdOut->on('data', function (array $json) use ($process, &$internalErrors, &$errors, &$jobs, $processStdIn, $postFileCallback, &$hasInferrablePropertyTypesFromConstructor, &$internalErrorsCount, &$reachedInternalErrorsCountLimit, $loop): void {
				foreach ($json['errors'] as $jsonError) {
					if (is_string($jsonError)) {
						$internalErrors[] = sprintf('Internal error: %s', $jsonError);
						continue;
					}

					$errors[] = Error::decode($jsonError);
				}

				if ($postFileCallback !== null) {
					$postFileCallback($json['filesCount']);
				}

				$hasInferrablePropertyTypesFromConstructor = $hasInferrablePropertyTypesFromConstructor || $json['hasInferrablePropertyTypesFromConstructor'];
				$internalErrorsCount += $json['internalErrorsCount'];
				if ($internalErrorsCount >= $this->internalErrorsCountLimit) {
					$reachedInternalErrorsCountLimit = true;
					$loop->stop();
				}

				if (count($jobs) === 0) {
					foreach ($process->pipes as $pipe) {
						$pipe->close();
					}

					$processStdIn->write(['action' => 'quit']);
					return;
				}

				$job = array_pop($jobs);
				$processStdIn->write(['action' => 'analyse', 'files' => $job]);
			});
			$processStdOut->on('error', $handleError);

			$stdErrBuffer = new StreamBuffer($process->stderr);
			$process->on('exit', static function ($exitCode) use (&$internalErrors, $stdErrBuffer): void {
				if ($exitCode === 0) {
					return;
				}

				$internalErrors[] = sprintf('Child process error: %s', $stdErrBuffer->getBuffer());
			});

			$job = array_pop($jobs);
			$processStdIn->write(['action' => 'analyse', 'files' => $job]);
			$processes[] = $process;
		}

		$loop->run();

		if ($reachedInternalErrorsCountLimit) {
			$internalErrors[] = sprintf('Reached internal errors count limit of %d, exiting...', $this->internalErrorsCountLimit);
		}

		return [
			'errors' => array_merge($ignoredErrorHelperResult->process($errors, $onlyFiles, $reachedInternalErrorsCountLimit), $internalErrors, $ignoredErrorHelperResult->getWarnings()),
			'hasInferrablePropertyTypesFromConstructor' => $hasInferrablePropertyTypesFromConstructor,
		];
	}

	private function getWorkerCommand(
		string $mainScript,
		?string $projectConfigFile,
		InputInterface $input
	): string
	{
		$args = array_merge([PHP_BINARY, $mainScript], array_slice($_SERVER['argv'], 1));
		$processCommandArray = [];
		foreach ($args as $arg) {
			if (in_array($arg, ['analyse', 'analyze'], true)) {
				break;
			}

			$processCommandArray[] = escapeshellarg($arg);
		}

		$processCommandArray[] = 'worker';
		if ($projectConfigFile !== null) {
			$processCommandArray[] = '--configuration';
			$processCommandArray[] = escapeshellarg($projectConfigFile);
		}

		$options = [
			'paths-file',
			AnalyseCommand::OPTION_LEVEL,
			'autoload-file',
			'memory-limit',
			'xdebug',
		];
		foreach ($options as $optionName) {
			/** @var bool|string|null $optionValue */
			$optionValue = $input->getOption($optionName);
			if (is_bool($optionValue)) {
				if ($optionValue === true) {
					$processCommandArray[] = sprintf('--%s', $optionName);
				}
				continue;
			}
			if ($optionValue === null) {
				continue;
			}

			$processCommandArray[] = sprintf('--%s', $optionName);
			$processCommandArray[] = escapeshellarg($optionValue);
		}

		/** @var string[] $paths */
		$paths = $input->getArgument('paths');
		foreach ($paths as $path) {
			$processCommandArray[] = escapeshellarg($path);
		}

		return implode(' ', $processCommandArray);
	}

}
