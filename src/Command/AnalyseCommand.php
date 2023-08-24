<?php declare(strict_types = 1);

namespace PHPStan\Command;

use OndraM\CiDetector\CiDetector;
use PHPStan\Command\ErrorFormatter\BaselineNeonErrorFormatter;
use PHPStan\Command\ErrorFormatter\BaselinePhpErrorFormatter;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\ErrorFormatter\TableErrorFormatter;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\Command\Symfony\SymfonyStyle;
use PHPStan\DependencyInjection\Container;
use PHPStan\File\CouldNotWriteFileException;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use PHPStan\File\ParentDirectoryRelativePathHelper;
use PHPStan\File\PathNotFoundException;
use PHPStan\File\RelativePathHelper;
use PHPStan\Internal\BytesHelper;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use Throwable;
use function array_intersect;
use function array_map;
use function count;
use function dirname;
use function filesize;
use function fopen;
use function get_class;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_dir;
use function is_file;
use function is_string;
use function mkdir;
use function pathinfo;
use function rewind;
use function sprintf;
use function stream_get_contents;
use function strlen;
use function substr;
use const PATHINFO_BASENAME;
use const PATHINFO_EXTENSION;

class AnalyseCommand extends Command
{

	private const NAME = 'analyse';

	public const OPTION_LEVEL = 'level';

	public const DEFAULT_LEVEL = CommandHelper::DEFAULT_LEVEL;

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
			->setDescription('Analyses source code')
			->setDefinition([
				new InputArgument('paths', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Paths with source code to run analysis on'),
				new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
				new InputOption(self::OPTION_LEVEL, 'l', InputOption::VALUE_REQUIRED, 'Level of rule options - the higher the stricter'),
				new InputOption(ErrorsConsoleStyle::OPTION_NO_PROGRESS, null, InputOption::VALUE_NONE, 'Do not show progress bar, only results'),
				new InputOption('debug', null, InputOption::VALUE_NONE, 'Show debug information - which file is analysed, do not catch internal errors'),
				new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
				new InputOption('error-format', null, InputOption::VALUE_REQUIRED, 'Format in which to print the result of the analysis', null),
				new InputOption('generate-baseline', 'b', InputOption::VALUE_OPTIONAL, 'Path to a file where the baseline should be saved', false),
				new InputOption('allow-empty-baseline', null, InputOption::VALUE_NONE, 'Do not error out when the generated baseline is empty'),
				new InputOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Memory limit for analysis'),
				new InputOption('xdebug', null, InputOption::VALUE_NONE, 'Allow running with XDebug for debugging purposes'),
				new InputOption('fix', null, InputOption::VALUE_NONE, 'Launch PHPStan Pro'),
				new InputOption('watch', null, InputOption::VALUE_NONE, 'Launch PHPStan Pro'),
				new InputOption('pro', null, InputOption::VALUE_NONE, 'Launch PHPStan Pro'),
			]);
	}

	/**
	 * @return string[]
	 */
	public function getAliases(): array
	{
		return ['analyze'];
	}

