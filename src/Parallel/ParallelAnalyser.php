<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use Closure;
use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use Nette\Utils\Random;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;
use PHPStan\Dependency\ExportedNode;
use PHPStan\Internal\ConsumptionTrackingCollector;
use PHPStan\Internal\FileConsumptionTracker;
use PHPStan\Process\ProcessHelper;
use React\EventLoop\StreamSelectLoop;
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
use function is_string;
use function max;
use function parse_url;
use function sprintf;
use const PHP_URL_PORT;

class ParallelAnalyser
{

	private const DEFAULT_TIMEOUT = 600.0;

	private float $processTimeout;

	private ProcessPool $processPool;

	private ?ConsumptionTrackingCollector $consumptionTrackingCollector = null;

	public function __construct(
		private int $internalErrorsCountLimit,
		float $processTimeout,
		private int $decoderBufferSize,
	)
	{
		$this->processTimeout = max($processTimeout, self::DEFAULT_TIMEOUT);
	}

	/**
	 * @param Closure(int ): void|null $postFileCallback
	 */
	public function analyse(
		Schedule $schedule,
		string $mainScript,
		?Closure $postFileCallback,
		?string $projectConfigFile,
		?string $tmpFile,
		?string $insteadOfFile,
		InputInterface $input,
		?ConsumptionTrackingCollector $consumptionTrackingCollector,
	): AnalyserResult
	{
		$jobs = array_reverse($schedule->getJobs());
		$loop = new StreamSelectLoop();

		$this->consumptionTrackingCollector = $consumptionTrackingCollector;
		$numberOfProcesses = $schedule->getNumberOfProcesses();
		$errors = [];
		$internalErrors = [];

		$server = new TcpServer('127.0.0.1:0', $loop);
		$this->processPool = new ProcessPool($server);
		$server->on('connection', function (ConnectionInterface $connection) use (&$jobs): void {
			// phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
			$jsonInvalidUtf8Ignore = defined('JSON_INVALID_UTF8_IGNORE') ? JSON_INVALID_UTF8_IGNORE : 0;
			// phpcs:enable
			$decoder = new Decoder($connection, true, 512, $jsonInvalidUtf8Ignore, $this->decoderBufferSize);
			$encoder = new Encoder($connection, $jsonInvalidUtf8Ignore);
			$decoder->on('data', function (array $data) use (&$jobs, $decoder, $encoder): void {
				if ($data['action'] !== 'hello') {
					return;
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

		/** @var int $serverPort */
		$serverPort = parse_url($serverAddress, PHP_URL_PORT);

		$internalErrorsCount = 0;

		$reachedInternalErrorsCountLimit = false;

		$handleError = function (Throwable $error) use (&$internalErrors, &$internalErrorsCount, &$reachedInternalErrorsCountLimit): void {
			$internalErrors[] = sprintf('Internal error: ' . $error->getMessage());
			$internalErrorsCount++;
			$reachedInternalErrorsCountLimit = true;
			$this->processPool->quitAll();
		};

		$dependencies = [];
		$exportedNodes = [];
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

			if ($tmpFile !== null && $insteadOfFile !== null) {
				$commandOptions[] = '--tmp-file';
				$commandOptions[] = escapeshellarg($tmpFile);
				$commandOptions[] = '--instead-of';
				$commandOptions[] = escapeshellarg($insteadOfFile);
			}

			if ($consumptionTrackingCollector !== null) {
				$commandOptions[] = '--track-consumption';
			}

			$process = new Process(ProcessHelper::getWorkerCommand(
				$mainScript,
				'worker',
				$projectConfigFile,
				$commandOptions,
				$input,
			), $loop, $this->processTimeout);
			$process->start(function (array $json) use ($process, &$internalErrors, &$errors, &$dependencies, &$exportedNodes, &$jobs, $postFileCallback, &$internalErrorsCount, &$reachedInternalErrorsCountLimit, $processIdentifier): void {
				foreach ($json['errors'] as $jsonError) {
					if (is_string($jsonError)) {
						$internalErrors[] = sprintf('Internal error: %s', $jsonError);
						continue;
					}

					$errors[] = Error::decode($jsonError);
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
				 * @var array<mixed[]> $fileExportedNodes
				 */
				foreach ($json['exportedNodes'] as $file => $fileExportedNodes) {
					if (count($fileExportedNodes) === 0) {
						continue;
					}
					$exportedNodes[$file] = array_map(static function (array $node): ExportedNode {
						$class = $node['type'];

						return $class::decode($node['data']);
					}, $fileExportedNodes);
				}

				if ($this->consumptionTrackingCollector !== null) {
					/**
					 * @var array{"file": string, "timeConsumed": float, "memoryConsumed": int, "totalMemoryConsumed": int} $consumptionData
					 */
					foreach ($json['consumptionData'] as $consumptionData) {
						$this->consumptionTrackingCollector->addConsumption(
							FileConsumptionTracker::createFromArray($consumptionData),
						);
					}
				}

				if ($postFileCallback !== null) {
					$postFileCallback($json['filesCount']);
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
			}, $handleError, function ($exitCode, string $output) use (&$internalErrors, &$internalErrorsCount, $processIdentifier): void {
				$this->processPool->tryQuitProcess($processIdentifier);
				if ($exitCode === 0) {
					return;
				}
				if ($exitCode === null) {
					return;
				}

				$internalErrors[] = sprintf('Child process error (exit code %d): %s', $exitCode, $output);
				$internalErrorsCount++;
			});
			$this->processPool->attachProcess($processIdentifier, $process);
		}

		$loop->run();

		if (count($jobs) > 0 && $internalErrorsCount === 0) {
			$internalErrors[] = 'Some parallel worker jobs have not finished.';
			$internalErrorsCount++;
		}

		return new AnalyserResult(
			$errors,
			$internalErrors,
			$internalErrorsCount === 0 ? $dependencies : null,
			$exportedNodes,
			$reachedInternalErrorsCountLimit,
		);
	}

}
