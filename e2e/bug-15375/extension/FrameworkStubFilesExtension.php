<?php declare(strict_types = 1);

namespace Bug15375;

use LogicException;
use PHPStan\PhpDoc\StubFilesExtension;
use function defined;

final class FrameworkStubFilesExtension implements StubFilesExtension
{

	public function getFiles(): array
	{
		// mimics larastan, which picks its stubs by the framework version its bootstrapFiles entry defines
		if (!defined('Bug15375\FRAMEWORK_VERSION')) {
			throw new LogicException('StubFilesExtension ran before bootstrapFiles.');
		}

		return [];
	}

}