	protected function initialize(InputInterface $input, OutputInterface $output): void
	{
		if ((bool) $input->getOption('debug')) {
			$application = $this->getApplication();
			if ($application === null) {
				return;
			}
			$application->setCatchExceptions(false);
			return;
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$paths = $input->getArgument('paths');
		$memoryLimit = $input->getOption('memory-limit');
		$autoloadFile = $input->getOption('autoload-file');
		$configuration = $input->getOption('configuration');
		$level = $input->getOption(self::OPTION_LEVEL);
		$allowXdebug = $input->getOption('xdebug');
		$debugEnabled = (bool) $input->getOption('debug');
		$fix = (bool) $input->getOption('fix') || (bool) $input->getOption('watch') || (bool) $input->getOption('pro');

		/** @var string|false|null $generateBaselineFile */
		$generateBaselineFile = $input->getOption('generate-baseline');
		if ($generateBaselineFile === false) {
			$generateBaselineFile = null;
		} elseif ($generateBaselineFile === null) {
			$generateBaselineFile = 'phpstan-baseline.neon';
		}

		$allowEmptyBaseline = (bool) $input->getOption('allow-empty-baseline');

		if (
			!is_array($paths)
			|| (!is_string($memoryLimit) && $memoryLimit !== null)
			|| (!is_string($autoloadFile) && $autoloadFile !== null)
			|| (!is_string($configuration) && $configuration !== null)
			|| (!is_string($level) && $level !== null)
			|| (!is_bool($allowXdebug))
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
				$generateBaselineFile,
				$level,
				$allowXdebug,
				$debugEnabled,
			);
		} catch (InceptionNotSuccessfulException $e) {
			return 1;
		}

		if ($generateBaselineFile === null && $allowEmptyBaseline) {
			$inceptionResult->getStdOutput()->getStyle()->error('You must pass the --generate-baseline option alongside --allow-empty-baseline.');
			return $inceptionResult->handleReturn(1, null);
		}

		$errorOutput = $inceptionResult->getErrorOutput();
		$obsoleteDockerImage = $_SERVER['PHPSTAN_OBSOLETE_DOCKER_IMAGE'] ?? 'false';
		if ($obsoleteDockerImage === 'true') {
			$errorOutput->writeLineFormatted('⚠️  You\'re using an obsolete PHPStan Docker image. ⚠️️');
			$errorOutput->writeLineFormatted('   You can obtain the current one from <fg=cyan>ghcr.io/phpstan/phpstan</>.');
			$errorOutput->writeLineFormatted('   Read more about it here:');
			$errorOutput->writeLineFormatted('   <fg=cyan>https://phpstan.org/user-guide/docker</>');
			$errorOutput->writeLineFormatted('');
		}

		$errorFormat = $input->getOption('error-format');

		if (!is_string($errorFormat) && $errorFormat !== null) {
			throw new ShouldNotHappenException();
		}

		if ($errorFormat === null) {
			$errorFormat = $inceptionResult->getContainer()->getParameter('errorFormat');
		}

		if ($errorFormat === null) {
			$errorFormat = 'table';
		}

		$container = $inceptionResult->getContainer();
		$errorFormatterServiceName = sprintf('errorFormatter.%s', $errorFormat);
		if (!$container->hasService($errorFormatterServiceName)) {
			$errorOutput->writeLineFormatted(sprintf(
				'Error formatter "%s" not found. Available error formatters are: %s',
				$errorFormat,
				implode(', ', array_map(static fn (string $name): string => substr($name, strlen('errorFormatter.')), $container->findServiceNamesByType(ErrorFormatter::class))),
			));
			return 1;
		}

		$generateBaselineFile = $inceptionResult->getGenerateBaselineFile();
		if ($generateBaselineFile !== null) {
			$baselineExtension = pathinfo($generateBaselineFile, PATHINFO_EXTENSION);
			if ($baselineExtension === '') {
				$inceptionResult->getStdOutput()->getStyle()->error(sprintf('Baseline filename must have an extension, %s provided instead.', pathinfo($generateBaselineFile, PATHINFO_BASENAME)));
				return $inceptionResult->handleReturn(1, null);
			}

			if (!in_array($baselineExtension, ['neon', 'php'], true)) {
				$inceptionResult->getStdOutput()->getStyle()->error(sprintf('Baseline filename extension must be .neon or .php, .%s was used instead.', $baselineExtension));

				return $inceptionResult->handleReturn(1, null);
			}
		}

		try {
			[$files, $onlyFiles] = $inceptionResult->getFiles();
		} catch (PathNotFoundException $e) {
			$inceptionResult->getErrorOutput()->writeLineFormatted(sprintf('<error>%s</error>', $e->getMessage()));
			return 1;
		} catch (InceptionNotSuccessfulException) {
			return 1;
		}

		if (count($files) === 0) {
			$bleedingEdge = (bool) $container->getParameter('featureToggles')['zeroFiles'];
			if (!$bleedingEdge) {
				$inceptionResult->getErrorOutput()->getStyle()->note('No files found to analyse.');
				$inceptionResult->getErrorOutput()->getStyle()->warning('This will cause a non-zero exit code in PHPStan 2.0.');

				return $inceptionResult->handleReturn(0, null);
			}

			$inceptionResult->getErrorOutput()->getStyle()->error('No files found to analyse.');

			return $inceptionResult->handleReturn(1, null);
		}

		$analysedConfigFiles = array_intersect($files, $container->getParameter('allConfigFiles'));
		foreach ($analysedConfigFiles as $analysedConfigFile) {
			$fileSize = @filesize($analysedConfigFile);
			if ($fileSize === false) {
				continue;
			}

			if ($fileSize <= 512 * 1024) {
				continue;
			}

			/** @var RelativePathHelper $relativePathHelper */
			$relativePathHelper = $container->getService('relativePathHelper');
			$inceptionResult->getErrorOutput()->getStyle()->warning(sprintf(
				'Configuration file %s (%s) is too big and might slow down PHPStan. Consider adding it to excludePaths.',
				$relativePathHelper->getRelativePath($analysedConfigFile),
				BytesHelper::bytes($fileSize),
			));
		}

		$application = $container->getByType(AnalyseApplication::class);

		$debug = $input->getOption('debug');
		if (!is_bool($debug)) {
			throw new ShouldNotHappenException();
		}

		try {
			$analysisResult = $application->analyse(
				$files,
				$onlyFiles,
				$inceptionResult->getStdOutput(),
				$inceptionResult->getErrorOutput(),
				$inceptionResult->isDefaultLevelUsed(),
				$debug,
				$inceptionResult->getProjectConfigFile(),
				$inceptionResult->getProjectConfigArray(),
				$input,
			);
		} catch (Throwable $t) {
			if ($debug) {
				$stdOutput = $inceptionResult->getStdOutput();
				$stdOutput->writeRaw(sprintf(
					'Uncaught %s: %s in %s:%d',
					get_class($t),
					$t->getMessage(),
					$t->getFile(),
					$t->getLine(),
				));
				$stdOutput->writeLineFormatted('');
				$stdOutput->writeRaw($t->getTraceAsString());
				$stdOutput->writeLineFormatted('');

				$previous = $t->getPrevious();
				while ($previous !== null) {
					$stdOutput->writeLineFormatted('');
					$stdOutput->writeLineFormatted('Caused by:');
					$stdOutput->writeRaw(sprintf(
						'Uncaught %s: %s in %s:%d',
						get_class($previous),
						$previous->getMessage(),
						$previous->getFile(),
						$previous->getLine(),
					));
					$stdOutput->writeRaw($previous->getTraceAsString());
					$stdOutput->writeLineFormatted('');
					$previous = $previous->getPrevious();
				}

				return $inceptionResult->handleReturn(1, null);
			}

			throw $t;
		}

		if ($generateBaselineFile !== null) {
			return $this->generateBaseline($generateBaselineFile, $inceptionResult, $analysisResult, $output, $allowEmptyBaseline, $baselineExtension);
		}

		if ($fix) {
			return $this->runFixer($inceptionResult, $analysisResult, $container, $onlyFiles, $input, $output, $files);
		}

		/** @var ErrorFormatter $errorFormatter */
		$errorFormatter = $container->getService($errorFormatterServiceName);

		return $inceptionResult->handleReturn(
			$errorFormatter->formatErrors($analysisResult, $inceptionResult->getStdOutput()),
			$analysisResult->getPeakMemoryUsageBytes(),
		);
	}

