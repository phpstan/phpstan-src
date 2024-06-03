<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use Composer\CaBundle\CaBundle;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Nette\Utils\Json;
use Phar;
use PHPStan\Analyser\Ignore\IgnoredErrorHelper;
use PHPStan\File\FileMonitor;
use PHPStan\File\FileMonitorResult;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use PHPStan\Internal\ComposerHelper;
use PHPStan\Internal\DirectoryCreator;
use PHPStan\Internal\DirectoryCreatorException;
use PHPStan\PhpDoc\StubFilesProvider;
use PHPStan\Process\ProcessHelper;
use PHPStan\Process\ProcessPromise;
use PHPStan\ShouldNotHappenException;
use Psr\Http\Message\ResponseInterface;
use React\ChildProcess\Process;
use React\Dns\Config\Config;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Http\Browser;
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
use function array_merge;
use function count;
use function defined;
use function escapeshellarg;
use function fclose;
use function fopen;
use function fwrite;
use function getenv;
use function http_build_query;
use function ini_get;
use function is_file;
use function parse_url;
use function React\Async\await;
use function sprintf;
use function strlen;
use function unlink;
use const JSON_INVALID_UTF8_IGNORE;
use const PHP_BINARY;
use const PHP_URL_PORT;
use const PHP_VERSION_ID;

class FixerApplication
{

	/** @var PromiseInterface<string>|null  */
	private PromiseInterface|null $processInProgress = null;

	private bool $fileMonitorActive = true;

	/**
	 * @param string[] $analysedPaths
	 * @param list<string> $dnsServers
	 * @param string[] $composerAutoloaderProjectPaths
	 * @param string[] $allConfigFiles
	 * @param string[] $bootstrapFiles
	 */
	public function __construct(
		private FileMonitor $fileMonitor,
		private IgnoredErrorHelper $ignoredErrorHelper,
		private StubFilesProvider $stubFilesProvider,
		private array $analysedPaths,
		private string $currentWorkingDirectory,
		private string $proTmpDir,
		private array $dnsServers,
		private array $composerAutoloaderProjectPaths,
		private array $allConfigFiles,
		private ?string $cliAutoloadFile,
		private array $bootstrapFiles,
		private ?string $editorUrl,
	)
	{
	}

