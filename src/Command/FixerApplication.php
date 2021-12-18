<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use Composer\CaBundle\CaBundle;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Jean85\PrettyVersions;
use Nette\Utils\Json;
use OutOfBoundsException;
use Phar;
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoredErrorHelper;
use PHPStan\Analyser\ResultCache\ResultCacheClearer;
use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use PHPStan\File\CouldNotReadFileException;
use PHPStan\File\FileMonitor;
use PHPStan\File\FileMonitorResult;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use PHPStan\Parallel\Scheduler;
use PHPStan\Process\CpuCoreCounter;
use PHPStan\Process\ProcessCanceledException;
use PHPStan\Process\ProcessCrashedException;
use PHPStan\Process\ProcessHelper;
use PHPStan\Process\ProcessPromise;
use PHPStan\Process\Runnable\RunnableQueue;
use PHPStan\Process\Runnable\RunnableQueueLogger;
use PHPStan\ShouldNotHappenException;
use Psr\Http\Message\ResponseInterface;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Http\Browser;
use React\Promise\CancellablePromiseInterface;
use React\Promise\ExtendedPromiseInterface;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Socket\TcpServer;
use React\Stream\ReadableStreamInterface;
use RuntimeException;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function Clue\React\Block\await;
use function count;
use function defined;
use function escapeshellarg;
use function fclose;
use function fopen;
use function fwrite;
use function getenv;
use function ini_get;
use function is_dir;
use function is_file;
use function is_string;
use function min;
use function mkdir;
use function parse_url;
use function React\Promise\resolve;
use function sprintf;
use function strlen;
use function strpos;
use function unlink;
use const PHP_BINARY;
use const PHP_URL_PORT;

class FixerApplication
{

	private FileMonitor $fileMonitor;

	private ResultCacheManagerFactory $resultCacheManagerFactory;

	private ResultCacheClearer $resultCacheClearer;

	private IgnoredErrorHelper $ignoredErrorHelper;

	private CpuCoreCounter $cpuCoreCounter;

	private Scheduler $scheduler;

	/** @var string[] */
	private array $analysedPaths;

	/** @var (ExtendedPromiseInterface&CancellablePromiseInterface)|null */
	private $processInProgress;

	private string $currentWorkingDirectory;

	private string $fixerTmpDir;

	private int $maximumNumberOfProcesses;

	private ?string $fixerSuggestionId = null;

	/**
	 * @param string[] $analysedPaths
	 */
	public function __construct(
		FileMonitor $fileMonitor,
		ResultCacheManagerFactory $resultCacheManagerFactory,
		ResultCacheClearer $resultCacheClearer,
		IgnoredErrorHelper $ignoredErrorHelper,
		CpuCoreCounter $cpuCoreCounter,
		Scheduler $scheduler,
		array $analysedPaths,
		string $currentWorkingDirectory,
		string $fixerTmpDir,
		int $maximumNumberOfProcesses,
	)
	{
		$this->fileMonitor = $fileMonitor;
		$this->resultCacheManagerFactory = $resultCacheManagerFactory;
		$this->resultCacheClearer = $resultCacheClearer;
		$this->ignoredErrorHelper = $ignoredErrorHelper;
		$this->cpuCoreCounter = $cpuCoreCounter;
		$this->scheduler = $scheduler;
		$this->analysedPaths = $analysedPaths;
		$this->currentWorkingDirectory = $currentWorkingDirectory;
		$this->fixerTmpDir = $fixerTmpDir;
		$this->maximumNumberOfProcesses = $maximumNumberOfProcesses;
	}

