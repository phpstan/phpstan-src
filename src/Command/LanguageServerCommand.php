<?php declare(strict_types = 1);

namespace PHPStan\Command;

use Phpactor\LanguageServer\LanguageServerBuilder;
use PHPStan\LanguageServer\PhpstanDispatcherFactory;
use Psr\Log\AbstractLogger;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;

class LanguageServerCommand extends Command
{

	const OPT_ADDRESS = 'address';

	/** @var array<string> */
	private array $composerAutoloaderProjectPaths;

	private const NAME = 'language-server';

	/**
	 * @param array<string> $composerAutoloaderProjectPaths
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
			->setDescription('Start the Phpstan language server')
			->setDefinition([
				new InputOption(self::OPT_ADDRESS, null, InputOption::VALUE_REQUIRED, 'TCP address (ommit to use stdio)'),
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
				'7',
				false,
				false
			);
		} catch (\PHPStan\Command\InceptionNotSuccessfulException $e) {
			return 1;
		}

		$container = $inceptionResult->getContainer();
		assert($output instanceof ConsoleOutput);

		$logger = new class extends AbstractLogger {

			public function log($level, $message, array $context = []): void
			{
				fwrite(STDERR, sprintf(
					'[%s] %s ::: %s',
					$level,
					$message,
					json_encode($context, JSON_PRETTY_PRINT)
				) . "\n");
			}

		};

		$builder = LanguageServerBuilder::create(
			new PhpstanDispatcherFactory($inceptionResult, $logger),
			$logger
		);

		if ($address = $input->getOption(self::OPT_ADDRESS)) {
			if (!is_string($address)) {
				throw new \RuntimeException('Address must be a string');
			}
			$builder->tcpServer($address);
		}

		$builder->build()->run();

		return 0;
	}

}
