<?php declare(strict_types = 1);

namespace PHPStan\Command;

use OndraM\CiDetector\CiDetector;
use PHPStan\Analyser\InternalError;
use PHPStan\Command\ErrorFormatter\BaselineNeonErrorFormatter;
use PHPStan\Command\ErrorFormatter\BaselinePhpErrorFormatter;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
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
use PHPStan\Internal\DirectoryCreator;
use PHPStan\Internal\DirectoryCreatorException;
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
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_unique;
use function array_values;
use function count;
use function dirname;
use function filesize;
use function fopen;
use function get_class;
use function implode;
use function in_array;
use function is_array;
use function is_bool;
use function is_file;
use function is_string;
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
				new InputOption('xdebug', null, InputOption::VALUE_NONE, 'Allow running with Xdebug for debugging purposes'),
				new InputOption('fix', null, InputOption::VALUE_NONE, 'Launch PHPStan Pro'),
				new InputOption('watch', null, InputOption::VALUE_NONE, 'Launch PHPStan Pro'),
				new InputOption('pro', null, InputOption::VALUE_NONE, 'Launch PHPStan Pro'),
				new InputOption('fail-without-result-cache', null, InputOption::VALUE_NONE, 'Return non-zero exit code when result cache is not used'),
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
		$failWithoutResultCache = (bool) $input->getOption('fail-without-result-cache');

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
		/** @var RelativePathHelper $relativePathHelper */
		$relativePathHelper = $container->getService('relativePathHelper');
		foreach ($analysedConfigFiles as $analysedConfigFile) {
			$fileSize = @filesize($analysedConfigFile);
			if ($fileSize === false) {
				continue;
			}

			if ($fileSize <= 512 * 1024) {
				continue;
			}

			$inceptionResult->getErrorOutput()->getStyle()->warning(sprintf(
				'Configuration file %s (%s) is too big and might slow down PHPStan. Consider adding it to excludePaths.',
				$relativePathHelper->getRelativePath($analysedConfigFile),
				BytesHelper::bytes($fileSize),
			));
		}

		if ($fix) {
			if ($generateBaselineFile !== null) {
				$inceptionResult->getStdOutput()->getStyle()->error('You cannot pass the --generate-baseline option when running PHPStan Pro.');
				return $inceptionResult->handleReturn(1, null);
			}

			return $this->runFixer($inceptionResult, $container, $onlyFiles, $input, $output, $files);
		}

		/** @var AnalyseApplication $application */
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

		/**
		 * Variable $internalErrorsTuples contains both "internal errors"
		 * and "errors with non-ignorable exception" as InternalError objects.
		 */
		$internalErrorsTuples = [];
		$internalFileSpecificErrors = [];
		foreach ($analysisResult->getInternalErrorObjects() as $internalError) {
			$internalErrorsTuples[$internalError->getMessage()] = [new InternalError(
				$internalError->getTraceAsString() !== null ? sprintf('Internal error: %s', $internalError->getMessage()) : $internalError->getMessage(),
				$internalError->getContextDescription(),
				$internalError->getTrace(),
				$internalError->getTraceAsString(),
				$internalError->shouldReportBug(),
			), false];
		}
		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			if (!$fileSpecificError->hasNonIgnorableException()) {
				continue;
			}

			$message = $fileSpecificError->getMessage();
			$metadata = $fileSpecificError->getMetadata();
			$hasStackTrace = false;
			if (
				$fileSpecificError->getIdentifier() === 'phpstan.internal'
				&& array_key_exists(InternalError::STACK_TRACE_AS_STRING_METADATA_KEY, $metadata)
			) {
				$message = sprintf('Internal error: %s', $message);
				$hasStackTrace = true;
			}

			if (!$hasStackTrace) {
				if (!array_key_exists($fileSpecificError->getMessage(), $internalFileSpecificErrors)) {
					$internalFileSpecificErrors[$fileSpecificError->getMessage()] = $fileSpecificError;
				}
			}

			$internalErrorsTuples[$fileSpecificError->getMessage()] = [new InternalError(
				$message,
				sprintf('analysing file %s', $fileSpecificError->getTraitFilePath() ?? $fileSpecificError->getFilePath()),
				$metadata[InternalError::STACK_TRACE_METADATA_KEY] ?? [],
				$metadata[InternalError::STACK_TRACE_AS_STRING_METADATA_KEY] ?? null,
				true,
			), !$hasStackTrace];
		}

		$internalErrorsTuples = array_values($internalErrorsTuples);
		$bugReportUrl = 'https://github.com/phpstan/phpstan/issues/new?template=Bug_report.yaml';

		/**
		 * Variable $internalErrors only contains non-file-specific "internal errors".
		 */
		$internalErrors = [];
		foreach ($internalErrorsTuples as [$internalError, $isInFileSpecificErrors]) {
			if ($isInFileSpecificErrors) {
				continue;
			}

			$message = sprintf('%s while %s', $internalError->getMessage(), $internalError->getContextDescription());
			if ($internalError->getTraceAsString() !== null) {
				if (OutputInterface::VERBOSITY_VERBOSE <= $output->getVerbosity()) {
					$firstTraceItem = $internalError->getTrace()[0] ?? null;
					$trace = '';
					if ($firstTraceItem !== null && $firstTraceItem['file'] !== null && $firstTraceItem['line'] !== null) {
						$trace = sprintf('## %s(%d)%s', $firstTraceItem['file'], $firstTraceItem['line'], "\n");
					}
					$trace .= $internalError->getTraceAsString();

					if ($internalError->shouldReportBug()) {
						$message .= sprintf('%sPost the following stack trace to %s: %s%s', "\n", $bugReportUrl, "\n", $trace);
					} else {
						$message .= sprintf('%s%s', "\n\n", $trace);
					}
				} else {
					if ($internalError->shouldReportBug()) {
						$message .= sprintf('%sRun PHPStan with -v option and post the stack trace to:%s%s%s', "\n\n", "\n", $bugReportUrl, "\n");
					} else {
						$message .= sprintf('%sRun PHPStan with -v option to see the stack trace', "\n");
					}
				}
			}

			$internalErrors[] = new InternalError(
				$message,
				$internalError->getContextDescription(),
				$internalError->getTrace(),
				$internalError->getTraceAsString(),
				$internalError->shouldReportBug(),
			);
		}

		if ($generateBaselineFile !== null) {
			if (count($internalErrorsTuples) > 0) {
				foreach ($internalErrorsTuples as [$internalError]) {
					$inceptionResult->getStdOutput()->writeLineFormatted($internalError->getMessage());
					$inceptionResult->getStdOutput()->writeLineFormatted('');
				}

				$inceptionResult->getStdOutput()->getStyle()->error(sprintf(
					'%s occurred. Baseline could not be generated.',
					count($internalErrors) === 1 ? 'An internal error' : 'Internal errors',
				));

				return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes());
			}

			return $this->generateBaseline($generateBaselineFile, $inceptionResult, $analysisResult, $output, $allowEmptyBaseline, $baselineExtension, $failWithoutResultCache);
		}

		/** @var ErrorFormatter $errorFormatter */
		$errorFormatter = $container->getService($errorFormatterServiceName);

		if (count($internalErrorsTuples) > 0) {
			$analysisResult = new AnalysisResult(
				array_values($internalFileSpecificErrors),
				array_map(static fn (InternalError $internalError) => $internalError->getMessage(), $internalErrors),
				[],
				[],
				[],
				$analysisResult->isDefaultLevelUsed(),
				$analysisResult->getProjectConfigFile(),
				$analysisResult->isResultCacheSaved(),
				$analysisResult->getPeakMemoryUsageBytes(),
				$analysisResult->isResultCacheUsed(),
				$analysisResult->getChangedProjectExtensionFilesOutsideOfAnalysedPaths(),
			);

			$exitCode = $errorFormatter->formatErrors($analysisResult, $inceptionResult->getStdOutput());

			$errorOutput->writeLineFormatted('⚠️  Result is incomplete because of internal errors. ⚠️');
			$errorOutput->writeLineFormatted('   Fix these errors first and then re-run PHPStan');
			$errorOutput->writeLineFormatted('   to get all reported errors.');
			$errorOutput->writeLineFormatted('');

			return $inceptionResult->handleReturn(
				$exitCode,
				$analysisResult->getPeakMemoryUsageBytes(),
			);
		}

		$exitCode = $errorFormatter->formatErrors($analysisResult, $inceptionResult->getStdOutput());
		if ($failWithoutResultCache && !$analysisResult->isResultCacheUsed()) {
			$exitCode = 2;
		}

		if (
			$analysisResult->isResultCacheUsed()
			&& $analysisResult->isResultCacheSaved()
			&& !$onlyFiles
			&& $inceptionResult->getProjectConfigArray() !== null
		) {
			$projectServicesNotInAnalysedPaths = array_values(array_unique($analysisResult->getChangedProjectExtensionFilesOutsideOfAnalysedPaths()));
			$projectServiceFileNamesNotInAnalysedPaths = array_keys($analysisResult->getChangedProjectExtensionFilesOutsideOfAnalysedPaths());

			if (count($projectServicesNotInAnalysedPaths) > 0) {
				$one = count($projectServicesNotInAnalysedPaths) === 1;
				$errorOutput->writeLineFormatted('<comment>Result cache might not behave correctly.</comment>');
				$errorOutput->writeLineFormatted(sprintf('You\'re using custom %s in your project config', $one ? 'extension' : 'extensions'));
				$errorOutput->writeLineFormatted(sprintf('but %s not part of analysed paths:', $one ? 'this extension is' : 'these extensions are'));
				$errorOutput->writeLineFormatted('');
				foreach ($projectServicesNotInAnalysedPaths as $service) {
					$errorOutput->writeLineFormatted(sprintf('- %s', $service));
				}

				$errorOutput->writeLineFormatted('');

				$errorOutput->writeLineFormatted('When you edit them and re-run PHPStan, the result cache will get stale.');

				$directoriesToAdd = [];
				foreach ($projectServiceFileNamesNotInAnalysedPaths as $path) {
					$directoriesToAdd[] = dirname($relativePathHelper->getRelativePath($path));
				}

				$directoriesToAdd = array_unique($directoriesToAdd);
				$oneDirectory = count($directoriesToAdd) === 1;

				$errorOutput->writeLineFormatted(sprintf('Add %s to your analysed paths to get rid of this problem:', $oneDirectory ? 'this directory' : 'these directories'));

				$errorOutput->writeLineFormatted('');

				foreach ($directoriesToAdd as $directory) {
					$errorOutput->writeLineFormatted(sprintf('- %s', $directory));
				}

				$errorOutput->writeLineFormatted('');

				$bleedingEdge = (bool) $container->getParameter('featureToggles')['projectServicesNotInAnalysedPaths'];
				if ($bleedingEdge) {
					return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes());
				}

				$errorOutput->getStyle()->warning('This will cause a non-zero exit code in PHPStan 2.0.');
			}
		}

		return $inceptionResult->handleReturn(
			$exitCode,
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

	private function generateBaseline(string $generateBaselineFile, InceptionResult $inceptionResult, AnalysisResult $analysisResult, OutputInterface $output, bool $allowEmptyBaseline, string $baselineExtension, bool $failWithoutResultCache): int
	{
		if (!$allowEmptyBaseline && !$analysisResult->hasErrors()) {
			$inceptionResult->getStdOutput()->getStyle()->error('No errors were found during the analysis. Baseline could not be generated.');
			$inceptionResult->getStdOutput()->writeLineFormatted('To allow generating empty baselines, pass <fg=cyan>--allow-empty-baseline</> option.');

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

		try {
			DirectoryCreator::ensureDirectoryExists($baselineFileDirectory, 0644);
		} catch (DirectoryCreatorException $e) {
			$inceptionResult->getStdOutput()->writeLineFormatted($e->getMessage());

			return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes());
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

		$exitCode = 0;
		if ($failWithoutResultCache && !$analysisResult->isResultCacheUsed()) {
			$exitCode = 2;
		}

		return $inceptionResult->handleReturn($exitCode, $analysisResult->getPeakMemoryUsageBytes());
	}

	/**
	 * @param string[] $files
	 */
	private function runFixer(InceptionResult $inceptionResult, Container $container, bool $onlyFiles, InputInterface $input, OutputInterface $output, array $files): int
	{
		$ciDetector = new CiDetector();
		if ($ciDetector->isCiDetected()) {
			$inceptionResult->getStdOutput()->writeLineFormatted('PHPStan Pro can\'t run in CI environment yet. Stay tuned!');

			return $inceptionResult->handleReturn(1, null);
		}

		/** @var FixerApplication $fixerApplication */
		$fixerApplication = $container->getByType(FixerApplication::class);

		return $fixerApplication->run(
			$inceptionResult->getProjectConfigFile(),
			$input,
			$output,
			count($files),
			$_SERVER['argv'][0],
		);
	}

}
