<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Command\ErrorFormatter\BaselineNeonErrorFormatter;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Symfony\SymfonyOutput;
use PHPStan\Command\Symfony\SymfonyStyle;
use PHPStan\File\FileWriter;
use PHPStan\File\ParentDirectoryRelativePathHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\StreamOutput;
use function stream_get_contents;

class AnalyseCommand extends \Symfony\Component\Console\Command\Command
{

	private const NAME = 'analyse';

	public const OPTION_LEVEL = 'level';

	public const DEFAULT_LEVEL = CommandHelper::DEFAULT_LEVEL;

	/** @var string[] */
	private $composerAutoloaderProjectPaths;

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
				new InputOption('error-format', null, InputOption::VALUE_REQUIRED, 'Format in which to print the result of the analysis', 'table'),
				new InputOption('generate-baseline', null, InputOption::VALUE_OPTIONAL, 'Path to a file where the baseline should be saved', false),
				new InputOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Memory limit for analysis'),
				new InputOption('xdebug', null, InputOption::VALUE_NONE, 'Allow running with XDebug for debugging purposes'),
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
		$paths = $input->getArgument('paths');
		$memoryLimit = $input->getOption('memory-limit');
		$autoloadFile = $input->getOption('autoload-file');
		$configuration = $input->getOption('configuration');
		$level = $input->getOption(self::OPTION_LEVEL);
		$pathsFile = $input->getOption('paths-file');
		$allowXdebug = $input->getOption('xdebug');
		$debugEnabled = (bool) $input->getOption('debug');

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
		$errorFormat = $input->getOption('error-format');

		if (!is_string($errorFormat) && $errorFormat !== null) {
			throw new \PHPStan\ShouldNotHappenException();
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

		/** @var ErrorFormatter $errorFormatter */
		$errorFormatter = $container->getService($errorFormatterServiceName);

		/** @var AnalyseApplication  $application */
		$application = $container->getByType(AnalyseApplication::class);

		$debug = $input->getOption('debug');
		if (!is_bool($debug)) {
			throw new \PHPStan\ShouldNotHappenException();
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

		$analysisResult = $application->analyse(
			$inceptionResult->getFiles(),
			$inceptionResult->isOnlyFiles(),
			$inceptionResult->getStdOutput(),
			$inceptionResult->getErrorOutput(),
			$inceptionResult->isDefaultLevelUsed(),
			$debug,
			$inceptionResult->getProjectConfigFile(),
			$input
		);

		if ($generateBaselineFile !== null) {
			if (!$analysisResult->hasErrors()) {
				$inceptionResult->getStdOutput()->getStyle()->error('No errors were found during the analysis. Baseline could not be generated.');

				return $inceptionResult->handleReturn(1);
			}

			$baselineFileDirectory = dirname($generateBaselineFile);
			$baselineErrorFormatter = new BaselineNeonErrorFormatter(new ParentDirectoryRelativePathHelper($baselineFileDirectory));

			$streamOutput = $this->createStreamOutput();
			$errorConsoleStyle = new ErrorsConsoleStyle(new StringInput(''), $streamOutput);
			$output = new SymfonyOutput($streamOutput, new SymfonyStyle($errorConsoleStyle));
			$baselineErrorFormatter->formatErrors($analysisResult, $output);

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
