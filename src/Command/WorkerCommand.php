<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Clue\React\NDJson\Decoder;
use Clue\React\NDJson\Encoder;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\FileAnalyser;
use PHPStan\Analyser\InferrablePropertyTypesFromConstructorHelper;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Rules\Registry;
use React\EventLoop\StreamSelectLoop;
use React\Stream\ReadableResourceStream;
use React\Stream\WritableResourceStream;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class WorkerCommand extends Command
{

	private const NAME = 'worker';

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
			->setDescription('(Internal) Support for parallel analysis.')
			->setDefinition([
				new InputArgument('paths', InputArgument::OPTIONAL | InputArgument::IS_ARRAY, 'Paths with source code to run analysis on'),
				new InputOption('paths-file', null, InputOption::VALUE_REQUIRED, 'Path to a file with a list of paths to run analysis on'),
				new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
				new InputOption(AnalyseCommand::OPTION_LEVEL, 'l', InputOption::VALUE_REQUIRED, 'Level of rule options - the higher the stricter'),
				new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
				new InputOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Memory limit for analysis'),
				new InputOption('xdebug', null, InputOption::VALUE_NONE, 'Allow running with XDebug for debugging purposes'),
			]);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$paths = $input->getArgument('paths');
		$memoryLimit = $input->getOption('memory-limit');
		$autoloadFile = $input->getOption('autoload-file');
		$configuration = $input->getOption('configuration');
		$level = $input->getOption(AnalyseCommand::OPTION_LEVEL);
		$pathsFile = $input->getOption('paths-file');
		$allowXdebug = $input->getOption('xdebug');

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
				$level,
				$allowXdebug
			);
		} catch (\PHPStan\Command\InceptionNotSuccessfulException $e) {
			return 1;
		}

		$container = $inceptionResult->getContainer();

		/** @var FileAnalyser $fileAnalyser */
		$fileAnalyser = $container->getByType(FileAnalyser::class);

		/** @var Registry $registry */
		$registry = $container->getByType(Registry::class);

		/** @var NodeScopeResolver $nodeScopeResolver */
		$nodeScopeResolver = $container->getByType(NodeScopeResolver::class);
		$loop = new StreamSelectLoop();

		$stdout = new Encoder(new WritableResourceStream(STDOUT, $loop));
		$handleError = static function (\Throwable $error) use ($stdout): void {
			$stdout->write([
				'errors' => [$error->getMessage()],
				'filesCount' => 0,
				'hasInferrablePropertyTypesFromConstructor' => false,
				'internalErrorsCount' => 1,
			]);
			$stdout->end();
		};
		$stdout->on('error', $handleError);

		// todo collectErrors (from Analyser)
		$stdin = new Decoder(new ReadableResourceStream(STDIN, $loop), true);
		$stdin->on('data', static function (array $json) use ($fileAnalyser, $registry, $nodeScopeResolver, $stdout): void {
			$action = $json['action'];
			if ($action !== 'analyse') {
				return;
			}

			$internalErrorsCount = 0;
			$files = $json['files'];
			$nodeScopeResolver->setAnalysedFiles($files);
			$errors = [];
			$inferrablePropertyTypesFromConstructorHelper = new InferrablePropertyTypesFromConstructorHelper();
			foreach ($files as $file) {
				try {
					$fileErrors = $fileAnalyser->analyseFile($file, $registry, $inferrablePropertyTypesFromConstructorHelper);
					foreach ($fileErrors as $fileError) {
						$errors[] = $fileError;
					}
				} catch (\Throwable $t) {
					$internalErrorsCount++;
					$internalErrorMessage = sprintf('Internal error: %s', $t->getMessage());
					$internalErrorMessage .= sprintf(
						'%sRun PHPStan with --debug option and post the stack trace to:%s%s',
						"\n",
						"\n",
						'https://github.com/phpstan/phpstan/issues/new'
					);
					$errors[] = new Error($internalErrorMessage, $file, null, false);
				}
			}

			$stdout->write([
				'errors' => $errors,
				'filesCount' => count($files),
				'hasInferrablePropertyTypesFromConstructor' => $inferrablePropertyTypesFromConstructorHelper->hasInferrablePropertyTypesFromConstructor(),
				'internalErrorsCount' => $internalErrorsCount,
			]);
		});
		$stdin->on('error', $handleError);
		$loop->run();

		return 0;
	}

}
