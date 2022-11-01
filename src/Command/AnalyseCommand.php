<?php declare(strict_types = 1);

namespace PHPStan\Command;

use OndraM\CiDetector\CiDetector;
use PHPStan\Analyser\ResultCache\ResultCacheClearer;
use PHPStan\Command\ErrorFormatter\BaselineNeonErrorFormatter;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\ErrorFormatter\TableErrorFormatter;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\Command\Symfony\SymfonyStyle;
use PHPStan\File\FileWriter;
use PHPStan\File\ParentDirectoryRelativePathHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use function stream_get_contents;

class AnalyseCommand extends \Symfony\Component\Console\Command\Command
{

	private const NAME = 'analyse';

	public const OPTION_LEVEL = 'level';

	public const DEFAULT_LEVEL = CommandHelper::DEFAULT_LEVEL;

	/** @var string[] */
	private array $composerAutoloaderProjectPaths;

	/**
	 * @param string[] $composerAutoloaderProjectPaths
	 */
	public function __construct(
		array $composerAutoloaderProjectPaths
	)
	{
		parent::__construct();
		$this->composerAutoloaderProjectPaths = $composerAutoloaderProjectPaths;
	}

	protected function configure(): void
	{
		$this->setName(self::NAME)
			->setDescription('Analyses source code')
			->setDefinition([
				new InputArgument('paths', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Paths with source code to run analysis on'),
				new InputOption('paths-file', null, InputOption::VALUE_REQUIRED, 'Path to a file with a list of paths to run analysis on'),
				new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
				new InputOption(self::OPTION_LEVEL, 'l', InputOption::VALUE_REQUIRED, 'Level of rule options - the higher the stricter'),
				new InputOption(ErrorsConsoleStyle::OPTION_NO_PROGRESS, null, InputOption::VALUE_NONE, 'Do not show progress bar, only results'),
				new InputOption('debug', null, InputOption::VALUE_NONE, 'Show debug information - which file is analysed, do not catch internal errors'),
				new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
				new InputOption('error-format', null, InputOption::VALUE_REQUIRED, 'Format in which to print the result of the analysis', null),
				new InputOption('generate-baseline', null, InputOption::VALUE_OPTIONAL, 'Path to a file where the baseline should be saved', false),
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
				throw new \PHPStan\ShouldNotHappenException();
			}
			$application->setCatchExceptions(false);
			return;
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		if ($output instanceof ConsoleOutputInterface) {
			$errorOutput = $output->getErrorOutput();
			$errorOutput->writeln('');
			$errorOutput->writeln("⚠️  You're running a really old version of PHPStan.️");
			$errorOutput->writeln('');
			$errorOutput->writeln('The last release in the 0.12.x series with new features');

			$lastRelease = new \DateTimeImmutable('2021-09-12 00:00:00');
			$daysSince = (time() - $lastRelease->getTimestamp()) / 60 / 60 / 24;
			if ($daysSince > 360) {
				$errorOutput->writeln('and bugfixes was released on <fg=red>September 12th 2021</>,');
				$errorOutput->writeln(sprintf('that\'s <fg=red>%d days ago.</>', floor($daysSince)));
			} else {
				$errorOutput->writeln('and bugfixes was released on <fg=red>September 12th 2021</>.');
			}
			$errorOutput->writeln('');

			$errorOutput->writeln('Since then more than <fg=cyan>67 new PHPStan versions</> were released');
			$errorOutput->writeln('with hundreds of new features, bugfixes, and other');
			$errorOutput->writeln('quality of life improvements.');
			$errorOutput->writeln('');

			$errorOutput->writeln("To learn about what you're missing out on, check out");
			$errorOutput->writeln('this blog with articles about the latest major releases:');
			$errorOutput->writeln('<options=underscore>https://phpstan.org/blog</>');
			$errorOutput->writeln('');

			$errorOutput->writeln('Upgrade today to <fg=green>PHPStan 1.8 or newer</> by using');
			$errorOutput->writeln('<fg=cyan>"phpstan/phpstan": "^1.8"</> in your <fg=cyan>composer.json</>.');
			$errorOutput->writeln('');
		}

		$paths = $input->getArgument('paths');
		$memoryLimit = $input->getOption('memory-limit');
		$autoloadFile = $input->getOption('autoload-file');
		$configuration = $input->getOption('configuration');
		$level = $input->getOption(self::OPTION_LEVEL);
		$pathsFile = $input->getOption('paths-file');
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

		if (
			!is_array($paths)
			|| (!is_string($memoryLimit) && $memoryLimit !== null)
			|| (!is_string($autoloadFile) && $autoloadFile !== null)
			|| (!is_string($configuration) && $configuration !== null)
			|| (!is_string($level) && $level !== null)
			|| (!is_string($pathsFile) && $pathsFile !== null)
			|| (!is_bool($allowXdebug))
		) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		try {
			$inceptionResult = CommandHelper::begin(
				$input,
				$output,
				$paths,
				$pathsFile,
				$memoryLimit,
				$autoloadFile,
				$this->composerAutoloaderProjectPaths,
				$configuration,
				$generateBaselineFile,
				$level,
				$allowXdebug,
				true,
				$debugEnabled
			);
		} catch (\PHPStan\Command\InceptionNotSuccessfulException $e) {
			return 1;
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
			throw new \PHPStan\ShouldNotHappenException();
		}

		if ($errorFormat === null) {
			$errorFormat = 'table';
			$ciDetector = new CiDetector();

			try {
				$ci = $ciDetector->detect();
				if ($ci->getCiName() === CiDetector::CI_GITHUB_ACTIONS) {
					$errorFormat = 'github';
				} elseif ($ci->getCiName() === CiDetector::CI_TEAMCITY) {
					$errorFormat = 'teamcity';
				}
			} catch (\OndraM\CiDetector\Exception\CiNotDetectedException $e) {
				// pass
			}
		}

		$container = $inceptionResult->getContainer();
		$errorFormatterServiceName = sprintf('errorFormatter.%s', $errorFormat);
		if (!$container->hasService($errorFormatterServiceName)) {
			$errorOutput->writeLineFormatted(sprintf(
				'Error formatter "%s" not found. Available error formatters are: %s',
				$errorFormat,
				implode(', ', array_map(static function (string $name): string {
					return substr($name, strlen('errorFormatter.'));
				}, $container->findServiceNamesByType(ErrorFormatter::class)))
			));
			return 1;
		}

		if ($errorFormat === 'baselineNeon') {
			$errorOutput = $inceptionResult->getErrorOutput();
			$errorOutput->writeLineFormatted('⚠️  You\'re using an obsolete option <fg=cyan>--error-format baselineNeon</>. ⚠️️');
			$errorOutput->writeLineFormatted('');
			$errorOutput->writeLineFormatted('   There\'s a new and much better option <fg=cyan>--generate-baseline</>. Here are the advantages:');
			$errorOutput->writeLineFormatted('   1) The current baseline file does not have to be commented-out');
			$errorOutput->writeLineFormatted('      nor emptied when generating the new baseline. It\'s excluded automatically.');
			$errorOutput->writeLineFormatted('   2) Output no longer has to be redirected to a file, PHPStan saves the baseline');
			$errorOutput->writeLineFormatted('      to a specified path (defaults to <fg=cyan>phpstan-baseline.neon</>).');
			$errorOutput->writeLineFormatted('   3) Baseline contains correct relative paths if saved to a subdirectory.');
			$errorOutput->writeLineFormatted('');
		}

		$generateBaselineFile = $inceptionResult->getGenerateBaselineFile();
		if ($generateBaselineFile !== null) {
			$baselineExtension = pathinfo($generateBaselineFile, PATHINFO_EXTENSION);
			if ($baselineExtension === '') {
				$inceptionResult->getStdOutput()->getStyle()->error(sprintf('Baseline filename must have an extension, %s provided instead.', pathinfo($generateBaselineFile, PATHINFO_BASENAME)));
				return $inceptionResult->handleReturn(1);
			}

			if ($baselineExtension !== 'neon') {
				$inceptionResult->getStdOutput()->getStyle()->error(sprintf('Baseline filename extension must be .neon, .%s was used instead.', $baselineExtension));

				return $inceptionResult->handleReturn(1);
			}
		}

		try {
			[$files, $onlyFiles] = $inceptionResult->getFiles();
		} catch (\PHPStan\File\PathNotFoundException $e) {
			$inceptionResult->getErrorOutput()->writeLineFormatted(sprintf('<error>%s</error>', $e->getMessage()));
			return 1;
		}

		/** @var AnalyseApplication  $application */
		$application = $container->getByType(AnalyseApplication::class);

		$debug = $input->getOption('debug');
		if (!is_bool($debug)) {
			throw new \PHPStan\ShouldNotHappenException();
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
				$input
			);
		} catch (\Throwable $t) {
			if ($debug) {
				$inceptionResult->getStdOutput()->writeRaw(sprintf(
					'Uncaught %s: %s in %s:%d',
					get_class($t),
					$t->getMessage(),
					$t->getFile(),
					$t->getLine()
				));
				$inceptionResult->getStdOutput()->writeLineFormatted('');
				$inceptionResult->getStdOutput()->writeRaw($t->getTraceAsString());
				$inceptionResult->getStdOutput()->writeLineFormatted('');

				return $inceptionResult->handleReturn(1);
			}

			throw $t;
		}

		if ($generateBaselineFile !== null) {
			if (!$analysisResult->hasErrors()) {
				$inceptionResult->getStdOutput()->getStyle()->error('No errors were found during the analysis. Baseline could not be generated.');

				return $inceptionResult->handleReturn(1);
			}
			if ($analysisResult->hasInternalErrors()) {
				$inceptionResult->getStdOutput()->getStyle()->error('An internal error occurred. Baseline could not be generated. Re-run PHPStan without --generate-baseline to see what\'s going on.');

				return $inceptionResult->handleReturn(1);
			}

			$baselineFileDirectory = dirname($generateBaselineFile);
			$baselineErrorFormatter = new BaselineNeonErrorFormatter(new ParentDirectoryRelativePathHelper($baselineFileDirectory));

			$streamOutput = $this->createStreamOutput();
			$errorConsoleStyle = new ErrorsConsoleStyle(new StringInput(''), $streamOutput);
			$baselineOutput = new SymfonyOutput($streamOutput, new SymfonyStyle($errorConsoleStyle));
			$baselineErrorFormatter->formatErrors($analysisResult, $baselineOutput);

			$stream = $streamOutput->getStream();
			rewind($stream);
			$baselineContents = stream_get_contents($stream);
			if ($baselineContents === false) {
				throw new \PHPStan\ShouldNotHappenException();
			}

			if (!is_dir($baselineFileDirectory)) {
				$mkdirResult = @mkdir($baselineFileDirectory, 0644, true);
				if ($mkdirResult === false) {
					$inceptionResult->getStdOutput()->writeLineFormatted(sprintf('Failed to create directory "%s".', $baselineFileDirectory));

					return $inceptionResult->handleReturn(1);
				}
			}

			try {
				FileWriter::write($generateBaselineFile, $baselineContents);
			} catch (\PHPStan\File\CouldNotWriteFileException $e) {
				$inceptionResult->getStdOutput()->writeLineFormatted($e->getMessage());

				return $inceptionResult->handleReturn(1);
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

			return $inceptionResult->handleReturn(0);
		}

		if ($fix) {
			$ciDetector = new CiDetector();
			if ($ciDetector->isCiDetected()) {
				$inceptionResult->getStdOutput()->writeLineFormatted('PHPStan Pro can\'t run in CI environment yet. Stay tuned!');

				return $inceptionResult->handleReturn(1);
			}
			$container->getByType(ResultCacheClearer::class)->clearTemporaryCaches();
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
					$analysisResult->isDefaultLevelUsed(),
					$analysisResult->getProjectConfigFile(),
					$analysisResult->isResultCacheSaved()
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

				return $inceptionResult->handleReturn(1);
			}

			if (!$analysisResult->isResultCacheSaved() && !$onlyFiles) {
				// this can happen only if there are some regex-related errors in ignoreErrors configuration
				$stdOutput = $inceptionResult->getStdOutput();
				if (count($analysisResult->getFileSpecificErrors()) > 0) {
					$stdOutput->getStyle()->error('Unknown error. Please report this as a bug.');
					return $inceptionResult->handleReturn(1);
				}

				$stdOutput->getStyle()->error('PHPStan Pro can\'t be launched because of these errors:');

				/** @var TableErrorFormatter $tableErrorFormatter */
				$tableErrorFormatter = $container->getService('errorFormatter.table');
				$tableErrorFormatter->formatErrors($analysisResult, $stdOutput);

				$stdOutput->writeLineFormatted('Please fix them first and then re-run PHPStan.');

				if ($stdOutput->isDebug()) {
					$stdOutput->writeLineFormatted('Result cache was not saved.');
				}

				return $inceptionResult->handleReturn(1);
			}

			$inceptionResult->handleReturn(0); // delete memory limit file

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
				$_SERVER['argv'][0]
			);
		}

		/** @var ErrorFormatter $errorFormatter */
		$errorFormatter = $container->getService($errorFormatterServiceName);

		return $inceptionResult->handleReturn(
			$errorFormatter->formatErrors($analysisResult, $inceptionResult->getStdOutput())
		);
	}

	private function createStreamOutput(): StreamOutput
	{
		$resource = fopen('php://memory', 'w', false);
		if ($resource === false) {
			throw new \PHPStan\ShouldNotHappenException();
		}
		return new StreamOutput($resource);
	}

}
