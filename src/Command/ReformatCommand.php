<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ReformatCommand extends Command
{

	private const NAME = 'reformat';

	protected function configure(): void
	{
		$this->setName(self::NAME)
			->setDescription('Read a previously generated analysis result from STDIN in JSON format and converts it to a different format')
			->setDefinition([
				new InputOption('error-format', null, InputOption::VALUE_REQUIRED, 'Format in which to print the result of the analysis', 'table'),
				new InputOption('memory-limit', null, InputOption::VALUE_REQUIRED, 'Memory limit for the run'),
				new InputOption('xdebug', null, InputOption::VALUE_NONE, 'Allow running with XDebug for debugging purposes'),
			]);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$memoryLimit = $input->getOption('memory-limit');
		$allowXdebug = $input->getOption('xdebug');

		if ((!is_string($memoryLimit) && $memoryLimit !== null)
			|| (!is_bool($allowXdebug))
		) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$inceptionResult = CommandHelper::begin(
			$input,
			$output,
			['.'],
			null,
			$memoryLimit,
			null,
			[],
			null,
			null,
			$allowXdebug
		);

		$errorFormat = $input->getOption('error-format');
		if (!is_string($errorFormat)) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$errorOutput = $inceptionResult->getErrorOutput();
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

		/** @var ErrorFormatter $errorFormatter */
		$errorFormatter = $container->getService($errorFormatterServiceName);

		if (!defined('STDIN')) {
			$errorOutput->writeLineFormatted('STDIN is not defined');
			return 1;
		}
		$jsonString = stream_get_contents(STDIN);
		if ($jsonString === false) {
			$errorOutput->writeLineFormatted('reading from STDIN failed');
			return 1;
		}
		$analysisResult = JsonErrorFormatterDeserializer::deserializeErrors($jsonString);
		return $errorFormatter->formatErrors($analysisResult, $inceptionResult->getStdOutput());
	}

}
