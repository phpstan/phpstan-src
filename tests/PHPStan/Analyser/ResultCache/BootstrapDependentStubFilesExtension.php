<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use LogicException;
use PHPStan\PhpDoc\StubFilesExtension;

/**
 * Stands for a StubFilesExtension relying on the bootstrapFiles, which have not run yet when the
 * result cache is restored.
 */
final class BootstrapDependentStubFilesExtension implements StubFilesExtension
{

	public function getFiles(): array
	{
		throw new LogicException('The StubFilesExtensions must not run while the result cache is restored.');
	}

}