	private function createStreamOutput(): StreamOutput
	{
		$resource = fopen('php://memory', 'w', false);
		if ($resource === false) {
			throw new ShouldNotHappenException();
		}
		return new StreamOutput($resource);
	}

	private function generateBaseline(string $generateBaselineFile, InceptionResult $inceptionResult, AnalysisResult $analysisResult, OutputInterface $output, bool $allowEmptyBaseline, string $baselineExtension): int
	{
		if (!$allowEmptyBaseline && !$analysisResult->hasErrors()) {
			$inceptionResult->getStdOutput()->getStyle()->error('No errors were found during the analysis. Baseline could not be generated.');
			$inceptionResult->getStdOutput()->writeLineFormatted('To allow generating empty baselines, pass <fg=cyan>--allow-empty-baseline</> option.');

			return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes());
		}
		if ($analysisResult->hasInternalErrors()) {
			$inceptionResult->getStdOutput()->getStyle()->error('An internal error occurred. Baseline could not be generated. Re-run PHPStan without --generate-baseline to see what\'s going on.');

			return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes());
		}

		$streamOutput = $this->createStreamOutput();
		$errorConsoleStyle = new ErrorsConsoleStyle(new StringInput(''), $streamOutput);
		$baselineOutput = new SymfonyOutput($streamOutput, new SymfonyStyle($errorConsoleStyle));
		$baselineFileDirectory = dirname($generateBaselineFile);
		$baselinePathHelper = new ParentDirectoryRelativePathHelper($baselineFileDirectory);

		if ($baselineExtension === 'php') {
			$baselineErrorFormatter = new BaselinePhpErrorFormatter($baselinePathHelper);
			$baselineErrorFormatter->formatErrors($analysisResult, $baselineOutput);
		} else {
			$baselineErrorFormatter = new BaselineNeonErrorFormatter($baselinePathHelper);
			$existingBaselineContent = is_file($generateBaselineFile) ? FileReader::read($generateBaselineFile) : '';
			$baselineErrorFormatter->formatErrors($analysisResult, $baselineOutput, $existingBaselineContent);
		}

		$stream = $streamOutput->getStream();
		rewind($stream);
		$baselineContents = stream_get_contents($stream);
		if ($baselineContents === false) {
			throw new ShouldNotHappenException();
		}

		if (!is_dir($baselineFileDirectory)) {
			$mkdirResult = @mkdir($baselineFileDirectory, 0644, true);
			if ($mkdirResult === false) {
				$inceptionResult->getStdOutput()->writeLineFormatted(sprintf('Failed to create directory "%s".', $baselineFileDirectory));

				return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes());
			}
		}

		try {
			FileWriter::write($generateBaselineFile, $baselineContents);
		} catch (CouldNotWriteFileException $e) {
			$inceptionResult->getStdOutput()->writeLineFormatted($e->getMessage());

			return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes());
		}

		$errorsCount = 0;
		$unignorableCount = 0;
		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			if (!$fileSpecificError->canBeIgnored()) {
				$unignorableCount++;
				if ($output->isVeryVerbose()) {
					$inceptionResult->getStdOutput()->writeLineFormatted('Unignorable could not be added to the baseline:');
					$inceptionResult->getStdOutput()->writeLineFormatted($fileSpecificError->getMessage());
					$inceptionResult->getStdOutput()->writeLineFormatted($fileSpecificError->getFile());
					$inceptionResult->getStdOutput()->writeLineFormatted('');
				}
				continue;
			}

			$errorsCount++;
		}

		$message = sprintf('Baseline generated with %d %s.', $errorsCount, $errorsCount === 1 ? 'error' : 'errors');

		if (
			$unignorableCount === 0
			&& count($analysisResult->getNotFileSpecificErrors()) === 0
		) {
			$inceptionResult->getStdOutput()->getStyle()->success($message);
		} else {
			$inceptionResult->getStdOutput()->getStyle()->warning($message . "\nSome errors could not be put into baseline. Re-run PHPStan and fix them.");
		}

		return $inceptionResult->handleReturn(0, $analysisResult->getPeakMemoryUsageBytes());
	}

	/**
	 * @param string[] $files
	 */
	private function runFixer(InceptionResult $inceptionResult, AnalysisResult $analysisResult, Container $container, bool $onlyFiles, InputInterface $input, OutputInterface $output, array $files): int
	{
		$ciDetector = new CiDetector();
		if ($ciDetector->isCiDetected()) {
			$inceptionResult->getStdOutput()->writeLineFormatted('PHPStan Pro can\'t run in CI environment yet. Stay tuned!');

			return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes());
		}
		$hasInternalErrors = $analysisResult->hasInternalErrors();
		$nonIgnorableErrorsByException = [];
		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			if (!$fileSpecificError->hasNonIgnorableException()) {
				continue;
			}

			$nonIgnorableErrorsByException[] = $fileSpecificError;
		}

		if ($hasInternalErrors || count($nonIgnorableErrorsByException) > 0) {
			$fixerAnalysisResult = new AnalysisResult(
				$nonIgnorableErrorsByException,
				$analysisResult->getInternalErrors(),
				$analysisResult->getInternalErrors(),
				[],
				$analysisResult->getCollectedData(),
				$analysisResult->isDefaultLevelUsed(),
				$analysisResult->getProjectConfigFile(),
				$analysisResult->isResultCacheSaved(),
				$analysisResult->getPeakMemoryUsageBytes(),
			);

			$stdOutput = $inceptionResult->getStdOutput();
			$stdOutput->getStyle()->error('PHPStan Pro can\'t be launched because of these errors:');

			/** @var TableErrorFormatter $tableErrorFormatter */
			$tableErrorFormatter = $container->getService('errorFormatter.table');
			$tableErrorFormatter->formatErrors($fixerAnalysisResult, $stdOutput);

			$stdOutput->writeLineFormatted('Please fix them first and then re-run PHPStan.');

			if ($stdOutput->isDebug()) {
				$stdOutput->writeLineFormatted(sprintf('hasInternalErrors: %s', $hasInternalErrors ? 'true' : 'false'));
				$stdOutput->writeLineFormatted(sprintf('nonIgnorableErrorsByExceptionCount: %d', count($nonIgnorableErrorsByException)));
			}

			return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes());
		}

		if (!$analysisResult->isResultCacheSaved() && !$onlyFiles) {
			// this can happen only if there are some regex-related errors in ignoreErrors configuration
			$stdOutput = $inceptionResult->getStdOutput();
			if (count($analysisResult->getFileSpecificErrors()) > 0) {
				$stdOutput->getStyle()->error('Unknown error. Please report this as a bug.');
				return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes());
			}

			$stdOutput->getStyle()->error('PHPStan Pro can\'t be launched because of these errors:');

			/** @var TableErrorFormatter $tableErrorFormatter */
			$tableErrorFormatter = $container->getService('errorFormatter.table');
			$tableErrorFormatter->formatErrors($analysisResult, $stdOutput);

			$stdOutput->writeLineFormatted('Please fix them first and then re-run PHPStan.');

			if ($stdOutput->isDebug()) {
				$stdOutput->writeLineFormatted('Result cache was not saved.');
			}

			return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes());
		}

		$inceptionResult->handleReturn(0, $analysisResult->getPeakMemoryUsageBytes());

		/** @var FixerApplication $fixerApplication */
		$fixerApplication = $container->getByType(FixerApplication::class);

		return $fixerApplication->run(
			$inceptionResult->getProjectConfigFile(),
			$inceptionResult,
			$input,
			$output,
			$analysisResult->getFileSpecificErrors(),
			$analysisResult->getNotFileSpecificErrors(),
			count($files),
			$_SERVER['argv'][0],
		);
	}

}
