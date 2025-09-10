<?php declare(strict_types = 1);

namespace PHPStan\Command;

use OndraM\CiDetector\CiDetector;
use Override;
use PHPStan\Analyser\InternalError;
use PHPStan\Command\ErrorFormatter\BaselineNeonErrorFormatter;
use PHPStan\Command\ErrorFormatter\BaselinePhpErrorFormatter;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\Command\Symfony\SymfonyStyle;
use PHPStan\DependencyInjection\Container;
use PHPStan\Diagnose\DiagnoseExtension;
use PHPStan\Diagnose\PHPStanDiagnoseExtension;
use PHPStan\File\CouldNotWriteFileException;
use PHPStan\File\FileHelper;
use PHPStan\File\FileReader;
use PHPStan\File\FileWriter;
use PHPStan\File\ParentDirectoryRelativePathHelper;
use PHPStan\File\PathNotFoundException;
use PHPStan\File\RelativePathHelper;
use PHPStan\Fixable\FileChangedException;
use PHPStan\Fixable\MergeConflictException;
use PHPStan\Fixable\Patcher;
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
use function array_reverse;
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
use function str_contains;
use function stream_get_contents;
use function strlen;
use function substr;
use const PATHINFO_BASENAME;
use const PATHINFO_EXTENSION;

/**
 * @phpstan-import-type Trace from InternalError as InternalErrorTrace
 */
final class AnalyseCommand extends Command
{

	private const NAME = 'analyse';

	public const OPTION_LEVEL = 'level';

