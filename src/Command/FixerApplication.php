<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;
use Phar;
use PHPStan\Analyser\InternalError;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\File\FileMonitor;
use PHPStan\File\FileMonitorFactory;
use PHPStan\File\FileMonitorResult;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Internal\DirectoryCreator;
use PHPStan\Internal\DirectoryCreatorException;
use PHPStan\Internal\HttpClientFactory;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\Process\ProcessCrashedException;
use PHPStan\Process\ProcessHelper;
use PHPStan\Process\SpawnedProcessPromise;
use PHPStan\ShouldNotHappenException;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Promise\PromiseInterface;
use React\Socket\ConnectionInterface;
use React\Socket\TcpServer;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function array_merge;
use function count;
use function defined;
use function escapeshellarg;
use function get_class;
use function http_build_query;
use function ini_get;
use function is_file;
use function microtime;
use function min;
use function parse_url;
use function sprintf;
use function unlink;
use const JSON_INVALID_UTF8_IGNORE;
use const PHP_BINARY;
use const PHP_URL_PORT;
use const PHP_VERSION_ID;

#[AutowiredService]
final class FixerApplication
{

	/**
	 * A burst of changes, like a git checkout or a coding standard fixer at work,
	 * is analysed once it is over, not as soon as its first file changes.
	 */
	private const CHANGES_QUIET_SECONDS = 0.5;

	/** Changes that never stop for that long are analysed this long after the first one anyway. */
	private const CHANGES_WAIT_SECONDS_LIMIT = 2.0;

	/** @var PromiseInterface<string>|null  */
	private PromiseInterface|null $processInProgress = null;

	/** Files changed during the analysis in progress, another one starts once it ends. */
	private bool $analysisRequested = false;

	private bool $fileMonitorActive = true;

	/** @var string[] watched files besides the analysed ones and the config files */
	private array $watchedFilesWithoutConfig = [];

	/**
	 * @param string[] $analysedPaths
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $allConfigFiles
	 * @param string[] $bootstrapFiles
	 */
	public function __construct(
		private FileMonitorFactory $fileMonitorFactory,
		private StubFilesProvider $stubFilesProvider,
		#[AutowiredParameter]
		private array $analysedPaths,
		#[AutowiredParameter]
		private string $currentWorkingDirectory,
		#[AutowiredParameter(ref: '%pro.tmpDir%')]
		private string $proTmpDir,
		#[AutowiredParameter]
		private array $composerAutoloaderProjectPaths,
		#[AutowiredParameter]
		private array $allConfigFiles,
		#[AutowiredParameter]
		private ?string $cliAutoloadFile,
		#[AutowiredParameter]
		private array $bootstrapFiles,
		#[AutowiredParameter]
		private ?string $editorUrl,
		#[AutowiredParameter]
		private string $usedLevel,
		private HttpClientFactory $httpClientFactory,
	)
	{
	}

	private ?FileMonitor $fileMonitor = null;

