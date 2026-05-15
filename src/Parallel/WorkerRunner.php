<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\InternalError;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Rules\Registry as RuleRegistry;
use React\EventLoop\StreamSelectLoop;
use React\Socket\ConnectionInterface;
use React\Socket\TcpConnector;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function array_fill_keys;
use function array_filter;
use function array_merge;
use function array_unshift;
use function array_values;
use function defined;
use function memory_get_peak_usage;
use function sprintf;

/**
 * The parallel worker logic that runs *after* the application boot.
 *
 * Extracted from WorkerCommand so it can be reused without re-booting: a
 * pcntl_fork()-ed child (see ForkedProcess) inherits the already-booted DI
 * container and calls run() directly, while WorkerCommand still calls it after
 * the expensive CommandHelper::begin() boot of a freshly spawned process.
 *
 * It connects back to ParallelAnalyser's TCP server, performs the NDJSON
 * handshake and analyses the files it is handed.
 */
#[AutowiredService]
final class WorkerRunner
{

	public function __construct(
		private FileAnalyser $fileAnalyser,
		private RuleRegistry $ruleRegistry,
		private CollectorRegistry $collectorRegistry,
		private NodeScopeResolver $nodeScopeResolver,
		#[AutowiredParameter(ref: '%parallel.buffer%')]
		private int $decoderBufferSize,
	)
	{
	}

	/**
	 * @param string[] $analysedFiles the full analysed-files list (raw, before tmp-file substitution)
	 * @return int exit code (0 on success, 1 if a worker-communication error occurred)
	 */
	public function run(
		OutputInterface $output,
		array $analysedFiles,
		int $port,
		string $identifier,
		?string $tmpFile,
		?string $insteadOfFile,
	): int
	{
		$analysedFiles = $this->switchTmpFile($analysedFiles, $insteadOfFile, $tmpFile);
		$this->nodeScopeResolver->setAnalysedFiles($analysedFiles);
		$analysedFiles = array_fill_keys($analysedFiles, true);

		// Always a fresh event loop: in a forked child the parent's inherited
		// loop must never be touched.
		$loop = new StreamSelectLoop();
		$errorCount = 0;

		$tcpConnector = new TcpConnector($loop);
		$tcpConnector->connect(sprintf('127.0.0.1:%d', $port))->then(function (ConnectionInterface $connection) use ($identifier, $output, $analysedFiles, $tmpFile, $insteadOfFile, &$errorCount): void {
			// phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
			$jsonInvalidUtf8Ignore = defined('JSON_INVALID_UTF8_IGNORE') ? JSON_INVALID_UTF8_IGNORE : 0;
			// phpcs:enable
			$out = new Encoder($connection, $jsonInvalidUtf8Ignore);
			$in = new Decoder($connection, true, options: $jsonInvalidUtf8Ignore, maxlength: $this->decoderBufferSize);
			$out->write(['action' => 'hello', 'identifier' => $identifier]);
			$this->runWorker($out, $in, $output, $analysedFiles, $tmpFile, $insteadOfFile, $errorCount);
		});

		$loop->run();

		return $errorCount > 0 ? 1 : 0;
	}

