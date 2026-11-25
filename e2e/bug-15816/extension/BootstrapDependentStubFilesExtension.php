<?php declare(strict_types = 1);

namespace Bug15816;

use PHPStan\PhpDoc\StubFilesExtension;
use function constant;
use function glob;

final class BootstrapDependentStubFilesExtension implements StubFilesExtension
{

	public function getFiles(): array
	{
		// the constant is defined by a bootstrapFiles entry, like larastan's LARAVEL_VERSION
		return glob(constant('Bug15816\STUBS_DIR') . '/*.stub') ?: [];
	}

}