	/**
	 * @param Error[] $fileSpecificErrors
	 * @param string[] $notFileSpecificErrors
	 */
	public function run(
		?string $projectConfigFile,
		InceptionResult $inceptionResult,
		InputInterface $input,
		OutputInterface $output,
		array $fileSpecificErrors,
		array $notFileSpecificErrors,
		int $filesCount,
		string $mainScript,
	): int
	{
		$loop = new StreamSelectLoop();
		$server = new TcpServer('127.0.0.1:0', $loop);
		/** @var string $serverAddress */
		$serverAddress = $server->getAddress();

		/** @var int $serverPort */
		$serverPort = parse_url($serverAddress, PHP_URL_PORT);

		$reanalyseProcessQueue = new RunnableQueue(
			new class () implements RunnableQueueLogger {

				public function log(string $message): void
				{
				}

			},
			min($this->cpuCoreCounter->getNumberOfCpuCores(), $this->maximumNumberOfProcesses),
		);

		$server->on('connection', function (ConnectionInterface $connection) use ($loop, $projectConfigFile, $input, $output, $fileSpecificErrors, $notFileSpecificErrors, $mainScript, $filesCount, $reanalyseProcessQueue, $inceptionResult): void {
			// phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
			$jsonInvalidUtf8Ignore = defined('JSON_INVALID_UTF8_IGNORE') ? JSON_INVALID_UTF8_IGNORE : 0;
			// phpcs:enable
			$decoder = new Decoder($connection, true, 512, $jsonInvalidUtf8Ignore, 128 * 1024 * 1024);
			$encoder = new Encoder($connection, $jsonInvalidUtf8Ignore);
			$encoder->write(['action' => 'initialData', 'data' => [
				'fileSpecificErrors' => $fileSpecificErrors,
				'notFileSpecificErrors' => $notFileSpecificErrors,
				'currentWorkingDirectory' => $this->currentWorkingDirectory,
				'analysedPaths' => $this->analysedPaths,
				'projectConfigFile' => $projectConfigFile,
				'filesCount' => $filesCount,
				'phpstanVersion' => $this->getPhpstanVersion(),
			]]);
			$decoder->on('data', function (array $data) use (
				$loop,
				$encoder,
				$projectConfigFile,
				$input,
				$output,
				$mainScript,
				$reanalyseProcessQueue,
				$inceptionResult
			): void {
				if ($data['action'] === 'webPort') {
					$output->writeln(sprintf('Open your web browser at: <fg=cyan>http://127.0.0.1:%d</>', $data['data']['port']));
					$output->writeln('Press [Ctrl-C] to quit.');
					return;
				}
				if ($data['action'] === 'restoreResultCache') {
					$this->fixerSuggestionId = $data['data']['fixerSuggestionId'];
				}
				if ($data['action'] !== 'reanalyse') {
					return;
				}

				$id = $data['id'];

				$this->reanalyseWithTmpFile(
					$loop,
					$inceptionResult,
					$mainScript,
					$reanalyseProcessQueue,
					$projectConfigFile,
					$data['data']['tmpFile'],
					$data['data']['insteadOfFile'],
					$data['data']['fixerSuggestionId'],
					$input,
				)->done(static function (string $output) use ($encoder, $id): void {
					$encoder->write(['id' => $id, 'response' => Json::decode($output, Json::FORCE_ARRAY)]);
				}, static function (Throwable $e) use ($encoder, $id, $output): void {
					if ($e instanceof ProcessCrashedException) {
						$output->writeln('<error>Worker process exited: ' . $e->getMessage() . '</error>');
						$encoder->write(['id' => $id, 'error' => $e->getMessage()]);
						return;
					}
					if ($e instanceof ProcessCanceledException) {
						$encoder->write(['id' => $id, 'error' => $e->getMessage()]);
						return;
					}

					$output->writeln('<error>Unexpected error: ' . $e->getMessage() . '</error>');
					$encoder->write(['id' => $id, 'error' => $e->getMessage()]);
				});
			});

			$this->fileMonitor->initialize($this->analysedPaths);
			$this->monitorFileChanges($loop, function (FileMonitorResult $changes) use ($loop, $mainScript, $projectConfigFile, $input, $encoder, $output, $reanalyseProcessQueue, $inceptionResult): void {
				$reanalyseProcessQueue->cancelAll();
				if ($this->processInProgress !== null) {
					$this->processInProgress->cancel();
					$this->processInProgress = null;
				} else {
					$encoder->write(['action' => 'analysisStart']);
				}

				$this->reanalyseAfterFileChanges(
					$loop,
					$inceptionResult,
					$mainScript,
					$projectConfigFile,
					$this->fixerSuggestionId,
					$input,
				)->done(function (array $json) use ($encoder, $changes): void {
					$this->processInProgress = null;
					$this->fixerSuggestionId = null;
					$encoder->write(['action' => 'analysisEnd', 'data' => [
						'fileSpecificErrors' => $json['fileSpecificErrors'],
						'notFileSpecificErrors' => $json['notFileSpecificErrors'],
						'filesCount' => $changes->getTotalFilesCount(),
					]]);
					$this->resultCacheClearer->clearTemporaryCaches();
				}, function (Throwable $e) use ($encoder, $output): void {
					$this->processInProgress = null;
					$this->fixerSuggestionId = null;
					$output->writeln('<error>Worker process exited: ' . $e->getMessage() . '</error>');
					$encoder->write(['action' => 'analysisCrash', 'data' => [
						'error' => $e->getMessage(),
					]]);
				});
			});
		});

		try {
			$fixerProcess = $this->getFixerProcess($output, $serverPort);
		} catch (FixerProcessException $e) {
			return 1;
		}

		$fixerProcess->start($loop);
		$fixerProcess->on('exit', static function ($exitCode) use ($output, $loop): void {
			$loop->stop();
			if ($exitCode === null) {
				return;
			}
			if ($exitCode === 0) {
				return;
			}
			$output->writeln(sprintf('<fg=red>PHPStan Pro process exited with code %d.</>', $exitCode));
		});

		$loop->run();

		return 0;
	}