	/**
	 * @param array<string, true> $analysedFiles
	 */
	private function runWorker(
		WritableStreamInterface $out,
		ReadableStreamInterface $in,
		OutputInterface $output,
		array $analysedFiles,
		?string $tmpFile,
		?string $insteadOfFile,
		int &$errorCount,
	): void
	{
		$handleError = static function (Throwable $error) use ($out, $output, &$errorCount): void {
			$errorCount++;
			$output->writeln(sprintf('Error: %s', $error->getMessage()));
			$out->write([
				'action' => 'result',
				'result' => [
					'errors' => [],
					'internalErrors' => [
						new InternalError(
							$error->getMessage(),
							'communicating with main process in parallel worker',
							InternalError::prepareTrace($error),
							$error->getTraceAsString(),
							shouldReportBug: true,
						),
					],
					'filteredPhpErrors' => [],
					'allPhpErrors' => [],
					'locallyIgnoredErrors' => [],
					'linesToIgnore' => [],
					'unmatchedLineIgnores' => [],
					'collectedData' => [],
					'memoryUsage' => memory_get_peak_usage(true),
					'dependencies' => [],
					'exportedNodes' => [],
					'files' => [],
					'internalErrorsCount' => 1,
				],
			]);
			$out->end();
		};
		$out->on('error', $handleError);
		$fileAnalyser = $this->fileAnalyser;
		$ruleRegistry = $this->ruleRegistry;
		$collectorRegistry = $this->collectorRegistry;
		$in->on('data', static function (array $json) use ($fileAnalyser, $ruleRegistry, $collectorRegistry, $out, $analysedFiles, $tmpFile, $insteadOfFile): void {
			$action = $json['action'];
			if ($action !== 'analyse') {
				return;
			}

			$internalErrorsCount = 0;
			$files = $json['files'];
			$errors = [];
			$internalErrors = [];
			$filteredPhpErrors = [];
			$allPhpErrors = [];
			$locallyIgnoredErrors = [];
			$linesToIgnore = [];
			$unmatchedLineIgnores = [];
			$collectedData = [];
			$dependencies = [];
			$usedTraitDependencies = [];
			$exportedNodes = [];
			$processedFiles = [];
			foreach ($files as $file) {
				try {
					if ($file === $insteadOfFile) {
						$file = $tmpFile;
					}
					$fileAnalyserResult = $fileAnalyser->analyseFile($file, $analysedFiles, $ruleRegistry, $collectorRegistry, null);
					$fileErrors = $fileAnalyserResult->getErrors();
					$filteredPhpErrors = array_merge($filteredPhpErrors, $fileAnalyserResult->getFilteredPhpErrors());
					$allPhpErrors = array_merge($allPhpErrors, $fileAnalyserResult->getAllPhpErrors());
					$linesToIgnore[$file] = $fileAnalyserResult->getLinesToIgnore();
					$unmatchedLineIgnores[$file] = $fileAnalyserResult->getUnmatchedLineIgnores();
					$dependencies[$file] = $fileAnalyserResult->getDependencies();
					$usedTraitDependencies[$file] = $fileAnalyserResult->getUsedTraitDependencies();
					$exportedNodes[$file] = $fileAnalyserResult->getExportedNodes();
					$processedFiles = array_merge($processedFiles, $fileAnalyserResult->getProcessedFiles());
					foreach ($fileErrors as $fileError) {
						$errors[] = $fileError;
					}
					foreach ($fileAnalyserResult->getLocallyIgnoredErrors() as $locallyIgnoredError) {
						$locallyIgnoredErrors[] = $locallyIgnoredError;
					}
					foreach ($fileAnalyserResult->getCollectedData() as $collectedFile => $dataPerCollector) {
						foreach ($dataPerCollector as $collectorType => $collectorData) {
							foreach ($collectorData as $data) {
								$collectedData[$collectedFile][$collectorType][] = $data;
							}
						}
					}
				} catch (Throwable $t) {
					$internalErrorsCount++;
					$internalErrors[] = new InternalError(
						$t->getMessage(),
						sprintf('analysing file %s', $file),
						InternalError::prepareTrace($t),
						$t->getTraceAsString(),
						shouldReportBug: true,
					);
				}
			}

			$out->write([
				'action' => 'result',
				'result' => [
					'errors' => $errors,
					'internalErrors' => $internalErrors,
					'filteredPhpErrors' => $filteredPhpErrors,
					'allPhpErrors' => $allPhpErrors,
					'locallyIgnoredErrors' => $locallyIgnoredErrors,
					'linesToIgnore' => $linesToIgnore,
					'unmatchedLineIgnores' => $unmatchedLineIgnores,
					'collectedData' => $collectedData,
					'memoryUsage' => memory_get_peak_usage(true),
					'dependencies' => $dependencies,
					'usedTraitDependencies' => $usedTraitDependencies,
					'exportedNodes' => $exportedNodes,
					'files' => $files,
					'processedFiles' => $processedFiles,
					'internalErrorsCount' => $internalErrorsCount,
				]]);
		});
		$in->on('error', $handleError);
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
