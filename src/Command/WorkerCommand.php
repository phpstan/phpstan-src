<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Collectors\Registry as CollectorRegistry;
use PHPStan\DependencyInjection\Container;
use PHPStan\File\PathNotFoundException;
use PHPStan\Rules\Registry as RuleRegistry;
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
use function array_merge;
use function defined;
use function is_array;
use function is_bool;
use function is_string;
use function memory_get_peak_usage;
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
				new InputOption('xdebug', null, InputOption::VALUE_NONE, 'Allow running with Xdebug for debugging purposes'),
				new InputOption('port', null, InputOption::VALUE_REQUIRED),
				new InputOption('identifier', null, InputOption::VALUE_REQUIRED),
			])
			->setHidden(true);
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

		if (
			!is_array($paths)
			|| (!is_string($memoryLimit) && $memoryLimit !== null)
			|| (!is_string($autoloadFile) && $autoloadFile !== null)
			|| (!is_string($configuration) && $configuration !== null)
			|| (!is_string($level) && $level !== null)
			|| (!is_bool($allowXdebug))
			|| !is_string($port)
			|| !is_string($identifier)
		) {
			throw new ShouldNotHappenException();
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
				false,
			);
		} catch (InceptionNotSuccessfulException $e) {
			return 1;
		}
		$loop = new StreamSelectLoop();

		$container = $inceptionResult->getContainer();

		try {
			[$analysedFiles] = $inceptionResult->getFiles();
		} catch (PathNotFoundException $e) {
			$inceptionResult->getErrorOutput()->writeLineFormatted(sprintf('<error>%s</error>', $e->getMessage()));
			return 1;
		} catch (InceptionNotSuccessfulException) {
			return 1;
		}

		$nodeScopeResolver = $container->getByType(NodeScopeResolver::class);
		$nodeScopeResolver->setAnalysedFiles($analysedFiles);

		$analysedFiles = array_fill_keys($analysedFiles, true);

		$tcpConnector = new TcpConnector($loop);
		$tcpConnector->connect(sprintf('127.0.0.1:%d', $port))->done(function (ConnectionInterface $connection) use ($container, $identifier, $output, $analysedFiles): void {
			// phpcs:disable SlevomatCodingStandard.Namespaces.ReferenceUsedNamesOnly
			$jsonInvalidUtf8Ignore = defined('JSON_INVALID_UTF8_IGNORE') ? JSON_INVALID_UTF8_IGNORE : 0;
			// phpcs:enable
			$out = new Encoder($connection, $jsonInvalidUtf8Ignore);
			$in = new Decoder($connection, true, 512, $jsonInvalidUtf8Ignore, $container->getParameter('parallel')['buffer']);
			$out->write(['action' => 'hello', 'identifier' => $identifier]);
			$this->runWorker($container, $out, $in, $output, $analysedFiles);
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
					'files' => [],
					'internalErrorsCount' => 1,
				],
			]);
			$out->end();
		};
		$out->on('error', $handleError);
		$fileAnalyser = $container->getByType(FileAnalyser::class);
		$ruleRegistry = $container->getByType(RuleRegistry::class);
		$collectorRegistry = $container->getByType(CollectorRegistry::class);
		$in->on('data', function (array $json) use ($fileAnalyser, $ruleRegistry, $collectorRegistry, $out, $analysedFiles, $output): void {
			$action = $json['action'];
			if ($action !== 'analyse') {
				return;
			}

			$internalErrorsCount = 0;
			$files = $json['files'];
			$errors = [];
			$filteredPhpErrors = [];
			$allPhpErrors = [];
			$locallyIgnoredErrors = [];
			$linesToIgnore = [];
			$unmatchedLineIgnores = [];
			$collectedData = [];
			$dependencies = [];
			$exportedNodes = [];
			foreach ($files as $file) {
				try {
					$fileAnalyserResult = $fileAnalyser->analyseFile($file, $analysedFiles, $ruleRegistry, $collectorRegistry, null);
					$fileErrors = $fileAnalyserResult->getErrors();
					$filteredPhpErrors = array_merge($filteredPhpErrors, $fileAnalyserResult->getFilteredPhpErrors());
					$allPhpErrors = array_merge($allPhpErrors, $fileAnalyserResult->getAllPhpErrors());
					$linesToIgnore[$file] = $fileAnalyserResult->getLinesToIgnore();
					$unmatchedLineIgnores[$file] = $fileAnalyserResult->getUnmatchedLineIgnores();
					$dependencies[$file] = $fileAnalyserResult->getDependencies();
					$exportedNodes[$file] = $fileAnalyserResult->getExportedNodes();
					foreach ($fileErrors as $fileError) {
						$errors[] = $fileError;
					}
					foreach ($fileAnalyserResult->getLocallyIgnoredErrors() as $locallyIgnoredError) {
						$locallyIgnoredErrors[] = $locallyIgnoredError;
					}
					foreach ($fileAnalyserResult->getCollectedData() as $data) {
						$collectedData[] = $data;
					}
				} catch (Throwable $t) {
					$this->errorCount++;
					$internalErrorsCount++;
					$internalErrorMessage = sprintf('Internal error: %s while analysing file %s', $t->getMessage(), $file);

					$bugReportUrl = 'https://github.com/phpstan/phpstan/issues/new?template=Bug_report.yaml';
					if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
						$trace = sprintf('## %s(%d)%s', $t->getFile(), $t->getLine(), "\n");
						$trace .= $t->getTraceAsString();
						$internalErrorMessage .= sprintf('%sPost the following stack trace to %s: %s%s', "\n\n", $bugReportUrl, "\n", $trace);
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
					'filteredPhpErrors' => $filteredPhpErrors,
					'allPhpErrors' => $allPhpErrors,
					'locallyIgnoredErrors' => $locallyIgnoredErrors,
					'linesToIgnore' => $linesToIgnore,
					'unmatchedLineIgnores' => $unmatchedLineIgnores,
					'collectedData' => $collectedData,
					'memoryUsage' => memory_get_peak_usage(true),
					'dependencies' => $dependencies,
					'exportedNodes' => $exportedNodes,
					'files' => $files,
					'internalErrorsCount' => $internalErrorsCount,
				]]);
		});
		$in->on('error', $handleError);
	}

}
