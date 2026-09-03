<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use Closure;
use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use Nette\Utils\Random;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\InternalError;
use PHPStan\Cache\ArenaCache;
use PHPStan\Command\CommandHelper;
use PHPStan\Command\Output;
use PHPStan\Dependency\RootExportedNode;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Process\ProcessHelper;
use PHPStan\Process\TerminationSignal;
use PHPStan\Reflection\BetterReflection\SourceLocator\PreForkDirectorySymbolScanner;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\TcpServer;
use Symfony\Component\Console\Input\InputInterface;
use Throwable;
use function array_map;
use function array_pop;
use function array_reverse;
use function count;
use function defined;
use function escapeshellarg;
use function getenv;
use function ini_get;
use function max;
use function parse_url;
use function sprintf;
use function str_contains;
use const PHP_URL_PORT;

#[AutowiredService]
final class ParallelAnalyser
{

	private const DEFAULT_TIMEOUT = 600.0;

	private float $processTimeout;

	private ProcessPool $processPool;

	public function __construct(
		#[AutowiredParameter]
		private int $internalErrorsCountLimit,
		#[AutowiredParameter(ref: '%parallel.processTimeout%')]
		float $processTimeout,
		#[AutowiredParameter(ref: '%parallel.buffer%')]
		private int $decoderBufferSize,
		private ForkParallelChecker $forkParallelChecker,
		private PreForkDirectorySymbolScanner $preForkDirectorySymbolScanner,
		private WorkerRunner $workerRunner,
	)
	{
		$this->processTimeout = max($processTimeout, self::DEFAULT_TIMEOUT);
	}