	public function run(
		InceptionResult $inceptionResult,
		InputInterface $input,
		OutputInterface $output,
		int $filesCount,
		string $mainScript,
	): int
	{
		$projectConfigFile = $inceptionResult->getProjectConfigFile();

		$loop = new StreamSelectLoop();
		$server = new TcpServer('127.0.0.1:0', $loop);
		/** @var string $serverAddress */
		$serverAddress = $server->getAddress();

		/** @var int<0, 65535> $serverPort */
		$serverPort = parse_url($serverAddress, PHP_URL_PORT);

		$server->on('connection', function (ConnectionInterface $connection) use ($loop, $projectConfigFile, $input, $output, $mainScript, $filesCount): void {
			// phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
			$jsonInvalidUtf8Ignore = defined('JSON_INVALID_UTF8_IGNORE') ? JSON_INVALID_UTF8_IGNORE : 0;
			// phpcs:enable
			$decoder = new Decoder($connection, true, options: $jsonInvalidUtf8Ignore, maxlength: 128 * 1024 * 1024);
			$encoder = new Encoder($connection, $jsonInvalidUtf8Ignore);
			$encoder->write(['action' => 'initialData', 'data' => [
				'currentWorkingDirectory' => $this->currentWorkingDirectory,
				'analysedPaths' => $this->analysedPaths,
				'projectConfigFile' => $projectConfigFile,
				'filesCount' => $filesCount,
				'phpstanVersion' => ComposerHelper::getPhpStanVersion(),
				'editorUrl' => $this->editorUrl,
				'ruleLevel' => $this->usedLevel,
			]]);
			$decoder->on('data', function (array $data) use (
				$output,
			): void {
				if ($data['action'] === 'webPort') {
					$output->writeln(sprintf('Open your web browser at: <fg=cyan>http://127.0.0.1:%d</>', $data['data']['port']));
					$output->writeln('Press [Ctrl-C] to quit.');
					return;
				}
				if ($data['action'] === 'resumeFileMonitor') {
					$this->fileMonitorActive = true;
					return;
				}
				if ($data['action'] === 'pauseFileMonitor') {
					$this->fileMonitorActive = false;
					return;
				}
			});

			$this->watchedFilesWithoutConfig = array_merge(
				$this->getComposerLocks(),
				$this->getComposerInstalled(),
				$this->getExecutedFiles(),
				$this->getStubFiles(),
			);
			$this->fileMonitor = $this->fileMonitorFactory->create(array_merge($this->watchedFilesWithoutConfig, $this->allConfigFiles));

			$this->analyse(
				$loop,
				$mainScript,
				$projectConfigFile,
				$input,
				$encoder,
			);

			$this->monitorFileChanges($loop, static function (FileMonitorResult $changes) use ($encoder): void {
				if (count($changes->getChangedFiles()) === 0) {
					return;
				}

				$encoder->write(['action' => 'changedFiles', 'data' => [
					'paths' => $changes->getChangedFiles(),
				]]);
			}, function () use ($loop, $mainScript, $projectConfigFile, $input, $encoder): void {
				// The analysis in progress is not stopped: what it saves to the result cache makes the next
				// one cheaper, and that one starts once it ends, for everything that changed meanwhile.
				if ($this->processInProgress !== null) {
					$this->analysisRequested = true;
					return;
				}

				$this->analyse(
					$loop,
					$mainScript,
					$projectConfigFile,
					$input,
					$encoder,
				);
			});
		});

		try {
			$fixerProcess = $this->getFixerProcess($output, $serverPort);
		} catch (FixerProcessException) {
			return 1;
		}

		$fixerProcess->start($loop);
		$fixerProcess->on('exit', function ($exitCode) use ($output, $loop): void {
			$loop->stop();
			if ($exitCode === null) {
				return;
			}
			if ($exitCode === 0) {
				return;
			}
			$output->writeln(sprintf('<fg=red>PHPStan Pro process exited with code %d.</>', $exitCode));
			@unlink($this->proTmpDir . '/phar-info.json');
		});

		$loop->run();

		return 0;
	}

	/**
	 * @throws FixerProcessException
	 */
	private function getFixerProcess(OutputInterface $output, int $serverPort): Process
	{
		try {
			DirectoryCreator::ensureDirectoryExists($this->proTmpDir, 0777);
		} catch (DirectoryCreatorException $e) {
			$output->writeln($e->getMessage());
			throw new FixerProcessException();
		}

		$pharPath = $this->proTmpDir . '/phpstan-fixer.phar';
		$infoPath = $this->proTmpDir . '/phar-info.json';

		try {
			$this->downloadPhar($output, $pharPath, $infoPath);
		} catch (GuzzleException $e) {
			if (!is_file($pharPath)) {
				$this->printDownloadError($output, $e);

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
			$output->writeln(sprintf('%s: %s', get_class($e), $e->getMessage()));

			throw new FixerProcessException();
		}

		if ($phar->getSignature()['hash_type'] !== 'OpenSSL') {
			@unlink($pharPath);
			@unlink($infoPath);
			$output->writeln('<fg=red>PHPStan Pro PHAR signature is corrupted.</>');
			$output->writeln(sprintf('Wrong hash type: %s', $phar->getSignature()['hash_type']));

			throw new FixerProcessException();
		}

		$env = Environment::getCleanedArray();
		$env['PHPSTAN_PRO_TMP_DIR'] = $this->proTmpDir;
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
				$output->writeln(sprintf('   <fg=cyan>-v ~/.phpstan-pro:%s</>', $this->proTmpDir));
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
				$output->writeln(sprintf('   <fg=cyan>-v ~/phpstan-pro:%s</>', $this->proTmpDir));
				$output->writeln('');
			}
		}