	/**
	 * @throws FixerProcessException
	 */
	private function getFixerProcess(OutputInterface $output, int $serverPort): Process
	{
		if (!@mkdir($this->fixerTmpDir, 0777) && !is_dir($this->fixerTmpDir)) {
			$output->writeln(sprintf('Cannot create a temp directory %s', $this->fixerTmpDir));
			throw new FixerProcessException();
		}

		$pharPath = $this->fixerTmpDir . '/phpstan-fixer.phar';
		$infoPath = $this->fixerTmpDir . '/phar-info.json';

		try {
			$this->downloadPhar($output, $pharPath, $infoPath);
		} catch (RuntimeException $e) {
			if (!is_file($pharPath)) {
				$output->writeln('<fg=red>Could not download the PHPStan Pro executable.</>');
				$output->writeln($e->getMessage());

				throw new FixerProcessException();
			}
		}

		$pubKeyPath = $pharPath . '.pubkey';
		FileWriter::write($pubKeyPath, FileReader::read(__DIR__ . '/fixer-phar.pubkey'));

		try {
			$phar = new Phar($pharPath);
		} catch (Throwable $e) {
			@unlink($pharPath);
			@unlink($infoPath);
			$output->writeln('<fg=red>PHPStan Pro PHAR signature is corrupted.</>');

			throw new FixerProcessException();
		}

		if ($phar->getSignature()['hash_type'] !== 'OpenSSL') {
			@unlink($pharPath);
			@unlink($infoPath);
			$output->writeln('<fg=red>PHPStan Pro PHAR signature is corrupted.</>');

			throw new FixerProcessException();
		}

		$env = getenv();
		$forcedPort = $_SERVER['PHPSTAN_PRO_WEB_PORT'] ?? null;
		if ($forcedPort !== null) {
			$env['PHPSTAN_PRO_WEB_PORT'] = $_SERVER['PHPSTAN_PRO_WEB_PORT'];
			$isDocker = $this->isDockerRunning();
			if ($isDocker) {
				$output->writeln('Running in Docker? Don\'t forget to do these steps:');

				$output->writeln('1) Publish this port when running Docker:');
				$output->writeln(sprintf('   <fg=cyan>-p 127.0.0.1:%d:%d</>', $_SERVER['PHPSTAN_PRO_WEB_PORT'], $_SERVER['PHPSTAN_PRO_WEB_PORT']));
				$output->writeln('2) Map the temp directory to a persistent volume');
				$output->writeln('   so that you don\'t have to log in every time:');
				$output->writeln(sprintf('   <fg=cyan>-v ~/.phpstan-pro:%s</>', $this->fixerTmpDir));
				$output->writeln('');
			}
		} else {
			$isDocker = $this->isDockerRunning();
			if ($isDocker) {
				$output->writeln('Running in Docker? You need to do these steps in order to launch PHPStan Pro:');
				$output->writeln('');
				$output->writeln('1) Set the PHPSTAN_PRO_WEB_PORT environment variable in the Dockerfile:');
				$output->writeln('   <fg=cyan>ENV PHPSTAN_PRO_WEB_PORT=11111</>');
				$output->writeln('2) Expose this port in the Dockerfile:');
				$output->writeln('   <fg=cyan>EXPOSE 11111</>');
				$output->writeln('3) Publish this port when running Docker:');
				$output->writeln('   <fg=cyan>-p 127.0.0.1:11111:11111</>');
				$output->writeln('4) Map the temp directory to a persistent volume');
				$output->writeln('   so that you don\'t have to log in every time:');
				$output->writeln(sprintf('   <fg=cyan>-v ~/phpstan-pro:%s</>', $this->fixerTmpDir));
				$output->writeln('');
			}
		}

		return new Process(sprintf('%s -d memory_limit=%s %s --port %d', PHP_BINARY, escapeshellarg(ini_get('memory_limit')), escapeshellarg($pharPath), $serverPort), null, $env, []);
	}