	/**
	 * @param string[] $allAnalysedFiles
	 * @param Closure(int, list<string>=): void|null $postFileCallback
	 * @param (callable(list<Error>, list<Error>, string[]): void)|null $onFileAnalysisHandler
	 * @param Output|null $errorOutput where spawned workers report what they run with at -vvv; null when nobody is listening
	 * @return PromiseInterface<AnalyserResult>
	 */
	public function analyse(
		LoopInterface $loop,
		Schedule $schedule,
		array $allAnalysedFiles,
		string $mainScript,
		?Closure $postFileCallback,
		?string $projectConfigFile,
		?string $tmpFile,
		?string $insteadOfFile,
		InputInterface $input,
		?callable $onFileAnalysisHandler,
		?Output $errorOutput,
	): PromiseInterface
	{
		$jobs = array_reverse($schedule->getJobs());

		$numberOfProcesses = $schedule->getNumberOfProcesses();

		// Single-run shared-memory arena (turbo extension only; the seam is a
		// no-op otherwise). Workers receive the name via the --arena option and
		// attach before sending their hello. The spawn loop below starts one
		// process per available slot unless there are no jobs at all.
		// PHPSTAN_ARENA=0 disables just the arena, leaving the rest of the
		// extension active.
		$arenaName = null;
		if ($numberOfProcesses > 1 && getenv('PHPSTAN_ARENA') !== '0') {
			$arenaName = ArenaCache::create(Random::generate());
			if ($arenaName !== null) {
				// the workers' boot re-derives this list by walking the
				// analysed paths again; publishing what the schedule was
				// already built from lets them skip that walk
				ArenaCache::publish('analysed-files', $allAnalysedFiles);
			}
		}
		$expectedWorkerCount = count($jobs) === 0 ? 0 : $numberOfProcesses;
		$helloCount = 0;
		$someChildEnded = false;
		$errors = [];
		$filteredPhpErrors = [];
		$allPhpErrors = [];
		$locallyIgnoredErrors = [];
		$linesToIgnore = [];
		$unmatchedLineIgnores = [];
		/** @var array<string, int> $peakMemoryUsages */
		$peakMemoryUsages = [];
		$internalErrors = [];
		$internalErrorsCount = 0;
		$collectedData = [];
		$dependencies = [];
		$usedTraitDependencies = [];
		$packageDependencies = [];
		$reachedInternalErrorsCountLimit = false;
		$exportedNodes = [];
		/** @var list<string> $allProcessedFiles */
		$allProcessedFiles = [];

		/** @var Deferred<AnalyserResult> $deferred */
		$deferred = new Deferred();

		$useFork = $this->forkParallelChecker->isSupported();

		$server = new TcpServer('127.0.0.1:0', $loop);
		$this->processPool = new ProcessPool($server, static function () use ($deferred, &$jobs, &$internalErrors, &$internalErrorsCount, &$reachedInternalErrorsCountLimit, &$errors, &$filteredPhpErrors, &$allPhpErrors, &$locallyIgnoredErrors, &$linesToIgnore, &$unmatchedLineIgnores, &$collectedData, &$dependencies, &$usedTraitDependencies, &$packageDependencies, &$exportedNodes, &$peakMemoryUsages, &$allProcessedFiles, $arenaName): void {
			if ($arenaName !== null) {
				ArenaCache::destroy();
			}

			if (count($jobs) > 0 && $internalErrorsCount === 0) {
				$internalErrors[] = new InternalError(
					'Some parallel worker jobs have not finished.',
					'running parallel worker',
					trace: [],
					traceAsString: null,
					shouldReportBug: true,
				);
				$internalErrorsCount++;
			}

			$deferred->resolve(new AnalyserResult(
				unorderedErrors: $errors,
				filteredPhpErrors: $filteredPhpErrors,
				allPhpErrors: $allPhpErrors,
				locallyIgnoredErrors: $locallyIgnoredErrors,
				linesToIgnore: $linesToIgnore,
				unmatchedLineIgnores: $unmatchedLineIgnores,
				internalErrors: $internalErrors,
				collectedData: $collectedData,
				dependencies: $internalErrorsCount === 0 ? $dependencies : null,
				usedTraitDependencies: $internalErrorsCount === 0 ? $usedTraitDependencies : null,
				packageDependencies: $internalErrorsCount === 0 ? $packageDependencies : null,
				exportedNodes: $exportedNodes,
				reachedInternalErrorsCountLimit: $reachedInternalErrorsCountLimit,
				// The heaviest single worker. Summing the workers' peaks would describe a
				// moment that never happens - they do not peak at the same time - while
				// each worker's own peak is what its memory_limit is measured against.
				peakMemoryUsageBytes: $peakMemoryUsages === [] ? 0 : max($peakMemoryUsages),
				processedFiles: $allProcessedFiles,
				workerCount: count($peakMemoryUsages),
			));
		});
		$server->on('connection', function (ConnectionInterface $connection) use (&$jobs, $arenaName, $expectedWorkerCount, &$helloCount, $errorOutput, $useFork): void {
			// phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
			$jsonInvalidUtf8Ignore = defined('JSON_INVALID_UTF8_IGNORE') ? JSON_INVALID_UTF8_IGNORE : 0;
			// phpcs:enable
			$decoder = new Decoder($connection, true, options: $jsonInvalidUtf8Ignore, maxlength: $this->decoderBufferSize);
			$encoder = new Encoder($connection, $jsonInvalidUtf8Ignore);
			$decoder->on('data', function (array $data) use (&$jobs, $decoder, $encoder, $arenaName, $expectedWorkerCount, &$helloCount, $errorOutput, $useFork): void {
				if ($data['action'] !== 'hello') {
					return;
				}

				// Workers attach to the arena before saying hello; once every
				// spawned worker checked in, the name can go away — the mapping
				// stays valid, and the kernel reclaims it with the last process
				// no matter how the run ends.
				$helloCount++;
				if ($arenaName !== null && $helloCount === $expectedWorkerCount) {
					ArenaCache::unlinkName();
				}
				if ($errorOutput !== null && $errorOutput->isVeryVerbose() && !$useFork) {
					// the spawn command line asks for these (see ProcessHelper);
					// this is whether they took effect. A forked worker inherits
					// the main process and has nothing of its own to report.
					$errorOutput->writeLineFormatted(sprintf(
						'Spawned worker %d/%d checked in: turbo %s, OPcache %s, trusted types %s',
						$helloCount,
						$expectedWorkerCount,
						($data['turbo'] ?? false) === true ? 'on' : 'off',
						($data['opcache'] ?? false) === true ? 'on' : 'off',
						($data['trustedTypes'] ?? false) === true ? 'on' : 'off',
					));
				}

				$identifier = $data['identifier'];
				$process = $this->processPool->getProcess($identifier);
				$process->bindConnection($decoder, $encoder);
				if (count($jobs) === 0) {
					$this->processPool->tryQuitProcess($identifier);
					return;
				}

				$job = array_pop($jobs);
				$process->request(['action' => 'analyse', 'files' => $job]);
			});
		});
		/** @var string $serverAddress */
		$serverAddress = $server->getAddress();

		/** @var int<0, 65535> $serverPort */
		$serverPort = parse_url($serverAddress, PHP_URL_PORT);

		$handleError = function (Throwable $error) use (&$internalErrors, &$internalErrorsCount, &$reachedInternalErrorsCountLimit): void {
			$internalErrors[] = new InternalError(
				$error->getMessage(),
				'communicating with parallel worker',
				InternalError::prepareTrace($error),
				$error->getTraceAsString(),
				shouldReportBug: !$error instanceof ProcessTimedOutException,
			);
			$internalErrorsCount++;
			$reachedInternalErrorsCountLimit = true;
			$this->processPool->quitAll();
		};

		if ($useFork && $numberOfProcesses > 1) {
			// Build the directory symbol indexes here, in the parent, so the
			// children inherit them copy-on-write instead of each scanning the
			// same directories (see PreForkDirectorySymbolScanner). With a
			// single worker there is nothing to share, and doing it here would
			// only take work off the lazy path that worker might never reach.
			$this->preForkDirectorySymbolScanner->scanBeforeFork();
		}

		for ($i = 0; $i < $numberOfProcesses; $i++) {
			if (count($jobs) === 0) {
				break;
			}

			$processIdentifier = Random::generate();
			$commandOptions = [
				'--port',
				(string) $serverPort,
				'--identifier',
				$processIdentifier,
			];

			if ($arenaName !== null) {
				$commandOptions[] = '--arena';
				$commandOptions[] = escapeshellarg($arenaName);
			}

			if ($tmpFile !== null && $insteadOfFile !== null) {
				$commandOptions[] = '--tmp-file';
				$commandOptions[] = escapeshellarg($tmpFile);
				$commandOptions[] = '--instead-of';
				$commandOptions[] = escapeshellarg($insteadOfFile);
			}

			$process = $this->createProcess(
				$useFork,
				$loop,
				$server,
				$serverPort,
				$processIdentifier,
				$allAnalysedFiles,
				$mainScript,
				$projectConfigFile,
				$commandOptions,
				$tmpFile,
				$insteadOfFile,
				$input,
			);
			$process->start(function (array $json) use ($process, &$internalErrors, &$errors, &$filteredPhpErrors, &$allPhpErrors, &$locallyIgnoredErrors, &$linesToIgnore, &$unmatchedLineIgnores, &$collectedData, &$dependencies, &$usedTraitDependencies, &$packageDependencies, &$exportedNodes, &$peakMemoryUsages, &$jobs, $postFileCallback, &$internalErrorsCount, &$reachedInternalErrorsCountLimit, $processIdentifier, $onFileAnalysisHandler, &$allProcessedFiles): void {
				$fileErrors = [];
				foreach ($json['errors'] as $jsonError) {
					$fileErrors[] = Error::decode($jsonError);
				}
				foreach ($json['internalErrors'] as $internalJsonError) {
					$internalErrors[] = InternalError::decode($internalJsonError);
				}

				foreach ($json['filteredPhpErrors'] as $filteredPhpError) {
					$filteredPhpErrors[] = Error::decode($filteredPhpError);
				}

				foreach ($json['allPhpErrors'] as $allPhpError) {
					$allPhpErrors[] = Error::decode($allPhpError);
				}

				$locallyIgnoredFileErrors = [];
				foreach ($json['locallyIgnoredErrors'] as $locallyIgnoredJsonError) {
					$locallyIgnoredFileErrors[] = Error::decode($locallyIgnoredJsonError);
				}

				if ($onFileAnalysisHandler !== null) {
					$onFileAnalysisHandler($fileErrors, $locallyIgnoredFileErrors, $json['files']);
				}

				foreach ($fileErrors as $fileError) {
					$errors[] = $fileError;
				}

				foreach ($locallyIgnoredFileErrors as $locallyIgnoredFileError) {
					$locallyIgnoredErrors[] = $locallyIgnoredFileError;
				}

				foreach ($json['collectedData'] as $file => $jsonDataByCollector) {
					foreach ($jsonDataByCollector as $collectorType => $listOfCollectedData) {
						foreach ($listOfCollectedData as $rawCollectedData) {
							$collectedData[$file][$collectorType][] = $rawCollectedData;
						}
					}
				}

				/**
				 * @var string $file
				 * @var array<string> $fileDependencies
				 */
				foreach ($json['dependencies'] as $file => $fileDependencies) {
					$dependencies[$file] = $fileDependencies;
				}

				/**
				 * @var string $file
				 * @var array<string> $fileUsedTraitDependencies
				 */
				foreach ($json['usedTraitDependencies'] as $file => $fileUsedTraitDependencies) {
					$usedTraitDependencies[$file] = $fileUsedTraitDependencies;
				}

				/**
				 * @var string $file
				 * @var array<string> $filePackageDependencies
				 */
				foreach ($json['packageDependencies'] as $file => $filePackageDependencies) {
					$packageDependencies[$file] = $filePackageDependencies;
				}

				foreach ($json['linesToIgnore'] as $file => $fileLinesToIgnore) {
					if (count($fileLinesToIgnore) === 0) {
						continue;
					}
					$linesToIgnore[$file] = $fileLinesToIgnore;
				}

				foreach ($json['unmatchedLineIgnores'] as $file => $fileUnmatchedLineIgnores) {
					if (count($fileUnmatchedLineIgnores) === 0) {
						continue;
					}
					$unmatchedLineIgnores[$file] = $fileUnmatchedLineIgnores;
				}

				/**
				 * @var string $file
				 * @var array<mixed[]> $fileExportedNodes
				 */
				foreach ($json['exportedNodes'] as $file => $fileExportedNodes) {
					if (count($fileExportedNodes) === 0) {
						continue;
					}
					$exportedNodes[$file] = array_map(static function (array $node): RootExportedNode {
						/** @var class-string<RootExportedNode> $class */
						$class = $node['type'];

						return $class::decode($node['data']);
					}, $fileExportedNodes);
				}

				foreach ($json['processedFiles'] as $processedFile) {
					$allProcessedFiles[] = $processedFile;
				}

				if ($postFileCallback !== null) {
					$postFileCallback(count($json['files']), $json['processedFiles']);
				}

				if (!isset($peakMemoryUsages[$processIdentifier]) || $peakMemoryUsages[$processIdentifier] < $json['memoryUsage']) {
					$peakMemoryUsages[$processIdentifier] = $json['memoryUsage'];
				}

				$internalErrorsCount += $json['internalErrorsCount'];
				if ($internalErrorsCount >= $this->internalErrorsCountLimit) {
					$reachedInternalErrorsCountLimit = true;
					$this->processPool->quitAll();
				}

				if (count($jobs) === 0) {
					$this->processPool->tryQuitProcess($processIdentifier);
					return;
				}

				$job = array_pop($jobs);
				$process->request(['action' => 'analyse', 'files' => $job]);
			}, $handleError, function ($exitCode, string $output, ?int $termSignal) use (&$someChildEnded, &$internalErrors, &$internalErrorsCount, $processIdentifier): void {
				// The main process is not sampled here any more: its own peak comes
				// later (collecting the workers' results, saving the result cache) and
				// is read where the number is printed. Only worker peaks are summed.
				$someChildEnded = true;

				if ($exitCode === 0) {
					$this->processPool->tryQuitProcess($processIdentifier);
					return;
				}

				// A worker killed by a signal never gets to run any PHP: no error
				// is printed, no shutdown function runs, and the output the main
				// process reads back is empty. The signal is the whole report -
				// without it the run says nothing but "Some parallel worker jobs
				// have not finished", or, when the dead worker was holding the
				// last job, nothing at all.
				if ($termSignal !== null) {
					$internalErrors[] = new InternalError(sprintf(
						"Child process was killed by signal %s.\n%s%s",
						TerminationSignal::describe($termSignal),
						'The OS kills a worker this way when it runs out of memory (the kernel OOM killer sends SIGKILL) or shared memory (SIGBUS), or when it crashes natively (SIGSEGV).',
						$output === '' ? '' : "\n" . $output,
					), 'running parallel worker', trace: [], traceAsString: null, shouldReportBug: false);
					$internalErrorsCount++;
					$this->processPool->tryQuitProcess($processIdentifier);
					return;
				}

				if ($exitCode === null) {
					if ($output !== '') {
						$internalErrors[] = new InternalError(sprintf('Child process ended unexpectedly: %s', $output), 'running parallel worker', trace: [], traceAsString: null, shouldReportBug: true);
						$internalErrorsCount++;
					}
					$this->processPool->tryQuitProcess($processIdentifier);
					return;
				}

				$memoryLimitMessage = CommandHelper::MEMORY_LIMIT_CRASH_MESSAGE;
				if (str_contains($output, $memoryLimitMessage)) {
					foreach ($internalErrors as $internalError) {
						if (!str_contains($internalError->getMessage(), $memoryLimitMessage)) {
							continue;
						}

						$this->processPool->tryQuitProcess($processIdentifier);
						return;
					}
					$internalErrors[] = new InternalError(sprintf(
						"Child process error: %s: %s\n%s\n",
						$memoryLimitMessage,
						ini_get('memory_limit'),
						'Increase your memory limit in php.ini or run PHPStan with --memory-limit CLI option.',
					), 'running parallel worker', trace: [], traceAsString: null, shouldReportBug: false);
					$internalErrorsCount++;
					$this->processPool->tryQuitProcess($processIdentifier);
					return;
				}

				$internalErrors[] = new InternalError(sprintf('Child process error (exit code %d): %s', $exitCode, $output), 'running parallel worker', trace: [], traceAsString: null, shouldReportBug: true);
				$internalErrorsCount++;
				$this->processPool->tryQuitProcess($processIdentifier);
			});
			$this->processPool->attachProcess($processIdentifier, $process);
		}

		return $deferred->promise();
	}

	/**
	 * @param string[] $allAnalysedFiles
	 * @param string[] $commandOptions
	 */
	private function createProcess(
		bool $useFork,
		LoopInterface $loop,
		TcpServer $server,
		int $serverPort,
		string $processIdentifier,
		array $allAnalysedFiles,
		string $mainScript,
		?string $projectConfigFile,
		array $commandOptions,
		?string $tmpFile,
		?string $insteadOfFile,
		InputInterface $input,
	): Process
	{
		if ($useFork) {
			return new ForkedProcess(
				$loop,
				$this->processTimeout,
				$this->workerRunner,
				$server,
				$serverPort,
				$processIdentifier,
				$allAnalysedFiles,
				$tmpFile,
				$insteadOfFile,
			);
		}

		return new SpawnedProcess(ProcessHelper::getWorkerCommand(
			$mainScript,
			'worker',
			$projectConfigFile,
			$commandOptions,
			$input,
		), $loop, $this->processTimeout);
	}

}
