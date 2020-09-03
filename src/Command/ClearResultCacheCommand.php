<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Analyser\ResultCache\ResultCacheManagerFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ClearResultCacheCommand extends Command
{

	private const NAME = 'clear-result-cache';

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
			->setDescription('Clears the result cache.')
			->setDefinition([
				new InputOption('configuration', 'c', InputOption::VALUE_REQUIRED, 'Path to project configuration file'),
				new InputOption('autoload-file', 'a', InputOption::VALUE_REQUIRED, 'Project\'s additional autoload file path'),
			]);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$autoloadFile = $input->getOption('autoload-file');
		$configuration = $input->getOption('configuration');

		if (
			(!is_string($autoloadFile) && $autoloadFile !== null)
			|| (!is_string($configuration) && $configuration !== null)
		) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		try {
			$inceptionResult = CommandHelper::begin(
				$input,
				$output,
				['.'],
				null,
				null,
				$autoloadFile,
				$this->composerAutoloaderProjectPaths,
				$configuration,
				null,
				'0',
				false,
				false
			);
		} catch (\PHPStan\Command\InceptionNotSuccessfulException $e) {
			return 1;
		}

		$container = $inceptionResult->getContainer();

		/** @var ResultCacheManagerFactory $resultCacheManagerFactory */
		$resultCacheManagerFactory = $container->getByType(ResultCacheManagerFactory::class);
		$path = $resultCacheManagerFactory->create([])->clear();

		$output->writeln('<info>Result cache cleared from directory:</info>');
		$output->writeln($path);

		return 0;
	}

}