	private function downloadPhar(
		OutputInterface $output,
		string $pharPath,
		string $infoPath,
	): void
	{
		$currentVersion = null;
		if (is_file($pharPath) && is_file($infoPath)) {
			/** @var array{version: string, date: string} $currentInfo */
			$currentInfo = Json::decode(FileReader::read($infoPath), Json::FORCE_ARRAY);
			$currentVersion = $currentInfo['version'];
			$currentDate = DateTime::createFromFormat(DateTime::ATOM, $currentInfo['date']);
			if ($currentDate === false) {
				throw new ShouldNotHappenException();
			}
			if ((new DateTimeImmutable('', new DateTimeZone('UTC'))) <= $currentDate->modify('+24 hours')) {
				return;
			}

			$output->writeln('<fg=green>Checking if there\'s a new PHPStan Pro release...</>');
		}

		$loop = new StreamSelectLoop();
		$client = new Browser(
			$loop,
			new Connector(
				$loop,
				[
					'timeout' => 5,
					'tls' => [
						'cafile' => CaBundle::getBundledCaBundlePath(),
					],
					'dns' => '1.1.1.1',
				],
			),
		);

		/** @var array{url: string, version: string} $latestInfo */
		$latestInfo = Json::decode((string) await($client->get('https://fixer-download-api.phpstan.com/latest'), $loop, 5.0)->getBody(), Json::FORCE_ARRAY); // @phpstan-ignore-line
		if ($currentVersion !== null && $latestInfo['version'] === $currentVersion) {
			$this->writeInfoFile($infoPath, $latestInfo['version']);
			$output->writeln('<fg=green>You\'re running the latest PHPStan Pro!</>');
			return;
		}

		$output->writeln('<fg=green>Downloading the latest PHPStan Pro...</>');

		$pharPathResource = fopen($pharPath, 'w');
		if ($pharPathResource === false) {
			throw new ShouldNotHappenException(sprintf('Could not open file %s for writing.', $pharPath));
		}
		$progressBar = new ProgressBar($output);
		$client->requestStreaming('GET', $latestInfo['url'])->done(static function (ResponseInterface $response) use ($progressBar, $pharPathResource): void {
			$body = $response->getBody();
			if (!$body instanceof ReadableStreamInterface) {
				throw new ShouldNotHappenException();
			}

			$totalSize = (int) $response->getHeaderLine('Content-Length');
			$progressBar->setFormat('file_download');
			$progressBar->setMessage(sprintf('%.2f MB', $totalSize / 1000000), 'fileSize');
			$progressBar->start($totalSize);

			$bytes = 0;
			$body->on('data', static function ($chunk) use ($pharPathResource, $progressBar, &$bytes): void {
				$bytes += strlen($chunk);
				fwrite($pharPathResource, $chunk);
				$progressBar->setProgress($bytes);
			});
		}, static function (Throwable $e) use ($output): void {
			$output->writeln(sprintf('<fg=red>Could not download the PHPStan Pro executable:</> %s', $e->getMessage()));
		});

		$loop->run();

		fclose($pharPathResource);

		$progressBar->finish();
		$output->writeln('');
		$output->writeln('');

		$this->writeInfoFile($infoPath, $latestInfo['version']);
	}

	private function writeInfoFile(string $infoPath, string $version): void
	{
		FileWriter::write($infoPath, Json::encode([
			'version' => $version,
			'date' => (new DateTimeImmutable('', new DateTimeZone('UTC')))->format(DateTime::ATOM),
		]));
	}

	/**
	 * @param callable(FileMonitorResult): void $hasChangesCallback
	 */
	private function monitorFileChanges(LoopInterface $loop, callable $hasChangesCallback): void
	{
		$callback = function () use (&$callback, $loop, $hasChangesCallback): void {
			$changes = $this->fileMonitor->getChanges();

			if ($changes->hasAnyChanges()) {
				$hasChangesCallback($changes);
			}

			$loop->addTimer(1.0, $callback);
		};
		$loop->addTimer(1.0, $callback);
	}