	public function run(
		?string $projectConfigFile,
		InputInterface $input,
		OutputInterface $output,
		int $filesCount,
		string $mainScript,
	): int
	{
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
			$decoder = new Decoder($connection, true, 512, $jsonInvalidUtf8Ignore, 128 * 1024 * 1024);
			$encoder = new Encoder($connection, $jsonInvalidUtf8Ignore);
			$encoder->write(['action' => 'initialData', 'data' => [
				'currentWorkingDirectory' => $this->currentWorkingDirectory,
				'analysedPaths' => $this->analysedPaths,
				'projectConfigFile' => $projectConfigFile,
				'filesCount' => $filesCount,
				'phpstanVersion' => ComposerHelper::getPhpStanVersion(),
				'editorUrl' => $this->editorUrl,
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

			$this->fileMonitor->initialize(array_merge(
				$this->analysedPaths,
				$this->getComposerLocks(),
				$this->getComposerInstalled(),
				$this->getExecutedFiles(),
				$this->getStubFiles(),
				$this->allConfigFiles,
			));

			$this->analyse(
				$loop,
				$mainScript,
				$projectConfigFile,
				$input,
				$output,
				$encoder,
			);

			$this->monitorFileChanges($loop, function (FileMonitorResult $changes) use ($loop, $mainScript, $projectConfigFile, $input, $encoder, $output): void {
				if ($this->processInProgress !== null) {
					$this->processInProgress->cancel();
					$this->processInProgress = null;
				}

				if (count($changes->getChangedFiles()) > 0) {
					$encoder->write(['action' => 'changedFiles', 'data' => [
						'paths' => $changes->getChangedFiles(),
					]]);
				}

				$this->analyse(
					$loop,
					$mainScript,
					$projectConfigFile,
					$input,
					$output,
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
		} catch (RuntimeException $e) {
			if (!is_file($pharPath)) {
				$this->printDownloadError($output, $e);

				throw new FixerProcessException();
			}
		}

		$pubKeyPath = $pharPath . '.pubkey';
		FileWriter::write($pubKeyPath, FileReader::read(__DIR__ . '/fixer-phar.pubkey'));

		try {
			$phar = new Phar($pharPath);
		} catch (Throwable) {
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

		return new Process(sprintf('%s -d memory_limit=%s %s --port %d', escapeshellarg(PHP_BINARY), escapeshellarg(ini_get('memory_limit')), escapeshellarg($pharPath), $serverPort), null, $env, []);
	}

	private function downloadPhar(
		OutputInterface $output,
		string $pharPath,
		string $infoPath,
	): void
	{
		$currentVersion = null;
		$branch = 'main';
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

		$dnsConfig = new Config();
		$dnsConfig->nameservers = $this->dnsServers;

		$client = new Browser(
			new Connector(
				[
					'timeout' => 5,
					'tls' => [
						'cafile' => CaBundle::getBundledCaBundlePath(),
					],
					'dns' => $dnsConfig,
				],
			),
		);

		/**
		 * @var array{url: string, version: string} $latestInfo
		 */
		$latestInfo = Json::decode((string) await($client->get(sprintf('https://fixer-download-api.phpstan.com/latest?%s', http_build_query(['phpVersion' => PHP_VERSION_ID, 'branch' => $branch]))))->getBody(), Json::FORCE_ARRAY);
		if ($currentVersion !== null && $latestInfo['version'] === $currentVersion) {
			$this->writeInfoFile($infoPath, $latestInfo['version'], $branch);
			$output->writeln('<fg=green>You\'re running the latest PHPStan Pro!</>');
			return;
		}

		$output->writeln('<fg=green>Downloading the latest PHPStan Pro...</>');

		$pharPathResource = fopen($pharPath, 'w');
		if ($pharPathResource === false) {
			throw new ShouldNotHappenException(sprintf('Could not open file %s for writing.', $pharPath));
		}
		$progressBar = new ProgressBar($output);
		$client->requestStreaming('GET', $latestInfo['url'])->then(static function (ResponseInterface $response) use ($progressBar, $pharPathResource): void {
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
		}, function (Throwable $e) use ($output): void {
			$this->printDownloadError($output, $e);
		});

		Loop::run();

		fclose($pharPathResource);

		$progressBar->finish();
		$output->writeln('');
		$output->writeln('');

		$this->writeInfoFile($infoPath, $latestInfo['version'], $branch);
	}

	private function printDownloadError(OutputInterface $output, Throwable $e): void
	{
		$output->writeln(sprintf('<fg=red>Could not download the PHPStan Pro executable:</> %s', $e->getMessage()));
		$output->writeln('');
		$output->writeln('Try different DNS servers in your configuration file:');
		$output->writeln('');
		$output->writeln('parameters:');
		$output->writeln("\tpro:");
		$output->writeln("\t\tdnsServers!:");
		$output->writeln("\t\t\t- '8.8.8.8'");
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
	 * @param callable(FileMonitorResult): void $hasChangesCallback
	 */
	private function monitorFileChanges(LoopInterface $loop, callable $hasChangesCallback): void
	{
		$callback = function () use (&$callback, $loop, $hasChangesCallback): void {
			if (!$this->fileMonitorActive) {
				$loop->addTimer(1.0, $callback);
				return;
			}
			if ($this->processInProgress !== null) {
				$loop->addTimer(1.0, $callback);
				return;
			}
			$changes = $this->fileMonitor->getChanges();

			if ($changes->hasAnyChanges()) {
				$hasChangesCallback($changes);
			}

			$loop->addTimer(1.0, $callback);
		};
		$loop->addTimer(1.0, $callback);
	}

	private function analyse(
		LoopInterface $loop,
		string $mainScript,
		?string $projectConfigFile,
		InputInterface $input,
		OutputInterface $output,
		Encoder $phpstanFixerEncoder,
	): void
	{
		$ignoredErrorHelperResult = $this->ignoredErrorHelper->initialize();
		if (count($ignoredErrorHelperResult->getErrors()) > 0) {
			throw new ShouldNotHappenException();
		}

		// TCP server for fixer:worker (TCP client)
		$server = new TcpServer('127.0.0.1:0', $loop);
		/** @var string $serverAddress */
		$serverAddress = $server->getAddress();
		/** @var int<0, 65535> $serverPort */
		$serverPort = parse_url($serverAddress, PHP_URL_PORT);

		$server->on('connection', static function (ConnectionInterface $connection) use ($phpstanFixerEncoder): void {
			// phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
			$jsonInvalidUtf8Ignore = defined('JSON_INVALID_UTF8_IGNORE') ? JSON_INVALID_UTF8_IGNORE : 0;
			// phpcs:enable
			$decoder = new Decoder($connection, true, 512, $jsonInvalidUtf8Ignore, 128 * 1024 * 1024);
			$decoder->on('data', static function (array $data) use ($phpstanFixerEncoder): void {
				$phpstanFixerEncoder->write($data);
			});
		});

		$process = new ProcessPromise($loop, 'changedFileAnalysis', ProcessHelper::getWorkerCommand(
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

		$this->processInProgress->then(function () use ($server): void {
			$this->processInProgress = null;
			$server->close();
		}, function (Throwable $e) use ($server, $output, $phpstanFixerEncoder): void {
			$this->processInProgress = null;
			$server->close();
			$output->writeln('<error>Worker process exited: ' . $e->getMessage() . '</error>');
			$phpstanFixerEncoder->write(['action' => 'analysisCrash', 'data' => [
				'errors' => [$e->getMessage()],
			]]);
			throw $e;
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
