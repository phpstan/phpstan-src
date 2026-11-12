<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\CurlMultiHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\RequestOptions;
use Nette\Utils\Json;
use Nette\Utils\JsonException;
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
use Psr\Http\Message\ResponseInterface;
use React\ChildProcess\Process;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Promise\Deferred;
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
use function is_string;
use function microtime;
use function min;
use function parse_url;
use function php_uname;
use function rename;
use function sha1;
use function sprintf;
use function substr;
use function unlink;
use const JSON_INVALID_UTF8_IGNORE;
use const PHP_BINARY;
use const PHP_OS_FAMILY;
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

	/** The PHPStan Pro branch to download */
	private const PHAR_BRANCH = '2.2.x';

	/** @var PromiseInterface<string>|null  */
	private PromiseInterface|null $processInProgress = null;

	/** Files changed during the analysis in progress, another one starts once it ends. */
	private bool $analysisRequested = false;

	/**
	 * The last analysis may have read files in another version than they have now. The file monitor
	 * cannot tell when a file is back to the contents it last saw, like an edit undone between two polls.
	 */
	private bool $filesChangedWhileAnalysed = false;

	/**
	 * Nothing changed since the last analysis but the files it reported. Whatever writes to the
	 * analysed files during every analysis, like a generator in a bootstrap file, would otherwise
	 * keep the analyses going, so an analysis that was started for this cannot start another one.
	 */
	private bool $analysingOnlyFilesChangedWhileAnalysed = false;

	private bool $fileMonitorActive = true;

	/** @var string[] watched files besides the analysed ones and the config files */
	private array $watchedFilesWithoutConfig = [];

	/** Writes to the PHPStan Pro process connected now, an updated one replaces it. */
	private ?Encoder $fixerEncoder = null;

	private ?Process $fixerProcess = null;

	/** The command of the PHPStan Pro process running now, without the --web-port of a restart */
	private ?string $fixerCommand = null;

	/** @var array<string, string>|null */
	private ?array $fixerEnv = null;

	/**
	 * The process before the update, started again if the updated one exits before it connects.
	 *
	 * @var array{command: string, backupPath: string|null, info: string|null}|null
	 */
	private ?array $fixerBeforeUpdate = null;

	/** Why the update did not finish, told to the process started instead of the updated one. */
	private ?string $updateFailureAfterRestart = null;

	/** The port the PHPStan Pro process serves the web UI on, the updated one serves it on the same port. */
	private ?int $webPort = null;

	private bool $updateInProgress = false;

	/** The user asked for the update, PHPStan Pro restarts once it is downloaded; otherwise it waits for them to ask. */
	private bool $restartRequested = false;

	/** The running process was told to exit, its exit starts the updated one. */
	private bool $restarting = false;

	/** @var array{path: string, info: array{url: string, version: string}}|null the downloaded PHAR, installed once the running process exits */
	private ?array $downloadedUpdate = null;

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
			$isUpdatedProcess = $this->fixerEncoder !== null;
			$this->fixerEncoder = $encoder;
			if ($this->fixerBeforeUpdate !== null) {
				// the updated process runs, the previous PHAR is not needed anymore
				if ($this->fixerBeforeUpdate['backupPath'] !== null) {
					@unlink($this->fixerBeforeUpdate['backupPath']);
				}
				$this->fixerBeforeUpdate = null;
			}
			$encoder->write(['action' => 'initialData', 'data' => [
				'currentWorkingDirectory' => $this->currentWorkingDirectory,
				'analysedPaths' => $this->analysedPaths,
				'projectConfigFile' => $projectConfigFile,
				'filesCount' => $filesCount,
				'phpstanVersion' => ComposerHelper::getPhpStanVersion(),
				'editorUrl' => $this->editorUrl,
				'ruleLevel' => $this->usedLevel,
				// PHPStan Pro may ask for an update, see update()
				'inPlaceUpdate' => true,
			]]);
			if ($this->updateFailureAfterRestart !== null) {
				$encoder->write(['action' => 'updateFailed', 'data' => [
					'message' => $this->updateFailureAfterRestart,
					'restart' => true,
				]]);
				$this->updateFailureAfterRestart = null;
			}
			$decoder->on('data', function (array $data) use (
				$loop,
				$output,
			): void {
				if ($data['action'] === 'webPort') {
					if ($this->webPort === null) {
						$output->writeln(sprintf('Open your web browser at: <fg=cyan>http://127.0.0.1:%d</>', $data['data']['port']));
						$output->writeln('Press [Ctrl-C] to quit.');
					} elseif ($this->webPort !== $data['data']['port']) {
						$output->writeln(sprintf('PHPStan Pro moved to: <fg=cyan>http://127.0.0.1:%d</>', $data['data']['port']));
					}
					$this->webPort = $data['data']['port'];
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
				if ($data['action'] === 'update') {
					// PHPStan Pro checks for updates in the background too, those are downloaded without a restart
					$this->update($loop, $output, (bool) ($data['data']['restart'] ?? true));
					return;
				}
			});

			// an updated process starts without errors, it gets them from a new analysis
			if ($isUpdatedProcess) {
				if ($this->processInProgress !== null) {
					// the analysis in progress reports to the process that exited
					$this->analysisRequested = true;
					return;
				}

				$this->analyse($loop, $mainScript, $projectConfigFile, $input);
				return;
			}

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
			);

			$this->monitorFileChanges($loop, function (FileMonitorResult $changes): void {
				if (count($changes->getChangedFiles()) === 0) {
					return;
				}

				$this->fixerEncoder?->write(['action' => 'changedFiles', 'data' => [
					'paths' => $changes->getChangedFiles(),
				]]);
			}, function () use ($loop, $mainScript, $projectConfigFile, $input): void {
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
				);
			});
		});

		try {
			$fixerCommand = $this->getFixerCommand($output, $serverPort);
		} catch (FixerProcessException) {
			return 1;
		}

		$this->startFixerProcess($fixerCommand, null, $loop, $output, $serverPort);

		$loop->run();

		return 0;
	}

	/**
	 * @param int|null $webPort the port of the process this one replaces, its pages reconnect to it
	 */
	private function startFixerProcess(string $command, ?int $webPort, LoopInterface $loop, OutputInterface $output, int $serverPort): void
	{
		$this->fixerCommand = $command;
		if ($webPort !== null) {
			$command .= sprintf(' --web-port %d', $webPort);
		}
		$fixerProcess = new Process($command, env: $this->fixerEnv, fds: []);
		$this->fixerProcess = $fixerProcess;
		$fixerProcess->start($loop);
		$fixerProcess->on('exit', function ($exitCode) use ($output, $loop, $serverPort): void {
			if ($this->restarting) {
				$this->startUpdatedFixerProcess($loop, $output, $serverPort);
				return;
			}

			// downloaded in the background, never installed
			if ($this->downloadedUpdate !== null) {
				@unlink($this->downloadedUpdate['path']);
			}

			if ($this->fixerBeforeUpdate !== null) {
				$this->restoreFixerBeforeUpdate($exitCode, $loop, $output, $serverPort);
				return;
			}

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
	}

	/**
	 * Asked for by PHPStan Pro: downloads the latest PHPStan Pro while the running one keeps working, and reports the progress to it.
	 * Without $restart, the download waits for PHPStan Pro to ask again with it (updateReady). With it, once the download
	 * is verified, the running process tells its pages and exits, and the updated one starts on the same web port,
	 * without opening the browser: the pages reconnect to it.
	 */
	private function update(LoopInterface $loop, OutputInterface $output, bool $restart): void
	{
		if ($this->restarting) {
			return;
		}
		if ($restart) {
			$this->restartRequested = true;
		}

		if ($this->updateInProgress) {
			// the download in progress restarts PHPStan Pro once it ends, its next progress tells the pages so
			return;
		}

		if ($this->downloadedUpdate !== null) {
			if ($restart) {
				$this->restartFixer($loop, $this->downloadedUpdate['info']['version']);
			} else {
				$this->fixerEncoder?->write(['action' => 'updateReady', 'data' => [
					'version' => $this->downloadedUpdate['info']['version'],
				]]);
			}
			return;
		}

		$this->updateInProgress = true;
		$pharPath = $this->proTmpDir . '/phpstan-fixer.phar';
		$infoPath = $this->proTmpDir . '/phar-info.json';
		$currentUrl = null;
		try {
			DirectoryCreator::ensureDirectoryExists($this->proTmpDir, 0777);
		} catch (DirectoryCreatorException $e) {
			$this->failUpdate($output, $e->getMessage());
			return;
		}
		if (is_file($pharPath) && is_file($infoPath)) {
			/** @var array{url?: string} $currentInfo */
			$currentInfo = Json::decode(FileReader::read($infoPath), Json::FORCE_ARRAY);
			$currentUrl = $currentInfo['url'] ?? null;
		}

		$lastProgressAt = 0.0;
		$this->requestAsync($loop, $this->getLatestUrl(self::PHAR_BRANCH, PHP_OS_FAMILY, php_uname('m')), [])->then(static fn (ResponseInterface $response): ResponseInterface => $response, static function (Throwable $e): void {
			throw new FixerUpdateException(sprintf('Could not find the latest PHPStan Pro: %s', self::describeHttpFailure($e)));
		})->then(function (ResponseInterface $response) use ($loop, $output, $currentUrl, &$lastProgressAt): PromiseInterface {
			try {
				/** @var array{url: string, version: string} $latestInfo */
				$latestInfo = Json::decode((string) $response->getBody(), Json::FORCE_ARRAY);
			} catch (JsonException) {
				throw new FixerUpdateException('Could not find the latest PHPStan Pro: the download server answered something else than JSON.');
			}
			if ($latestInfo['url'] === $currentUrl) {
				throw new FixerUpdateException('You\'re running the latest PHPStan Pro.');
			}

			$output->writeln(sprintf('<fg=green>Downloading PHPStan Pro %s...</>', $latestInfo['version']));
			$downloadPath = sprintf('%s/phpstan-fixer-%s.phar', $this->proTmpDir, substr(sha1($latestInfo['url']), 0, 16));
			$this->fixerEncoder?->write(['action' => 'updateProgress', 'data' => [
				'version' => $latestInfo['version'],
				'downloadedBytes' => 0,
				'totalBytes' => 0,
				'restart' => $this->restartRequested,
			]]);

			return $this->requestAsync($loop, $latestInfo['url'], [
				RequestOptions::SINK => $downloadPath,
				RequestOptions::TIMEOUT => 120,
				RequestOptions::PROGRESS => function (int $downloadTotal, int $downloadedBytes) use ($latestInfo, &$lastProgressAt): void {
					// what the pages can show, not every chunk
					$now = microtime(true);
					if ($downloadTotal === 0 || ($now - $lastProgressAt < 0.1 && $downloadedBytes < $downloadTotal)) {
						return;
					}
					$lastProgressAt = $now;
					$this->fixerEncoder?->write(['action' => 'updateProgress', 'data' => [
						'version' => $latestInfo['version'],
						'downloadedBytes' => $downloadedBytes,
						'totalBytes' => $downloadTotal,
						'restart' => $this->restartRequested,
					]]);
				},
			])->then(static fn (): array => ['path' => $downloadPath, 'info' => $latestInfo], static function (Throwable $e) use ($downloadPath, $latestInfo): void {
				@unlink($downloadPath);
				throw new FixerUpdateException(sprintf('Could not download PHPStan Pro %s: %s', $latestInfo['version'], self::describeHttpFailure($e)));
			});
		})->then(function (array $downloaded) use ($loop): void {
			$this->verifyDownloadedPhar($downloaded['path']);
			$this->downloadedUpdate = $downloaded;
			$this->updateInProgress = false;
			if ($this->restartRequested) {
				$this->restartFixer($loop, $downloaded['info']['version']);
				return;
			}

			$this->fixerEncoder?->write(['action' => 'updateReady', 'data' => [
				'version' => $downloaded['info']['version'],
			]]);
		})->catch(function (Throwable $e) use ($output): void {
			$this->failUpdate($output, $e instanceof FixerUpdateException ? $e->getMessage() : sprintf('Could not update PHPStan Pro: %s', $e->getMessage()));
		});
	}

	/**
	 * A failed background check stays silent, PHPStan Pro checks again later.
	 */
	private function failUpdate(OutputInterface $output, string $message): void
	{
		$this->updateInProgress = false;
		$restart = $this->restartRequested;
		$this->restartRequested = false;
		if ($restart) {
			$output->writeln(sprintf('<fg=red>%s</>', $message));
		}
		$this->fixerEncoder?->write(['action' => 'updateFailed', 'data' => [
			'message' => $message,
			'restart' => $restart,
		]]);
	}

	/**
	 * PHPStan Pro tells its pages and exits, the exit starts the updated one, see startFixerProcess().
	 */
	private function restartFixer(LoopInterface $loop, string $version): void
	{
		$this->restarting = true;
		$this->fixerEncoder?->write(['action' => 'updateRestart', 'data' => [
			'version' => $version,
		]]);
		$fixerProcess = $this->fixerProcess;
		$loop->addTimer(10, static function () use ($fixerProcess): void {
			if ($fixerProcess === null || !$fixerProcess->isRunning()) {
				return;
			}

			$fixerProcess->terminate();
		});
	}

	/**
	 * @throws FixerUpdateException
	 */
	private function verifyDownloadedPhar(string $path): void
	{
		$pubKeyPath = $path . '.pubkey';
		FileWriter::write($pubKeyPath, FileReader::read(__DIR__ . '/fixer-phar.pubkey'));
		try {
			$phar = new Phar($path);
			$hashType = $phar->getSignature()['hash_type'];
		} catch (Throwable) {
			$hashType = null;
		} finally {
			@unlink($pubKeyPath);
		}

		if ($hashType !== 'OpenSSL') {
			@unlink($path);
			throw new FixerUpdateException('The downloaded PHPStan Pro is not signed correctly, it was deleted.');
		}
	}

	private function startUpdatedFixerProcess(LoopInterface $loop, OutputInterface $output, int $serverPort): void
	{
		if ($this->downloadedUpdate === null || $this->webPort === null || $this->fixerCommand === null) {
			throw new ShouldNotHappenException();
		}

		$downloaded = $this->downloadedUpdate;
		$this->downloadedUpdate = null;
		$this->restarting = false;
		$this->restartRequested = false;
		$previousCommand = $this->fixerCommand;

		// replaced only now, Windows does not let a running PHAR be overwritten; the previous one is kept
		// until the updated one connects, so that it can be started again if the updated one does not start
		$pharPath = $this->proTmpDir . '/phpstan-fixer.phar';
		$infoPath = $this->proTmpDir . '/phar-info.json';
		$backupPath = null;
		if (is_file($pharPath)) {
			$backupPath = $pharPath . '.previous';
			if (!@rename($pharPath, $backupPath)) {
				$backupPath = null;
			}
		}
		if (($backupPath === null && is_file($pharPath)) || !@rename($downloaded['path'], $pharPath)) {
			@unlink($downloaded['path']);
			if ($backupPath !== null) {
				@rename($backupPath, $pharPath);
			}
			$this->updateFailureAfterRestart = sprintf('Could not replace the PHPStan Pro PHAR in %s.', $this->proTmpDir);
			$output->writeln(sprintf('<fg=red>%s</>', $this->updateFailureAfterRestart));
			$this->startFixerProcess($previousCommand, $this->webPort, $loop, $output, $serverPort);
			return;
		}

		$this->fixerBeforeUpdate = [
			'command' => $previousCommand,
			'backupPath' => $backupPath,
			'info' => is_file($infoPath) ? FileReader::read($infoPath) : null,
		];
		$this->writeInfoFile($infoPath, $downloaded['info'], self::PHAR_BRANCH, PHP_OS_FAMILY, php_uname('m'));
		$output->writeln(sprintf('<fg=green>PHPStan Pro was updated to %s.</>', $downloaded['info']['version']));

		$this->startFixerProcess($this->getPharCommand($pharPath, $serverPort), $this->webPort, $loop, $output, $serverPort);
	}

	/**
	 * The updated process exited before it connected, the previous one is started again.
	 */
	private function restoreFixerBeforeUpdate(?int $exitCode, LoopInterface $loop, OutputInterface $output, int $serverPort): void
	{
		if ($this->fixerBeforeUpdate === null || $this->webPort === null) {
			throw new ShouldNotHappenException();
		}

		$previous = $this->fixerBeforeUpdate;
		$this->fixerBeforeUpdate = null;
		$pharPath = $this->proTmpDir . '/phpstan-fixer.phar';
		$infoPath = $this->proTmpDir . '/phar-info.json';
		@unlink($pharPath);
		if ($previous['backupPath'] !== null) {
			@rename($previous['backupPath'], $pharPath);
		}
		if ($previous['info'] !== null) {
			FileWriter::write($infoPath, $previous['info']);
		} else {
			@unlink($infoPath);
		}

		$this->updateFailureAfterRestart = sprintf('The updated PHPStan Pro did not start (exit code %s), the previous version runs again.', $exitCode ?? 'none');
		$output->writeln(sprintf('<fg=red>%s</>', $this->updateFailureAfterRestart));
		$this->startFixerProcess($previous['command'], $this->webPort, $loop, $output, $serverPort);
	}

	private static function describeHttpFailure(Throwable $e): string
	{
		if ($e instanceof ConnectException) {
			return 'the server cannot be reached';
		}
		if ($e instanceof RequestException) {
			$response = $e->getResponse();
			if ($response !== null && $response->getStatusCode() >= 400) {
				return sprintf('the server answered %d %s', $response->getStatusCode(), $response->getReasonPhrase());
			}

			// cURL's own message, without the URL and the link to its documentation Guzzle adds
			$error = $e->getHandlerContext()['error'] ?? null;
			if (is_string($error) && $error !== '') {
				return sprintf('the transfer failed (%s)', $error);
			}
		}

		return $e->getMessage();
	}

	/**
	 * @param array<mixed> $options
	 * @return PromiseInterface<ResponseInterface>
	 */
	private function requestAsync(LoopInterface $loop, string $url, array $options): PromiseInterface
	{
		// does not wait for the transfers, the loop calls it
		$handler = new CurlMultiHandler(['select_timeout' => 0]);
		$client = $this->httpClientFactory->createClient(['handler' => HandlerStack::create($handler)]);
		$deferred = new Deferred();
		$timer = $loop->addPeriodicTimer(0.01, static function () use ($handler): void {
			$handler->tick();
		});
		$client->requestAsync('GET', $url, $options)->then(
			static function (ResponseInterface $response) use ($deferred, $loop, $timer): void {
				$loop->cancelTimer($timer);
				$deferred->resolve($response);
			},
			static function (Throwable $e) use ($deferred, $loop, $timer): void {
				$loop->cancelTimer($timer);
				$deferred->reject($e);
			},
		);

		return $deferred->promise();
	}

	private function getLatestUrl(string $branch, string $os, string $arch): string
	{
		return sprintf('https://fixer-download-api.phpstan.com/latest?%s', http_build_query(['phpVersion' => PHP_VERSION_ID, 'branch' => $branch, 'os' => $os, 'arch' => $arch]));
	}

	/**
	 * @throws FixerProcessException
	 */
	private function getFixerCommand(OutputInterface $output, int $serverPort): string
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

		$this->fixerEnv = $env;

		return $this->getPharCommand($pharPath, $serverPort);
	}

	private function getPharCommand(string $pharPath, int $serverPort): string
	{
		return sprintf('%s -d memory_limit=%s %s --port %d', escapeshellarg(PHP_BINARY), escapeshellarg(ini_get('memory_limit')), escapeshellarg($pharPath), $serverPort);
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
		$currentUrl = null;
		$branch = self::PHAR_BRANCH;
		// the PHAR bundles a native binary, download-api answers the one for this system
		$os = PHP_OS_FAMILY;
		$arch = php_uname('m');
		if (is_file($pharPath) && is_file($infoPath)) {
			/** @var array{version: string, date: string, branch?: string, url?: string, os?: string, arch?: string} $currentInfo */
			$currentInfo = Json::decode(FileReader::read($infoPath), Json::FORCE_ARRAY);
			if (($currentInfo['os'] ?? null) === $os && ($currentInfo['arch'] ?? null) === $arch) {
				$currentUrl = $currentInfo['url'] ?? null;
			}

			if ($currentUrl === null) {
				// downloaded on another system (the directory was carried over, or is shared with a Docker container),
				// or by an older PHPStan; its native binary might not run here
				@unlink($pharPath);
				@unlink($infoPath);
			} else {
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
		}

		$client = $this->httpClientFactory->createClient([]);

		$latestUrl = $this->getLatestUrl($branch, $os, $arch);

		/**
		 * @var array{url: string, version: string} $latestInfo
		 */
		$latestInfo = Json::decode($client->get($latestUrl)->getBody()->getContents(), Json::FORCE_ARRAY);
		// the URL changes with the version, and with the PHAR download-api picks for this system
		if ($latestInfo['url'] === $currentUrl) {
			$this->writeInfoFile($infoPath, $latestInfo, $branch, $os, $arch);
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

		$this->writeInfoFile($infoPath, $latestInfo, $branch, $os, $arch);
	}

	private function printDownloadError(OutputInterface $output, Throwable $e): void
	{
		$output->writeln(sprintf('<fg=red>Could not download the PHPStan Pro executable:</> %s', $e->getMessage()));
		$output->writeln('');
	}

	/**
	 * @param array{url: string, version: string} $latestInfo
	 */
	private function writeInfoFile(string $infoPath, array $latestInfo, string $branch, string $os, string $arch): void
	{
		FileWriter::write($infoPath, Json::encode([
			'version' => $latestInfo['version'],
			'url' => $latestInfo['url'],
			'os' => $os,
			'arch' => $arch,
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

			// not during an analysis, the one starting after it might already be for something else
			if ($this->filesChangedWhileAnalysed && $this->processInProgress === null) {
				$this->filesChangedWhileAnalysed = false;
				$this->analysingOnlyFilesChangedWhileAnalysed = true;
				$firstChangeAt ??= $now;
				$lastChangeAt = $now;
			}

			if ($changes->hasAnyChanges()) {
				$this->analysingOnlyFilesChangedWhileAnalysed = false;
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
	): void
	{
		// this analysis reads the files as they are now
		$this->filesChangedWhileAnalysed = false;

		// the process this analysis is for, an updated one gets the next analysis
		$phpstanFixerEncoder = $this->fixerEncoder;
		if ($phpstanFixerEncoder === null) {
			throw new ShouldNotHappenException();
		}

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

				if ($data['action'] === 'filesChangedWhileAnalysed') {
					$this->filesChangedWhileAnalysed = !$this->analysingOnlyFilesChangedWhileAnalysed;
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
		})->finally(function () use ($loop, $mainScript, $projectConfigFile, $input): void {
			$this->processInProgress = null;
			if (!$this->analysisRequested) {
				return;
			}

			$this->analysisRequested = false;
			$this->analyse($loop, $mainScript, $projectConfigFile, $input);
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
