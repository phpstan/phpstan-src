<?php declare(strict_types = 1);

namespace PHPStan\Reflection\BetterReflection\SourceLocator;

use PHPStan\Command\Output;
use PHPStan\DependencyInjection\AutowiredService;
use Throwable;
use function get_class;
use function sprintf;

/**
 * Prints the exceptions that AutoloadFunctionsSourceLocator swallows from autoloaders registered
 * by bootstrapFiles. BootstrapFilesRunner enables it with --debug, where the analysis runs in the
 * main process. The class is then reported as not found, and this keeps the autoloader's reason.
 */
#[AutowiredService]
final class BootstrapAutoloaderExceptionReporter
{

	private ?Output $output = null;

	public function enable(Output $output): void
	{
		$this->output = $output;
	}

	public function report(string $className, Throwable $e): void
	{
		if ($this->output === null) {
			return;
		}

		$this->output->writeLineFormatted(sprintf('%s thrown in %s on line %d by a bootstrap autoloader for class %s: %s', get_class($e), $e->getFile(), $e->getLine(), $className, $e->getMessage()));
		$this->output->writeLineFormatted($e->getTraceAsString());
	}

}
