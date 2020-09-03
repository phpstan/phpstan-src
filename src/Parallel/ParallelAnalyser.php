<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use Nette\Utils\Random;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;
use PHPStan\Dependency\ExportedNode;
use PHPStan\Process\ProcessHelper;
use React\EventLoop\StreamSelectLoop;
use React\Socket\ConnectionInterface;
use Symfony\Component\Console\Input\InputInterface;
use function parse_url;

class ParallelAnalyser
{

	private int $internalErrorsCountLimit;

	private float $processTimeout;

	private ProcessPool $processPool;

	private int $decoderBufferSize;

	public function __construct(
		int $internalErrorsCountLimit,
		float $processTimeout,
		int $decoderBufferSize
	)
	{
		$this->internalErrorsCountLimit = $internalErrorsCountLimit;
		$this->processTimeout = $processTimeout;
		$this->decoderBufferSize = $decoderBufferSize;
	}

	/**
	 * @param Schedule $schedule
	 * @param string $mainScript
	 * @param \Closure(int): void|null $postFileCallback
	 * @param string|null $projectConfigFile
	 * @return AnalyserResult
	 */
	public function analyse(
		Schedule $schedule,
		string $mainScript,
		?\Closure $postFileCallback,
		?string $projectConfigFile,
		InputInterface $input
	): AnalyserResult
	{
		$jobs = array_reverse($schedule->getJobs());
		$loop = new StreamSelectLoop();

		$numberOfProcesses = $schedule->getNumberOfProcesses();
		$errors = [];
		$internalErrors = [];

		$server = new \React\Socket\TcpServer('127.0.0.1:0', $loop);
		$this->processPool = new ProcessPool($server);
		$server->on('connection', function (ConnectionInterface $connection) use (&$jobs): void {
			$decoder = new Decoder($connection, true, 512, 0, $this->decoderBufferSize);
			$encoder = new Encoder($connection);
			$decoder->on('data', function (array $data) use (&$jobs, $decoder, $encoder): void {
				if ($data['action'] !== 'hello') {
					return;
				}

				$identifier = $data['identifier'];
				$process = $this->processPool->getProcess($identifier);
				$process->bindConnection($decoder, $encoder);
				if (count($jobs) === 0) {
					$this->processPool->quitProcess($identifier);
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

		// todo should probably differentiate between not showing unmatched ignores + showing "Internal error limit reached..."
		$reachedInternalErrorsCountLimit = false;

		$handleError = function (\Throwable $error) use (&$internalErrors, &$internalErrorsCount, &$reachedInternalErrorsCountLimit): void {
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
			$process = new Process(ProcessHelper::getWorkerCommand(
				$mainScript,
				'worker',
				$projectConfigFile,
				[
					'--port',
					(string) $serverPort,
					'--identifier',
					$processIdentifier,
				],
				$input
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
				 * @var array<ExportedNode> $fileExportedNodes
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

				if ($postFileCallback !== null) {
					$postFileCallback($json['filesCount']);
				}

				$internalErrorsCount += $json['internalErrorsCount'];
				if ($internalErrorsCount >= $this->internalErrorsCountLimit) {
					$reachedInternalErrorsCountLimit = true;
					$this->processPool->quitAll();
				}

				if (count($jobs) === 0) {
					$this->processPool->quitProcess($processIdentifier);
					return;
				}

				$job = array_pop($jobs);
				$process->request(['action' => 'analyse', 'files' => $job]);
			}, $handleError, function ($exitCode, string $output) use (&$internalErrors, $processIdentifier): void {
				$this->processPool->tryQuitProcess($processIdentifier);
				if ($exitCode === 0) {
					return;
				}
				if ($exitCode === null) {
					return;
				}

				$internalErrors[] = sprintf('Child process error (exit code %d): %s', $exitCode, $output);
			});
			$this->processPool->attachProcess($processIdentifier, $process);
		}

		$loop->run();

		if (count($jobs) > 0) {
			throw new \PHPStan\ShouldNotHappenException('Some jobs remaining');
		}

		return new AnalyserResult(
			$errors,
			$internalErrors,
			$internalErrorsCount === 0 ? $dependencies : null,
			$exportedNodes,
			$reachedInternalErrorsCountLimit
		);
	}

}