		return new Process(sprintf('%s -d memory_limit=%s %s --port %d', escapeshellarg(PHP_BINARY), escapeshellarg(ini_get('memory_limit')), escapeshellarg($pharPath), $serverPort), env: $env, fds: []);
	}

	/**
	 * @throws GuzzleException
	 */
	private function downloadPhar(
		OutputInterface $output,
		string $pharPath,
		string $infoPath,
	): void
	{
		$currentVersion = null;
		$branch = '2.2.x';
		if (is_file($pharPath) && is_file($infoPath)) {
			/** @var array{version: string, date: string, branch?: string} $currentInfo */
			$currentInfo = Json::decode(FileReader::read($infoPath), Json::FORCE_ARRAY);
			$currentVersion = $currentInfo['version'];
			$currentBranch = $currentInfo['branch'] ?? 'master';
			$currentDate = DateTime::createFromFormat(DateTime::ATOM, $currentInfo['date']);
			if ($currentDate === false) {
				throw new ShouldNotHappenException();
			}
			if (
				$currentBranch === $branch
				&& (new DateTimeImmutable('', new DateTimeZone('UTC'))) <= $currentDate->modify('+24 hours')
			) {
				return;
			}

			$output->writeln('<fg=green>Checking if there\'s a new PHPStan Pro release...</>');
		}

		$client = $this->httpClientFactory->createClient([]);

		$latestUrl = sprintf('https://fixer-download-api.phpstan.com/latest?%s', http_build_query(['phpVersion' => PHP_VERSION_ID, 'branch' => $branch]));

		/**
		 * @var array{url: string, version: string} $latestInfo
		 */
		$latestInfo = Json::decode($client->get($latestUrl)->getBody()->getContents(), Json::FORCE_ARRAY);
		if ($currentVersion !== null && $latestInfo['version'] === $currentVersion) {
			$this->writeInfoFile($infoPath, $latestInfo['version'], $branch);
			$output->writeln('<fg=green>You\'re running the latest PHPStan Pro!</>');
			return;
		}

		$output->writeln('<fg=green>Downloading the latest PHPStan Pro...</>');

		$progressBar = new ProgressBar($output);
		$bytes = 0;

		$client->get($latestInfo['url'], [
			RequestOptions::SINK => $pharPath,
			RequestOptions::TIMEOUT => 120,
			RequestOptions::PROGRESS => static function (int $downloadTotal, int $downloadedBytes) use ($progressBar, &$bytes): void {
				if ($downloadTotal === 0) {
					return;
				}

				if ($progressBar->getMaxSteps() === 0) {
					$progressBar->setFormat('file_download');
					$progressBar->setMessage(sprintf('%.2f MB', $downloadTotal / 1000000), 'fileSize');
					$progressBar->start($downloadTotal);
				}

				if ($downloadedBytes <= $bytes) {
					return;
				}

				$bytes = $downloadedBytes;
				$progressBar->setProgress($bytes);
			},
		]);

		$progressBar->finish();
		$output->writeln('');
		$output->writeln('');

		$this->writeInfoFile($infoPath, $latestInfo['version'], $branch);
	}

	private function printDownloadError(OutputInterface $output, Throwable $e): void
	{
		$output->writeln(sprintf('<fg=red>Could not download the PHPStan Pro executable:</> %s', $e->getMessage()));
		$output->writeln('');
	}

	private function writeInfoFile(string $infoPath, string $version, string $branch): void
	{
		FileWriter::write($infoPath, Json::encode([
			'version' => $version,
			'branch' => $branch,
			'date' => (new DateTimeImmutable('', new DateTimeZone('UTC')))->format(DateTime::ATOM),
		]));
	}

	/**
	 * @param callable(FileMonitorResult): void $hasChangesCallback called for every poll with changes, the code shown can follow them right away
	 * @param callable(): void $changesSettledCallback called once the changes stop, to analyse them
	 */
	private function monitorFileChanges(LoopInterface $loop, callable $hasChangesCallback, callable $changesSettledCallback): void
	{
		if ($this->fileMonitor === null) {
			throw new ShouldNotHappenException();
		}
		$fileMonitor = $this->fileMonitor;
		$interval = $fileMonitor->getPollInterval();
		$firstChangeAt = null;
		$lastChangeAt = null;
		$callback = function () use (&$callback, &$firstChangeAt, &$lastChangeAt, $loop, $hasChangesCallback, $changesSettledCallback, $fileMonitor, $interval): void {
			if (!$this->fileMonitorActive) {
				$loop->addTimer($interval, $callback);
				return;
			}
			$changes = $fileMonitor->getChanges();
			$now = microtime(true);

			if ($changes->hasAnyChanges()) {
				$firstChangeAt ??= $now;
				$lastChangeAt = $now;
				$hasChangesCallback($changes);
			}

			if ($firstChangeAt === null || $lastChangeAt === null) {
				$loop->addTimer($interval, $callback);
				return;
			}

			if ($now - $lastChangeAt >= self::CHANGES_QUIET_SECONDS || $now - $firstChangeAt >= self::CHANGES_WAIT_SECONDS_LIMIT) {
				$firstChangeAt = null;
				$lastChangeAt = null;
				$changesSettledCallback();
				$loop->addTimer($interval, $callback);
				return;
			}

			// the monitor may poll less often than this, the end of the changes is noticed by the next poll
			$loop->addTimer(min($interval, self::CHANGES_QUIET_SECONDS), $callback);
		};
		$loop->addTimer($interval, $callback);
	}

	private function analyse(
		LoopInterface $loop,
		string $mainScript,
		?string $projectConfigFile,
		InputInterface $input,
		Encoder $phpstanFixerEncoder,
	): void
	{
		// TCP server for fixer:worker (TCP client)
		$server = new TcpServer('127.0.0.1:0', $loop);
		/** @var string $serverAddress */
		$serverAddress = $server->getAddress();
		/** @var int<0, 65535> $serverPort */
		$serverPort = parse_url($serverAddress, PHP_URL_PORT);

		$server->on('connection', function (ConnectionInterface $connection) use ($phpstanFixerEncoder): void {
			// phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
			$jsonInvalidUtf8Ignore = defined('JSON_INVALID_UTF8_IGNORE') ? JSON_INVALID_UTF8_IGNORE : 0;
			// phpcs:enable
			$decoder = new Decoder($connection, true, options: $jsonInvalidUtf8Ignore, maxlength: 128 * 1024 * 1024);
			$decoder->on('data', function (array $data) use ($phpstanFixerEncoder): void {
				// the config files of this analysis, the config may include other files than at the start
				if ($data['action'] === 'watchedConfigFiles') {
					if ($this->fileMonitor === null) {
						throw new ShouldNotHappenException();
					}
					$this->fileMonitor->watchFiles(array_merge($this->watchedFilesWithoutConfig, $data['result']['files']));
					return;
				}

				$phpstanFixerEncoder->write($data);
			});
		});

		// Always a fresh process, never a fork of this one: this process runs as long as
		// PHPStan Pro does, so its container and config are the ones it started with.
		// A spawned fixer:worker reads the config files again, which picks up edits
		// like errors added to the baseline. It still forks its own analysis workers.
		$process = new SpawnedProcessPromise($loop, ProcessHelper::getWorkerCommand(
			$mainScript,
			'fixer:worker',
			$projectConfigFile,
			[
				'--server-port',
				(string) $serverPort,
			],
			$input,
		));
		$this->processInProgress = $process->run();

		$this->processInProgress->then(static function () use ($server): void {
			$server->close();
		}, static function (Throwable $e) use ($server, $phpstanFixerEncoder): void {
			$server->close();

			if ($e instanceof ProcessCrashedException) {
				$message = 'Analysis crashed';
				$traceAsString = $e->getMessage();
				$trace = [];
			} else {
				$message = $e->getMessage();
				$traceAsString = $e->getTraceAsString();
				$trace = InternalError::prepareTrace($e);
			}
			$phpstanFixerEncoder->write(['action' => 'analysisCrash', 'data' => [
				'internalErrors' => [new InternalError(
					$message,
					'running PHPStan Pro worker',
					$trace,
					$traceAsString,
					shouldReportBug: false,
				)],
			]]);
		})->finally(function () use ($loop, $mainScript, $projectConfigFile, $input, $phpstanFixerEncoder): void {
			$this->processInProgress = null;
			if (!$this->analysisRequested) {
				return;
			}

			$this->analysisRequested = false;
			$this->analyse($loop, $mainScript, $projectConfigFile, $input, $phpstanFixerEncoder);
		});
	}

	private function isDockerRunning(): bool
	{
		return is_file('/.dockerenv');
	}

	/**
	 * @return list<string>
	 */
	private function getComposerLocks(): array
	{
		$locks = [];
		foreach ($this->composerAutoloaderProjectPaths as $autoloadPath) {
			$lockPath = $autoloadPath . '/composer.lock';
			if (!is_file($lockPath)) {
				continue;
			}

			$locks[] = $lockPath;
		}

		return $locks;
	}

	/**
	 * @return list<string>
	 */
	private function getComposerInstalled(): array
	{
		$files = [];
		foreach ($this->composerAutoloaderProjectPaths as $autoloadPath) {
			$composer = ComposerHelper::getComposerConfig($autoloadPath);
			if ($composer === null) {
				continue;
			}

			$filePath = ComposerHelper::getVendorDirFromComposerConfig($autoloadPath, $composer) . '/composer/installed.php';
			if (!is_file($filePath)) {
				continue;
			}

			$files[] = $filePath;
		}

		return $files;
	}

	/**
	 * @return list<string>
	 */
	private function getExecutedFiles(): array
	{
		$files = [];
		if ($this->cliAutoloadFile !== null) {
			$files[] = $this->cliAutoloadFile;
		}

		foreach ($this->bootstrapFiles as $bootstrapFile) {
			$files[] = $bootstrapFile;
		}

		return $files;
	}

	/**
	 * @return list<string>
	 */
	private function getStubFiles(): array
	{
		$stubFiles = [];
		foreach ($this->stubFilesProvider->getProjectStubFiles() as $stubFile) {
			$stubFiles[] = $stubFile;
		}

		return $stubFiles;
	}

}