	private function reanalyseWithTmpFile(
		LoopInterface $loop,
		InceptionResult $inceptionResult,
		string $mainScript,
		RunnableQueue $runnableQueue,
		?string $projectConfigFile,
		string $tmpFile,
		string $insteadOfFile,
		string $fixerSuggestionId,
		InputInterface $input,
	): PromiseInterface
	{
		$resultCacheManager = $this->resultCacheManagerFactory->create([$insteadOfFile => $tmpFile]);
		[$inceptionFiles] = $inceptionResult->getFiles();
		$resultCache = $resultCacheManager->restore($inceptionFiles, false, false, $inceptionResult->getProjectConfigArray(), $inceptionResult->getErrorOutput());
		$schedule = $this->scheduler->scheduleWork($this->cpuCoreCounter->getNumberOfCpuCores(), $resultCache->getFilesToAnalyse());

		$process = new ProcessPromise($loop, $fixerSuggestionId, ProcessHelper::getWorkerCommand(
			$mainScript,
			'fixer:worker',
			$projectConfigFile,
			[
				'--tmp-file',
				escapeshellarg($tmpFile),
				'--instead-of',
				escapeshellarg($insteadOfFile),
				'--save-result-cache',
				escapeshellarg($fixerSuggestionId),
				'--allow-parallel',
			],
			$input,
		));

		return $runnableQueue->queue($process, $schedule->getNumberOfProcesses());
	}

	private function reanalyseAfterFileChanges(
		LoopInterface $loop,
		InceptionResult $inceptionResult,
		string $mainScript,
		?string $projectConfigFile,
		?string $fixerSuggestionId,
		InputInterface $input,
	): PromiseInterface
	{
		$ignoredErrorHelperResult = $this->ignoredErrorHelper->initialize();
		if (count($ignoredErrorHelperResult->getErrors()) > 0) {
			throw new ShouldNotHappenException();
		}

		$projectConfigArray = $inceptionResult->getProjectConfigArray();

		$resultCacheManager = $this->resultCacheManagerFactory->create([]);
		[$inceptionFiles, $isOnlyFiles] = $inceptionResult->getFiles();
		$resultCache = $resultCacheManager->restore($inceptionFiles, false, false, $projectConfigArray, $inceptionResult->getErrorOutput(), $fixerSuggestionId);
		if (count($resultCache->getFilesToAnalyse()) === 0) {
			$result = $resultCacheManager->process(
				new AnalyserResult([], [], [], [], false),
				$resultCache,
				$inceptionResult->getErrorOutput(),
				false,
				true,
			)->getAnalyserResult();
			$intermediateErrors = $ignoredErrorHelperResult->process(
				$result->getErrors(),
				$isOnlyFiles,
				$inceptionFiles,
				count($result->getInternalErrors()) > 0 || $result->hasReachedInternalErrorsCountLimit(),
			);
			$finalFileSpecificErrors = [];
			$finalNotFileSpecificErrors = [];
			foreach ($intermediateErrors as $intermediateError) {
				if (is_string($intermediateError)) {
					$finalNotFileSpecificErrors[] = $intermediateError;
					continue;
				}

				$finalFileSpecificErrors[] = $intermediateError;
			}

			return resolve([
				'fileSpecificErrors' => $finalFileSpecificErrors,
				'notFileSpecificErrors' => $finalNotFileSpecificErrors,
			]);
		}

		$options = ['--save-result-cache', '--allow-parallel'];
		if ($fixerSuggestionId !== null) {
			$options[] = '--restore-result-cache';
			$options[] = $fixerSuggestionId;
		}
		$process = new ProcessPromise($loop, 'changedFileAnalysis', ProcessHelper::getWorkerCommand(
			$mainScript,
			'fixer:worker',
			$projectConfigFile,
			$options,
			$input,
		));
		$this->processInProgress = $process->run();

		return $this->processInProgress->then(static fn (string $output): array => Json::decode($output, Json::FORCE_ARRAY));
	}

	private function getPhpstanVersion(): string
	{
		try {
			return PrettyVersions::getVersion('phpstan/phpstan')->getPrettyVersion();
		} catch (OutOfBoundsException $e) {
			return 'Version unknown';
		}
	}

	private function isDockerRunning(): bool
	{
		if (!is_file('/proc/1/cgroup')) {
			return false;
		}

		try {
			$contents = FileReader::read('/proc/1/cgroup');

			return strpos($contents, 'docker') !== false;
		} catch (CouldNotReadFileException $e) {
			return false;
		}
	}

}
