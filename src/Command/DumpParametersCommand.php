<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Nette\Neon\Neon;
use Nette\Utils\Json;
use PHPStan\ShouldNotHappenException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function implode;
use function in_array;
use function is_string;

class DumpParametersCommand extends Command
{

	private const NAME = 'dump-parameters';
	private const FORMATS = ['json', 'neon'];

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
			->setDescription('Dumps all parameters')
			->setDefinition([
				new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
				new InputOption(AnalyseCommand::OPTION_LEVEL, 'l', InputOption::VALUE_REQUIRED, 'Level of rule options - the higher the stricter'),
				new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
				new InputOption('debug', null, InputOption::VALUE_NONE, 'Show debug information - which file is analysed, do not catch internal errors'),
				new InputOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Memory limit for clearing result cache'),
				new InputOption('format', null, InputOption::VALUE_REQUIRED, 'Format in which to print the result', 'neon'),
			]);
	}

	protected function initialize(InputInterface $input, OutputInterface $output): void
	{
		if ((bool) $input->getOption('debug')) {
			$application = $this->getApplication();
			if ($application === null) {
				throw new ShouldNotHappenException();
			}
			$application->setCatchExceptions(false);
			return;
		}
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$memoryLimit = $input->getOption('memory-limit');
		$autoloadFile = $input->getOption('autoload-file');
		$configuration = $input->getOption('configuration');
		$level = $input->getOption(AnalyseCommand::OPTION_LEVEL);

		if (
			(!is_string($memoryLimit) && $memoryLimit !== null)
			|| (!is_string($autoloadFile) && $autoloadFile !== null)
			|| (!is_string($configuration) && $configuration !== null)
			|| (!is_string($level) && $level !== null)
		) {
			throw new ShouldNotHappenException();
		}

		try {
			$inceptionResult = CommandHelper::begin(
				$input,
				$output,
				['.'],
				$memoryLimit,
				$autoloadFile,
				$this->composerAutoloaderProjectPaths,
				$configuration,
				null,
				$level,
				false,
			);
		} catch (InceptionNotSuccessfulException) {
			return 1;
		}

		$parameters = $inceptionResult->getContainer()->getParameters();

		// always set to '.'
		unset($parameters['analysedPaths']);
		// irrelevant Nette parameters
		unset($parameters['debugMode']);
		unset($parameters['productionMode']);
		unset($parameters['tempDir']);
		unset($parameters['__validate']);
		// internal - static reflection
		unset($parameters['singleReflectionFile']);
		unset($parameters['singleReflectionInsteadOfFile']);

		$format = $input->getOption('format');
		if (!in_array($format, self::FORMATS, true)) {
			$inceptionResult->getStdOutput()->getStyle()->error('Invalid format "' . $format . '", expected one of "' . implode('", "', self::FORMATS) . '".');

			return 1;
		}

		if ($format === 'json') {
			$result = Json::encode($parameters, Json::PRETTY);
		} else {
			$result = Neon::encode($parameters, true);
		}

		$output->writeln($result);

		return 0;
	}

}
