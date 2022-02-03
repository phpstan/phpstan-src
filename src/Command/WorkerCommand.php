<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\Container;
use PHPStan\File\PathNotFoundException;
use PHPStan\Internal\FileConsumptionTracker;
use PHPStan\Rules\Registry;
use PHPStan\ShouldNotHappenException;
use React\EventLoop\StreamSelectLoop;
use React\Socket\ConnectionInterface;
use React\Socket\TcpConnector;
use React\Stream\ReadableStreamInterface;
use React\Stream\WritableStreamInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function array_fill_keys;
use function array_filter;
use function array_values;
use function count;
use function defined;
use function is_array;
use function is_bool;
use function is_string;
use function sprintf;

class WorkerCommand extends Command
{

	private const NAME = 'worker';

	private int $errorCount = 0;

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		private array $composerAutoloaderProjectPaths,
	)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setName(self::NAME)
			->setDescription('(Internal) Support for parallel analysis.')
			->setDefinition([
				new InputArgument('paths', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Paths with source code to run analysis on'),
				new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
				new InputOption(AnalyseCommand::OPTION_LEVEL, 'l', InputOption::VALUE_REQUIRED, 'Level of rule options - the higher the stricter'),
				new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
				new InputOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Memory limit for analysis'),
				new InputOption('xdebug', null, InputOption::VALUE_NONE, 'Allow running with XDebug for debugging purposes'),
				new InputOption('port', null, InputOption::VALUE_REQUIRED),
				new InputOption('identifier', null, InputOption::VALUE_REQUIRED),
				new InputOption('tmp-file', null, InputOption::VALUE_REQUIRED),
				new InputOption('instead-of', null, InputOption::VALUE_REQUIRED),
				new InputOption('track-consumption', null, InputOption::VALUE_NONE),
			]);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$paths = $input->getArgument('paths');
		$memoryLimit = $input->getOption('memory-limit');
		$autoloadFile = $input->getOption('autoload-file');
		$configuration = $input->getOption('configuration');
		$level = $input->getOption(AnalyseCommand::OPTION_LEVEL);
		$allowXdebug = $input->getOption('xdebug');
		$port = $input->getOption('port');
		$identifier = $input->getOption('identifier');
		$trackConsumption = $input->getOption('track-consumption');

		if (
			!is_array($paths)
			|| (!is_string($memoryLimit) && $memoryLimit !== null)
			|| (!is_string($autoloadFile) && $autoloadFile !== null)
			|| (!is_string($configuration) && $configuration !== null)
			|| (!is_string($level) && $level !== null)
			|| (!is_bool($allowXdebug))
			|| !is_string($port)
			|| !is_string($identifier)
			|| !is_bool($trackConsumption)
		) {
			throw new ShouldNotHappenException();
		}

		/** @var string|null $tmpFile */
		$tmpFile = $input->getOption('tmp-file');

		/** @var string|null $insteadOfFile */
		$insteadOfFile = $input->getOption('instead-of');

		$singleReflectionFile = null;
		if ($tmpFile !== null) {
			$singleReflectionFile = $tmpFile;
		}

		try {
			$inceptionResult = CommandHelper::begin(
				$input,
				$output,
				$paths,
				$memoryLimit,
				$autoloadFile,
				$this->composerAutoloaderProjectPaths,
				$configuration,
				null,
				$level,
				$allowXdebug,
				false,
				$singleReflectionFile,
				null,
				false,
			);
		} catch (InceptionNotSuccessfulException $e) {
			return 1;
		}
		$loop = new StreamSelectLoop();

		$container = $inceptionResult->getContainer();

		try {
			[$analysedFiles] = $inceptionResult->getFiles();
			$analysedFiles = $this->switchTmpFile($analysedFiles, $insteadOfFile, $tmpFile);
		} catch (PathNotFoundException $e) {
			$inceptionResult->getErrorOutput()->writeLineFormatted(sprintf('<error>%s</error>', $e->getMessage()));
			return 1;
		}

		/** @var NodeScopeResolver $nodeScopeResolver */
		$nodeScopeResolver = $container->getByType(NodeScopeResolver::class);
		$nodeScopeResolver->setAnalysedFiles($analysedFiles);

		$analysedFiles = array_fill_keys($analysedFiles, true);

		$tcpConector = new TcpConnector($loop);
		$tcpConector->connect(sprintf('127.0.0.1:%d', $port))->done(function (ConnectionInterface $connection) use ($container, $identifier, $output, $analysedFiles, $tmpFile, $insteadOfFile, $trackConsumption): void {
			// phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
			$jsonInvalidUtf8Ignore = defined('JSON_INVALID_UTF8_IGNORE') ? JSON_INVALID_UTF8_IGNORE : 0;
			// phpcs:enable
			$out = new Encoder($connection, $jsonInvalidUtf8Ignore);
			$in = new Decoder($connection, true, 512, $jsonInvalidUtf8Ignore, $container->getParameter('parallel')['buffer']);
			$out->write(['action' => 'hello', 'identifier' => $identifier]);
			$this->runWorker($container, $out, $in, $output, $analysedFiles, $tmpFile, $insteadOfFile, $trackConsumption);
		});

		$loop->run();

		if ($this->errorCount > 0) {
			return 1;
		}

		return 0;
	}

	/**
	 * @param array<string, true> $analysedFiles
	 */
	private function runWorker(
		Container $container,
		WritableStreamInterface $out,
		ReadableStreamInterface $in,
		OutputInterface $output,
		array $analysedFiles,
		?string $tmpFile,
		?string $insteadOfFile,
		bool $trackConsumption,
	): void
	{
		$handleError = function (Throwable $error) use ($out, $output): void {
			$this->errorCount++;
			$output->writeln(sprintf('Error: %s', $error->getMessage()));
			$out->write([
				'action' => 'result',
				'result' => [
					'errors' => [$error->getMessage()],
					'dependencies' => [],
					'filesCount' => 0,
					'internalErrorsCount' => 1,
				],
			]);
			$out->end();
		};
		$out->on('error', $handleError);
		/** @var FileAnalyser $fileAnalyser */
		$fileAnalyser = $container->getByType(FileAnalyser::class);
		/** @var Registry $registry */
		$registry = $container->getByType(Registry::class);
		$in->on('data', function (array $json) use ($fileAnalyser, $registry, $out, $analysedFiles, $tmpFile, $insteadOfFile, $output, $trackConsumption): void {
			$action = $json['action'];
			if ($action !== 'analyse') {
				return;
			}

			$internalErrorsCount = 0;
			$files = $json['files'];
			$errors = [];
			$dependencies = [];
			$exportedNodes = [];
			$consumptionData = [];
			foreach ($files as $file) {
				$consumptionTracker = null;
				try {
					if ($file === $insteadOfFile) {
						$file = $tmpFile;
					}
					if ($trackConsumption) {
						$consumptionTracker = new FileConsumptionTracker($file);
						$consumptionTracker->start();
					}
					$fileAnalyserResult = $fileAnalyser->analyseFile($file, $analysedFiles, $registry, null);
					$fileErrors = $fileAnalyserResult->getErrors();
					$dependencies[$file] = $fileAnalyserResult->getDependencies();
					$exportedNodes[$file] = $fileAnalyserResult->getExportedNodes();
					foreach ($fileErrors as $fileError) {
						$errors[] = $fileError;
					}

					if ($consumptionTracker instanceof FileConsumptionTracker) {
						$consumptionTracker->stop();
						$consumptionData[] = $consumptionTracker->toArray();
					}

				} catch (Throwable $t) {
					$this->errorCount++;
					$internalErrorsCount++;
					$internalErrorMessage = sprintf('Internal error: %s in file %s', $t->getMessage(), $file);

					$bugReportUrl = 'https://github.com/phpstan/phpstan/issues/new?template=Bug_report.md';
					if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
						$internalErrorMessage .= sprintf('%sPost the following stack trace to %s: %s%s', "\n\n", $bugReportUrl, "\n", $t->getTraceAsString());
					} else {
						$internalErrorMessage .= sprintf('%sRun PHPStan with -v option and post the stack trace to:%s%s', "\n", "\n", $bugReportUrl);
					}

					$errors[] = $internalErrorMessage;
				}
			}

			$out->write([
				'action' => 'result',
				'result' => [
					'errors' => $errors,
					'dependencies' => $dependencies,
					'exportedNodes' => $exportedNodes,
					'consumptionData' => $consumptionData,
					'filesCount' => count($files),
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
		$analysedFiles = array_values(array_filter($analysedFiles, static function (string $file) use ($insteadOfFile): bool {
			if ($insteadOfFile === null) {
				return true;
			}
			return $file !== $insteadOfFile;
		}));
		if ($tmpFile !== null) {
			$analysedFiles[] = $tmpFile;
		}

		return $analysedFiles;
	}

}
