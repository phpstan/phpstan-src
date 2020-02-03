<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use Nette\Utils\Random;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoredErrorHelper;
use PHPStan\Command\AnalyseCommand;
use React\EventLoop\StreamSelectLoop;
use React\Socket\ConnectionInterface;
use Symfony\Component\Console\Input\InputInterface;
use function escapeshellarg;
use function parse_url;

class ParallelAnalyser
{

	/** @var IgnoredErrorHelper */
	private $ignoredErrorHelper;

	/** @var int */
	private $internalErrorsCountLimit;

	/** @var ProcessPool */
	private $processPool;

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

		$numberOfProcesses = $schedule->getNumberOfProcesses();
		$errors = [];
		$internalErrors = [];
		$hasInferrablePropertyTypesFromConstructor = false;

		$server = new \React\Socket\TcpServer('127.0.0.1:0', $loop);
		$this->processPool = new ProcessPool($server);
		$server->on('connection', function (ConnectionInterface $connection) use (&$jobs): void {
			$decoder = new Decoder($connection, true, 512, 0, 4 * 1024 * 1024);
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

		for ($i = 0; $i < $numberOfProcesses; $i++) {
			if (count($jobs) === 0) {
				break;
			}

			$processIdentifier = Random::generate();
			$process = new Process($this->getWorkerCommand(
				$mainScript,
				$projectConfigFile,
				$serverPort,
				$processIdentifier,
				$input
			), $loop);
			$process->start(function (array $json) use ($process, &$internalErrors, &$errors, &$jobs, $postFileCallback, &$hasInferrablePropertyTypesFromConstructor, &$internalErrorsCount, &$reachedInternalErrorsCountLimit, $processIdentifier): void {
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
					$this->processPool->quitAll();
				}

				if (count($jobs) === 0) {
					$this->processPool->quitProcess($processIdentifier);
					return;
				}

				$job = array_pop($jobs);
				$process->request(['action' => 'analyse', 'files' => $job]);
			}, $handleError, function ($exitCode, string $stdErr) use (&$internalErrors, $processIdentifier): void {
				$this->processPool->tryQuitProcess($processIdentifier);
				if ($exitCode === 0) {
					return;
				}
				if ($exitCode === null) {
					return;
				}

				$internalErrors[] = sprintf('Child process error: %s', $stdErr);
			});
			$this->processPool->attachProcess($processIdentifier, $process);
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
		int $port,
		string $identifier,
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

		$processCommandArray[] = sprintf('--port');
		$processCommandArray[] = $port;

		$processCommandArray[] = sprintf('--identifier');
		$processCommandArray[] = escapeshellarg($identifier);

		/** @var string[] $paths */
		$paths = $input->getArgument('paths');
		foreach ($paths as $path) {
			$processCommandArray[] = escapeshellarg($path);
		}

		return implode(' ', $processCommandArray);
	}

}