	public const DEFAULT_LEVEL = CommandHelper::DEFAULT_LEVEL;

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		private array $composerAutoloaderProjectPaths,
		private float $analysisStartTime,
	)
	{
		parent::__construct();
	}

	#[Override]
	protected function configure(): void
	{
		$this->setName(self::NAME)
			->setDescription('Analyses source code')
			->setDefinition([
				new InputArgument('paths', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Paths with source code to run analysis on'),
				new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
				new InputOption(self::OPTION_LEVEL, 'l', InputOption::VALUE_REQUIRED, 'Level of rule options - the higher the stricter'),
				new InputOption(ErrorsConsoleStyle::OPTION_NO_PROGRESS, mode: InputOption::VALUE_NONE, description: 'Do not show progress bar, only results'),
				new InputOption('debug', mode: InputOption::VALUE_NONE, description: 'Show debug information - which file is analysed, do not catch internal errors'),
				new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
				new InputOption('error-format', mode: InputOption::VALUE_REQUIRED, description: 'Format in which to print the result of the analysis'),
				new InputOption('generate-baseline', 'b', InputOption::VALUE_OPTIONAL, 'Path to a file where the baseline should be saved', false),
				new InputOption('allow-empty-baseline', mode: InputOption::VALUE_NONE, description: 'Do not error out when the generated baseline is empty'),
				new InputOption('memory-limit', mode: InputOption::VALUE_REQUIRED, description: 'Memory limit for analysis'),
				new InputOption('xdebug', mode: InputOption::VALUE_NONE, description: 'Allow running with Xdebug for debugging purposes'),
				new InputOption('tmp-file', mode: InputOption::VALUE_REQUIRED, description: '(Editor mode) Edited file used in place of --instead-of file'),
				new InputOption('instead-of', mode: InputOption::VALUE_REQUIRED, description: '(Editor mode) File being replaced by --tmp-file'),
				new InputOption('fix', mode: InputOption::VALUE_NONE, description: 'Fix auto-fixable errors (experimental)'),
				new InputOption('watch', mode: InputOption::VALUE_NONE, description: 'Launch PHPStan Pro'),
				new InputOption('pro', mode: InputOption::VALUE_NONE, description: 'Launch PHPStan Pro'),
				new InputOption('fail-without-result-cache', mode: InputOption::VALUE_NONE, description: 'Return non-zero exit code when result cache is not used'),
			]);
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getAliases(): array
	{
		return ['analyze'];
	}

	#[Override]
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

	#[Override]
	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$paths = $input->getArgument('paths');
		$memoryLimit = $input->getOption('memory-limit');
		$autoloadFile = $input->getOption('autoload-file');
		$configuration = $input->getOption('configuration');
		$level = $input->getOption(self::OPTION_LEVEL);
		$allowXdebug = $input->getOption('xdebug');
		$debugEnabled = (bool) $input->getOption('debug');
		$pro = (bool) $input->getOption('watch') || (bool) $input->getOption('pro');
		$fix = (bool) $input->getOption('fix');
		$failWithoutResultCache = (bool) $input->getOption('fail-without-result-cache');

		/** @var string|false|null $generateBaselineFile */
		$generateBaselineFile = $input->getOption('generate-baseline');
		if ($generateBaselineFile === false) {
			$generateBaselineFile = null;
		} elseif ($generateBaselineFile === null) {
			$generateBaselineFile = 'phpstan-baseline.neon';
		}

		$allowEmptyBaseline = (bool) $input->getOption('allow-empty-baseline');

		$tmpFile = $input->getOption('tmp-file');
		$insteadOfFile = $input->getOption('instead-of');

		if (
			!is_array($paths)
			|| (!is_string($memoryLimit) && $memoryLimit !== null)
			|| (!is_string($autoloadFile) && $autoloadFile !== null)
			|| (!is_string($configuration) && $configuration !== null)
			|| (!is_string($level) && $level !== null)
			|| (!is_string($tmpFile) && $tmpFile !== null)
			|| (!is_string($insteadOfFile) && $insteadOfFile !== null)
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
				$tmpFile,
				$insteadOfFile,
				true,
			);
		} catch (InceptionNotSuccessfulException $e) {
			return 1;
		}

		if ($generateBaselineFile === null && $allowEmptyBaseline) {
			$inceptionResult->getStdOutput()->getStyle()->error('You must pass the --generate-baseline option alongside --allow-empty-baseline.');
			return $inceptionResult->handleReturn(1, null, $this->analysisStartTime);
		}

		if ($inceptionResult->getEditorModeTmpFile() !== null) {
			if ($generateBaselineFile !== null) {
				$inceptionResult->getStdOutput()->getStyle()->error('Editor mode options --tmp-file and --instead-of cannot be used when generating the baseline.');
				return $inceptionResult->handleReturn(1, null, $this->analysisStartTime);
			}
			if ($pro) {
				$inceptionResult->getStdOutput()->getStyle()->error('Editor mode options --tmp-file and --instead-of cannot be used with PHPStan Pro.');
				return $inceptionResult->handleReturn(1, null, $this->analysisStartTime);
			}
			if ($fix) {
				$inceptionResult->getStdOutput()->getStyle()->error('Editor mode options --tmp-file and --instead-of cannot be used with --fix.');
				return $inceptionResult->handleReturn(1, null, $this->analysisStartTime);
			}
		}

		if ($fix) {
			if ($generateBaselineFile !== null) {
				$inceptionResult->getStdOutput()->getStyle()->error('Errors cannot be fixed when generating the baseline.');
				return $inceptionResult->handleReturn(1, null, $this->analysisStartTime);
			}

			$inceptionResult->getErrorOutput()->getStyle()->note('The --fix CLI option no longer launches PHPStan Pro. Use --pro instead if you want to launch PHPStan Pro');
		}

		$errorOutput = $inceptionResult->getErrorOutput();
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
				return $inceptionResult->handleReturn(1, null, $this->analysisStartTime);
			}

			if (!in_array($baselineExtension, ['neon', 'php'], true)) {
				$inceptionResult->getStdOutput()->getStyle()->error(sprintf('Baseline filename extension must be .neon or .php, .%s was used instead.', $baselineExtension));

				return $inceptionResult->handleReturn(1, null, $this->analysisStartTime);
			}
		}

		try {
			[$files, $onlyFiles] = $inceptionResult->getFiles();
		} catch (PathNotFoundException $e) {
			$this->runDiagnoseExtensions($container, $inceptionResult->getErrorOutput());
			$inceptionResult->getErrorOutput()->writeLineFormatted(sprintf('<error>%s</error>', $e->getMessage()));
			return 1;
		} catch (InceptionNotSuccessfulException) {
			$this->runDiagnoseExtensions($container, $inceptionResult->getErrorOutput());
			return 1;
		}

		if (count($files) === 0) {
			$this->runDiagnoseExtensions($container, $inceptionResult->getErrorOutput());

			$inceptionResult->getErrorOutput()->getStyle()->error('No files found to analyse.');

			return $inceptionResult->handleReturn(1, null, $this->analysisStartTime);
		}

		if ($inceptionResult->getEditorModeInsteadOfFile() !== null) {
			if (!in_array($inceptionResult->getEditorModeInsteadOfFile(), $files, true)) {
				$inceptionResult->getStdOutput()->getStyle()->error(sprintf('File %s passed to --instead-of is not in analysed project files.', $inceptionResult->getEditorModeInsteadOfFile()));
				return $inceptionResult->handleReturn(1, null, $this->analysisStartTime);
			}
		}

		if ($inceptionResult->getEditorModeTmpFile() !== null) {
			if (in_array($inceptionResult->getEditorModeTmpFile(), $files, true)) {
				$inceptionResult->getStdOutput()->getStyle()->error(sprintf('File %s passed to --tmp-file is already in analysed project files.', $inceptionResult->getEditorModeInsteadOfFile()));
				return $inceptionResult->handleReturn(1, null, $this->analysisStartTime);
			}
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

		if ($pro) {
			if ($generateBaselineFile !== null) {
				$inceptionResult->getStdOutput()->getStyle()->error('You cannot pass the --generate-baseline option when running PHPStan Pro.');
				return $inceptionResult->handleReturn(1, null, $this->analysisStartTime);
			}

			return $this->runFixer($inceptionResult, $container, $onlyFiles, $input, $output, $files);
		}

		/** @var AnalyseApplication $application */
		$application = $container->getByType(AnalyseApplication::class);

		$debug = $input->getOption('debug');
		if (!is_bool($debug)) {
			throw new ShouldNotHappenException();
		}

		if ($fix) {
			$inceptionResult->getErrorOutput()->writeLineFormatted('Analysing files...');
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
				$inceptionResult->getEditorModeTmpFile(),
				$inceptionResult->getEditorModeInsteadOfFile(),
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

				return $inceptionResult->handleReturn(1, null, $this->analysisStartTime);
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

		$fileHelper = $container->getByType(FileHelper::class);

		/**
		 * Variable $internalErrors only contains non-file-specific "internal errors".
		 */
		$internalErrors = [];
		foreach ($internalErrorsTuples as [$internalError, $isInFileSpecificErrors]) {
			if ($isInFileSpecificErrors) {
				continue;
			}

			$internalErrors[] = new InternalError(
				$this->getMessageFromInternalError($fileHelper, $internalError, $output->getVerbosity()),
				$internalError->getContextDescription(),
				$internalError->getTrace(),
				$internalError->getTraceAsString(),
				$internalError->shouldReportBug(),
			);
		}

		if ($generateBaselineFile !== null) {
			$this->runDiagnoseExtensions($container, $inceptionResult->getErrorOutput());
			if (count($internalErrorsTuples) > 0) {
				foreach ($internalErrorsTuples as [$internalError]) {
					$inceptionResult->getStdOutput()->writeLineFormatted($internalError->getMessage());
					$inceptionResult->getStdOutput()->writeLineFormatted('');
				}

				$inceptionResult->getStdOutput()->getStyle()->error(sprintf(
					'%s occurred. Baseline could not be generated.',
					count($internalErrors) === 1 ? 'An internal error' : 'Internal errors',
				));

				return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes(), $this->analysisStartTime);
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

			$this->runDiagnoseExtensions($container, $inceptionResult->getErrorOutput());

			$errorOutput->writeLineFormatted('⚠️  Result is incomplete because of severe errors. ⚠️');
			$errorOutput->writeLineFormatted('   Fix these errors first and then re-run PHPStan');
			$errorOutput->writeLineFormatted('   to get all reported errors.');
			$errorOutput->writeLineFormatted('');

			return $inceptionResult->handleReturn(
				$exitCode,
				$analysisResult->getPeakMemoryUsageBytes(),
				$this->analysisStartTime,
			);
		}

		if ($fix) {
			$fixableErrors = [];
			foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
				if ($fileSpecificError->getFixedErrorDiff() === null) {
					continue;
				}

				$fixableErrors[] = $fileSpecificError;
			}

			$fixableErrorsCount = count($fixableErrors);
			if ($fixableErrorsCount === 0) {
				$inceptionResult->getStdOutput()->getStyle()->error('No fixable errors found');
				$exitCode = 1;
			} else {
				$skippedCount = 0;
				$diffsByFile = [];
				foreach ($fixableErrors as $fixableError) {
					$fixFile = $fixableError->getFilePath();
					if ($fixableError->getTraitFilePath() !== null) {
						$fixFile = $fixableError->getTraitFilePath();
					}

					if ($fixableError->getFixedErrorDiff() === null) {
						throw new ShouldNotHappenException();
					}

					$diffsByFile[$fixFile][] = $fixableError->getFixedErrorDiff();
				}

				$inceptionResult->getErrorOutput()->writeLineFormatted('Fixing errors...');
				$errorOutput->getStyle()->progressStart($fixableErrorsCount);

				$patcher = $container->getByType(Patcher::class);
				foreach ($diffsByFile as $file => $diffs) {
					$diffsCount = count($diffs);
					try {
						$finalFileContents = $patcher->applyDiffs($file, $diffs);
						$errorOutput->getStyle()->progressAdvance($diffsCount);
					} catch (FileChangedException | MergeConflictException) {
						$skippedCount += $diffsCount;
						$errorOutput->getStyle()->progressAdvance($diffsCount);
						continue;
					}

					FileWriter::write($file, $finalFileContents);
				}

				$errorOutput->getStyle()->progressFinish();

				if ($skippedCount > 0) {
					$inceptionResult->getStdOutput()->getStyle()->warning(sprintf(
						'%d %s fixed, %d %s skipped',
						$fixableErrorsCount,
						$fixableErrorsCount === 1 ? 'error' : 'errors',
						$skippedCount,
						$skippedCount === 1 ? 'error' : 'errors',
					));
				} else {
					$inceptionResult->getStdOutput()->getStyle()->success(sprintf(
						'%d %s fixed',
						$fixableErrorsCount,
						$fixableErrorsCount === 1 ? 'error' : 'errors',
					));
				}
				$exitCode = 0;
			}
		} else {
			$exitCode = $errorFormatter->formatErrors($analysisResult, $inceptionResult->getStdOutput());
		}

		if ($exitCode === 0 && $failWithoutResultCache && !$analysisResult->isResultCacheUsed()) {
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

				return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes(), $this->analysisStartTime);
			}
		}

		$this->runDiagnoseExtensions($container, $inceptionResult->getErrorOutput());

		return $inceptionResult->handleReturn(
			$exitCode,
			$analysisResult->getPeakMemoryUsageBytes(),
			$this->analysisStartTime,
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

	private function getMessageFromInternalError(FileHelper $fileHelper, InternalError $internalError, int $verbosity): string
	{
		$message = sprintf('%s while %s', $internalError->getMessage(), $internalError->getContextDescription());
		$hasLarastan = false;
		$isLaravelLast = false;

		foreach (array_reverse($internalError->getTrace()) as $traceItem) {
			if ($traceItem['file'] === null) {
				continue;
			}

			$file = $fileHelper->normalizePath($traceItem['file'], '/');

			if (str_contains($file, '/larastan/')) {
				$hasLarastan = true;
				$isLaravelLast = false;
				continue;
			}

			if (!str_contains($file, '/laravel/framework/')) {
				continue;
			}

			$isLaravelLast = true;
		}
		if ($hasLarastan) {
			if ($isLaravelLast) {
				$message .= "\n";
				$message .= "\n" . 'This message is coming from Laravel Framework itself.';
				$message .= "\n" . 'Larastan boots up your application in order to provide';
				$message .= "\n" . 'smarter static analysis of your codebase.';
				$message .= "\n";
				$message .= "\n" . 'In order to do that, the environment you run PHPStan in';
				$message .= "\n" . 'must match the environment you run your application in.';
				$message .= "\n";
				$message .= "\n" . 'Make sure you\'ve set your environment variables';
				$message .= "\n" . 'or the .env file correctly.';

				return $message;
			}

			$bugReportUrl = 'https://github.com/larastan/larastan/issues/new?template=bug-report.md';
		} else {
			$bugReportUrl = 'https://github.com/phpstan/phpstan/issues/new?template=Bug_report.yaml';
		}
		if ($internalError->getTraceAsString() !== null) {
			if (OutputInterface::VERBOSITY_VERBOSE <= $verbosity) {
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

		return $message;
	}

	private function generateBaseline(string $generateBaselineFile, InceptionResult $inceptionResult, AnalysisResult $analysisResult, OutputInterface $output, bool $allowEmptyBaseline, string $baselineExtension, bool $failWithoutResultCache): int
	{
		if (!$allowEmptyBaseline && !$analysisResult->hasErrors()) {
			$inceptionResult->getStdOutput()->getStyle()->error('No errors were found during the analysis. Baseline could not be generated.');
			$inceptionResult->getStdOutput()->writeLineFormatted('To allow generating empty baselines, pass <fg=cyan>--allow-empty-baseline</> option.');

			return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes(), $this->analysisStartTime);
		}

		$streamOutput = $this->createStreamOutput();
		$errorConsoleStyle = new ErrorsConsoleStyle(new StringInput(''), $streamOutput);
		$baselineOutput = new SymfonyOutput($streamOutput, new SymfonyStyle($errorConsoleStyle));
		$baselineFileDirectory = dirname($generateBaselineFile);
		$baselinePathHelper = new ParentDirectoryRelativePathHelper($baselineFileDirectory);
		$rawMessageInBaseline = $inceptionResult->getContainer()->getParameter('featureToggles')['rawMessageInBaseline'];

		if ($baselineExtension === 'php') {
			$baselineErrorFormatter = new BaselinePhpErrorFormatter($baselinePathHelper, $rawMessageInBaseline);
			$baselineErrorFormatter->formatErrors($analysisResult, $baselineOutput);
		} else {
			$baselineErrorFormatter = new BaselineNeonErrorFormatter($baselinePathHelper, $rawMessageInBaseline);
			$existingBaselineContent = is_file($generateBaselineFile) ? FileReader::read($generateBaselineFile) : '';
			$baselineErrorFormatter->formatErrors($analysisResult, $baselineOutput, $existingBaselineContent);
		}

		$stream = $streamOutput->getStream();
		rewind($stream);
		$baselineContents = stream_get_contents($stream);

		try {
			DirectoryCreator::ensureDirectoryExists($baselineFileDirectory, 0644);
		} catch (DirectoryCreatorException $e) {
			$inceptionResult->getStdOutput()->writeLineFormatted($e->getMessage());

			return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes(), $this->analysisStartTime);
		}

		try {
			FileWriter::write($generateBaselineFile, $baselineContents);
		} catch (CouldNotWriteFileException $e) {
			$inceptionResult->getStdOutput()->writeLineFormatted($e->getMessage());

			return $inceptionResult->handleReturn(1, $analysisResult->getPeakMemoryUsageBytes(), $this->analysisStartTime);
		}

		$errorsCount = 0;
		$unignorableCount = 0;
		foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
			if (!$fileSpecificError->canBeIgnored()) {
				$unignorableCount++;
				if ($output->isVeryVerbose()) {
					$inceptionResult->getStdOutput()->writeLineFormatted('<error>Unignorable errors could not be added to the baseline:</error>');
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
			if ($output->isVeryVerbose()) {
				$inceptionResult->getStdOutput()->getStyle()->warning($message . "\nSome errors could not be put into baseline.");
			} else {
				$inceptionResult->getStdOutput()->getStyle()->warning($message . "\nSome errors could not be put into baseline. Re-run PHPStan with \"-vv\" and fix them.");
			}
		}

		$exitCode = 0;
		if ($failWithoutResultCache && !$analysisResult->isResultCacheUsed()) {
			$exitCode = 2;
		}

		return $inceptionResult->handleReturn($exitCode, $analysisResult->getPeakMemoryUsageBytes(), $this->analysisStartTime);
	}

	/**
	 * @param string[] $files
	 */
	private function runFixer(InceptionResult $inceptionResult, Container $container, bool $onlyFiles, InputInterface $input, OutputInterface $output, array $files): int
	{
		$ciDetector = new CiDetector();
		if ($ciDetector->isCiDetected()) {
			$inceptionResult->getStdOutput()->writeLineFormatted('PHPStan Pro can\'t run in CI environment yet. Stay tuned!');

			return $inceptionResult->handleReturn(1, null, $this->analysisStartTime);
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

	private function runDiagnoseExtensions(Container $container, Output $errorOutput): void
	{
		if (!$errorOutput->isDebug()) {
			return;
		}

		/** @var PHPStanDiagnoseExtension $phpstanDiagnoseExtension */
		$phpstanDiagnoseExtension = $container->getService('phpstanDiagnoseExtension');

		// not using tag for this extension to make sure it's always first
		$phpstanDiagnoseExtension->print($errorOutput);

		/** @var DiagnoseExtension $extension */
		foreach ($container->getServicesByTag(DiagnoseExtension::EXTENSION_TAG) as $extension) {
			$extension->print($errorOutput);
		}
	}

}
