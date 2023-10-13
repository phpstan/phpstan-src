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
use PHPStan\Analyser\AnalyserResult;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\IgnoredErrorHelper;
use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use PHPStan\File\FileMonitor;
use PHPStan\File\FileMonitorResult;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use PHPStan\File\PathNotFoundException;
use PHPStan\Internal\ComposerHelper;
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
use function count;
use function defined;
use function escapeshellarg;
use function fclose;
use function fopen;
use function fwrite;
use function getenv;
use function http_build_query;
use function ini_get;
use function is_dir;
use function is_file;
use function is_string;
use function memory_get_peak_usage;
use function mkdir;
use function parse_url;
use function React\Async\await;
use function React\Promise\resolve;
use function sprintf;
use function strlen;
use function unlink;
use const PHP_BINARY;
use const PHP_URL_PORT;
use const PHP_VERSION_ID;

class FixerApplication
{

	/** @var (ExtendedPromiseInterface&CancellablePromiseInterface)|null */
	private $processInProgress;

	/**
	 * @param string[] $analysedPaths
	 * @param list<string> $dnsServers
	 */
	public function __construct(
		private FileMonitor $fileMonitor,
		private ResultCacheManagerFactory $resultCacheManagerFactory,
		private IgnoredErrorHelper $ignoredErrorHelper,
		private array $analysedPaths,
		private string $currentWorkingDirectory,
		private string $fixerTmpDir,
		private array $dnsServers,
	)
	{
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

		/** @var int<0, 65535> $serverPort */
		$serverPort = parse_url($serverAddress, PHP_URL_PORT);

		$server->on('connection', function (ConnectionInterface $connection) use ($loop, $projectConfigFile, $input, $output, $fileSpecificErrors, $notFileSpecificErrors, $mainScript, $filesCount, $inceptionResult): void {
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
				'phpstanVersion' => ComposerHelper::getPhpStanVersion(),
			]]);
			$decoder->on('data', static function (array $data) use (
				$output,
			): void {
				if ($data['action'] === 'webPort') {
					$output->writeln(sprintf('Open your web browser at: <fg=cyan>http://127.0.0.1:%d</>', $data['data']['port']));
					$output->writeln('Press [Ctrl-C] to quit.');
					return;
				}
			});

			$this->fileMonitor->initialize($this->analysedPaths);
			$this->monitorFileChanges($loop, function (FileMonitorResult $changes) use ($loop, $mainScript, $projectConfigFile, $input, $encoder, $output, $inceptionResult): void {
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
					$input,
				)->done(function (array $json) use ($encoder, $changes): void {
					$this->processInProgress = null;
					$encoder->write(['action' => 'analysisEnd', 'data' => [
						'fileSpecificErrors' => $json['fileSpecificErrors'],
						'notFileSpecificErrors' => $json['notFileSpecificErrors'],
						'filesCount' => $changes->getTotalFilesCount(),
					]]);
				}, function (Throwable $e) use ($encoder, $output): void {
					$this->processInProgress = null;
					$output->writeln('<error>Worker process exited: ' . $e->getMessage() . '</error>');
					$encoder->write(['action' => 'analysisCrash', 'data' => [
						'error' => $e->getMessage(),
					]]);
				});
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
			@unlink($this->fixerTmpDir . '/phar-info.json');
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
		$env['PHPSTAN_PRO_TMP_DIR'] = $this->fixerTmpDir;
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

		return new Process(sprintf('%s -d memory_limit=%s %s --port %d', escapeshellarg(PHP_BINARY), escapeshellarg(ini_get('memory_limit')), escapeshellarg($pharPath), $serverPort), null, $env, []);
	}

	private function downloadPhar(
		OutputInterface $output,
		string $pharPath,
		string $infoPath,
	): void
	{
		$currentVersion = null;
		$branch = 'master';
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
			$changes = $this->fileMonitor->getChanges();

			if ($changes->hasAnyChanges()) {
				$hasChangesCallback($changes);
			}

			$loop->addTimer(1.0, $callback);
		};
		$loop->addTimer(1.0, $callback);
	}

	private function reanalyseAfterFileChanges(
		LoopInterface $loop,
		InceptionResult $inceptionResult,
		string $mainScript,
		?string $projectConfigFile,
		InputInterface $input,
	): PromiseInterface
	{
		$ignoredErrorHelperResult = $this->ignoredErrorHelper->initialize();
		if (count($ignoredErrorHelperResult->getErrors()) > 0) {
			throw new ShouldNotHappenException();
		}

		$projectConfigArray = $inceptionResult->getProjectConfigArray();

		$resultCacheManager = $this->resultCacheManagerFactory->create();

		try {
			[$inceptionFiles, $isOnlyFiles] = $inceptionResult->getFiles();
		} catch (InceptionNotSuccessfulException | PathNotFoundException) {
			throw new ShouldNotHappenException();
		}

		$resultCache = $resultCacheManager->restore($inceptionFiles, false, false, $projectConfigArray, $inceptionResult->getErrorOutput());
		if (count($resultCache->getFilesToAnalyse()) === 0) {
			$result = $resultCacheManager->process(
				new AnalyserResult([], [], [], [], [], false, memory_get_peak_usage(true)),
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

	private function isDockerRunning(): bool
	{
		return is_file('/.dockerenv');
	}

}
